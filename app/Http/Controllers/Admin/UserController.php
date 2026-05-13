<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Listado de funcionarios y super-admin. Excluye explicitamente a los
     * ciudadanos (que se gestionan via su propio flujo de auth).
     */
    public function index(Request $request): View
    {
        $query = User::query()
            ->whereIn('role', [User::ROLE_FUNCTIONARY, User::ROLE_SUPER_ADMIN])
            ->orderBy('name');

        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }
        if ($request->filled('active') && $request->input('active') !== 'all') {
            $query->where('is_active', $request->input('active') === 'yes');
        }
        if ($request->filled('q')) {
            $term = $request->input('q');
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', "%{$term}%")
                  ->orWhere('last_name', 'like', "%{$term}%")
                  ->orWhere('email', 'like', "%{$term}%")
                  ->orWhere('national_id', 'like', "%{$term}%");
            });
        }

        return view('admin.users.index', [
            'users' => $query->paginate(20)->withQueryString(),
            'filters' => $request->only(['role', 'active', 'q']),
        ]);
    }

    public function create(): View
    {
        return view('admin.users.form', [
            'user' => new User(['role' => User::ROLE_FUNCTIONARY, 'is_active' => true]),
            'mode' => 'create',
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        User::create($request->validated());

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Funcionario creado correctamente.');
    }

    public function edit(User $user): View
    {
        // No permitimos editar ciudadanos desde aqui (solo staff).
        abort_if($user->isCitizen(), 404);

        return view('admin.users.form', [
            'user' => $user,
            'mode' => 'edit',
        ]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        abort_if($user->isCitizen(), 404);

        $data = $request->validated();

        // Password vacio en edicion = no se actualiza. Eloquent + el cast
        // 'hashed' se encarga del hashing cuando hay valor.
        if (empty($data['password'])) {
            unset($data['password']);
        }

        // Salvaguarda: un super-admin no puede degradarse a si mismo a
        // funcionario, podria quedar el sistema sin super-admins.
        if ($user->id === $request->user()->id && $user->isSuperAdmin() && $data['role'] !== User::ROLE_SUPER_ADMIN) {
            return back()
                ->withInput()
                ->withErrors(['role' => 'No puedes cambiar tu propio rol de super-admin.']);
        }

        $user->update($data);

        return redirect()
            ->route('admin.users.index')
            ->with('status', 'Usuario actualizado correctamente.');
    }

    /**
     * Activa o desactiva un usuario. is_active=false impide login.
     * El destroy de Laravel queda inutilizado: nunca hard-delete por auditoria.
     */
    public function toggleActive(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isCitizen(), 404);

        // Salvaguarda: nadie se desactiva a si mismo (riesgo de quedar
        // bloqueado al instante).
        if ($user->id === $request->user()->id) {
            return back()->withErrors(['active' => 'No puedes desactivar tu propia cuenta.']);
        }

        $user->update(['is_active' => ! $user->is_active]);

        $msg = $user->is_active ? 'Usuario reactivado.' : 'Usuario desactivado.';
        return back()->with('status', $msg);
    }

    /**
     * No usamos destroy: los usuarios se desactivan con toggleActive en vez
     * de borrarse, para mantener auditoria. La ruta queda como 405 si se
     * intenta.
     */
    public function destroy(User $user): RedirectResponse
    {
        abort(405, 'Los usuarios se desactivan con toggleActive, no se eliminan.');
    }
}
