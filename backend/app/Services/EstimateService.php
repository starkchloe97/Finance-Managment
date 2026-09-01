<?php

namespace App\Services;

use App\Helpers\NumberGenerator;
use App\Models\Estimate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Asset;

/**
 * An estimate captures both sides of a job in one step: what each line costs
 * us and what we charge the customer for it. Profit is therefore known as soon
 * as the quote is built, rather than being worked out later by comparing
 * separate documents.
 *
 * Only the sell figures are ever shown to the customer; cost and profit are
 * internal.
 */
class EstimateService
{
    public function create(array $data)
{
    return DB::transaction(function () use ($data) {

        $lines = array_map($this->buildLine(...), $data['items']);

        $estimate = Estimate::create([
            'code' => NumberGenerator::generate('EST', Estimate::class),
            'customer_id' => $data['customer_id'],
            'estimate_date' => $data['estimate_date'],
            'valid_until' => $data['valid_until'] ?? null,
            'pickup' => $data['pickup'],
            'destination' => $data['destination'],
            'service_type' => $data['service_type'],
            'estimated_cost' => array_sum(array_column($lines, 'cost_total')),
            'estimated_sell' => array_sum(array_column($lines, 'sell_total')),
            'estimated_profit' => array_sum(array_column($lines, 'profit')),
            'status' => 'draft',
            'remarks' => $data['remarks'] ?? null,
        ]);

        foreach ($data['items'] as $index => $itemData) {
            $line = $estimate->items()->create($lines[$index]);

            if (
                strtolower($itemData['category']) === 'vehicle' &&
                !empty($itemData['vehicles'])
            ) {
                $this->saveVehicleRequirements(
                    $line,
                    $itemData['vehicles']
                );
            }
        }

        return $estimate->load(
            'customer',
            'items.vehicles.asset'
        );
    });
}

private function saveVehicleRequirements(
    $estimateItem,
    array $vehicles
): void {
    foreach ($vehicles as $vehicle) {

        if ($vehicle['source'] === 'company') {

            if (empty($vehicle['asset_id'])) {
                throw ValidationException::withMessages([
                    'items' => 'A company vehicle must be selected.',
                ]);
            }

            $asset = Asset::query()
                ->whereKey($vehicle['asset_id'])
                ->where('asset_type', 'vehicle')
                ->where('status', 'active')
                ->first();

            if (!$asset) {
                throw ValidationException::withMessages([
                    'items' => 'One of the selected company vehicles is no longer available.',
                ]);
            }

            $estimateItem->vehicles()->create([
                'source' => 'company',
                'asset_id' => $asset->id,

                /*
                 * Do not copy asset information here.
                 * The relationship gives us the current company asset.
                 */
                'vehicle_name' => null,
                'make' => null,
                'model' => null,
                'model_year' => null,
                'registration_number' => null,
                'vin' => null,
                'engine_number' => null,
                'vehicle_type' => null,
                'color' => null,
                'notes' => $vehicle['notes'] ?? null,
            ]);

            continue;
        }

        /*
         * Hired vehicle.
         *
         * It deliberately has NO asset_id.
         */
        $estimateItem->vehicles()->create([
            'source' => 'hired',
            'asset_id' => null,

            'vehicle_name' => $vehicle['vehicle_name'] ?? null,
            'make' => $vehicle['make'] ?? null,
            'model' => $vehicle['model'] ?? null,
            'model_year' => $vehicle['model_year'] ?? null,
            'registration_number' => $vehicle['registration_number'] ?? null,
            'vin' => $vehicle['vin'] ?? null,
            'engine_number' => $vehicle['engine_number'] ?? null,
            'vehicle_type' => $vehicle['vehicle_type'] ?? null,
            'color' => $vehicle['color'] ?? null,
            'notes' => $vehicle['notes'] ?? null,
        ]);
    }
}

    /**
     * Edit a quote that has not been converted. Totals are rebuilt from the
     * line items exactly as they were on create; the job keeps its own copy of
     * the figures once conversion has happened.
     */
    public function update(Estimate $estimate, array $data)
    {
        return DB::transaction(function () use ($estimate, $data) {

            if ($estimate->transportJob) {
                throw ValidationException::withMessages([
                    'estimate' => 'This estimate has already been converted and cannot be edited.',
                ]);
            }

            $lines = array_map($this->buildLine(...), $data['items']);

            $estimate->update([
                'customer_id' => $data['customer_id'],
                'estimate_date' => $data['estimate_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'pickup' => $data['pickup'],
                'destination' => $data['destination'],
                'service_type' => $data['service_type'],
                'estimated_cost' => array_sum(array_column($lines, 'cost_total')),
                'estimated_sell' => array_sum(array_column($lines, 'sell_total')),
                'estimated_profit' => array_sum(array_column($lines, 'profit')),
                'status' => $data['status'] ?? $estimate->status,
                'remarks' => $data['remarks'] ?? null,
            ]);

           $estimate->items()->delete();

            foreach ($data['items'] as $index => $itemData) {
                $line = $estimate->items()->create($lines[$index]);

                if (
                    strtolower($itemData['category']) === 'vehicle' &&
                    !empty($itemData['vehicles'])
                ) {
                    $this->saveVehicleRequirements(
                        $line,
                        $itemData['vehicles']
                    );
                }
            }
            $estimate->items()->createMany($lines);

            return $estimate->load(
                'customer',
                'items.vehicles.asset'
            );
        });
    }

    /**
     * Price one line. The estimate totals are just the sum of these, so the
     * arithmetic only lives here.
     */
    private function buildLine(array $item): array
    {
        $costTotal = $item['quantity'] * $item['cost_price'];
        $sellTotal = $item['quantity'] * $item['sell_price'];

        return [
            'title' => $item['title'],
            'category' => $item['category'],
            'quantity' => $item['quantity'],
            'cost_price' => $item['cost_price'],
            'sell_price' => $item['sell_price'],
            'cost_total' => $costTotal,
            'sell_total' => $sellTotal,
            'profit' => $sellTotal - $costTotal,
            'remarks' => $item['remarks'] ?? null,
        ];
    }

    public function show(Estimate $estimate)
{
    $estimate->load(
        'customer',
        'items.vehicles.asset'
    );

    return new EstimateResource($estimate);
}
}
