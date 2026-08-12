<?php

namespace App\Services;

use App\Models\Estimate;
use App\Models\EstimateItem;
use App\Helpers\NumberGenerator;
use Illuminate\Support\Facades\DB;

class EstimateService
{
    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {

            $subtotal = 0;

            foreach ($data['items'] as $item) {

                $subtotal +=
                    $item['quantity'] *
                    $item['unit_price'];

            }

            if (($data['margin_type'] ?? 'fixed') === 'percentage') {

                $margin =
                    $subtotal *
                    ($data['margin'] / 100);

            } else {

                $margin = $data['margin'] ?? 0;

            }

            $estimate = Estimate::create([

                'code'=>NumberGenerator::generate(
                    'EST',
                    Estimate::class
                ),

                'customer_id'=>$data['customer_id'],

                'estimate_date'=>$data['estimate_date'],

                'valid_until'=>$data['valid_until'] ?? null,

                'pickup'=>$data['pickup'],

                'destination'=>$data['destination'],

                'service_type'=>$data['service_type'],

                'subtotal'=>$subtotal,

                'margin'=>$margin,

                'margin_type'=>$data['margin_type'] ?? 'fixed',

                'total'=>$subtotal+$margin,

                'status'=>'draft',

                'remarks'=>$data['remarks'] ?? null

            ]);

            foreach ($data['items'] as $item) {

                EstimateItem::create([

                    'estimate_id'=>$estimate->id,

                    'title'=>$item['title'],

                    'category'=>$item['category'],

                    'quantity'=>$item['quantity'],

                    'unit_price'=>$item['unit_price'],

                    'total'=>$item['quantity']*$item['unit_price'],

                    'notes'=>$item['notes'] ?? null

                ]);

            }

            return $estimate->load('customer','items');

        });
    }
}