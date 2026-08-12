<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $service
    ){}

    public function index()
{
    $query = Customer::query();

    if (request('search')) {
        $query->where('name', 'like', '%' . request('search') . '%')
              ->orWhere('phone', 'like', '%' . request('search') . '%')
              ->orWhere('company', 'like', '%' . request('search') . '%');
    }

    return CustomerResource::collection(
        $query->latest()->paginate(10)
    );
}

    public function store(CustomerRequest $request)
    {
        return new CustomerResource(
            $this->service->create($request->validated())
        );
    }

    public function show(Customer $customer)
    {
        return new CustomerResource($customer);
    }

    public function update(CustomerRequest $request,Customer $customer)
    {
        return new CustomerResource(
            $this->service->update(
                $customer,
                $request->validated()
            )
        );
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return response()->json([
            'message'=>'Customer deleted.'
        ]);
    }
}