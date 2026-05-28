<?php

namespace App\Exports;

use App\Models\Observation;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;

/**
 * Exportacion de observaciones a CSV/Excel. Acepta el mismo query que
 * el index del controller; chunkea via FromQuery para soportar volumenes
 * grandes sin reventar memoria.
 */
class ObservationsExport implements FromQuery, WithHeadings, WithMapping, WithTitle
{
    public function __construct(private Builder $query)
    {
    }

    public function query(): Builder
    {
        // Eager-load para evitar N+1 al iterar
        return $this->query
            ->with(['consultation', 'stage'])
            ->orderBy('id');
    }

    public function headings(): array
    {
        return [
            'ID',
            'Codigo publico',
            'Fecha de envio',
            'Proceso (consulta)',
            'Slug del proceso',
            'Tipo de instrumento',
            'Etapa',
            'Asunto',
            'Categoria',
            'Cuerpo de la observacion',
            'Metodo de identificacion',
            'RUT (snapshot)',
            'Nombre completo (snapshot)',
            'Correo (snapshot)',
            'IP de origen',
            'Navegador (user-agent)',
        ];
    }

    public function map($obs): array
    {
        return [
            $obs->id,
            $obs->public_id,
            $obs->submitted_at?->format('d/m/Y H:i'),
            $obs->consultation?->title,
            $obs->consultation?->slug,
            $obs->consultation?->instrument_type,
            $obs->stage?->name,
            $obs->subject,
            $obs->category,
            $obs->body,
            match ($obs->auth_method_used) {
                'claveunica' => 'ClaveUnica',
                'guest' => 'Sin registro',
                default => 'Registro manual',
            },
            $obs->snapshot_national_id,
            $obs->snapshot_full_name,
            $obs->snapshot_email,
            $obs->ip_address,
            $obs->user_agent,
        ];
    }

    public function title(): string
    {
        return 'Observaciones';
    }
}
