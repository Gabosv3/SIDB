<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushToken extends Model
{
    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'ultima_actividad',
    ];

    protected $casts = [
        'ultima_actividad' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
