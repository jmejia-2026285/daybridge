<?php

namespace App\Http\Controllers;

use App\Services\Moodle\MoodleException;
use App\Services\Moodle\MoodleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Inicio de sesión único hacia Moodle: pide una llave de un solo uso
 * para la cuenta vinculada (por correo) y redirige ya autenticado.
 */
class MoodleSsoController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $moodle = app(MoodleService::class);

        if (! $moodle->isAvailable()) {
            return redirect()->route('learning.index');
        }

        try {
            $loginUrl = $moodle->requestLoginUrl($request->user(), (string) $request->ip());
        } catch (MoodleException $exception) {
            report($exception);

            return redirect()->route('learning.index');
        }

        if ($target = $this->safeTarget($request, $moodle->baseUrl())) {
            $loginUrl .= (str_contains($loginUrl, '?') ? '&' : '?').'wantsurl='.urlencode($target);
        }

        return redirect()->away($loginUrl);
    }

    /**
     * Solo se aceptan destinos dentro de Moodle, para no permitir redirecciones maliciosas.
     */
    private function safeTarget(Request $request, string $baseUrl): ?string
    {
        $to = (string) $request->query('to');

        if ($to === '' || ! str_starts_with($to, $baseUrl.'/')) {
            return null;
        }

        return $to;
    }
}
