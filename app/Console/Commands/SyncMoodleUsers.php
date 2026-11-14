<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\Moodle\MoodleException;
use App\Services\Moodle\MoodleService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('moodle:sync-users
    {--create : Crea en Moodle las cuentas que no existan (Moodle envía la contraseña por correo)}
    {--email=* : Limita la sincronización a estos correos}')]
#[Description('Vincula los colaboradores activos con su cuenta de Moodle por correo y, opcionalmente, crea las que falten')]
class SyncMoodleUsers extends Command
{
    public function handle(MoodleService $moodle): int
    {
        if (! $moodle->isAvailable()) {
            $this->components->error('Moodle no está configurado. Revisa MOODLE_ENABLED, MOODLE_URL y MOODLE_TOKEN en .env.');

            return self::FAILURE;
        }

        $users = User::query()
            ->active()
            ->when($this->option('email') !== [], fn ($query) => $query->whereIn('email', $this->option('email')))
            ->orderBy('name')
            ->get();

        $rows = [];
        $failures = 0;

        foreach ($users as $user) {
            try {
                [$moodleUserId, $result] = $this->sync($moodle, $user);
            } catch (MoodleException $exception) {
                $failures++;
                [$moodleUserId, $result] = [null, 'Error: '.$exception->getMessage()];
            }

            $rows[] = [$user->name, $user->email, $moodleUserId ?? '—', $result];
        }

        $this->table(['Colaborador', 'Correo', 'Id Moodle', 'Resultado'], $rows);

        return $failures === 0 ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @return array{0: ?int, 1: string}
     *
     * @throws MoodleException
     */
    private function sync(MoodleService $moodle, User $user): array
    {
        if ($user->moodle_user_id !== null) {
            return [$user->moodle_user_id, 'Ya vinculada'];
        }

        $moodleUserId = $moodle->linkAccount($user);

        if ($moodleUserId !== null) {
            return [$moodleUserId, 'Vinculada'];
        }

        if (! $this->option('create')) {
            return [null, 'Sin cuenta en Moodle'];
        }

        return [$moodle->createAccount($user), 'Creada'];
    }
}
