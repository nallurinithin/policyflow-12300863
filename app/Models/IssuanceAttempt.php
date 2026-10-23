<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IssuanceAttempt extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'policy_request_id',
        'attempt_number',
        'scenario',
        'provider_reference',
        'response_status',
        'response_payload',
        'error_details',
    ];

    protected $casts = [
        'attempt_number' => 'integer',
        'created_at'     => 'datetime',
    ];

    public function policyRequest(): BelongsTo
    {
        return $this->belongsTo(PolicyRequest::class);
    }
}
