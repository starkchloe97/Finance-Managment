<?php
namespace App\Services;

use App\Models\Customer;
use App\Helpers\NumberGenerator;

class CustomerService
{
    public function create(array $data)
{
    $data['code'] = NumberGenerator::generate(
        'CUS',
        Customer::class
    );

    return Customer::create($data);
}

    public function update(Customer $customer,array $data)
    {
        $customer->update($data);

        return $customer;
    }
}