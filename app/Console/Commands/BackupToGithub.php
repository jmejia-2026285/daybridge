<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Process;
use PDO;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

#[Signature('backup:run {--retention= : Días de retención de respaldos en el repositorio (por omisión: 60)}')]
#[Description('Crea un respaldo de la base de datos y los archivos, y lo sube al repositorio privado de GitHub.')]
class BackupToGithub extends Command
{
    private const TEMP_DIR = 'storage/framework/backup';

    public function handle(): int
    {
        $repo = config('services.backup.repo', 'jmejia-2026285/daybridge-respaldo');
        $retention = (int) ($this->option('retention') ?? config('services.backup.retention_days', 60));
        $tag = 'respaldo-'.now()->format('Ymd');
        $filename = 'daybridge-respaldo-'.now()->format('Y-m-d').'.zip';

        if (! class_exists(ZipArchive::class)) {
            $this->error('La extensión zip de PHP no está disponible.');

            return self::FAILURE;
        }

        if (! Process::run('gh --version')->successful()) {
            $this->error('No se encontró la CLI de GitHub (gh) en el PATH.');

            return self::FAILURE;
        }

        $this->info('Copiando base de datos de forma consistente...');
        $databaseCopy = $this->copyDatabase();

        $this->info('Comprimiendo archivos...');
        $zipPath = $this->buildZip($databaseCopy, $filename);

        if (! is_file($zipPath)) {
            $this->error('No fue posible crear el respaldo comprimido.');

            return self::FAILURE;
        }

        $sizeMb = round(filesize($zipPath) / 1048576, 1);
        $this->info("Respaldo creado: {$filename} ({$sizeMb} MB)");

        $this->info('Subiendo a GitHub...');
        $this->ensureRelease($repo, $tag, $zipPath, $filename);

        $this->info('Aplicando retención de '.$retention.' días...');
        $this->pruneReleases($repo, $retention);

        unlink($zipPath);
        unlink($databaseCopy);

        $this->info("Listo. Respaldo disponible en https://github.com/{$repo}/releases/tag/{$tag}");

        return self::SUCCESS;
    }

    /**
     * Crea una copia consistente de la base de datos SQLite (independiente del WAL).
     */
    private function copyDatabase(): string
    {
        $source = database_path('database.sqlite');
        $copy = base_path(self::TEMP_DIR.'/database.sqlite');

        if (! is_dir(dirname($copy))) {
            mkdir(dirname($copy), 0775, true);
        }

        if (is_file($copy)) {
            unlink($copy);
        }

        $pdo = new PDO('sqlite:'.$source, null, null, [PDO::SQLITE_ATTR_OPEN_FLAGS => PDO::SQLITE_OPEN_READONLY]);
        $pdo->exec("VACUUM INTO '".str_replace('\\', '/', $copy)."'");
        $pdo = null;

        return $copy;
    }

    /**
     * Comprime la copia de la base de datos y los archivos subidos por los usuarios.
     */
    private function buildZip(string $databaseCopy, string $filename): string
    {
        $zipPath = base_path(self::TEMP_DIR.'/'.$filename);
        $dirs = [
            'storage/app/public' => storage_path('app/public'),
            'storage/app/private/documents' => storage_path('app/private/documents'),
        ];

        if (is_file($zipPath)) {
            unlink($zipPath);
        }

        $zip = new ZipArchive;
        $zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $zip->addFile($databaseCopy, 'database/database.sqlite');

        foreach ($dirs as $prefix => $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $files = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );

            foreach ($files as $file) {
                $relative = $prefix.'/'.substr($file->getPathname(), strlen($dir) + 1);

                if (str_contains($relative, 'livewire-tmp')) {
                    continue;
                }

                $zip->addFile($file->getPathname(), $relative);
            }
        }

        $zip->close();

        return $zipPath;
    }

    /**
     * Sube el respaldo como una release del repositorio (crea o reutiliza la del día).
     */
    private function ensureRelease(string $repo, string $tag, string $zipPath, string $filename): void
    {
        $exists = Process::run(['gh', 'release', 'view', $tag, '--repo', $repo])
            ->successful();

        if ($exists) {
            Process::run(['gh', 'release', 'delete-asset', $tag, $filename, '--repo', $repo, '--yes']);
            Process::run(['gh', 'release', 'upload', $tag, $zipPath, '--repo', $repo, '--clobber']);

            return;
        }

        $result = Process::run([
            'gh', 'release', 'create', $tag,
            $zipPath.'#'.$filename,
            '--repo', $repo,
            '--title', 'Respaldo '.now()->format('d/m/Y'),
            '--notes', 'Respaldo automático de Daybridge: base de datos y archivos subidos.',
        ]);

        if (! $result->successful()) {
            $this->error($result->errorOutput() ?: $result->output());
            exit(self::FAILURE);
        }
    }

    /**
     * Elimina las releases más antiguas que el período de retención.
     */
    private function pruneReleases(string $repo, int $retentionDays): void
    {
        $result = Process::run(['gh', 'release', 'list', '--repo', $repo, '--limit', '200', '--json', 'tagName,createdAt']);
        $releases = json_decode($result->output() ?: '[]', true);

        foreach ($releases as $release) {
            $createdAt = Carbon::parse($release['createdAt']);

            if ($createdAt->lt(now()->subDays($retentionDays))) {
                Process::run(['gh', 'release', 'delete', $release['tagName'], '--repo', $repo, '--yes', '--cleanup-tag']);
                $this->line('Eliminado respaldo vencido: '.$release['tagName']);
            }
        }
    }
}
