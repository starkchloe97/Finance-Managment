<?php

namespace Database\Factories;

use App\Models\LoanBorrower;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LoanBorrower>
 */
class LoanBorrowerFactory extends Factory
{
    protected $model = LoanBorrower::class;

    public function definition(): array
    {
        return [
            'borrower_code' => 'BRW-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'name' => fake()->company().' '.fake()->randomElement(['Logistics', 'Transport', 'Cargo', 'Freight']),
            'email' => fake()->unique()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'address' => fake()->address(),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
