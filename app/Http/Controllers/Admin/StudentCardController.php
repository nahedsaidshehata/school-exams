<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class StudentCardController extends Controller
{
    public function pdf(Request $request)
    {
        $ids = (array) $request->input('student_ids', []);
        $ids = array_values(array_filter($ids, fn ($v) => is_string($v) && trim($v) !== ''));

        if (count($ids) === 0) {
            return back()->with('error', 'Please select at least one student.');
        }

        $students = User::with(['school', 'studentProfile'])
            ->where('role', 'student')
            ->whereIn('id', $ids)
            ->get();

        if ($students->count() === 0) {
            return back()->with('error', 'No students found for selected IDs.');
        }

        // ✅ Optional logo (put your logo here)
        // public/images/card-logo.png
        $logoPath = public_path('images/card-logo.png');
        $logoDataUri = null;
        if (is_file($logoPath)) {
            $ext = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
            $mime = $ext === 'jpg' || $ext === 'jpeg' ? 'image/jpeg' : 'image/png';
            $logoDataUri = 'data:' . $mime . ';base64,' . base64_encode(file_get_contents($logoPath));
        }

        $cards = [];

        foreach ($students as $student) {
            $tokenRaw = $this->ensureActiveToken($student->id);
            $passwordPlain = $this->ensurePrintablePassword($student); // ✅ fixed

            $loginUrl = route('card.login', ['token' => $tokenRaw]);
            $qrDataUri = $this->makeQrDataUri($loginUrl);

            $cards[] = [
                'student_name'  => $student->full_name ?? $student->username,
                'username'      => $student->username,
                'password'      => $passwordPlain,
                'school'        => $student->school->name_en ?? '',
                'grade'         => $student->studentProfile?->grade ?? '',
                'academic_year' => $student->studentProfile?->year ?? '',
                'login_url'     => $loginUrl,
                'qr'            => $qrDataUri,
            ];
        }

        try {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.students.cards.pdf', [
                'cards' => $cards,
                'logoDataUri' => $logoDataUri,
            ])->setPaper('a4', 'portrait');
        } catch (\Throwable $e) {
            return back()->with('error', 'PDF generation failed: ' . $e->getMessage());
        }

        $fileName = 'student_cards_' . now()->format('Ymd_His') . '.pdf';
        return $pdf->download($fileName);
    }

    public function rotate(Request $request)
    {
        $ids = (array) $request->input('student_ids', []);
        $ids = array_values(array_filter($ids, fn ($v) => is_string($v) && trim($v) !== ''));

        if (count($ids) === 0) {
            return back()->with('error', 'Please select at least one student.');
        }

        $students = User::where('role', 'student')->whereIn('id', $ids)->get();
        if ($students->count() === 0) {
            return back()->with('error', 'No students found for selected IDs.');
        }

        foreach ($students as $s) {
            $this->rotateToken($s->id);
        }

        return back()->with('success', 'QR tokens rotated for ' . $students->count() . ' student(s). Old cards will stop working.');
    }

    private function ensureActiveToken(string $userId): string
    {
        $row = DB::table('student_card_tokens')
            ->where('user_id', $userId)
            ->where('active', 1)
            ->first();

        if ($row) {
            try {
                return Crypt::decryptString($row->token_enc);
            } catch (\Throwable $e) {
                return $this->rotateToken($userId);
            }
        }

        return $this->rotateToken($userId, false);
    }

    private function rotateToken(string $userId, bool $deactivateOld = true): string
    {
        if ($deactivateOld) {
            DB::table('student_card_tokens')
                ->where('user_id', $userId)
                ->where('active', 1)
                ->update([
                    'active' => 0,
                    'rotated_at' => now(),
                    'updated_at' => now(),
                ]);
        }

        $tokenRaw = bin2hex(random_bytes(32)); // 64 chars
        $hash = hash('sha256', $tokenRaw);
        $enc  = Crypt::encryptString($tokenRaw);

        DB::table('student_card_tokens')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $userId,
            'token_hash' => $hash,
            'token_enc' => $enc,
            'active' => 1,
            'rotated_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $tokenRaw;
    }

    /**
     * ✅ Never overwrite an existing stored password with a default.
     * ✅ If missing, generate a real password AND update users.password hash to match it.
     */
    private function ensurePrintablePassword(User $student): string
    {
        $row = DB::table('student_card_credentials')->where('user_id', $student->id)->first();

        if ($row) {
            try {
                $plain = Crypt::decryptString($row->password_enc);
                if (is_string($plain) && trim($plain) !== '') {
                    return $plain;
                }
            } catch (\Throwable $e) {
                // ✅ IMPORTANT: do NOT overwrite (this is what was causing "password" to come back)
                return '—';
            }
            return '—';
        }

        // If not stored yet: generate and sync to real login password
        $plain = $this->generateKidPassword();

        DB::table('student_card_credentials')->insert([
            'id' => (string) Str::uuid(),
            'user_id' => $student->id,
            'password_enc' => Crypt::encryptString($plain),
            'updated_by' => auth()->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // ✅ Sync real login password to match printed card
        $student->password = Hash::make($plain);
        $student->save();

        return $plain;
    }

    private function generateKidPassword(): string
    {
        // Example style: IL-4130
        $prefix = 'IL';
        $num = random_int(1000, 9999);
        return $prefix . '-' . $num;
    }

    private function makeQrDataUri(string $url): string
    {
        $sources = [
            'https://chart.googleapis.com/chart?cht=qr&chs=220x220&chld=M|1&chl=' . urlencode($url),
            'https://api.qrserver.com/v1/create-qr-code/?size=220x220&data=' . urlencode($url),
        ];

        foreach ($sources as $qrUrl) {
            $png = $this->httpGetBinary($qrUrl);
            if ($png !== null) {
                return 'data:image/png;base64,' . base64_encode($png);
            }
        }

        throw new \RuntimeException('QR generation failed: could not fetch remote QR image (check internet / firewall / SSL).');
    }

    private function httpGetBinary(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_CONNECTTIMEOUT => 8,
                CURLOPT_TIMEOUT        => 15,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_USERAGENT      => 'SchoolExams/1.0',
            ]);
            $data = curl_exec($ch);
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if (is_string($data) && $data !== '' && $code >= 200 && $code < 300) {
                return $data;
            }
        }

        if (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOL)) {
            $data = @file_get_contents($url);
            if (is_string($data) && $data !== '') {
                return $data;
            }
        }

        return null;
    }
}
