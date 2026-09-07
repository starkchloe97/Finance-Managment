<?php

namespace Database\Factories;

use App\Models\ContractVehicle;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ContractVehicle>
 */
class ContractVehicleFactory extends Factory
{
    protected $model = ContractVehicle::class;

    public function definition(): array
    {
        $make = fake()->randomElement(['Toyota', 'Hino', 'Isuzu', 'Mercedes', 'Ford']);
        $model = fake()->randomElement(['Hiace', 'Dutro', 'NPR', 'Sprinter', 'Transit']);

        return [
            'vehicle_contract_id' => null, // set by seeder
            'vehicle_number' => fake()->unique()->regexify('[A-Z]{3}-[0-9]{3,4}'),
            'make' => $make,
            'model' => $model,
            'model_year' => (string) fake()->numberBetween(2019, 2024),
            'vehicle_type' => fake()->randomElement(['van', 'truck', 'pickup']),
            'monthly_rental' => fake()->randomFloat(2, 80000, 250000),
            'duty_hours_per_day' => fake()->numberBetween(8, 12),
            'duty_days_per_week' => fake()->numberBetween(5, 7),
            'public_holiday_rate' => fake()->randomFloat(2, 1500, 3000),
            'overtime_rate' => fake()->randomFloat(2, 800, 1500),
            'monthly_mileage_limit' => fake()->numberBetween(2000, 4000),
            'excess_mileage_rate' => fake()->randomFloat(2, 20, 60),
            'status' => fake()->randomElement(['active', 'active', 'active', 'inactive', 'completed']),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
