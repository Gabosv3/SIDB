<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IdempotencyRequest extends Model
{
    protected $table = 'idempotency_requests';

    protected $fillable = [
        'user_id',
        'endpoint',
        'idempotency_key',
        'response_status',
        'response_body',
    ];

    protected $casts = [
        'response_status' => 'integer',
        'response_body'   => 'array',
    ];
}
