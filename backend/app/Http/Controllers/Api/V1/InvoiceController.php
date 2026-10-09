<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\InvoiceRequest;
use App\Http\Requests\PaymentRequest;
use App\Http\Resources\InvoiceResource;
use App\Http\Resources\PaymentResource;
use App\Models\Invoice;
use App\Models\TransportJob;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function __construct(private InvoiceService $service) {}

    public function index(Request $request)
    {
        return InvoiceResource::collection(
            $this->service->list($request->validate([
                'search' => ['nullable', 'string', 'max:255'],
                'party' => ['nullable', 'string', 'max:255'],
                'status' => ['nullable', 'in:unpaid,partially_paid,paid,overdue'],
                'category' => ['nullable', 'string', 'max:40'],
                'customer_id' => ['nullable', 'integer'],
                'from' => ['nullable', 'date'],
                'to' => ['nullable', 'date', 'after_or_equal:from'],
                'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            ]), $request->routeIs('receivables') ? 'receivable' : ($request->routeIs('payables') ? 'payable' : null))
        );
    }

    public function show(Invoice $invoice)
    {
        return new InvoiceResource($this->service->show($invoice));
    }

    public function update(InvoiceRequest $request, Invoice $invoice)
    {
        return new InvoiceResource($this->service->update($invoice, $request->validated()));
    }

    public function store(InvoiceRequest $request)
    {
        return (new InvoiceResource($this->service->create($request->validated(), (int) $request->user()->id)))
            ->response()
            ->setStatusCode(201);
    }

    public function companyDetails()
    {
        return response()->json([
            'data' => [
                'company_name' => config('invoice.company_name'),
                'company_phone' => config('invoice.company_phone'),
                'company_address' => config('invoice.company_address'),
                'company_str_no' => config('invoice.company_str_no'),
                'company_ntn_no' => config('invoice.company_ntn_no'),
                'company_stnt_no' => config('invoice.company_stnt_no'),
                'company_bank_details' => config('invoice.company_bank_details'),
                'company_logo_url' => config('invoice.company_logo_url'),
            ],
        ]);
    }

    public function forJob(TransportJob $job, Request $request)
    {
        return (new InvoiceResource($this->service->createForJob($job, (int) $request->user()->id)))
            ->response()
            ->setStatusCode(201);
    }

    public function paymentList()
    {
        return PaymentResource::collection($this->service->paymentList());
    }

    public function storePayment(PaymentRequest $request)
    {
        return (new PaymentResource($this->service->recordPayment($request->validated(), (int) $request->user()->id)))
            ->response()
            ->setStatusCode(201);
    }

    public function commission(Request $request)
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'party' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:unpaid,partially_paid,paid,overdue'],
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $filters['category'] = 'commission';

        return InvoiceResource::collection($this->service->list($filters, 'receivable'));
    }
}
