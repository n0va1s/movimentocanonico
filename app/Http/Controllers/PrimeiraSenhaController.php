<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PrimeiraSenhaController extends Controller
{
    public function update(Request $request)
    {
        $user = $request->user();
        abort_unless($user->senha_temporaria_expira_em, 404);
        abort_if(now()->greaterThan($user->senha_temporaria_expira_em), 403);

        $dados = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ]);

        $user->forceFill([
            'password' => Hash::make($dados['password']),
            'senha_temporaria_expira_em' => null,
        ])->save();
        $request->session()->regenerate();

        return redirect()->route('dashboard');
    }
}
