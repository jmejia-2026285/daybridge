<?php

namespace App\Services\Moodle;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;

/**
 * Cliente mínimo para los Web Services REST de Moodle
 * (`/webservice/rest/server.php`, protocolo REST con respuesta JSON).
 */
class MoodleClient
{
    public function __construct(private readonly HttpFactory $http) {}

    public function isConfigured(): bool
    {
        return (bool) config('services.moodle.enabled')
            && filled(config('services.moodle.url'))
            && filled(config('services.moodle.token'));
    }

    public function baseUrl(): string
    {
        return (string) config('services.moodle.url');
    }

    /**
     * Llama a una función del Web Service y devuelve la respuesta decodificada.
     *
     * @param  array<string, mixed>  $parameters
     * @return array<int|string, mixed>
     *
     * @throws MoodleException
     */
    public function call(string $function, array $parameters = []): array
    {
        if (! $this->isConfigured()) {
            throw MoodleException::notConfigured();
        }

        try {
            $response = $this->http
                ->baseUrl($this->baseUrl())
                ->timeout((int) config('services.moodle.timeout', 10))
                ->acceptJson()
                ->asForm()
                ->post('/webservice/rest/server.php', [
                    'wstoken' => config('services.moodle.token'),
                    'wsfunction' => $function,
                    'moodlewsrestformat' => 'json',
                    ...$parameters,
                ])
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            // No se encadena la excepción original: su petición incluye el
            // wstoken y terminaría en storage/logs al hacer report().
            $status = $exception instanceof RequestException ? ' (HTTP '.$exception->response->status().')' : '';

            throw MoodleException::unreachable(new \RuntimeException($exception::class.$status));
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            // Funciones que no devuelven nada responden `null`.
            return [];
        }

        if (isset($payload['exception']) || isset($payload['errorcode'])) {
            throw MoodleException::fromResponse($function, $payload);
        }

        return $payload;
    }

    /**
     * @return array{sitename: string, release: string, userid: int, username: string}
     */
    public function siteInfo(): array
    {
        $info = $this->call('core_webservice_get_site_info');

        return [
            'sitename' => (string) ($info['sitename'] ?? ''),
            'release' => (string) ($info['release'] ?? ''),
            'userid' => (int) ($info['userid'] ?? 0),
            'username' => (string) ($info['username'] ?? ''),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function findUserByEmail(string $email): ?array
    {
        $users = $this->call('core_user_get_users_by_field', [
            'field' => 'email',
            'values' => [$email],
        ]);

        return $users[0] ?? null;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function userCourses(int $moodleUserId): array
    {
        return array_values($this->call('core_enrol_get_users_courses', [
            'userid' => $moodleUserId,
            'returnusercount' => 0,
        ]));
    }

    /**
     * Calificación final por curso, indexada por id de curso.
     *
     * @return array<int, string>
     */
    public function courseGrades(int $moodleUserId): array
    {
        $response = $this->call('gradereport_overview_get_course_grades', [
            'userid' => $moodleUserId,
        ]);

        return collect($response['grades'] ?? [])
            ->mapWithKeys(fn (array $grade): array => [(int) $grade['courseid'] => (string) ($grade['grade'] ?? '')])
            ->all();
    }

    /**
     * @param  array{username: string, firstname: string, lastname: string, email: string, auth: string}  $attributes
     */
    public function createUser(array $attributes): int
    {
        $created = $this->call('core_user_create_users', [
            'users' => [[...$attributes, 'createpassword' => 1]],
        ]);

        return (int) ($created[0]['id'] ?? 0);
    }

    /**
     * URL de Moodle con una llave de un solo uso que inicia sesión sin pedir contraseña (SSO).
     *
     * @throws MoodleException
     */
    public function requestLoginUrl(string $email, string $ip): string
    {
        $response = $this->call('auth_userkey_request_login_url', [
            'user' => ['email' => $email, 'ip' => $ip === '::1' ? '127.0.0.1' : $ip],
        ]);

        return (string) ($response['loginurl'] ?? '');
    }
}
