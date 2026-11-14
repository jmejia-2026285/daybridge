<?php

namespace App\Services\Moodle;

use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class MoodleCourse
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly string $shortName,
        public readonly string $summary,
        public readonly ?int $progress,
        public readonly bool $completed,
        public readonly ?Carbon $startsAt,
        public readonly ?Carbon $endsAt,
        public readonly ?Carbon $lastAccessAt,
        public readonly ?string $grade,
        public readonly string $url,
    ) {}

    /**
     * @param  array<string, mixed>  $course  Elemento de `core_enrol_get_users_courses`.
     */
    public static function fromApi(array $course, ?string $grade, string $baseUrl): self
    {
        $progress = $course['progress'] ?? null;
        $completed = (bool) ($course['completed'] ?? false);

        return new self(
            id: (int) $course['id'],
            name: html_entity_decode(strip_tags((string) ($course['displayname'] ?? $course['fullname'] ?? 'Curso')), ENT_QUOTES),
            shortName: (string) ($course['shortname'] ?? ''),
            summary: Str::limit(trim(html_entity_decode(strip_tags((string) ($course['summary'] ?? '')), ENT_QUOTES)), 220),
            progress: $completed ? 100 : ($progress === null ? null : (int) round((float) $progress)),
            completed: $completed,
            startsAt: self::timestamp($course['startdate'] ?? null),
            endsAt: self::timestamp($course['enddate'] ?? null),
            lastAccessAt: self::timestamp($course['lastaccess'] ?? null),
            grade: in_array($grade, [null, '', '-'], true) ? null : $grade,
            url: rtrim($baseUrl, '/').'/course/view.php?id='.(int) $course['id'],
        );
    }

    public function status(): string
    {
        return match (true) {
            $this->completed => 'completed',
            $this->lastAccessAt !== null || ($this->progress ?? 0) > 0 => 'in_progress',
            default => 'not_started',
        };
    }

    public function statusLabel(): string
    {
        return match ($this->status()) {
            'completed' => 'Completado',
            'in_progress' => 'En curso',
            default => 'Sin iniciar',
        };
    }

    public function badgeClasses(): string
    {
        return match ($this->status()) {
            'completed' => 'border-success/30 bg-success-soft text-success',
            'in_progress' => 'border-accent bg-accent-soft text-accent-strong',
            default => 'border-line bg-sunken text-ink-muted',
        };
    }

    public function dotClasses(): string
    {
        return match ($this->status()) {
            'completed' => 'bg-success',
            'in_progress' => 'bg-accent',
            default => 'bg-line-strong',
        };
    }

    private static function timestamp(mixed $value): ?Carbon
    {
        return is_numeric($value) && (int) $value > 0
            ? Carbon::createFromTimestamp((int) $value, config('app.timezone'))
            : null;
    }
}
