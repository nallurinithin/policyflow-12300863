<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class PolicyRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'client_request_id',
        'agent_id',
        'customer_name',
        'date_of_birth',
        'destination',
        'trip_start',
        'trip_end',
        'plan_code',
        'sum_insured',
        'travel_days',
        'calculated_premium',
        'status',
        'policy_number',
        'provider_reference',
    ];

    protected $casts = [
        'date_of_birth'     => 'date',
        'trip_start'        => 'date',
        'trip_end'          => 'date',
        'sum_insured'       => 'decimal:2',
        'calculated_premium'=> 'decimal:2',
        'travel_days'       => 'integer',
    ];

    /**
     * Auto-generate UUID on creation.
     */
    protected static function booted(): void
    {
        static::creating(function (PolicyRequest $request) {
            if (empty($request->uuid)) {
                $request->uuid = (string) Str::uuid();
            }
        });
    }

    // ─── Relationships ───────────────────────────────────────────

    public function issuanceAttempts(): HasMany
    {
        return $this->hasMany(IssuanceAttempt::class)->orderBy('attempt_number');
    }

    public function providerEvents(): HasMany
    {
        return $this->hasMany(ProviderEvent::class)->orderByDesc('created_at');
    }

    public function stateHistory(): HasMany
    {
        return $this->hasMany(PolicyStateHistory::class)->orderBy('created_at');
    }

    // ─── Helpers ─────────────────────────────────────────────────

    /**
     * Get the latest attempt number for this request.
     */
    public function getLatestAttemptNumber(): int
    {
        return (int) $this->issuanceAttempts()->max('attempt_number');
    }

    /**
     * Check if this request has reached a terminal state.
     */
    public function isTerminal(): bool
    {
        return $this->status === 'ISSUED';
    }

    /**
     * Check if this request can be submitted to the insurer.
     */
    public function canSubmit(): bool
    {
        return in_array($this->status, ['VALIDATED', 'FAILED', 'NEEDS_RECONCILIATION']);
    }
}
