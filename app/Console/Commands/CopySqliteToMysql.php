<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

#[Signature('db:copy-sqlite-to-mysql
    {--source=sqlite_legacy : Conexión SQLite de origen}
    {--target=mysql : Conexión de destino (MySQL) con las migraciones ya ejecutadas}
    {--truncate : Vacía las tablas de destino antes de copiar}
    {--chunk=500 : Filas por lote de inserción}')]
#[Description('Copia todos los datos de la base SQLite actual a MySQL, tabla por tabla y respetando las llaves foráneas')]
class CopySqliteToMysql extends Command
{
    /**
     * Tables that the target fills by itself and must never be copied.
     *
     * @var list<string>
     */
    private const SKIPPED_TABLES = ['migrations', 'sqlite_sequence'];

    public function handle(): int
    {
        $source = DB::connection($this->option('source'));
        $target = DB::connection($this->option('target'));
        $chunkSize = max(1, (int) $this->option('chunk'));

        if ($source->getDriverName() !== 'sqlite') {
            $this->components->error("La conexión de origen «{$source->getName()}» debe ser SQLite.");

            return self::FAILURE;
        }

        if ($source->getName() === $target->getName()) {
            $this->components->error('El origen y el destino no pueden ser la misma conexión.');

            return self::FAILURE;
        }

        $tables = $this->tablesToCopy($source, $target);

        if ($tables === null) {
            return self::FAILURE;
        }

        if (! $this->targetIsReady($target, $tables)) {
            return self::FAILURE;
        }

        $overflows = $this->findOverflowingValues($source, $target, $tables);

        if ($overflows !== []) {
            $this->components->error('Hay valores más largos de lo que permite MySQL. Corrígelos en SQLite y vuelve a intentar:');
            $this->table(['Tabla', 'Columna', 'Máximo en MySQL', 'Más largo en SQLite'], $overflows);

            return self::FAILURE;
        }

        $this->components->info("Copiando {$this->countLabel(count($tables))} de «{$source->getName()}» a «{$target->getName()}».");

        $target->getSchemaBuilder()->disableForeignKeyConstraints();

        try {
            if ($this->option('truncate')) {
                foreach (array_reverse($tables) as $table) {
                    $target->table($table)->truncate();
                }
            }

            $summary = [];

            foreach ($tables as $table) {
                $this->components->task($table, function () use ($source, $target, $table, $chunkSize, &$summary): void {
                    $copied = $this->copyTable($source, $target, $table, $chunkSize);
                    $summary[] = [$table, $source->table($table)->count(), $copied, $target->table($table)->count()];
                });
            }
        } catch (Throwable $exception) {
            $this->components->error("La copia se detuvo: {$exception->getMessage()}");

            return self::FAILURE;
        } finally {
            $target->getSchemaBuilder()->enableForeignKeyConstraints();
        }

        $this->newLine();
        $this->table(['Tabla', 'Filas en SQLite', 'Copiadas', 'Filas en MySQL'], $summary);

        $mismatches = collect($summary)->filter(fn (array $row): bool => $row[1] !== $row[3]);

        if ($mismatches->isNotEmpty()) {
            $this->components->error('El conteo no coincide en: '.$mismatches->pluck(0)->implode(', '));

            return self::FAILURE;
        }

        $this->components->info('Copia completa. Cambia DB_CONNECTION=mysql en .env y ejecuta php artisan optimize:clear.');

        return self::SUCCESS;
    }

