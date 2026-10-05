<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsistenteFrase extends Model
{
    public const UPDATED_AT = null;

    protected $table = 'asistente_frases_no_entendidas';

    protected $fillable = ['user_id', 'frase', 'tipo', 'pantalla'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
