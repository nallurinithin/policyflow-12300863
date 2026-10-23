<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProviderEvent extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'event_id',
        'policy_request_id',
        'event_type',
        'provider_reference',
        'policy_number',
        'payload',
        'processed',
        'processed_at',
    ];

    protected $casts = [
        'processed'    => 'boolean',
        'processed_at' => 'datetime',
        'created_at'   => 'datetime',
    ];

    public function policyRequest(): BelongsTo
    {
        return $this->belongsTo(PolicyRequest::class);
    }
}
