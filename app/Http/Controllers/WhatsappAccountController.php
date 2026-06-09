<?php

namespace App\Http\Controllers;

use App\Models\WhatsappAccount;
use App\Services\WhatsAppCloudApiService;
use Illuminate\Http\Request;

class WhatsappAccountController extends Controller
{
    public function __construct(protected WhatsAppCloudApiService $whatsapp) {}

    public function index($tenant)
    {
        $cuentas = WhatsappAccount::with('user')->orderByDesc('is_default')->orderBy('nombre')->get();
        return view('whatsapp.accounts.index', compact('tenant', 'cuentas'));
    }

    public function create($tenant)
    {
        $usuarios = \App\Models\User::orderBy('name')->get();
        return view('whatsapp.accounts.create', compact('tenant', 'usuarios'));
    }

    public function store(Request $request, $tenant)
    {
        $data = $request->validate([
            'nombre'               => 'required|string|max:100',
            'tipo'                 => 'required|in:vendedor,cobrador,empresa',
            'user_id'              => 'nullable|exists:users,id',
            'phone_number_id'      => 'nullable|string|max:100',
            'display_phone_number' => 'nullable|string|max:30',
            'verified_name'        => 'nullable|string|max:100',
            'waba_id'              => 'nullable|string|max:100',
            'meta_business_id'     => 'nullable|string|max:100',
            'access_token'         => 'nullable|string',
            'is_default'           => 'boolean',
        ]);

        // Solo puede haber un default
        if (! empty($data['is_default'])) {
            WhatsappAccount::where('is_default', true)->update(['is_default' => false]);
        }

        WhatsappAccount::create($data);

        return redirect()->route('whatsapp.accounts.index', $tenant)
            ->with('success', 'Cuenta registrada correctamente.');
    }

    public function edit($tenant, WhatsappAccount $account)
    {
        $usuarios = \App\Models\User::orderBy('name')->get();
        return view('whatsapp.accounts.edit', compact('tenant', 'account', 'usuarios'));
    }

    public function update(Request $request, $tenant, WhatsappAccount $account)
    {
        $data = $request->validate([
            'nombre'               => 'required|string|max:100',
            'tipo'                 => 'required|in:vendedor,cobrador,empresa',
            'user_id'              => 'nullable|exists:users,id',
            'phone_number_id'      => 'nullable|string|max:100',
            'display_phone_number' => 'nullable|string|max:30',
            'verified_name'        => 'nullable|string|max:100',
            'waba_id'              => 'nullable|string|max:100',
            'meta_business_id'     => 'nullable|string|max:100',
            'access_token'         => 'nullable|string',
            'is_default'           => 'boolean',
            'estado'               => 'required|in:activo,inactivo,suspendido',
        ]);

        // Si no se envía nuevo token, no sobreescribir
        if (empty($data['access_token'])) {
            unset($data['access_token']);
        }

        if (! empty($data['is_default'])) {
            WhatsappAccount::where('id', '!=', $account->id)
                ->where('is_default', true)
                ->update(['is_default' => false]);
        }

        $account->update($data);

        return redirect()->route('whatsapp.accounts.index', $tenant)
            ->with('success', 'Cuenta actualizada correctamente.');
    }

    public function destroy($tenant, WhatsappAccount $account)
    {
        $account->delete();
        return redirect()->route('whatsapp.accounts.index', $tenant)
            ->with('success', 'Cuenta eliminada.');
    }

    public function setDefault($tenant, WhatsappAccount $account)
    {
        WhatsappAccount::where('is_default', true)->update(['is_default' => false]);
        $account->update(['is_default' => true]);

        return back()->with('success', "'{$account->nombre}' establecida como cuenta principal.");
    }

    public function testSend(Request $request, $tenant, WhatsappAccount $account)
    {
        $numero = $request->input('numero', $account->display_phone_number);

        if (! $numero) {
            return response()->json(['success' => false, 'error' => 'Ingresa un número de prueba.']);
        }

        // En modo prueba Meta solo acepta templates aprobados (hello_world)
        $resultado = $this->whatsapp->sendTemplateMessage(
            $account,
            $numero,
            'hello_world',
            'en_US'
        );

        return response()->json($resultado);
    }
}
