<?php

namespace App\Console\Commands;

use App\Exports\ObservationsExport;
use App\Models\Consultation;
use App\Models\Observation;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Genera respaldo automatizado de observaciones cada 48 horas durante
 * el periodo activo de consultas (exigencia del brief).
 *
 * Comportamiento:
 *  - Solo se ejecuta si hay al menos una consulta en status='active'
 *  - Genera UN xlsx con TODAS las observaciones de consultas activas
 *  - Guarda en disco 'local' bajo backups/observations/{filename}
 *  - En produccion el FILESYSTEM_DISK=s3 hara que el zip caiga en S3
 *
 * Schedule en routes/console.php: cada 2 dias a las 02:00.
 */
class BackupObservations extends Command
{
    protected $signature = 'gore:backup-observations
                            {--force : Ejecuta aunque no haya consultas activas}';

    protected $description = 'Genera respaldo de observaciones de consultas activas (cada 48h durante periodo de consulta)';

    public function handle(): int
    {
        $activeConsultations = Consultation::query()
            ->where('status', Consultation::STATUS_ACTIVE)
            ->pluck('id');

        if ($activeConsultations->isEmpty() && ! $this->option('force')) {
            $this->info('No hay consultas activas. Saltando backup.');
            return self::SUCCESS;
        }

        $query = Observation::query()
            ->when($activeConsultations->isNotEmpty(),
                fn ($q) => $q->whereIn('consultation_id', $activeConsultations));

        $count = $query->clone()->count();
        $this->info("Generando respaldo de {$count} observaciones de "
            . $activeConsultations->count() . ' consultas activas...');

        $filename = sprintf(
            'observations-backup-%s.xlsx',
            now()->format('Y-m-d_His'),
        );
        $relativePath = "backups/observations/{$filename}";

        // store() coloca el archivo en el disco configurado (local en dev, s3 en prod).
        // ObservationsExport reusa la misma logica que el export del backoffice.
        Excel::store(new ObservationsExport($query), $relativePath, 'local');

        $absPath = Storage::disk('local')->path($relativePath);
        $size = Storage::disk('local')->exists($relativePath)
            ? round(Storage::disk('local')->size($relativePath) / 1024, 1) . ' KB'
            : 'desconocido';

        $this->info("Respaldo generado: {$relativePath} ({$size})");
        $this->line("Ruta absoluta: {$absPath}");

        return self::SUCCESS;
    }
}
