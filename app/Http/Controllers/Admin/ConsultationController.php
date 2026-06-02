<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreConsultationRequest;
use App\Http\Requests\Admin\UpdateConsultationRequest;
use App\Models\Consultation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsultationController extends Controller
{
    public function index(Request $request): View
    {
        $query = Consultation::query()
            ->withCount(['stages', 'observations'])
            ->latest();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('type')) {
            $query->where('instrument_type', $request->input('type'));
        }
        if ($request->filled('q')) {
            $term = $request->input('q');
            $query->where(function ($q) use ($term) {
                $q->where('title', 'like', "%{$term}%")
                  ->orWhere('slug', 'like', "%{$term}%");
            });
        }

        $consultations = $query->paginate(15)->withQueryString();

        return view('admin.consultations.index', [
            'consultations' => $consultations,
            'filters' => $request->only(['status', 'type', 'q']),
        ]);
    }

    public function create(): View
    {
        return view('admin.consultations.create', [
            'consultation' => new Consultation([
                'status' => Consultation::STATUS_DRAFT,
                'auth_methods' => [Consultation::AUTH_CLAVEUNICA, Consultation::AUTH_GUEST],
            ]),
        ]);
    }

    public function store(StoreConsultationRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;

        $consultation = Consultation::create($data);

        return redirect()
            ->route('admin.consultations.show', $consultation)
            ->with('status', 'Consulta creada correctamente.');
    }

    public function show(Consultation $consultation): View
    {
        $consultation->load(['stages', 'documents', 'creator']);
        $consultation->loadCount('observations');

        return view('admin.consultations.show', [
            'consultation' => $consultation,
        ]);
    }

    public function edit(Consultation $consultation): View
    {
        return view('admin.consultations.edit', [
            'consultation' => $consultation,
        ]);
    }

    public function update(UpdateConsultationRequest $request, Consultation $consultation): RedirectResponse
    {
        $data = $request->validated();
        $data['updated_by'] = $request->user()->id;

        $consultation->update($data);

        return redirect()
            ->route('admin.consultations.show', $consultation)
            ->with('status', 'Consulta actualizada correctamente.');
    }

    public function destroy(Consultation $consultation): RedirectResponse
    {
        $consultation->delete();

        return redirect()
            ->route('admin.consultations.index')
            ->with('status', 'Consulta archivada correctamente.');
    }
}
