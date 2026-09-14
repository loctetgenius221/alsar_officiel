<?php

namespace App\Domain\Payments\Models;

use App\Domain\Members\Models\Member;
use Database\Factories\MembershipPaymentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'member_id', 'tier', 'amount', 'currency', 'gateway',
    'gateway_reference', 'status', 'paid_at', 'receipt_path',
])]
class MembershipPayment extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'paid_at' => 'datetime',
            'amount' => 'integer',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    protected static function newFactory(): Factory
    {
        return MembershipPaymentFactory::new();
    }
}