    /**
     * Source tables ordered so that every parent table is copied before its children.
     *
     * @return list<string>|null
     */
    private function tablesToCopy(Connection $source, Connection $target): ?array
    {
        $sourceSchema = $source->getSchemaBuilder();
        $targetTables = $target->getSchemaBuilder()->getTableListing(schemaQualified: false);

        $tables = collect($sourceSchema->getTableListing(schemaQualified: false))
            ->reject(fn (string $table): bool => in_array($table, self::SKIPPED_TABLES, true))
            ->values();

        $missing = $tables->diff($targetTables);

        if ($missing->isNotEmpty()) {
            $this->components->error('Faltan tablas en el destino: '.$missing->implode(', '));
            $this->line('  Ejecuta primero: <comment>php artisan migrate --database='.$target->getName().'</comment>');

            return null;
        }

        /** @var array<string, list<string>> $parents */
        $parents = $tables->mapWithKeys(fn (string $table): array => [
            $table => collect($sourceSchema->getForeignKeys($table))
                ->pluck('foreign_table')
                ->reject(fn (string $parent): bool => $parent === $table)
                ->intersect($tables)
                ->unique()
                ->values()
                ->all(),
        ])->all();

        $ordered = [];

        while (count($ordered) < count($parents)) {
            $ready = collect($parents)
                ->keys()
                ->diff($ordered)
                ->filter(fn (string $table): bool => array_diff($parents[$table], $ordered) === [])
                ->sort()
                ->values();

            if ($ready->isEmpty()) {
                // Circular references: append the rest; foreign keys are disabled while copying.
                $ready = collect($parents)->keys()->diff($ordered)->sort()->values();
            }

            array_push($ordered, ...$ready->all());
        }

        return $ordered;
    }

    /**
     * @param  list<string>  $tables
     */
    private function targetIsReady(Connection $target, array $tables): bool
    {
        if ($this->option('truncate')) {
            return true;
        }

        $filled = collect($tables)->filter(fn (string $table): bool => $target->table($table)->exists());

        if ($filled->isEmpty()) {
            return true;
        }

        $this->components->error('El destino ya tiene datos en: '.$filled->implode(', '));
        $this->line('  Usa <comment>--truncate</comment> para vaciarlas antes de copiar.');

        return false;
    }

    /**
     * Strings that SQLite accepted but a MySQL VARCHAR/CHAR column would reject in strict mode.
     *
     * @param  list<string>  $tables
     * @return list<array{0: string, 1: string, 2: int, 3: int}>
     */
    private function findOverflowingValues(Connection $source, Connection $target, array $tables): array
    {
        $overflows = [];

        foreach ($tables as $table) {
            $sourceColumns = collect($source->getSchemaBuilder()->getColumns($table))->pluck('name');

            foreach ($target->getSchemaBuilder()->getColumns($table) as $column) {
                if (! preg_match('/^(?:var)?char\((\d+)\)/i', $column['type'], $matches) || ! $sourceColumns->contains($column['name'])) {
                    continue;
                }

                $limit = (int) $matches[1];
                $longest = (int) $source->table($table)->max($source->raw('length('.$source->getQueryGrammar()->wrap($column['name']).')'));

                if ($longest > $limit) {
                    $overflows[] = [$table, $column['name'], $limit, $longest];
                }
            }
        }

        return $overflows;
    }

    private function copyTable(Connection $source, Connection $target, string $table, int $chunkSize): int
    {
        $targetColumns = collect($target->getSchemaBuilder()->getColumns($table));
        $columns = collect($source->getSchemaBuilder()->getColumns($table))
            ->pluck('name')
            ->intersect($targetColumns->pluck('name'))
            ->values()
            ->all();

        $jsonColumns = $targetColumns
            ->filter(fn (array $column): bool => Str::lower($column['type_name']) === 'json')
            ->pluck('name')
            ->all();

        $orderColumn = in_array('id', $columns, true) ? 'id' : $columns[0];
        $copied = 0;

        $source->table($table)
            ->select($columns)
            ->orderBy($orderColumn)
            ->chunk($chunkSize, function ($rows) use ($target, $table, $jsonColumns, &$copied): void {
                $payload = $rows
                    ->map(fn (object $row): array => $this->normalizeRow((array) $row, $jsonColumns))
                    ->all();

                $target->table($table)->insert($payload);
                $copied += count($payload);
            });

        return $copied;
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $jsonColumns
     * @return array<string, mixed>
     */
    private function normalizeRow(array $row, array $jsonColumns): array
    {
        foreach ($jsonColumns as $column) {
            if (($row[$column] ?? null) === '') {
                $row[$column] = null;
            }
        }

        return $row;
    }

    private function countLabel(int $count): string
    {
        return $count === 1 ? '1 tabla' : "{$count} tablas";
    }
}
