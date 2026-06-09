<?php

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use App\Models\WhatsappAutomation;
use App\Models\WhatsappAutomationLog;
use App\Models\WhatsappTemplate;
use Illuminate\Http\Request;

class WhatsappAutomationController extends Controller
{
    public function index($tenant)
    {
        $automatizaciones = WhatsappAutomation::with(['cuenta', 'plantilla'])
            ->orderBy('tipo')
            ->get();

        $logs = WhatsappAutomationLog::with(['cliente', 'automatizacion'])
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        return view('whatsapp.automations.index', compact('tenant', 'automatizaciones', 'logs'));
    }

    public function create($tenant)
    {
        $cuentas   = WhatsappAccount::where('estado', 'activo')->orderBy('nombre')->get();
        $plantillas = WhatsappTemplate::where('status', 'approved')->orderBy('name')->get();
        $tipos     = WhatsappAutomation::tiposLabels();
        return view('whatsapp.automations.create', compact('tenant', 'cuentas', 'plantillas', 'tipos'));
    }

    public function store(Request $request, $tenant)
    {
        $data = $request->validate([
            'nombre'              => 'required|string|max:100',
            'tipo'                => 'required|in:' . implode(',', array_keys(WhatsappAutomation::tiposLabels())),
            'whatsapp_account_id' => 'nullable|exists:whatsapp_accounts,id',
            'template_id'         => 'nullable|exists:whatsapp_templates,id',
            'dias_antes'          => 'nullable|integer|min:0|max:30',
            'dias_despues'        => 'nullable|integer|min:0|max:90',
            'hora_envio'          => 'required|date_format:H:i',
            'activo'              => 'boolean',
        ]);

        WhatsappAutomation::create($data);

        return redirect()->route('whatsapp.automations.index', $tenant)
            ->with('success', 'Automatización creada.');
    }

    public function edit($tenant, WhatsappAutomation $automation)
    {
        $cuentas    = WhatsappAccount::where('estado', 'activo')->orderBy('nombre')->get();
        $plantillas = WhatsappTemplate::where('status', 'approved')->orderBy('name')->get();
        $tipos      = WhatsappAutomation::tiposLabels();
        return view('whatsapp.automations.edit', compact('tenant', 'automation', 'cuentas', 'plantillas', 'tipos'));
    }

    public function update(Request $request, $tenant, WhatsappAutomation $automation)
    {
        $data = $request->validate([
            'nombre'              => 'required|string|max:100',
            'tipo'                => 'required|in:' . implode(',', array_keys(WhatsappAutomation::tiposLabels())),
            'whatsapp_account_id' => 'nullable|exists:whatsapp_accounts,id',
            'template_id'         => 'nullable|exists:whatsapp_templates,id',
            'dias_antes'          => 'nullable|integer|min:0|max:30',
            'dias_despues'        => 'nullable|integer|min:0|max:90',
            'hora_envio'          => 'required|date_format:H:i',
            'activo'              => 'boolean',
        ]);

        $automation->update($data);

        return redirect()->route('whatsapp.automations.index', $tenant)
            ->with('success', 'Automatización actualizada.');
    }

    public function destroy($tenant, WhatsappAutomation $automation)
    {
        $automation->delete();
        return redirect()->route('whatsapp.automations.index', $tenant)
            ->with('success', 'Automatización eliminada.');
    }

    public function toggle($tenant, WhatsappAutomation $automation)
    {
        $automation->update(['activo' => ! $automation->activo]);

        return response()->json([
            'activo'  => $automation->activo,
            'message' => $automation->activo ? 'Activada' : 'Desactivada',
        ]);
    }
}
