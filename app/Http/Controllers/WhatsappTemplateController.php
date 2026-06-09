<?php

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;

class WhatsappTemplateController extends Controller
{
    public function index($tenant)
    {
        $plantillas = WhatsappTemplate::with('cuenta')->orderBy('name')->get();
        return view('whatsapp.templates.index', compact('tenant', 'plantillas'));
    }

    public function create($tenant)
    {
        $cuentas = WhatsappAccount::where('estado', 'activo')->orderBy('nombre')->get();
        return view('whatsapp.templates.create', compact('tenant', 'cuentas'));
    }

    public function store(Request $request, $tenant)
    {
        $data = $request->validate([
            'whatsapp_account_id' => 'nullable|exists:whatsapp_accounts,id',
            'name'                => 'required|string|max:100|regex:/^[a-z0-9_]+$/',
            'language'            => 'required|string|max:10',
            'category'            => 'required|in:UTILITY,MARKETING,AUTHENTICATION',
            'body'                => 'required|string',
        ]);

        WhatsappTemplate::create($data);

        return redirect()->route('whatsapp.templates.index', $tenant)
            ->with('success', 'Plantilla creada correctamente.');
    }

    public function edit($tenant, WhatsappTemplate $template)
    {
        $cuentas = WhatsappAccount::where('estado', 'activo')->orderBy('nombre')->get();
        return view('whatsapp.templates.edit', compact('tenant', 'template', 'cuentas'));
    }

    public function update(Request $request, $tenant, WhatsappTemplate $template)
    {
        $data = $request->validate([
            'whatsapp_account_id' => 'nullable|exists:whatsapp_accounts,id',
            'name'                => 'required|string|max:100|regex:/^[a-z0-9_]+$/',
            'language'            => 'required|string|max:10',
            'category'            => 'required|in:UTILITY,MARKETING,AUTHENTICATION',
            'body'                => 'required|string',
            'status'              => 'required|in:pending,approved,rejected',
        ]);

        $template->update($data);

        return redirect()->route('whatsapp.templates.index', $tenant)
            ->with('success', 'Plantilla actualizada.');
    }

    public function destroy($tenant, WhatsappTemplate $template)
    {
        $template->delete();
        return redirect()->route('whatsapp.templates.index', $tenant)
            ->with('success', 'Plantilla eliminada.');
    }
}
