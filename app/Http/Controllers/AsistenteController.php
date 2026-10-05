<?php

namespace App\Http\Controllers;

use App\Models\AsistenteFrase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class AsistenteController extends Controller
{
    /** Guarda una frase que el asistente no entendió (o entendió con duda), para ampliar sus palabras clave. */
    public function noEntendida(Request $request): Response
    {
        $data = $request->validate([
            'frase' => 'required|string|max:300',
            'tipo' => 'required|in:sin_resultado,dudosa',
            'pantalla' => 'nullable|string|max:200',
        ]);

        AsistenteFrase::create([
            'user_id' => $request->user()?->id,
            'frase' => trim($data['frase']),
            'tipo' => $data['tipo'],
            'pantalla' => $data['pantalla'] ?? null,
        ]);

        return response()->noContent();
    }
}
