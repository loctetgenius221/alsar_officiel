<?php

namespace Database\Factories;

use App\Domain\Members\Models\Member;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Member>
 */
class MemberFactory extends Factory
{
    protected $model = Member::class;

    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'birth_date' => fake()->date('Y-m-d', '-18 years'),
            'gender' => fake()->randomElement(['homme', 'femme']),
            'phone' => '+221 7'.fake()->numerify('# ## ## ##'),
            'email' => fake()->unique()->safeEmail(),
            'region' => fake()->randomElement(config('adhesion.regions')),
            'commune' => fake()->city(),
            'address' => null,
            'profession' => fake()->jobTitle(),
            'photo_path' => null,
            'id_document_number' => null,
            'section' => fake()->randomElement(['standard', 'jeunes', 'femmes']),
            'referral_source' => fake()->randomElement(['Réseaux sociaux', 'Bouche à oreille', 'Meeting local', null]),
            'status' => 'pending',
            'consent_at' => now(),
            'joined_at' => null,
        ];
    }

    public function active(): static
    {
        return $this->state(fn () => [
            'status' => 'active',
            'joined_at' => now(),
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'status' => 'inactive',
            'joined_at' => now(),
        ]);
    }
}
