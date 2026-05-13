<?php

namespace App\Http\Requests\Admin;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', 'file',
                // 100 MB en KB. Suficiente para PDFs grandes y videos cortos.
                'max:102400',
                // Tipos relevantes para instrumentos de ordenamiento territorial:
                // PDF (memorias), imagenes (mapas escaneados), DWG/DXF (planos),
                // ZIP (paquetes), MP4 (videos explicativos), XLSX/DOCX (anexos).
                'mimes:pdf,jpg,jpeg,png,dwg,dxf,zip,mp4,xlsx,docx',
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:1000'],
            'stage_id' => ['nullable', 'integer', 'exists:consultation_stages,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        // Si se entrega stage_id, debe pertenecer a la consulta del URL.
        $validator->after(function ($validator) {
            $stageId = $this->input('stage_id');
            $consultation = $this->route('consultation');
            if ($stageId && $consultation && ! $consultation->stages()->where('id', $stageId)->exists()) {
                $validator->errors()->add('stage_id', 'La etapa seleccionada no pertenece a esta consulta.');
            }
        });
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Debes seleccionar un archivo.',
            'file.file' => 'El archivo entregado no es valido.',
            'file.max' => 'El archivo supera el tamano maximo permitido (100 MB).',
            'file.mimes' => 'Formato no permitido. Acepta: PDF, JPG, PNG, DWG, DXF, ZIP, MP4, XLSX, DOCX.',
            'title.required' => 'El titulo del documento es obligatorio.',
        ];
    }
}
