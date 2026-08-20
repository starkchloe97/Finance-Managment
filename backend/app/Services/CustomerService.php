<?php

namespace App\Services;

use App\Helpers\NumberGenerator;
use App\Models\Customer;

class CustomerService
{
    public function create(array $data)
    {
        $data['code'] = NumberGenerator::generate(
            'CUS',
            Customer::class
        );

        return Customer::create($data)->load('jobs', 'estimates');
    }

    public function update(Customer $customer, array $data)
    {
        $customer->update($data);

        return $customer->load('jobs', 'estimates');
    }
}
