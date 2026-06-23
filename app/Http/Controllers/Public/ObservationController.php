<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreObservationRequest;
use App\Mail\ObservationSubmitted;
use App\Models\Consultation;
use App\Models\Observation;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class ObservationController extends Controller
{
    public function store(StoreObservationRequest $request, Consultation $consultation): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $disk = config('filesystems.default');

        // Identidad resuelta UNA sola vez: todas las observaciones de este envio
        // comparten el mismo snapshot de identidad y metodo de autenticacion.
        //  - Autenticado por ClaveUnica: actor SIEMPRE 'natural' (ClaveUnica
        //    solo identifica personas naturales chilenas). El RUT y nombre
        //    salen del modelo User; el resto del snapshot se deja nulo.
        //  - Guest natural: nombre, RUT/pasaporte, opcionales (telefono,
        //    comuna, edad) auto-declarados.
        //  - Guest PJ u Org: razon social y RUT de la entidad, opcionales
        //    (nombre fantasia, telefono, direccion).
        //
        // Invariante reforzado en Observation::creating(): PJ/Org NUNCA tienen
        // user_id. El FormRequest ya valido que actor_type sea coherente con
        // los campos que llegaron.
        if ($user) {
            $identityBranch = [
                'user_id' => $user->id,
                'auth_method_used' => session('auth_method', Observation::AUTH_CLAVEUNICA),
                'snapshot_actor_type' => Observation::ACTOR_NATURAL,
                'snapshot_id_type' => Observation::ID_TYPE_RUT,
                'snapshot_national_id' => $user->national_id,
                'snapshot_full_name' => trim($user->name . ' ' . $user->last_name),
                'snapshot_email' => $user->email,
            ];
        } else {
            $actorType = $data['actor_type'];
            $base = [
                'user_id' => null,
                'auth_method_used' => Observation::AUTH_GUEST,
                'snapshot_actor_type' => $actorType,
                'snapshot_email' => $data['guest_email'],
                'snapshot_phone' => $data['guest_phone'] ?? null,
            ];

            $identityBranch = match ($actorType) {
                Observation::ACTOR_NATURAL => $base + [
                    'snapshot_id_type' => $data['guest_id_type'],
                    'snapshot_national_id' => $data['guest_national_id'],
                    'snapshot_full_name' => $data['guest_name'],
                    'snapshot_comuna' => $data['guest_comuna'] ?? null,
                    'snapshot_age' => $data['guest_age'] ?? null,
                ],
                Observation::ACTOR_PJ,
                Observation::ACTOR_ORG => $base + [
                    'snapshot_legal_name' => $data['guest_legal_name'],
                    'snapshot_trade_name' => $data['guest_trade_name'] ?? null,
                    'snapshot_business_id' => $data['guest_business_id'],
                    'snapshot_address' => $data['guest_address'] ?? null,
                ],
            };
        }

        // 1) Subimos los adjuntos (IO) ANTES de tocar la BD, recolectando los
        //    metadatos por indice y los paths para limpiar si algo falla luego.
        //    Nombre aleatorio en el storage; conservamos el original como metadato.
        $attachmentMetaByIndex = [];
        $storedPaths = [];
        foreach ($data['observations'] as $i => $item) {
            $file = $request->file("observations.$i.attachment");
            if (! $file) {
                $attachmentMetaByIndex[$i] = [];
                continue;
            }
            try {
                $stored = $file->store('observations/' . $consultation->id, $disk);
            } catch (\Throwable $e) {
                foreach ($storedPaths as [$d, $p]) {
                    Storage::disk($d)->delete($p);
                }
                Log::error('Upload de adjunto fallo', [
                    'exception' => $e,
                    'user_id' => $user?->id,
                    'consultation_id' => $consultation->id,
                    'disk' => $disk,
                    'index' => $i,
                ]);
                return back()
                    ->withErrors(['observations' => 'No pudimos guardar uno de tus archivos. Intentalo de nuevo o envia la observacion sin adjunto.'])
                    ->withInput();
            }
            $storedPaths[] = [$disk, $stored];
            $attachmentMetaByIndex[$i] = [
                'attachment_path' => $stored,
                'attachment_disk' => $disk,
                'attachment_original_name' => Str::limit($file->getClientOriginalName(), 250, ''),
                'attachment_mime_type' => $file->getMimeType(),
                'attachment_size_bytes' => $file->getSize(),
            ];
        }

        // 2) Creamos las N observaciones en una transaccion, compartiendo
        //    identidad y un mismo submission_group_id. Si algo falla, rollback
        //    de las filas y limpieza de los archivos ya subidos.
        $groupId = (string) Str::uuid();
        try {
            $observations = DB::transaction(function () use ($data, $consultation, $groupId, $identityBranch, $attachmentMetaByIndex, $request) {
                $created = new Collection();
                foreach ($data['observations'] as $i => $item) {
                    $created->push(Observation::create([
                        'consultation_id' => $consultation->id,
                        'submission_group_id' => $groupId,

                        'subject' => $item['subject'] ?? null,
                        'body' => $item['body'],
                        'category' => $item['category'] ?? null,

                        // Trazabilidad operativa
                        'ip_address' => $request->ip(),
                        'user_agent' => substr((string) $request->userAgent(), 0, 500),

                        ...$identityBranch,
                        ...$attachmentMetaByIndex[$i],
                    ]));
                }
                return $created;
            });
        } catch (\Throwable $e) {
            foreach ($storedPaths as [$d, $p]) {
                Storage::disk($d)->delete($p);
            }
            Log::error('Creacion de observaciones fallo', [
                'exception' => $e,
                'user_id' => $user?->id,
                'consultation_id' => $consultation->id,
            ]);
            return back()
                ->withErrors(['observations' => 'No pudimos registrar tus observaciones. Intentalo de nuevo.'])
                ->withInput();
        }

        // Un solo correo de confirmacion que resume todas las observaciones.
        $emailTo = $user ? $user->email : $data['guest_email'];
        Mail::to($emailTo)->queue(new ObservationSubmitted($observations));

        return redirect()->route('public.observations.success', [
            'slug' => $consultation->slug,
            'publicId' => $observations->first()->public_id,
        ]);
    }

    public function success(string $slug, string $publicId): View
    {
        $first = Observation::query()
            ->where('public_id', $publicId)
            ->whereHas('consultation', fn ($q) => $q->where('slug', $slug))
            ->with('consultation')
            ->firstOrFail();

        // Observacion con usuario: solo el autor logueado puede verla
        // (evita filtrar el body via URL adivinada).
        // Observacion guest: cualquiera con el UUID del publicId puede verla;
        // el UUID es secreto-suficiente para la pagina de confirmacion (no
        // expone datos sensibles mas alla del propio body) y el ciudadano
        // accede via redirect post-submit o por el link del mail.
        if ($first->user_id !== null) {
            abort_unless(auth()->id() === $first->user_id, 404);
        }

        // Mostramos todas las observaciones del mismo envio (o solo esta si la
        // fila no tiene grupo, p.ej. datos historicos previos al multi-envio).
        $observations = $first->submission_group_id
            ? Observation::query()
                ->where('submission_group_id', $first->submission_group_id)
                ->where('consultation_id', $first->consultation_id)
                ->orderBy('id')
                ->get()
            : collect([$first]);

        return view('public.observations.success', [
            'observations' => $observations,
            'consultation' => $first->consultation,
        ]);
    }
}
