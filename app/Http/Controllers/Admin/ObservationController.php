<?php

namespace App\Http\Controllers\Admin;

use App\Exports\ObservationsExport;
use App\Http\Controllers\Controller;
use App\Models\Consultation;
use App\Models\Observation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ObservationController extends Controller
{
    public function index(Request $request): View
    {
        $showArchived = $request->boolean('archived');

        $consultations = Consultation::query()
            ->orderBy('title')
            ->get(['id', 'slug', 'title']);

        $query = $this->buildFilteredQuery($request);
        // onlyTrashed SOLO en el listado; el export (que reusa buildFilteredQuery)
        // sigue excluyendo archivadas por el SoftDeletes por defecto.
        if ($showArchived) {
            $query->onlyTrashed();
        }

        return view('admin.observations.index', [
            'observations' => $query->paginate(20)->withQueryString(),
            'consultations' => $consultations,
            'filters' => $request->only(['consultation_id', 'auth_method', 'from', 'to', 'q']),
            'showArchived' => $showArchived,
        ]);
    }

    /**
     * Archivar (soft-delete) una observacion. Reversible, restringido a
     * super-admin: saca del listado/export sin destruir el expediente.
     */
    public function archive(Request $request, Observation $observation): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $observation->delete();

        return back()->with('status', 'Observacion archivada. Puedes restaurarla desde el filtro "Archivadas".');
    }

    public function restore(Request $request, Observation $observation): RedirectResponse
    {
        abort_unless($request->user()->isSuperAdmin(), 403);

        $observation->restore();

        return back()->with('status', 'Observacion restaurada.');
    }

    public function show(Observation $observation): View
    {
        $observation->load(['consultation', 'user', 'response.responder']);

        return view('admin.observations.show', [
            'observation' => $observation,
        ]);
    }

    public function downloadAttachment(Observation $observation): StreamedResponse
    {
        abort_unless($observation->hasAttachment(), 404);

        // Usa el disk con el que se subio cada adjunto (puede variar por fila
        // tras migrar de 'local' a 's3'). Fallback al default si la columna
        // todavia no fue poblada para filas anteriores a la migration.
        $disk = $observation->attachment_disk ?: config('filesystems.default');

        // Nombrado por ID de expediente (no por el nombre que puso el
        // ciudadano) para que el archivo cruce con su fila del compendio.
        return Storage::disk($disk)->download(
            $observation->attachment_path,
            $observation->attachment_download_name
        );
    }

    public function export(Request $request, string $format = 'xlsx'): BinaryFileResponse
    {
        abort_unless(in_array($format, ['xlsx', 'csv'], true), 404);

        $query = $this->buildFilteredQuery($request);

        $filename = sprintf(
            'observaciones-gore-%s.%s',
            now()->format('Y-m-d_His'),
            $format,
        );

        $writerType = $format === 'csv' ? \Maatwebsite\Excel\Excel::CSV : \Maatwebsite\Excel\Excel::XLSX;

        return $this->tagDownload(
            Excel::download(new ObservationsExport($query), $filename, $writerType),
            $request,
        );
    }

    /**
     * Nombre de la cookie con la que el servidor le avisa al navegador que la
     * descarga ya salio. Va sin encriptar a proposito (ver bootstrap/app.php):
     * la lee el JS del listado, y lo unico que lleva es un token aleatorio que
     * genero ese mismo navegador.
     */
    public const DOWNLOAD_COOKIE = 'gore_export_ready';

    /**
     * Un export es una navegacion normal, no un fetch: cuando la respuesta es
     * un archivo el navegador no dispara ningun evento y la pagina se queda
     * exactamente igual. Con el ZIP de adjuntos eso son cerca de 60 segundos
     * de boton aparentemente muerto, tiempo de sobra para que el funcionario
     * vuelva a hacer clic o de la descarga por fallida -que es literalmente lo
     * que reporto GORE el 09-sep-2026-.
     *
     * Esta cookie es la unica senal que el navegador deja cuando una descarga
     * empieza: viaja en las cabeceras, o sea llega apenas el archivo esta
     * armado y antes de transferir los 330 MB. El JS la espera para apagar el
     * spinner. Si la respuesta termina siendo un aviso en vez de un archivo,
     * el redirect recarga la pagina y el spinner se va con ella, asi que ahi
     * no hace falta.
     */
    private function tagDownload(BinaryFileResponse $response, Request $request): BinaryFileResponse
    {
        $token = (string) $request->query('dl_token', '');

        // El token no autentica nada -solo empareja la respuesta con el clic
        // que la pidio-, pero igual se acota: lo que entra por la query no se
        // refleja en una cabecera sin revisarlo.
        if (preg_match('/^[A-Za-z0-9]{1,32}$/', $token) !== 1) {
            return $response;
        }

        $response->headers->setCookie(
            cookie(self::DOWNLOAD_COOKIE, $token, 1, '/', null, $request->isSecure(), false)
        );

        return $response;
    }

    /**
     * Descarga masiva de adjuntos en ZIP, cada archivo nombrado por el ID del
     * expediente. Reusa buildFilteredQuery, o sea respeta EXACTAMENTE los
     * mismos filtros que el compendio: lo que se baja en xlsx y lo que se baja
     * en zip son siempre el mismo universo. Pedido de GORE (03-sep-2026).
     *
     * Los topes viven en config/exports.php y salen del .env: en septiembre de
     * 2026 el proceso real tenia 307 adjuntos y 327 MB, o sea mas de lo que
     * suponia la primera calibracion, y conviene poder moverlos sin desplegar.
     */
    public function exportAttachments(Request $request): BinaryFileResponse|RedirectResponse
    {
        // El limite que manda es nginx (fastcgi_read_timeout 180s); esto solo
        // evita que php-fpm corte antes con su max_execution_time de 120s.
        // Medido en produccion el 09-sep-2026: 307 adjuntos / 327 MB tardaron
        // 41 s, o sea sobra tiempo.
        @set_time_limit(300);

        // Esa misma medicion dio un pico de 113,5 MB contra el memory_limit de
        // 128 MB de php-fpm: pasa hoy y revienta con un 500 apenas el proceso
        // crezca. Lo que ocupa la memoria no son los adjuntos (van por disco
        // con addFile) sino el compendio que se arma en RAM para meterlo en el
        // ZIP, que crece con TODAS las observaciones, no solo con las que
        // tienen archivo. Se sube solo para este endpoint.
        @ini_set('memory_limit', '512M');

        $maxFiles = (int) config('exports.zip_max_files');
        $maxBytes = (int) config('exports.zip_max_mb') * 1024 * 1024;

        $observations = $this->buildFilteredQuery($request)
            ->whereNotNull('attachment_path')
            ->get();

        if ($observations->isEmpty()) {
            return back()->with('warning', 'Ninguna observacion del filtro actual tiene archivos adjuntos.');
        }

        if ($observations->count() > $maxFiles) {
            return back()->with('warning', sprintf(
                'El filtro actual tiene %d adjuntos y el maximo por ZIP es %d. Acota por rango de fechas y bajalos en dos tandas.',
                $observations->count(),
                $maxFiles,
            ));
        }

        $totalBytes = (int) $observations->sum('attachment_size_bytes');
        if ($totalBytes > $maxBytes) {
            return back()->with('warning', sprintf(
                'Los adjuntos del filtro actual pesan %s y el maximo por ZIP es %s. Acota por rango de fechas y bajalos en dos tandas.',
                $this->humanBytes($totalBytes),
                $this->humanBytes($maxBytes),
            ));
        }

        $zipPath = tempnam(sys_get_temp_dir(), 'gore-adjuntos-');
        $zip = new \ZipArchive();
        abort_unless($zip->open($zipPath, \ZipArchive::OVERWRITE) === true, 500, 'No se pudo generar el ZIP.');

        // ZipArchive recien lee el contenido al hacer close(), asi que las
        // copias temporales tienen que seguir vivas hasta despues de cerrar.
        // Se copia a disco (en vez de addFromString) para no cargar todos los
        // adjuntos en memoria a la vez.
        $temps = [];
        $index = [];

        foreach ($observations as $obs) {
            $disk = $obs->attachment_disk ?: config('filesystems.default');

            try {
                $stream = Storage::disk($disk)->readStream($obs->attachment_path);
            } catch (\Throwable $e) {
                $stream = null;
            }

            // Un adjunto huerfano (borrado del bucket, path de otro disk) no
            // puede tumbar la descarga completa: se salta y queda en el log.
            if (! $stream) {
                Log::warning('ZIP de adjuntos: no se pudo leer el archivo', [
                    'observation_id' => $obs->id,
                    'public_id' => $obs->public_id,
                    'disk' => $disk,
                    'path' => $obs->attachment_path,
                ]);

                continue;
            }

            $copy = tempnam(sys_get_temp_dir(), 'gore-att-');
            $out = fopen($copy, 'wb');
            stream_copy_to_stream($stream, $out);
            fclose($out);
            fclose($stream);
            $temps[] = $copy;

            $entry = 'adjuntos/'.$obs->attachment_download_name;
            $zip->addFile($copy, $entry);

            // PDF, imagenes y ofimatica ya vienen comprimidos: pasarles deflate
            // quema CPU sin bajar el peso, y con cientos de MB eso es lo que
            // decide si el request termina antes del timeout de nginx.
            $ext = strtolower(pathinfo($entry, PATHINFO_EXTENSION));
            if (in_array($ext, (array) config('exports.zip_store_extensions'), true)) {
                $zip->setCompressionName($entry, \ZipArchive::CM_STORE);
            }

            $index[] = [
                $obs->attachment_download_name,
                $obs->public_id,
                $obs->submitted_at?->format('d/m/Y H:i'),
                $obs->consultation?->title,
                $obs->display_name,
                $obs->attachment_original_name,
            ];
        }

        if ($index === []) {
            $zip->close();
            @unlink($zipPath);

            return back()->with('warning', 'No se pudo recuperar ninguno de los adjuntos del filtro actual. Revisa el log de la aplicacion.');
        }

        // El ZIP se basta solo: los archivos, el indice que amarra cada uno a
        // su expediente y el mismo compendio del menu Exportar.
        $zip->addFromString('indice.csv', $this->attachmentsIndexCsv($index));
        $zip->addFromString(
            'compendio-observaciones.xlsx',
            Excel::raw(new ObservationsExport($this->buildFilteredQuery($request)), \Maatwebsite\Excel\Excel::XLSX),
        );
        $zip->close();

        foreach ($temps as $copy) {
            @unlink($copy);
        }

        return $this->tagDownload(
            response()
                ->download(
                    $zipPath,
                    sprintf('observaciones-adjuntos-%s.zip', now()->format('Y-m-d_His')),
                    ['Content-Type' => 'application/zip'],
                )
                ->deleteFileAfterSend(),
            $request,
        );
    }

    /**
     * Indice del ZIP: la tabla que amarra cada archivo con su expediente.
     * Lleva BOM para que Excel en Windows abra bien los nombres con tilde.
     */
    private function attachmentsIndexCsv(array $rows): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'Archivo en el ZIP',
            'ID del expediente',
            'Fecha de envio',
            'Proceso (consulta)',
            'Participante',
            'Nombre original del archivo',
        ]);
        foreach ($rows as $row) {
            fputcsv($handle, $row);
        }

        rewind($handle);
        $csv = (string) stream_get_contents($handle);
        fclose($handle);

        return "\xEF\xBB\xBF".$csv;
    }

    private function humanBytes(int $bytes): string
    {
        return round($bytes / 1024 / 1024).' MB';
    }

    /**
     * Construye la query base con todos los filtros del request. Reutilizado
     * por index() y export() para garantizar que el export refleja exactamente
     * lo que ve el funcionario en pantalla.
     */
    private function buildFilteredQuery(Request $request): Builder
    {
        $query = Observation::query()
            ->with([
                'consultation:id,slug,title,instrument_type',
                'response:id,observation_id,status,published_at',
            ])
            ->latest('submitted_at');

        if ($request->filled('consultation_id')) {
            $query->where('consultation_id', $request->input('consultation_id'));
        }
        if ($request->filled('auth_method') && in_array($request->input('auth_method'), ['claveunica', 'guest'], true)) {
            $query->where('auth_method_used', $request->input('auth_method'));
        }
        if ($request->filled('from')) {
            $query->where('submitted_at', '>=', $request->date('from')->startOfDay());
        }
        if ($request->filled('to')) {
            $query->where('submitted_at', '<=', $request->date('to')->endOfDay());
        }
        if ($request->filled('q')) {
            $term = $request->input('q');
            $query->where(function ($q) use ($term) {
                $q->where('subject', 'like', "%{$term}%")
                  ->orWhere('body', 'like', "%{$term}%")
                  ->orWhere('snapshot_national_id', 'like', "%{$term}%")
                  ->orWhere('snapshot_full_name', 'like', "%{$term}%")
                  // PJ/Org: la identidad vive en legal/trade/business, no en full_name
                  ->orWhere('snapshot_legal_name', 'like', "%{$term}%")
                  ->orWhere('snapshot_trade_name', 'like', "%{$term}%")
                  ->orWhere('snapshot_business_id', 'like', "%{$term}%")
                  ->orWhere('snapshot_email', 'like', "%{$term}%")
                  ->orWhere('public_id', $term);
            });
        }

        return $query;
    }
}
