<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MockInsurerPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_reference',
        'client_request_id',
        'policy_number',
        'status',
        'scenario',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
    ];
}
