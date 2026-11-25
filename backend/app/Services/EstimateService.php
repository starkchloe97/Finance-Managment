<?php

namespace App\Services;

use App\Helpers\NumberGenerator;
use App\Models\Asset;
use App\Models\Estimate;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

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
    public function __construct(
        private TransportJobService $transportJobs
    ) {}

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            $estimateType = $data['estimate_type'] ?? 'transportation';
            $details = $data['details'] ?? [];
            $lines = array_map(
                fn (array $item) => $this->buildLine($item, $estimateType),
                $data['items']
            );
            $totals = $this->totals($lines, $estimateType, $details, $data['tax_rate'] ?? 0);

            $estimate = Estimate::create([
                'code' => NumberGenerator::generate('EST', Estimate::class),
                'customer_id' => $data['customer_id'],
                'estimate_type' => $estimateType,
                'estimate_date' => $data['estimate_date'],
                'valid_until' => $data['valid_until'] ?? null,
                'pickup' => $data['pickup'] ?? ($details['job_location'] ?? ''),
                'destination' => $data['destination'] ?? ($estimateType === 'repair' ? 'Workshop' : ''),
                'service_type' => $data['service_type'],
                'tonnage' => $data['tonnage'] ?? null,
                'details' => $details,
                'terms' => $data['terms'] ?? null,
                'payment_terms' => $data['payment_terms'] ?? null,
                'tax_rate' => $totals['tax_rate'],
                'tax_amount' => $totals['tax_amount'],
                'grand_total' => $totals['grand_total'],
                'prepared_by' => $data['prepared_by'] ?? null,
                'checked_by' => $data['checked_by'] ?? null,
                'approved_by' => $data['approved_by'] ?? null,
                'customer_approval' => $data['customer_approval'] ?? null,
                'estimated_cost' => $totals['cost'],
                'estimated_sell' => $totals['subtotal'],
                'estimated_profit' => $totals['profit'],
                'status' => 'draft',
                'remarks' => $data['remarks'] ?? null,
            ]);

            foreach ($data['items'] as $index => $itemData) {
                $line = $estimate->items()->create($lines[$index]);

                if (
                    strtolower($itemData['category']) === 'vehicle' &&
                    ! empty($itemData['vehicles'])
                ) {
                    $this->saveVehicleRequirements(
                        $line,
                        $itemData['vehicles']
                    );
                }
            }

            return $estimate->load(
                'customer',
                'items.vehicles.asset',
                'transportJob'
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

                if (! $asset) {
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
            if (blank($vehicle['supplier_name'] ?? null)) {
                throw ValidationException::withMessages([
                    'items' => 'Enter the supplier name for every hired vehicle so its payable can be recorded.',
                ]);
            }

            $estimateItem->vehicles()->create([
                'source' => 'hired',
                'asset_id' => null,
                'supplier_name' => $vehicle['supplier_name'] ?? null,

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

            $estimateType = $data['estimate_type'] ?? $estimate->estimate_type ?? 'transportation';
            $details = array_key_exists('details', $data)
                ? ($data['details'] ?? [])
                : ($estimate->details ?? []);
            $taxRate = array_key_exists('tax_rate', $data)
                ? ($data['tax_rate'] ?? 0)
                : ($estimate->tax_rate ?? 0);
            $lines = array_map(
                fn (array $item) => $this->buildLine($item, $estimateType),
                $data['items']
            );
            $totals = $this->totals($lines, $estimateType, $details, $taxRate);

            $estimate->update([
                'customer_id' => $data['customer_id'],
                'estimate_type' => $estimateType,
                'estimate_date' => $data['estimate_date'],
                'valid_until' => array_key_exists('valid_until', $data) ? $data['valid_until'] : $estimate->valid_until,
                'pickup' => array_key_exists('pickup', $data)
                    ? ($data['pickup'] ?? ($details['job_location'] ?? ''))
                    : ($details['job_location'] ?? $estimate->pickup ?? ''),
                'destination' => array_key_exists('destination', $data)
                    ? ($data['destination'] ?? ($estimateType === 'repair' ? 'Workshop' : ''))
                    : ($estimateType === 'repair' ? 'Workshop' : ($estimate->destination ?? '')),
                'service_type' => $data['service_type'],
                'tonnage' => array_key_exists('tonnage', $data) ? $data['tonnage'] : $estimate->tonnage,
                'details' => $details,
                'terms' => array_key_exists('terms', $data) ? $data['terms'] : $estimate->terms,
                'payment_terms' => array_key_exists('payment_terms', $data) ? $data['payment_terms'] : $estimate->payment_terms,
                'tax_rate' => $totals['tax_rate'],
                'tax_amount' => $totals['tax_amount'],
                'grand_total' => $totals['grand_total'],
                'prepared_by' => array_key_exists('prepared_by', $data) ? $data['prepared_by'] : $estimate->prepared_by,
                'checked_by' => array_key_exists('checked_by', $data) ? $data['checked_by'] : $estimate->checked_by,
                'approved_by' => array_key_exists('approved_by', $data) ? $data['approved_by'] : $estimate->approved_by,
                'customer_approval' => array_key_exists('customer_approval', $data) ? $data['customer_approval'] : $estimate->customer_approval,
                'estimated_cost' => $totals['cost'],
                'estimated_sell' => $totals['subtotal'],
                'estimated_profit' => $totals['profit'],
                'status' => $data['status'] ?? $estimate->status,
                'remarks' => array_key_exists('remarks', $data) ? $data['remarks'] : $estimate->remarks,
            ]);

            $estimate->items()->get()->each(fn ($item) => $item->delete());

            foreach ($data['items'] as $index => $itemData) {
                $line = $estimate->items()->create($lines[$index]);

                if (
                    strtolower($itemData['category']) === 'vehicle' &&
                    ! empty($itemData['vehicles'])
                ) {
                    $this->saveVehicleRequirements(
                        $line,
                        $itemData['vehicles']
                    );
                }
            }
            // $estimate->items()->createMany($lines);

            if ($estimate->status === 'accepted' && ! $estimate->transportJob()->exists()) {
                $this->transportJobs->convert($estimate);
            }

            return $estimate->load(
                'customer',
                'items.vehicles.asset',
                'transportJob'
            );
        });
    }

    /**
     * Price one line. The estimate totals are just the sum of these, so the
     * arithmetic only lives here.
     */
    private function buildLine(array $item, string $estimateType): array
    {
        $partsCost = (float) ($item['parts_cost'] ?? 0);
        $laborCost = (float) ($item['labor_cost'] ?? 0);
        $repairCost = $partsCost + $laborCost;
        $costPrice = $estimateType === 'repair' && $repairCost > 0
            ? $repairCost
            : (float) $item['cost_price'];
        $sellPrice = $estimateType === 'repair' && $repairCost > 0
            ? (float) ($item['sell_price'] ?? $repairCost)
            : (float) $item['sell_price'];
        $costTotal = $item['quantity'] * $costPrice;
        $sellTotal = $item['quantity'] * $sellPrice;

        return [
            'title' => $item['title'],
            'category' => $item['category'],
            'quantity' => $item['quantity'],
            'cost_price' => $costPrice,
            'sell_price' => $sellPrice,
            'parts_cost' => $partsCost,
            'labor_cost' => $laborCost,
            'cost_total' => $costTotal,
            'sell_total' => $sellTotal,
            'profit' => $sellTotal - $costTotal,
            'remarks' => $item['remarks'] ?? null,
        ];
    }

    private function totals(array $lines, string $estimateType, array $details, float|int|string $taxRate): array
    {
        $extraCharges = $estimateType === 'repair'
            ? array_sum(array_map(
                fn (string $field) => (float) ($details[$field] ?? 0),
                ['workshop_charges', 'towing_charges', 'other_expenses']
            ))
            : 0;
        $cost = array_sum(array_column($lines, 'cost_total')) + $extraCharges;
        $subtotal = array_sum(array_column($lines, 'sell_total')) + $extraCharges;
        $rate = (float) $taxRate;
        $tax = round($subtotal * $rate / 100, 2);

        return [
            'cost' => $cost,
            'subtotal' => $subtotal,
            'profit' => $subtotal - $cost,
            'tax_rate' => $rate,
            'tax_amount' => $tax,
            'grand_total' => $subtotal + $tax,
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
