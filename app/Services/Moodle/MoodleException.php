<?php

namespace App\Services\Moodle;

use RuntimeException;
use Throwable;

class MoodleException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?string $errorCode = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 0, $previous);
    }

    public static function notConfigured(): self
    {
        return new self('La integración con Moodle no está configurada.', 'not_configured');
    }

    public static function unreachable(Throwable $previous): self
    {
        return new self('No fue posible conectar con Moodle.', 'unreachable', $previous);
    }

    /**
     * @param  array{exception?: string, errorcode?: string, message?: string}  $payload
     */
    public static function fromResponse(string $function, array $payload): self
    {
        $errorCode = $payload['errorcode'] ?? 'unknown';

        return new self("Moodle rechazó «{$function}» ({$errorCode}): ".($payload['message'] ?? 'sin detalle'), $errorCode);
    }

    /**
     * Mensaje seguro para mostrar a cualquier colaborador.
     */
    public function friendlyMessage(): string
    {
        return match ($this->errorCode) {
            'not_configured' => 'La plataforma de capacitación todavía no está configurada.',
            'unreachable' => 'La plataforma de capacitación no responde en este momento. Intenta de nuevo en unos minutos.',
            'invalidtoken', 'accessexception', 'webservicesnotenabled' => 'Daybridge no tiene permiso para consultar Moodle. Avisa a Administración.',
            default => 'Moodle devolvió un error al consultar tus cursos. Intenta de nuevo más tarde.',
        };
    }
}
