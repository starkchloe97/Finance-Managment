<?php

namespace Database\Factories;

use App\Models\VehicleContract;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VehicleContract>
 */
class VehicleContractFactory extends Factory
{
    protected $model = VehicleContract::class;

    public function definition(): array
    {
        $make = fake()->randomElement(['Toyota', 'Hino', 'Isuzu', 'Mercedes', 'Ford']);
        $model = fake()->randomElement(['Hiace', 'Dutro', 'NPR', 'Sprinter', 'Transit']);
        $totalVehicles = fake()->numberBetween(1, 5);
        $monthlyRental = fake()->randomFloat(2, 80000, 250000);

        $agreementDate = fake()->dateTimeBetween('-6 months', '-1 month');
        $durationMonths = fake()->numberBetween(3, 12);
        $endDate = (clone $agreementDate)->modify("+{$durationMonths} months");

        return [
            'contract_number' => 'VCON-'.str_pad((string) fake()->unique()->numberBetween(1, 999999), 6, '0', STR_PAD_LEFT),
            'agreement_date' => $agreementDate->format('Y-m-d'),
            'vendor_name' => fake()->company().' Fleet Services',
            'vendor_address' => fake()->address(),
            'customer_name' => fake()->company(),
            'customer_address' => fake()->address(),
            'customer_tin' => fake()->optional(0.6)->regexify('[0-9]{9}-[0-9]{1}'),
            'end_date' => $endDate->format('Y-m-d'),
            'duration_months' => $durationMonths,
            'service_type' => fake()->randomElement(['with_driver', 'without_driver']),
            'fuel_included' => fake()->boolean(30),
            'routine_maintenance_included' => fake()->boolean(80),
            'total_vehicles' => $totalVehicles,
            'vehicle_make' => $make,
            'vehicle_model' => $model,
            'vehicle_model_year' => (string) fake()->numberBetween(2019, 2024),
            'vehicle_type' => fake()->randomElement(['van', 'truck', 'pickup']),
            'monthly_rental_per_vehicle' => $monthlyRental,
            'total_monthly_rental' => round($monthlyRental * $totalVehicles, 2),
            'duty_hours_per_day' => fake()->numberBetween(8, 12),
            'duty_days_per_week' => fake()->numberBetween(5, 7),
            'public_holiday_rate' => fake()->randomFloat(2, 1500, 3000),
            'overtime_rate' => fake()->randomFloat(2, 800, 1500),
            'payment_terms' => fake()->randomElement(['Monthly in advance', 'Monthly in arrears', 'Quarterly']),
            'advance_months' => fake()->numberBetween(1, 3),
            'insurance_claim_period_days' => fake()->numberBetween(30, 60),
            'monthly_mileage_limit' => fake()->numberBetween(2000, 4000),
            'excess_mileage_rate' => fake()->randomFloat(2, 20, 60),
            'refrigeration_customer_responsibility' => fake()->boolean(70),
            'early_termination_months' => fake()->numberBetween(1, 6),
            'vendor_signatory_name' => fake()->name(),
            'vendor_signatory_designation' => fake()->randomElement(['Director', 'Manager', 'Owner']),
            'vendor_signatory_cnic' => fake()->regexify('[0-9]{13}'),
            'vendor_signature_date' => $agreementDate->format('Y-m-d'),
            'customer_signatory_name' => fake()->name(),
            'customer_signatory_designation' => fake()->randomElement(['Director', 'Manager', 'Procurement Head']),
            'customer_signatory_cnic' => fake()->regexify('[0-9]{13}'),
            'customer_signature_date' => $agreementDate->format('Y-m-d'),
            'witness_1_name' => fake()->name(),
            'witness_1_cnic' => fake()->regexify('[0-9]{13}'),
            'witness_2_name' => fake()->name(),
            'witness_2_cnic' => fake()->regexify('[0-9]{13}'),
            'status' => fake()->randomElement(['draft', 'active', 'active', 'active', 'completed']),
            'notes' => fake()->optional(0.3)->sentence(),
        ];
    }
}
