<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\Cliente;

class UniquePhone implements Rule
{
    protected $ignoreId;
    protected $field;

    public function __construct($ignoreId = null, $field = 'telefono_normal')
    {
        $this->ignoreId = $ignoreId;
        $this->field = $field;
    }

    public function passes($attribute, $value)
    {
        if (empty($value)) {
            return true;
        }

        $query = Cliente::where('activo', true)
            ->where(function ($q) use ($value) {
                $q->where('telefono_normal', $value)
                  ->orWhere('telefono_whatsapp', $value);
            });

        if ($this->ignoreId) {
            $query->where('id', '!=', $this->ignoreId);
        }

        return ! $query->exists();
    }

    public function message()
    {
        return 'El número de ' . ($this->field === 'telefono_normal' ? 'teléfono' : 'WhatsApp') . ' ya está registrado por otro cliente.';
    }
}
