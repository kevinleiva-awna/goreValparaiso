<?php

namespace App\Console\Commands;

use App\Models\ConsultationDocument;
use App\Models\Observation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Verifica que cada archivo referenciado en consultation_documents y
 * observations exista efectivamente en el disk con el que se subio.
 *
 * Util despues de migrar de FILESYSTEM_DISK=local a s3: detecta filas
 * cuyo storage_path apunta a un archivo inexistente (huerfano) y deja
 * un reporte tabulado.
 *
 * No mueve ni elimina archivos: solo reporta. Para migrar archivos
 * existentes de local a s3, usar `aws s3 sync storage/app/private/ s3://bucket/`
 * antes de cambiar FILESYSTEM_DISK y luego correr este comando para
 * verificar que todo quedo accesible.
 */
class StorageMigratePaths extends Command
{
    protected $signature = 'storage:migrate-paths
                            {--fix-disk : Actualiza storage_disk al valor actual de FILESYSTEM_DISK si el archivo SI existe alli pero no en el disk declarado}';

    protected $description = 'Verifica integridad de paths/disks en consultation_documents y observations; reporta huerfanos';

    public function handle(): int
    {
        $defaultDisk = config('filesystems.default');
        $fixDisk = (bool) $this->option('fix-disk');

        $this->info("Disk default actual: {$defaultDisk}");
        $this->newLine();

        $docOk = $docMissing = $docFixed = 0;
        $obsOk = $obsMissing = $obsFixed = 0;

        $this->info('=== consultation_documents ===');
        foreach (ConsultationDocument::query()->withTrashed()->lazy() as $doc) {
            $declared = $doc->storage_disk ?: $defaultDisk;
            if (Storage::disk($declared)->exists($doc->storage_path)) {
                $docOk++;
                continue;
            }

            // El archivo no esta donde dice. Probar el disk default.
            if ($declared !== $defaultDisk && Storage::disk($defaultDisk)->exists($doc->storage_path)) {
                if ($fixDisk) {
                    $doc->forceFill(['storage_disk' => $defaultDisk])->saveQuietly();
                    $docFixed++;
                    $this->line("  [FIXED] doc #{$doc->id} {$doc->storage_path} -> disk={$defaultDisk}");
                    continue;
                }
                $this->warn("  [MISMATCH] doc #{$doc->id} {$doc->storage_path} esta en {$defaultDisk}, declarado {$declared} (use --fix-disk)");
                continue;
            }

            $docMissing++;
            $this->error("  [MISSING] doc #{$doc->id} disk={$declared} path={$doc->storage_path} (consultation_id={$doc->consultation_id}, titulo={$doc->title})");
        }
        $this->info("consultation_documents: ok={$docOk} missing={$docMissing} fixed={$docFixed}");
        $this->newLine();

        $this->info('=== observations (con adjunto) ===');
        foreach (Observation::query()->whereNotNull('attachment_path')->lazy() as $obs) {
            $declared = $obs->attachment_disk ?: $defaultDisk;
            if (Storage::disk($declared)->exists($obs->attachment_path)) {
                $obsOk++;
                continue;
            }

            if ($declared !== $defaultDisk && Storage::disk($defaultDisk)->exists($obs->attachment_path)) {
                if ($fixDisk) {
                    $obs->forceFill(['attachment_disk' => $defaultDisk])->saveQuietly();
                    $obsFixed++;
                    $this->line("  [FIXED] obs #{$obs->id} {$obs->attachment_path} -> disk={$defaultDisk}");
                    continue;
                }
                $this->warn("  [MISMATCH] obs #{$obs->id} {$obs->attachment_path} esta en {$defaultDisk}, declarado {$declared} (use --fix-disk)");
                continue;
            }

            $obsMissing++;
            $this->error("  [MISSING] obs #{$obs->id} disk={$declared} path={$obs->attachment_path} (public_id={$obs->public_id})");
        }
        $this->info("observations: ok={$obsOk} missing={$obsMissing} fixed={$obsFixed}");
        $this->newLine();

        if ($docMissing > 0 || $obsMissing > 0) {
            $this->error("Hay archivos huerfanos. Revisa el log y migra los archivos faltantes antes de continuar.");
            return self::FAILURE;
        }

        $this->info('Todo en orden.');
        return self::SUCCESS;
    }
}
