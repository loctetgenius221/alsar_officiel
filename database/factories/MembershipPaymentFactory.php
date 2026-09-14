<?php

namespace Database\Factories;

use App\Domain\Members\Models\Member;
use App\Domain\Payments\Models\MembershipPayment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MembershipPayment>
 */
class MembershipPaymentFactory extends Factory
{
    protected $model = MembershipPayment::class;

    public function definition(): array
    {
        $tier = fake()->randomElement(array_keys(config('adhesion.tiers')));

        return [
            'member_id' => Member::factory(),
            'tier' => $tier,
            'amount' => config("adhesion.tiers.{$tier}.amount"),
            'currency' => config('adhesion.currency'),
            'gateway' => 'simulation',
            'gateway_reference' => 'SIM-'.strtoupper(Str::random(12)),
            'status' => 'pending',
            'paid_at' => null,
        ];
    }

    public function success(): static
    {
        return $this->state(fn () => [
            'status' => 'success',
            'paid_at' => now(),
        ]);
    }
}
