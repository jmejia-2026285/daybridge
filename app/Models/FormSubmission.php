<?php

namespace App\Models;

use Database\Factories\FormSubmissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['form_id', 'user_id', 'answers'])]
class FormSubmission extends Model
{
    /** @use HasFactory<FormSubmissionFactory> */
    use HasFactory;

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function answerFor(int $fieldId): ?string
    {
        $answers = $this->answers ?? [];
        $answer = $answers[$fieldId] ?? $answers[(string) $fieldId] ?? null;

        return $answer === null || $answer === '' ? null : (string) $answer;
    }

    protected function casts(): array
    {
        return [
            'answers' => 'array',
        ];
    }
}
