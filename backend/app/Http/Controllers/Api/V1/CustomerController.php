<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\CustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(
        private CustomerService $service
    ) {}

    /**
     * The search terms must be grouped. Left ungrouped they bind as
     * `(deleted_at is null and name like ?) or phone like ? or company like ?`,
     * which hands soft-deleted customers back as soon as their phone or company
     * matches.
     *
     * per_page is capped because this also feeds the customer dropdown on the
     * estimate form, which needs more than one page at a time.
     */
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($search = $request->input('search')) {
            $query->where(function ($grouped) use ($search) {
                $grouped->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%");
            });
        }

        $perPage = min(max((int) $request->input('per_page', 10), 1), 100);

        return CustomerResource::collection(
            $query->latest()->paginate($perPage)
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

    public function update(CustomerRequest $request, Customer $customer)
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
            'message' => 'Customer deleted.',
        ]);
    }
}
