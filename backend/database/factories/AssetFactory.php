<?php

namespace Database\Factories;

use App\Enums\AssetStatus;
use App\Enums\AssetType;
use App\Models\Asset;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function definition(): array
    {
        $make = fake()->randomElement(['Toyota', 'Hino', 'Isuzu', 'Mercedes', 'Ford', 'Mitsubishi']);
        $model = fake()->randomElement(['Cargo', 'Dynas', 'Fighter', 'Actros', 'Ranger', 'Canter']);

        return [
            'asset_code' => 'AST-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'asset_type' => AssetType::Vehicle,
            'name' => "{$make} {$model}",
            'make' => $make,
            'model' => $model,
            'model_year' => fake()->numberBetween(2018, 2024),
            'registration_number' => fake()->unique()->regexify('[A-Z]{3}-[0-9]{3,4}'),
            'vin' => fake()->unique()->regexify('[A-HJ-NPR-Z0-9]{17}'),
            'engine_number' => fake()->unique()->regexify('[A-Z0-9]{10,12}'),
            'vehicle_type' => fake()->randomElement(['truck', 'van', 'pickup', 'trailer']),
            'color' => fake()->safeColorName(),
            'purchase_date' => fake()->dateTimeBetween('-5 years', '-1 year')->format('Y-m-d'),
            'purchase_price' => fake()->randomFloat(2, 1500000, 8000000),
            'current_value' => fake()->randomFloat(2, 1000000, 7000000),
            'status' => fake()->randomElement(AssetStatus::cases()),
            'notes' => fake()->optional(0.3)->sentence(),
            'created_by' => null,
        ];
    }
}
