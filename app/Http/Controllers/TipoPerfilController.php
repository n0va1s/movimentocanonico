<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\TipoMovimento;
use App\Traits\LogContext;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TipoPerfilController extends Controller
{
    use LogContext;

    public function index(Request $request)
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);

        Log::info('Requisição de listagem de perfis de usuário (RoleList) iniciada', $context);

        $query = User::query()->with('movimento');

        if ($request->filled('nome')) {
            $nome = $request->input('nome');
            $query->where('name', 'like', "%{$nome}%");
        }

        if ($request->filled('perfil') && $request->input('perfil') !== 'all') {
            $query->where('role', $request->input('perfil'));
        }

        if ($request->filled('movimento') && $request->input('movimento') !== 'all') {
            $mov = $request->input('movimento');
            if ($mov === 'none') {
                $query->whereNull('idt_movimento');
            } else {
                $query->where('idt_movimento', $mov);
            }
        }

        $perfis = $query->orderBy('name', 'asc')->paginate(10)->withQueryString();
        $movements = TipoMovimento::orderBy('nom_movimento', 'asc')->get();

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Listagem de perfis de usuário concluída com sucesso', array_merge($context, [
            'total_perfis' => $perfis->total(),
            'duration_ms' => $duration,
        ]));

        return view('configuracoes.TipoPerfilList', compact('perfis', 'movements'));
    }

    public function change(Request $request)
    {
        $start = microtime(true);
        $context = $this->getLogContext($request);

        $rolesData = $request->input('role', []);
        $movementsData = $request->input('movimento', []);

        Log::info('Tentativa de atualização de perfis (roles) e movimentos de usuário', array_merge($context, [
            'total_perfis_enviados' => count($rolesData),
            'total_movimentos_enviados' => count($movementsData),
        ]));

        $request->validate([
            'role' => 'required|array',
            'role.*' => 'in:admin,coord,user,espec,sales',
            'movimento' => 'nullable|array',
            'movimento.*' => 'nullable|exists:tipo_movimento,idt_movimento',
        ]);

        foreach ($rolesData as $userId => $role) {
            $movementId = !empty($movementsData[$userId]) ? $movementsData[$userId] : null;
            User::where('id', $userId)->update([
                'role' => $role,
                'idt_movimento' => $movementId,
            ]);
        }

        $duration = round((microtime(true) - $start) * 1000, 2);
        Log::notice('Perfis de usuário atualizados com sucesso', array_merge($context, [
            'perfis_atualizados' => count($rolesData),
            'duration_ms' => $duration,
        ]));

        return back()->with('success', 'Perfis atualizados com sucesso!');
    }
}
