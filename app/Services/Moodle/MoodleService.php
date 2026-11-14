<?php

namespace App\Services\Moodle;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MoodleService
{
    public function __construct(private readonly MoodleClient $client) {}

    public function isAvailable(): bool
    {
        return $this->client->isConfigured();
    }

    public function baseUrl(): string
    {
        return $this->client->baseUrl();
    }

    /**
     * Devuelve el id de Moodle del colaborador, buscándolo por correo la primera vez.
     *
     * @throws MoodleException
     */
    public function linkAccount(User $user): ?int
    {
        if ($user->moodle_user_id !== null) {
            return $user->moodle_user_id;
        }

        $moodleUser = $this->client->findUserByEmail($user->email);

        if ($moodleUser === null) {
            return null;
        }

        $user->forceFill(['moodle_user_id' => (int) $moodleUser['id']])->save();

        return $user->moodle_user_id;
    }

    /**
     * Crea la cuenta en Moodle (Moodle envía la contraseña por correo) y la vincula.
     *
     * @throws MoodleException
     */
    public function createAccount(User $user): int
    {
        $existingId = $this->linkAccount($user);

        if ($existingId !== null) {
            return $existingId;
        }

        [$firstName, $lastName] = $this->splitName($user->name);

        $moodleUserId = $this->client->createUser([
            'username' => Str::lower($user->email),
            'firstname' => $firstName,
            'lastname' => $lastName,
            'email' => $user->email,
            'auth' => (string) config('services.moodle.user_auth', 'manual'),
        ]);

        $user->forceFill(['moodle_user_id' => $moodleUserId])->save();

        return $moodleUserId;
    }

    /**
     * Cursos donde está matriculado el colaborador, con avance y calificación.
     *
     * @return Collection<int, MoodleCourse>
     *
     * @throws MoodleException
     */
    public function coursesFor(User $user): Collection
    {
        $moodleUserId = $this->linkAccount($user);

        if ($moodleUserId === null) {
            return collect();
        }

        /** @var array{courses: list<array<string, mixed>>, grades: array<int, string>} $payload */
        $payload = Cache::remember(
            $this->cacheKey($moodleUserId),
            now()->addMinutes(max(1, (int) config('services.moodle.cache_minutes', 10))),
            fn (): array => [
                'courses' => $this->client->userCourses($moodleUserId),
                'grades' => $this->gradesOrEmpty($moodleUserId),
            ],
        );

        return collect($payload['courses'])
            ->filter(fn (array $course): bool => (bool) ($course['visible'] ?? true))
            ->map(fn (array $course): MoodleCourse => MoodleCourse::fromApi(
                $course,
                $payload['grades'][(int) $course['id']] ?? null,
                $this->baseUrl(),
            ))
            ->sortBy([
                fn (MoodleCourse $a, MoodleCourse $b): int => $a->completed <=> $b->completed,
                fn (MoodleCourse $a, MoodleCourse $b): int => ($b->lastAccessAt?->timestamp ?? 0) <=> ($a->lastAccessAt?->timestamp ?? 0),
                fn (MoodleCourse $a, MoodleCourse $b): int => strcasecmp($a->name, $b->name),
            ])
            ->values();
    }

    /**
     * URL de Moodle que inicia sesión con la cuenta vinculada sin pedir contraseña (SSO).
     *
     * @throws MoodleException
     */
    public function requestLoginUrl(User $user, string $ip): string
    {
        if ($this->linkAccount($user) === null) {
            throw new MoodleException('El colaborador no tiene cuenta en Moodle.', 'unlinked');
        }

        return $this->client->requestLoginUrl($user->email, $ip);
    }

    public function forgetCachedCourses(User $user): void
    {
        if ($user->moodle_user_id !== null) {
            Cache::forget($this->cacheKey($user->moodle_user_id));
        }
    }

    /**
     * El reporte de calificaciones exige permisos extra en el token; si no los tiene, se omite.
     *
     * @return array<int, string>
     */
    private function gradesOrEmpty(int $moodleUserId): array
    {
        try {
            return $this->client->courseGrades($moodleUserId);
        } catch (MoodleException $exception) {
            report($exception);

            return [];
        }
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function splitName(string $name): array
    {
        $parts = preg_split('/\s+/u', trim($name)) ?: [];
        $firstName = array_shift($parts) ?? $name;

        return [$firstName, $parts === [] ? $firstName : implode(' ', $parts)];
    }

    private function cacheKey(int $moodleUserId): string
    {
        return "moodle.courses.{$moodleUserId}";
    }
}
