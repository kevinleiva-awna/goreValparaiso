<?php

namespace App\Http\Controllers\Public\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\Auth\RegisterCitizenRequest;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredCitizenController extends Controller
{
    public function create(): View
    {
        return view('public.auth.register');
    }

    public function store(RegisterCitizenRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'national_id' => $data['national_id'],
            'name' => $data['name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'password' => $data['password'], // hash via cast 'hashed'
            'role' => User::ROLE_CITIZEN,
            'is_active' => true,
            // email_verified_at se queda null hasta que confirme el correo
        ]);

        // Dispara el evento Registered, que envia el mail de verificacion
        // (gracias a que User implementa MustVerifyEmail).
        event(new Registered($user));

        Auth::login($user);

        return redirect()->route('citizen.verification.notice');
    }
}
