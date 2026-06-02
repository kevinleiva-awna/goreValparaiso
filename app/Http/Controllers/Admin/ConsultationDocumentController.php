<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreConsultationDocumentRequest;
use App\Models\Consultation;
use App\Models\ConsultationDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ConsultationDocumentController extends Controller
{
    public function store(StoreConsultationDocumentRequest $request, Consultation $consultation): RedirectResponse
    {
        $data = $request->validated();
        $file = $request->file('file');
        $disk = config('filesystems.default');

        try {
            $document = DB::transaction(function () use ($data, $file, $consultation, $request, $disk) {
                $groupId = (string) Str::uuid();
                $relativePath = $this->storeFile($file, $consultation->id, $groupId, 1, $disk);

                return ConsultationDocument::create([
                    'consultation_id' => $consultation->id,
                    'stage_id' => $data['stage_id'] ?? null,
                    'title' => $data['title'],
                    'description' => $data['description'] ?? null,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size_bytes' => $file->getSize(),
                    'storage_path' => $relativePath,
                    'storage_disk' => $disk,
                    'file_group_id' => $groupId,
                    'version' => 1,
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                    'uploaded_by' => $request->user()->id,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Upload de antecedente fallo', [
                'exception' => $e,
                'user_id' => $request->user()->id,
                'consultation_id' => $consultation->id,
                'disk' => $disk,
                'size' => $file?->getSize(),
                'mime' => $file?->getClientMimeType(),
            ]);
            return back()
                ->withErrors(['file' => 'No pudimos subir el documento. Revisa los logs del servidor o intentalo de nuevo.'])
                ->withInput();
        }

        return back()->with('status', "Documento &quot;{$document->title}&quot; subido correctamente.");
    }

    public function destroy(Consultation $consultation, ConsultationDocument $document): RedirectResponse
    {
        // Soft delete: la fila queda con deleted_at poblado, el archivo en
        // disco se conserva (politica de expedientes inalterables del brief).
        $document->delete();

        return back()->with('status', 'Documento archivado.');
    }

    /**
     * Stream del archivo con Content-Disposition para forzar descarga
     * con el nombre original. Esta accion respeta el rol del middleware,
     * por lo que no se sirve el archivo publicamente desde S3/storage.
     * Usa el disk con el que se subio cada archivo (puede variar por fila
     * tras migrar de 'local' a 's3').
     */
    public function download(Consultation $consultation, ConsultationDocument $document): StreamedResponse
    {
        $disk = $document->storage_disk ?: config('filesystems.default');
        abort_unless(Storage::disk($disk)->exists($document->storage_path), 404);

        return Storage::disk($disk)->download(
            $document->storage_path,
            $document->original_filename,
            ['Content-Type' => $document->mime_type]
        );
    }

    /**
     * Reemplazo versionado: archiva la version actual (soft delete) y crea una
     * fila nueva con file_group_id compartido + version incrementada. Permite
     * reconstruir el historial completo de un documento.
     */
    public function replace(
        StoreConsultationDocumentRequest $request,
        Consultation $consultation,
        ConsultationDocument $document
    ): RedirectResponse {
        $file = $request->file('file');
        $disk = config('filesystems.default');

        try {
            DB::transaction(function () use ($document, $file, $request, $disk) {
                $nextVersion = $document->version + 1;
                $relativePath = $this->storeFile($file, $document->consultation_id, $document->file_group_id, $nextVersion, $disk);

                // Archivamos la version vigente (no la borramos del disco).
                $document->delete();

                ConsultationDocument::create([
                    'consultation_id' => $document->consultation_id,
                    'stage_id' => $document->stage_id,
                    'title' => $document->title,
                    'description' => $document->description,
                    'original_filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size_bytes' => $file->getSize(),
                    'storage_path' => $relativePath,
                    'storage_disk' => $disk,
                    'file_group_id' => $document->file_group_id,
                    'version' => $nextVersion,
                    'sha256' => hash_file('sha256', $file->getRealPath()),
                    'uploaded_by' => $request->user()->id,
                ]);
            });
        } catch (\Throwable $e) {
            Log::error('Replace de antecedente fallo', [
                'exception' => $e,
                'user_id' => $request->user()->id,
                'document_id' => $document->id,
                'consultation_id' => $document->consultation_id,
                'disk' => $disk,
            ]);
            return back()
                ->withErrors(['file' => 'No pudimos subir la nueva version. La version anterior sigue vigente.'])
                ->withInput();
        }

        return back()->with('status', 'Documento reemplazado. Nueva version archivada.');
    }

    /**
     * Estructura: consultations/{consultation_id}/{file_group_id}/v{version}/{filename}
     * Esta jerarquia hace facil encontrar todas las versiones de un documento.
     */
    private function storeFile(UploadedFile $file, int $consultationId, string $groupId, int $version, string $disk): string
    {
        $directory = "consultations/{$consultationId}/{$groupId}/v{$version}";
        return $file->storeAs($directory, $file->getClientOriginalName(), $disk);
    }
}
