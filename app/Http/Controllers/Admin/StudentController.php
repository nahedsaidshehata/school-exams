<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class StudentController extends Controller
{
    /**
     * Display a listing of students.
     */
    public function index(Request $request)
    {
        // current filters (keep them simple & stable)
        $filters = [
            'school_id'    => trim((string) $request->query('school_id', '')),
            'grade'        => trim((string) $request->query('grade', '')),
            'year'         => trim((string) $request->query('year', '')), // Academic Year
            'gender'       => trim((string) $request->query('gender', '')),
            'send'         => trim((string) $request->query('send', '')), // '1' or '0'
            'nationality'  => trim((string) $request->query('nationality', '')),
            'q'            => trim((string) $request->query('q', '')), // search
        ];

        // ✅ consider "filtered" if ANY filter/search exists
        $isFiltered = (
            $filters['school_id'] !== '' ||
            $filters['grade'] !== '' ||
            $filters['year'] !== '' ||
            $filters['gender'] !== '' ||
            $filters['send'] !== '' ||
            $filters['nationality'] !== '' ||
            $filters['q'] !== ''
        );

        // ✅ If filtered, force page=1 (so user never lands on empty page 2+)
        if ($isFiltered && (int) $request->query('page', 1) > 1) {
            $qs = $request->query();
            unset($qs['page']);
            return redirect()->route('admin.students.index', $qs);
        }

        $query = User::query()
            ->with(['school', 'studentProfile'])
            ->where('role', 'student');

        // Filter: School
        if ($filters['school_id'] !== '') {
            $query->where('school_id', $filters['school_id']);
        }

        // Filters based on studentProfile
        $hasProfileFilters =
            $filters['grade'] !== '' ||
            $filters['year'] !== '' ||
            $filters['gender'] !== '' ||
            $filters['send'] !== '' ||
            $filters['nationality'] !== '';

        if ($hasProfileFilters) {
            $query->whereHas('studentProfile', function ($p) use ($filters) {
                if ($filters['grade'] !== '') {
                    $p->where('grade', $filters['grade']);
                }

                if ($filters['year'] !== '') {
                    $p->where('year', $filters['year']);
                }

                if ($filters['gender'] !== '' && in_array($filters['gender'], ['male', 'female'], true)) {
                    $p->where('gender', $filters['gender']);
                }

                if ($filters['send'] !== '') {
                    // UI sends: '1' (Yes) or '0' (No)
                    if ($filters['send'] === '1') $p->where('send', true);
                    if ($filters['send'] === '0') $p->where('send', false);
                }

                if ($filters['nationality'] !== '') {
                    $p->where('nationality', $filters['nationality']);
                }
            });
        }

        // Search (name OR username OR email OR parent_email)
        if ($filters['q'] !== '') {
            $q = $filters['q'];

            $query->where(function ($w) use ($q) {
                $w->where('full_name', 'like', "%{$q}%")
                  ->orWhere('username', 'like', "%{$q}%")
                  ->orWhere('email', 'like', "%{$q}%")
                  ->orWhereHas('studentProfile', function ($p) use ($q) {
                      $p->where('parent_email', 'like', "%{$q}%");
                  });
            });
        }

        // ✅ Pagination: if filtered -> show ALL results in one page
        $total = (clone $query)->count();
        $perPage = $isFiltered ? max(1, $total) : 15;

        $students = $query
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        // Filter dropdown options
        $schools = School::orderBy('name_en')->get();

        $grades = StudentProfile::query()
            ->whereNotNull('grade')
            ->where('grade', '!=', '')
            ->select('grade')
            ->distinct()
            ->pluck('grade')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();

        $years = StudentProfile::query()
            ->whereNotNull('year')
            ->where('year', '!=', '')
            ->select('year')
            ->distinct()
            ->pluck('year')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();

        $nationalities = StudentProfile::query()
            ->whereNotNull('nationality')
            ->where('nationality', '!=', '')
            ->select('nationality')
            ->distinct()
            ->pluck('nationality')
            ->map(fn ($v) => trim((string) $v))
            ->filter(fn ($v) => $v !== '')
            ->values()
            ->all();

        sort($grades, SORT_NATURAL);
        rsort($years, SORT_NATURAL);
        sort($nationalities, SORT_NATURAL);

        return view('admin.students.index', compact(
            'students',
            'schools',
            'grades',
            'years',
            'nationalities',
            'filters',
            'isFiltered'
        ));
    }

    /**
     * Show the form for creating a new student.
     */
    public function create()
    {
        $schools = School::orderBy('name_en')->get();
        return view('admin.students.create', compact('schools'));
    }

    /**
     * Store a newly created student (manual).
     * ✅ Username/Password optional (matches your blade)
     */
    public function store(Request $request)
    {
        $request->validate([
            'school_id'   => 'required|uuid|exists:schools,id',
            'full_name'   => 'required|string|max:255',

            'username'    => 'nullable|string|max:255',
            'email'       => 'nullable|email|max:255',
            'password'    => 'nullable|string|min:8|confirmed',

            // profile fields
            'year'         => 'nullable|string|max:100', // Academic Year in UI
            'grade'        => 'nullable|string|max:100',
            'gender'       => 'nullable|in:male,female',
            'send'         => 'nullable|boolean',
            'parent_email' => 'nullable|email|max:255',
            'nationality'  => 'nullable|string|max:120',
        ]);

        $schoolId = $request->school_id;

        $username = trim((string) $request->username);
        if ($username === '') {
            $username = $this->generateUsername($request->full_name, $schoolId);
        } else {
            $exists = User::where('school_id', $schoolId)->where('username', $username)->exists();
            if ($exists) {
                return back()->withInput()->withErrors(['username' => 'Username already exists for this school.']);
            }
        }

        $email = $request->email ? trim((string)$request->email) : null;
        if ($email) {
            $existsEmail = User::where('school_id', $schoolId)->where('email', $email)->exists();
            if ($existsEmail) {
                return back()->withInput()->withErrors(['email' => 'Email already exists for this school.']);
            }
        }

        $passwordPlain = trim((string) $request->password);
        if ($passwordPlain === '') $passwordPlain = 'password';

        DB::beginTransaction();
        try {
            $student = User::create([
                'school_id' => $schoolId,
                'role'      => 'student',
                'username'  => $username,
                'email'     => $email,
                'password'  => Hash::make($passwordPlain),
                'full_name' => $request->full_name,
            ]);

            StudentProfile::updateOrCreate(
                ['user_id' => $student->id],
                [
                    'year'         => $request->year ?: null,
                    'grade'        => $request->grade ?: null,
                    'gender'       => $request->gender ?: null,
                    'send'         => (bool) $request->boolean('send'),
                    'parent_email' => $request->parent_email ?: null,
                    'nationality'  => $request->nationality ?: null,
                ]
            );

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withInput()->withErrors(['general' => 'Create failed: ' . $e->getMessage()]);
        }

        return redirect()->route('admin.students.index')
            ->with('success', 'Student created successfully.');
    }

    // باقي الملف كما هو بدون أي تعديل...
    public function importForm() { return view('admin.students.import'); }

    public function downloadTemplate()
    {
        $headers = [
            'SchoolName (required)',
            'StudentFullName',
            'Academic Year', // ✅ was Year
            'Grade',
            'Gender (male/female)',
            'SEND (Yes/No)',
            'ParentEmail',
            'Nationality',
            'Email',
            'UserName (Optional)',
            'Password (Optional)',
        ];

        $sheet = new Spreadsheet();
        $ws = $sheet->getActiveSheet();
        $ws->setTitle('Students');

        foreach ($headers as $i => $h) {
            $colLetter = Coordinate::stringFromColumnIndex($i + 1);
            $ws->setCellValue($colLetter . '1', $h);
        }

        $sample = [
            'Al-Noor International School',
            'Ahmed Ali Mohammed',
            '2025-2026',
            '6',
            'male',
            'No',
            'parent@example.com',
            'Egypt',
            'ahmed.ali@student.alnoor.edu',
            'ahmed_ali',
            'password',
        ];

        foreach ($sample as $i => $val) {
            $colLetter = Coordinate::stringFromColumnIndex($i + 1);
            $ws->setCellValue($colLetter . '2', $val);
        }

        $ws->getStyle('A1:' . Coordinate::stringFromColumnIndex(count($headers)) . '1')
            ->getFont()->setBold(true);

        for ($c = 1; $c <= count($headers); $c++) {
            $ws->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        $fileName = 'students_import_template.xlsx';

        return response()->streamDownload(function () use ($sheet) {
            $writer = new Xlsx($sheet);
            $writer->save('php://output');
        }, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function importStore(Request $request)
    {
        // كما هو بدون تغيير...
        $request->validate(['file' => 'required|file|mimes:xlsx|max:51200']);

        $path = $request->file('file')->getRealPath();
        $spreadsheet = IOFactory::load($path);
        $ws = $spreadsheet->getActiveSheet();

        $rows = $ws->toArray(null, true, true, true);
        if (count($rows) < 2) {
            return back()->withErrors(['file' => 'The XLSX file is empty.'])->withInput();
        }

        $headerRow = array_shift($rows);
        $headerMap = $this->normalizeHeaderMap($headerRow);

        if (!isset($headerMap['schoolname'])) {
            return back()->withErrors(['file' => "Missing required column: SchoolName"])->withInput();
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $errors = [];
        $creds = [];

        DB::beginTransaction();
        try {
            foreach ($rows as $rowIndex => $row) {
                $excelRowNumber = $rowIndex + 2;

                $schoolName = trim((string) ($row[$headerMap['schoolname']] ?? ''));
                if ($schoolName === '') {
                    $skipped++;
                    continue;
                }

                $school = School::where('name_en', $schoolName)
                    ->orWhere('name_ar', $schoolName)
                    ->first();

                if (!$school) {
                    $errors[] = "Row {$excelRowNumber}: School not found: {$schoolName}";
                    continue;
                }

                $fullName    = trim((string) ($headerMap['studentfullname'] ? ($row[$headerMap['studentfullname']] ?? '') : ''));
                $year        = trim((string) ($headerMap['year'] ? ($row[$headerMap['year']] ?? '') : ''));
                $grade       = trim((string) ($headerMap['grade'] ? ($row[$headerMap['grade']] ?? '') : ''));
                $genderRaw   = trim(strtolower((string) ($headerMap['gender'] ? ($row[$headerMap['gender']] ?? '') : '')));
                $sendRaw     = trim(strtolower((string) ($headerMap['send'] ? ($row[$headerMap['send']] ?? '') : '')));
                $parentEmail = trim((string) ($headerMap['parentemail'] ? ($row[$headerMap['parentemail']] ?? '') : ''));
                $nationality = trim((string) ($headerMap['nationality'] ? ($row[$headerMap['nationality']] ?? '') : ''));
                $email       = trim((string) ($headerMap['email'] ? ($row[$headerMap['email']] ?? '') : ''));
                $username    = trim((string) ($headerMap['username'] ? ($row[$headerMap['username']] ?? '') : ''));
                $password    = trim((string) ($headerMap['password'] ? ($row[$headerMap['password']] ?? '') : ''));

                $gender = null;
                if (in_array($genderRaw, ['male','m','ذكر'], true)) $gender = 'male';
                if (in_array($genderRaw, ['female','f','أنثى','انثى'], true)) $gender = 'female';

                $send = in_array($sendRaw, ['yes','y','1','true','نعم'], true);

                if ($parentEmail !== '' && !filter_var($parentEmail, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Row {$excelRowNumber}: Invalid ParentEmail: {$parentEmail}";
                    continue;
                }
                if ($parentEmail === '') $parentEmail = null;

                $email = ($email === '') ? null : $email;

                $student = null;

                if ($username !== '') {
                    $student = User::where('school_id', $school->id)
                        ->where('role', 'student')
                        ->where('username', $username)
                        ->first();
                }

                if (!$student && $email) {
                    $student = User::where('school_id', $school->id)
                        ->where('role', 'student')
                        ->where('email', $email)
                        ->first();
                }

                if (!$student) {
                    if ($username === '') {
                        $username = $this->generateUsername($fullName ?: 'student', $school->id);
                    } else {
                        $exists = User::where('school_id', $school->id)->where('username', $username)->exists();
                        if ($exists) {
                            $errors[] = "Row {$excelRowNumber}: Username already exists in this school: {$username}";
                            continue;
                        }
                    }

                    if ($email) {
                        $existsEmail = User::where('school_id', $school->id)->where('email', $email)->exists();
                        if ($existsEmail) {
                            $errors[] = "Row {$excelRowNumber}: Email already exists in this school: {$email}";
                            continue;
                        }
                    }

                    if ($password === '') $password = 'password';

                    $student = User::create([
                        'school_id' => $school->id,
                        'role'      => 'student',
                        'username'  => $username,
                        'email'     => $email,
                        'password'  => Hash::make($password),
                        'full_name' => $fullName ?: null,
                    ]);

                    $created++;

                    $creds[] = [
                        'row' => $excelRowNumber,
                        'school' => $schoolName,
                        'name' => $fullName ?: null,
                        'username' => $username,
                        'password' => $password,
                    ];
                } else {
                    $userUpdates = [];
                    if ($fullName !== '') $userUpdates['full_name'] = $fullName;
                    if ($email) $userUpdates['email'] = $email;

                    if (!empty($userUpdates)) {
                        $student->update($userUpdates);
                    }

                    $updated++;
                }

                $profileData = [];
                if ($year !== '') $profileData['year'] = $year;
                if ($grade !== '') $profileData['grade'] = $grade;
                if (!is_null($gender)) $profileData['gender'] = $gender;
                $profileData['send'] = $send;
                if (!is_null($parentEmail)) $profileData['parent_email'] = $parentEmail;
                if ($nationality !== '') $profileData['nationality'] = $nationality;

                StudentProfile::updateOrCreate(
                    ['user_id' => $student->id],
                    $profileData
                );
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['file' => 'Import failed: ' . $e->getMessage()]);
        }

        $redirect = redirect()->route('admin.students.index')
            ->with('success', "Created: {$created}. Updated: {$updated}. Skipped: {$skipped}.");

        if (!empty($errors)) {
            $redirect = $redirect->with('import_errors', $errors);
        }
        if (!empty($creds)) {
            $redirect = $redirect->with('import_creds', $creds);
        }

        return $redirect;
    }

    private function normalizeHeaderMap(array $headerRow): array
    {
        $map = [];

        foreach ($headerRow as $col => $text) {
            $key = strtolower(trim((string) $text));
            $key = preg_replace('/\s+/', '', $key);

            if (str_contains($key, 'schoolname')) $map['schoolname'] = $col;
            if (str_contains($key, 'studentfullname')) $map['studentfullname'] = $col;

            if ($key === 'year' || $key === 'academicyear') $map['year'] = $col;

            if ($key === 'grade') $map['grade'] = $col;
            if (str_contains($key, 'gender')) $map['gender'] = $col;
            if (str_contains($key, 'send')) $map['send'] = $col;
            if (str_contains($key, 'parentemail')) $map['parentemail'] = $col;
            if (str_contains($key, 'nationality')) $map['nationality'] = $col;
            if ($key === 'email') $map['email'] = $col;
            if (str_contains($key, 'username')) $map['username'] = $col;
            if (str_contains($key, 'password')) $map['password'] = $col;
        }

        $map += [
            'studentfullname' => $map['studentfullname'] ?? null,
            'year'            => $map['year'] ?? null,
            'grade'           => $map['grade'] ?? null,
            'gender'          => $map['gender'] ?? null,
            'send'            => $map['send'] ?? null,
            'parentemail'     => $map['parentemail'] ?? null,
            'nationality'     => $map['nationality'] ?? null,
            'email'           => $map['email'] ?? null,
            'username'        => $map['username'] ?? null,
            'password'        => $map['password'] ?? null,
        ];

        return $map;
    }

    private function generateUsername(string $fullName, string $schoolId): string
    {
        $base = trim($fullName);
        if ($base === '') $base = 'student';

        $base = strtolower($base);
        $base = preg_replace('/[^a-z0-9]+/i', '_', $base);
        $base = trim($base, '_');
        if ($base === '') $base = 'student';

        $candidate = $base;
        $i = 1;

        while (User::where('school_id', $schoolId)->where('username', $candidate)->exists()) {
            $i++;
            $candidate = $base . '_' . $i;
            if ($i > 5000) {
                $candidate = $base . '_' . Str::random(6);
                break;
            }
        }

        return $candidate;
    }
}
