<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class OpenAiQuestionGenerator
{
    protected string $apiKey;
    protected string $model;

    public function __construct()
    {
        $this->apiKey = config('services.openai.key') ?? env('OPENAI_API_KEY') ?? '';
        $this->model = config('services.openai.model') ?? env('OPENAI_MODEL') ?? 'gpt-4o';
    }

    public function generate(array $context, array $schemaParams): array
    {
        if (empty($this->apiKey)) {
            throw new \Exception('Missing OpenAI API Key in .env file (OPENAI_API_KEY).');
        }

        $systemPrompt = $this->buildSystemPrompt();
        $userPrompt = $this->buildUserPrompt($context, $schemaParams);

        try {
            $response = Http::withToken($this->apiKey)
                ->timeout(120)
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => $this->model,
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPrompt],
                    ],
                    'temperature' => 0.7,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if ($response->failed()) {
                Log::error('OpenAI API Error: ' . $response->body());
                throw new \Exception('OpenAI API request failed: ' . $response->status() . ' - ' . $response->body());
            }

            $json = $response->json('choices.0.message.content');
            if (!$json) {
                throw new \Exception('OpenAI returned empty content.');
            }

            $data = json_decode($json, true);
            if (!is_array($data) || !isset($data['questions'])) {
                // Try to fallback if root is list
                if (is_array($data) && isset($data[0]['type'])) {
                    return $data; // Sometimes it returns list directly
                }
                throw new \Exception('Invalid JSON structure received from OpenAI.');
            }

            return $data['questions'] ?? [];

        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            throw new \Exception('Connection timeout or network error connecting to OpenAI.');
        } catch (\Exception $e) {
            Log::error('OpenAI Generation Exception: ' . $e->getMessage());
            throw $e; // Re-throw to controller
        }
    }

    protected function buildSystemPrompt(): string
    {
        return <<<EOT
You are an expert educational content generator.
Your goal is to generate exam questions based on the provided lesson material.
You must output STRICT valid JSON with a root key "questions" containing an array of question objects.
Follow the language mode strictly (Arabic, English, or Both).
EOT;
    }

    protected function buildUserPrompt(array $context, array $params): string
    {
        $langMode = $context['lang_mode'] ?? 'both';
        $count = $params['count'] ?? 5;
        $types = implode(', ', $params['types'] ?? ['MCQ']);
        $diff = json_encode($params['difficulties'] ?? ['easy' => 30, 'medium' => 40, 'hard' => 30]);

        $lessonTitle = $context['lesson']['title_en'] . ' / ' . $context['lesson']['title_ar'];
        $manualContent = "AR: " . ($context['manual_content']['ar'] ?? '') . "\nEN: " . ($context['manual_content']['en'] ?? '');
        $extracted = $context['attachments_extracted_text'] ?? '';

        // Build schema instruction
        $schema = <<<SCHEMA
Each question object must follow this structure:
{
    "type": "MCQ" | "TF" | "ESSAY" | "REORDER" | "CLASSIFICATION",
    "difficulty": "easy" | "medium" | "hard",
    "text_ar": "Question text in Arabic (if applicable)",
    "text_en": "Question text in English (if applicable)",
    "prompt_ar": "Short prompt in Arabic (e.g. Choose correct answer)",
    "prompt_en": "Short prompt in English (e.g. Choose correct answer)",
    
    // For MCQ:
    "options": [
       {"text_ar": "...", "text_en": "...", "is_correct": boolean}
    ],
    
    // For TF:
    "options": [
       {"text_ar": "صحيح", "text_en": "True", "is_correct": boolean},
       {"text_ar": "خطأ", "text_en": "False", "is_correct": boolean}
    ],
    
    // For REORDER:
    "reorder_items": [
       {"text_ar": "...", "text_en": "..."} // in correct order
    ],
    
    // For CLASSIFICATION:
    "classification": {
       "categories": [
           {"id": "A", "label_ar": "...", "label_en": "..."},
           {"id": "B", "label_ar": "...", "label_en": "..."}
       ],
       "items": [
           {"text_ar": "...", "text_en": "...", "correct_category": "A" or "B"}
       ]
    },
    
    // For ESSAY:
    "essay": {
       "guidance_ar": "...", 
       "guidance_en": "..."
    }
}
SCHEMA;

        return <<<PROMPT
Context:
Lesson: $lessonTitle
Content:
$manualContent
$extracted

Requirements:
1. Generate exactly $count questions.
2. Question Types allowed: $types.
3. Difficulty Distribution: $diff (percentages).
4. Language Mode: $langMode (If 'ar', en fields can be null. If 'en', ar fields can be null. If 'both', provide both).
5. Output format: JSON.

Schema:
$schema

Generate the JSON now.
PROMPT;
    }
}
