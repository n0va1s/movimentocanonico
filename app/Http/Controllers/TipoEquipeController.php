<?php

namespace App\Http\Controllers;

use App\Models\TipoEquipe;
use App\Models\TipoMovimento;
use Illuminate\Http\Request;

class TipoEquipeController extends Controller
{
    protected array $regras = [
        'des_grupo' => 'required|string|max:255',
        'idt_movimento' => 'required|exists:tipo_movimento,idt_movimento',
    ];

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $equipes = TipoEquipe::with('movimento')->get();

        return view('configuracoes.TipoEquipeList', compact('equipes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $movimentos = TipoMovimento::orderBy('nom_movimento')->get();

        return view('configuracoes.TipoEquipeForm', [
            'equipe' => new TipoEquipe,
            'movimentos' => $movimentos,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        $validated = $request->validate($this->regras);

        TipoEquipe::create($validated);

        return redirect()->route('equipe.index')
            ->with('success', 'Tipo de equipe adicionado com sucesso!');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $equipe = TipoEquipe::findOrFail($id);
        $movimentos = TipoMovimento::orderBy('nom_movimento')->get();

        return view('configuracoes.TipoEquipeForm', compact('equipe', 'movimentos'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate($this->regras);
        $equipe = TipoEquipe::findOrFail($id);
        $equipe->update($validated);

        return redirect()->route('equipe.index')
            ->with('success', 'Tipo de equipe atualizado com sucesso!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $equipe = TipoEquipe::findOrFail($id);

        try {
            $equipe->delete();

            return redirect()->route('equipe.index')
                ->with('success', 'Tipo de equipe excluído com sucesso!');
        } catch (\Exception $e) {
            return redirect()->route('equipe.index')
                ->with('error', 'Erro ao excluir tipo de equipe: '.$e->getMessage());
        }
    }
}
