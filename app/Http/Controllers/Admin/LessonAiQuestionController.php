<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Models\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use App\Services\LessonAiContextBuilder;
use App\Services\OpenAiQuestionGenerator;

class LessonAiQuestionController extends Controller
{
    public function create(Lesson $lesson)
    {
        $lesson->load([
            'learningOutcomes' => function ($q) {
                $q->select('learning_outcomes.id', 'title_ar', 'title_en');
            }
        ]);

        return view('admin.lessons.ai.questions.create', compact('lesson'));
    }

    public function generate(Request $request, Lesson $lesson, LessonAiContextBuilder $contextBuilder, OpenAiQuestionGenerator $aiGenerator)
    {
        /**
         * ✅ FINAL FIX (field name + value compatibility)
         * - Accept both field names: lang_mode OR language_mode
         * - Accept both value styles: AR/EN/BOTH OR ar/en/both
         * - Normalize to: ar|en|both for internal usage
         */

        // Accept both field names
        $mode = $request->input('language_mode', $request->input('lang_mode'));
        $mode = is_string($mode) ? trim($mode) : $mode;

        // Unify to one key for validation
        $request->merge([
            'language_mode' => $mode,
        ]);

        $data = $request->validate([
            // Accept both AR/EN/BOTH and ar/en/both
            'language_mode' => 'required|in:AR,EN,BOTH,ar,en,both',

            'types' => 'required|array|min:1',
            'types.*' => 'in:MCQ,TF,ESSAY,CLASSIFICATION,REORDER,FILL_BLANK',
            'count' => 'required|integer|min:1|max:50',

            // distribution inputs موجودة في create.blade.php
            'difficulty_easy' => 'required|integer|min:0|max:100',
            'difficulty_medium' => 'required|integer|min:0|max:100',
            'difficulty_hard' => 'required|integer|min:0|max:100',
        ]);

        // ✅ Normalize language mode to: ar|en|both
        $langMode = $this->normalizeLangMode((string) $data['language_mode']);

        $types = array_values($data['types']);
        $count = (int) $data['count'];

        $easy = (int) $data['difficulty_easy'];
        $med = (int) $data['difficulty_medium'];
        $hard = (int) $data['difficulty_hard'];

        if (($easy + $med + $hard) !== 100) {
            return back()
                ->withInput()
                ->withErrors(['difficulty_easy' => 'Difficulty distribution must sum to 100%.']);
        }

        // ✅ Build Context
        $context = $contextBuilder->build($lesson, $langMode);

        try {
            // ✅ Try AI Generation
            $draftQuestions = $aiGenerator->generate($context, [
                'count' => $count,
                'types' => $types,
                'difficulties' => [
                    'easy' => $easy,
                    'medium' => $med,
                    'hard' => $hard,
                ],
            ]);
        } catch (\Exception $e) {
            // ✅ If AI fails, return error to user instead of silent stub fallback
            // This ensures they know WHY it failed (e.g. missing key)
            return back()
                ->withInput()
                ->withErrors(['ai_error' => 'AI Generation Failed: ' . $e->getMessage()]);
        }

        // If empty for some other reason
        if (empty($draftQuestions)) {
            return back()->withInput()->withErrors(['ai_error' => 'AI returned no questions.']);
        }

        // ✅ Normalize by language mode + per-type schema
        $draftQuestions = array_map(fn($q) => $this->normalizeDraftQuestion($q, $langMode), $draftQuestions);

        session()->put($this->draftSessionKey($lesson->id), [
            'lang_mode' => $langMode,
            'questions' => $draftQuestions,
        ]);

        $draftPayload = [
            'lang_mode' => $langMode,
            'questions' => $draftQuestions,
        ];

        return view('admin.lessons.ai.questions.review', [
            'lesson' => $lesson,
            'langMode' => $langMode,
            'draftQuestions' => $draftQuestions,
            'draftJson' => json_encode($draftPayload, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function store(Request $request, Lesson $lesson)
    {
        $request->validate([
            'draft_json' => 'required|string',
        ]);

        $payload = json_decode($request->input('draft_json'), true);
        if (!is_array($payload)) {
            return back()->withErrors(['draft_json' => 'Invalid JSON payload.']);
        }

        $langMode = Arr::get($payload, 'lang_mode', 'ar');
        $questions = Arr::get($payload, 'questions', []);
        if (!is_array($questions) || count($questions) === 0) {
            return back()->withErrors(['draft_json' => 'No questions found in payload.']);
        }

        $savedIds = [];

        foreach ($questions as $q) {
            $q = $this->normalizeDraftQuestion($q, $langMode);

            $type = Arr::get($q, 'type');
            if (!in_array($type, ['MCQ', 'TF', 'ESSAY', 'REORDER', 'CLASSIFICATION'], true)) {
                continue;
            }

            // ✅ DB type strategy:
            // - Keep TF in draft_json as "TF"
            // - Save to DB as type = "MCQ" + metadata subtype TRUE_FALSE
            $dbType = ($type === 'TF') ? 'MCQ' : $type;

            // difficulty is normalized in normalizeDraftQuestion() to easy|medium|hard
            $difficulty = Arr::get($q, 'difficulty', 'medium');
            $difficulty = is_string($difficulty) ? strtolower(trim($difficulty)) : 'medium';
            if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
                $difficulty = 'medium';
            }

            // ✅ metadata holds ALL question content (since questions table has no text_ar/text_en)
            $metadata = [
                'lang_mode' => $langMode,
                'source' => 'lesson_ai_generator',
                'ai_draft' => true,
                'schema_v' => 2,

                'question_text_ar' => Arr::get($q, 'text_ar'),
                'question_text_en' => Arr::get($q, 'text_en'),
                'difficulty' => $difficulty,
            ];

            if ($type === 'MCQ') {
                $metadata['options'] = Arr::get($q, 'options', []);
            } elseif ($type === 'TF') {
                $metadata['subtype'] = 'TRUE_FALSE';
                $metadata['options'] = Arr::get($q, 'options', []);
            } elseif ($type === 'REORDER') {
                $metadata['reorder_items'] = Arr::get($q, 'reorder_items', []);
            } elseif ($type === 'CLASSIFICATION') {
                $metadata['classification'] = Arr::get($q, 'classification', []);
            } else { // ESSAY
                $metadata['essay'] = Arr::get($q, 'essay', []);
            }

            $question = new Question();
            $question->lesson_id = $lesson->id;
            $question->type = $dbType;

            // ✅ IMPORTANT:
            // Your DB shows prompt_ar/prompt_en are NOT NULL in sqlite,
            // so we store '' instead of null to avoid constraint violations.
            $promptAr = Arr::get($q, 'prompt_ar');
            $promptEn = Arr::get($q, 'prompt_en');

            if ($langMode === 'ar') {
                $promptEn = '';
            }
            if ($langMode === 'en') {
                $promptAr = '';
            }

            $question->prompt_ar = is_null($promptAr) ? '' : (string) $promptAr;
            $question->prompt_en = is_null($promptEn) ? '' : (string) $promptEn;

            /**
             * ✅ FIX: SQLite CHECK constraint on questions.difficulty
             * DB غالبًا متوقع: Easy / Medium / Hard
             */
            $question->difficulty = strtoupper($difficulty); // Easy / Medium / Hard

            $question->metadata = json_encode($metadata, JSON_UNESCAPED_UNICODE);

            $question->save();
            $savedIds[] = $question->id;
        }

        session()->forget($this->draftSessionKey($lesson->id));

        // ✅ FIX: correct route name (no double admin.)
        return redirect()
            ->route('admin.lessons.ai.questions.create', $lesson)
            ->with('success', 'Saved ' . count($savedIds) . ' questions successfully.');
    }

    // -------------------------
    // Helpers
    // -------------------------

    /**
     * ✅ Accepts: AR/EN/BOTH OR ar/en/both
     * Returns: ar|en|both
     */
    private function normalizeLangMode(string $mode): string
    {
        $mode = strtolower(trim($mode));

        return match ($mode) {
            'ar' => 'ar',
            'en' => 'en',
            'both' => 'both',
            default => 'ar',
        };
    }

    private function draftSessionKey($lessonId): string
    {
        return "lesson_ai_draft_questions_{$lessonId}";
    }

    /**
     * Normalize + enforce schema by langMode and type
     */
    private function normalizeDraftQuestion(array $q, string $langMode): array
    {
        $q['id'] = Arr::get($q, 'id') ?: (string) Str::uuid();
        $q['type'] = Arr::get($q, 'type', 'MCQ');

        /**
         * ✅ Normalize difficulty (accept: easy/Easy/EASY)
         * Keep internal as: easy|medium|hard
         */
        $difficulty = Arr::get($q, 'difficulty', 'medium');
        $difficulty = is_string($difficulty) ? strtolower(trim($difficulty)) : 'medium';
        if (!in_array($difficulty, ['easy', 'medium', 'hard'], true)) {
            $difficulty = 'medium';
        }
        $q['difficulty'] = $difficulty;

        $q['text_ar'] = Arr::get($q, 'text_ar');
        $q['text_en'] = Arr::get($q, 'text_en');
        $q['prompt_ar'] = Arr::get($q, 'prompt_ar');
        $q['prompt_en'] = Arr::get($q, 'prompt_en');

        if ($langMode === 'ar') {
            $q['text_en'] = null;
            $q['prompt_en'] = null;
        } elseif ($langMode === 'en') {
            $q['text_ar'] = null;
            $q['prompt_ar'] = null;
        }

        // MCQ normalize
        if (($q['type'] ?? null) === 'MCQ') {
            $options = Arr::get($q, 'options', []);
            $options = is_array($options) ? $options : [];
            $options = array_values(array_map(function ($opt) use ($langMode) {
                $opt = is_array($opt) ? $opt : [];
                $opt['text_ar'] = Arr::get($opt, 'text_ar');
                $opt['text_en'] = Arr::get($opt, 'text_en');
                $opt['is_correct'] = (bool) Arr::get($opt, 'is_correct', false);

                if ($langMode === 'ar')
                    $opt['text_en'] = null;
                if ($langMode === 'en')
                    $opt['text_ar'] = null;

                return $opt;
            }, $options));

            // ensure at least 2 options
            if (count($options) < 2) {
                $options = [
                    ['text_ar' => 'اختيار 1', 'text_en' => ($langMode === 'both' || $langMode === 'en') ? 'Option 1' : null, 'is_correct' => true],
                    ['text_ar' => 'اختيار 2', 'text_en' => ($langMode === 'both' || $langMode === 'en') ? 'Option 2' : null, 'is_correct' => false],
                ];
                if ($langMode === 'ar') {
                    $options[0]['text_en'] = null;
                    $options[1]['text_en'] = null;
                }
                if ($langMode === 'en') {
                    $options[0]['text_ar'] = null;
                    $options[1]['text_ar'] = null;
                }
            }

            // ensure exactly 1 correct (if none selected -> first)
            $hasCorrect = false;
            foreach ($options as $opt) {
                if (!empty($opt['is_correct'])) {
                    $hasCorrect = true;
                    break;
                }
            }
            if (!$hasCorrect && isset($options[0])) {
                $options[0]['is_correct'] = true;
            }

            $q['options'] = $options;
        }

        // TF normalize (always 2 options: True/False)
        if (($q['type'] ?? null) === 'TF') {
            $opts = Arr::get($q, 'options', []);
            $opts = is_array($opts) ? $opts : [];

            // detect correct (default true)
            $correctIndex = 0;
            foreach ($opts as $i => $o) {
                if (!empty($o['is_correct'])) {
                    $correctIndex = $i;
                    break;
                }
            }

            $trueAr = 'صحيح';
            $falseAr = 'خطأ';
            $trueEn = 'True';
            $falseEn = 'False';

            $options = [
                [
                    'text_ar' => ($langMode === 'en') ? null : $trueAr,
                    'text_en' => ($langMode === 'ar') ? null : $trueEn,
                    'is_correct' => ($correctIndex === 0),
                ],
                [
                    'text_ar' => ($langMode === 'en') ? null : $falseAr,
                    'text_en' => ($langMode === 'ar') ? null : $falseEn,
                    'is_correct' => ($correctIndex === 1),
                ],
            ];

            // if correctIndex > 1, default true
            if (!($options[0]['is_correct'] || $options[1]['is_correct'])) {
                $options[0]['is_correct'] = true;
            }

            $q['options'] = $options;
        }

        // REORDER normalize
        if (($q['type'] ?? null) === 'REORDER') {
            $items = Arr::get($q, 'reorder_items', []);
            $items = is_array($items) ? $items : [];
            $items = array_values(array_map(function ($it) use ($langMode) {
                $it = is_array($it) ? $it : [];
                $it['text_ar'] = Arr::get($it, 'text_ar');
                $it['text_en'] = Arr::get($it, 'text_en');
                if ($langMode === 'ar')
                    $it['text_en'] = null;
                if ($langMode === 'en')
                    $it['text_ar'] = null;
                return $it;
            }, $items));
            $q['reorder_items'] = $items;
        }

        // CLASSIFICATION normalize
        if (($q['type'] ?? null) === 'CLASSIFICATION') {
            $cls = Arr::get($q, 'classification', []);
            $cls = is_array($cls) ? $cls : [];

            $categories = Arr::get($cls, 'categories', []);
            $categories = is_array($categories) ? $categories : [];
            $categories = array_values(array_map(function ($cat, $idx) use ($langMode) {
                $cat = is_array($cat) ? $cat : [];
                $cat['id'] = Arr::get($cat, 'id') ?: ($idx === 0 ? 'A' : 'B');
                $cat['label_ar'] = Arr::get($cat, 'label_ar', $idx === 0 ? 'التصنيف (أ)' : 'التصنيف (ب)');
                $cat['label_en'] = Arr::get($cat, 'label_en', $idx === 0 ? 'Category A' : 'Category B');

                if ($langMode === 'ar')
                    $cat['label_en'] = null;
                if ($langMode === 'en')
                    $cat['label_ar'] = null;

                return $cat;
            }, $categories, array_keys($categories)));

            if (count($categories) < 2) {
                $categories = [
                    ['id' => 'A', 'label_ar' => 'التصنيف (أ)', 'label_en' => 'Category A'],
                    ['id' => 'B', 'label_ar' => 'التصنيف (ب)', 'label_en' => 'Category B'],
                ];
                if ($langMode === 'ar') {
                    $categories[0]['label_en'] = null;
                    $categories[1]['label_en'] = null;
                }
                if ($langMode === 'en') {
                    $categories[0]['label_ar'] = null;
                    $categories[1]['label_ar'] = null;
                }
            }

            $items = Arr::get($cls, 'items', []);
            $items = is_array($items) ? $items : [];
            $items = array_values(array_map(function ($it) use ($langMode) {
                $it = is_array($it) ? $it : [];
                $it['text_ar'] = Arr::get($it, 'text_ar');
                $it['text_en'] = Arr::get($it, 'text_en');
                $it['correct_category'] = Arr::get($it, 'correct_category', 'A');
                if (!in_array($it['correct_category'], ['A', 'B'], true))
                    $it['correct_category'] = 'A';

                if ($langMode === 'ar')
                    $it['text_en'] = null;
                if ($langMode === 'en')
                    $it['text_ar'] = null;

                return $it;
            }, $items));

            $q['classification'] = [
                'categories' => $categories,
                'items' => $items,
            ];
        }

        return $q;
    }

    /**
     * Stub generator (replace with AI later)
     * Now supports MCQ + TF + ESS associated schemas
     * Also assigns per-question difficulty based on distribution.
     */
    private function stubDraft(array $types, int $count, string $langMode, array $dist): array
    {
        // ignore unsupported now (FILL_BLANK) in stub
        $supported = array_values(array_filter($types, fn($t) => in_array($t, ['MCQ', 'TF', 'ESSAY', 'CLASSIFICATION', 'REORDER'], true)));
        if (count($supported) === 0)
            $supported = ['MCQ'];

        // build difficulty pool
        $pool = [];
        $easyN = (int) round($count * ($dist['easy'] / 100));
        $medN = (int) round($count * ($dist['medium'] / 100));
        $hardN = max(0, $count - $easyN - $medN);

        for ($i = 0; $i < $easyN; $i++)
            $pool[] = 'easy';
        for ($i = 0; $i < $medN; $i++)
            $pool[] = 'medium';
        for ($i = 0; $i < $hardN; $i++)
            $pool[] = 'hard';
        shuffle($pool);

        $out = [];

        for ($i = 0; $i < $count; $i++) {
            $t = $supported[array_rand($supported)];
            $difficulty = $pool[$i] ?? 'medium';

            $base = [
                'id' => (string) Str::uuid(),
                'type' => $t,
                'difficulty' => $difficulty,

                'text_ar' => "سؤال تجريبي رقم " . ($i + 1),
                'text_en' => "Sample Question " . ($i + 1),
                'prompt_ar' => "اشرح/أجب باختصار.",
                'prompt_en' => "Answer briefly.",
            ];

            if ($t === 'MCQ') {
                $base['options'] = [
                    ['text_ar' => 'اختيار 1', 'text_en' => 'Option 1', 'is_correct' => true],
                    ['text_ar' => 'اختيار 2', 'text_en' => 'Option 2', 'is_correct' => false],
                    ['text_ar' => 'اختيار 3', 'text_en' => 'Option 3', 'is_correct' => false],
                    ['text_ar' => 'اختيار 4', 'text_en' => 'Option 4', 'is_correct' => false],
                ];
            }

            if ($t === 'TF') {
                $base['options'] = [
                    ['text_ar' => 'صحيح', 'text_en' => 'True', 'is_correct' => true],
                    ['text_ar' => 'خطأ', 'text_en' => 'False', 'is_correct' => false],
                ];
            }

            if ($t === 'REORDER') {
                $base['reorder_items'] = [
                    ['text_ar' => 'الخطوة الأولى', 'text_en' => 'Step one'],
                    ['text_ar' => 'الخطوة الثانية', 'text_en' => 'Step two'],
                    ['text_ar' => 'الخطوة الثالثة', 'text_en' => 'Step three'],
                ];
            }

            if ($t === 'CLASSIFICATION') {
                $base['classification'] = [
                    'categories' => [
                        ['id' => 'A', 'label_ar' => 'التصنيف (أ)', 'label_en' => 'Category A'],
                        ['id' => 'B', 'label_ar' => 'التصنيف (ب)', 'label_en' => 'Category B'],
                    ],
                    'items' => [
                        ['text_ar' => 'عنصر 1', 'text_en' => 'Item 1', 'correct_category' => 'A'],
                        ['text_ar' => 'عنصر 2', 'text_en' => 'Item 2', 'correct_category' => 'B'],
                        ['text_ar' => 'عنصر 3', 'text_en' => 'Item 3', 'correct_category' => 'A'],
                    ],
                ];
            }

            if ($t === 'ESSAY') {
                $base['essay'] = [
                    'guidance_ar' => 'اكتب فقرة من 3-5 جمل.',
                    'guidance_en' => 'Write 3-5 sentences.',
                ];
            }

            // apply lang nulling inside normalizer later
            $out[] = $base;
        }

        return $out;
    }
}
