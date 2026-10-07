<?php

namespace App\Services;

use App\Enums\InvoiceDirection;
use App\Enums\PaymentDirection;
use App\Helpers\NumberGenerator;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoicePayment;
use App\Models\Payment;
use App\Models\TransportJob;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceService
{
    public function list(array $filters, ?string $direction = null): LengthAwarePaginator
    {
        $query = Invoice::query()
            ->with(['customer', 'transportJob'])
            ->withSum('allocations as paid_amount_sum', 'amount');

        if ($direction) {
            $query->where('direction', $direction);
        }
        foreach (['category', 'customer_id'] as $field) {
            if (! empty($filters[$field])) {
                $query->where($field, $filters[$field]);
            }
        }
        if (! empty($filters['from'])) {
            $query->whereDate('invoice_date', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('invoice_date', '<=', $filters['to']);
        }
        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($group) use ($search) {
                $group->where('invoice_no', 'like', "%{$search}%")
                    ->orWhere('party_name', 'like', "%{$search}%")
                    ->orWhere('party_company', 'like', "%{$search}%");
            });
        }
        if (! empty($filters['party'])) {
            $query->where(function ($group) use ($filters) {
                $group->where('party_name', 'like', '%'.$filters['party'].'%')
                    ->orWhere('party_company', 'like', '%'.$filters['party'].'%');
            });
        }

        if (! empty($filters['status'])) {
            $allocated = '(select coalesce(sum(invoice_payments.amount), 0) from invoice_payments where invoice_payments.invoice_id = invoices.id)';
            $status = $filters['status'];
            if ($status === 'paid') {
                $query->whereRaw("$allocated >= invoices.total");
            } elseif ($status === 'partially_paid') {
                $query->whereRaw("$allocated > 0 and $allocated < invoices.total")
                    ->where(fn ($q) => $q->whereNull('due_date')->orWhereDate('due_date', '>=', today()));
            } elseif ($status === 'overdue') {
                $query->whereDate('due_date', '<', today())
                    ->whereRaw("$allocated < invoices.total");
            } elseif ($status === 'unpaid') {
                $query->whereRaw("$allocated = 0")
                    ->where(fn ($q) => $q->whereNull('due_date')->orWhereDate('due_date', '>=', today()));
            }
        }

        return $query->latest('invoice_date')
            ->paginate(min(max((int) ($filters['per_page'] ?? 15), 1), 100));
    }

    public function create(array $data, int $userId): Invoice
    {
        return DB::transaction(function () use ($data, $userId) {
            $customer = ! empty($data['customer_id']) ? Customer::findOrFail($data['customer_id']) : null;
            $subtotal = 0.0;
            $lines = [];
            foreach ($data['items'] as $item) {
                $amount = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $subtotal = round($subtotal + $amount, 2);
                $lines[] = $item + ['amount' => $amount];
            }
            $tax = round((float) ($data['tax_amount'] ?? 0), 2);
            $total = round($subtotal + $tax, 2);
            if ($total <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Invoice total must be greater than zero.',
                ]);
            }
            if ($total > 9999999999999.99 || collect($lines)->contains(fn ($line) => $line['amount'] > 9999999999999.99)) {
                throw ValidationException::withMessages([
                    'items' => 'Invoice amounts exceed the maximum supported value.',
                ]);
            }

            $invoice = Invoice::create([
                'invoice_no' => NumberGenerator::generate(
                    $data['direction'] === InvoiceDirection::Receivable->value ? 'SI' : 'PI',
                    Invoice::class
                ),
                'direction' => $data['direction'],
                'category' => $data['category'],
                'customer_id' => $customer?->id,
                'party_name' => $customer?->name ?? $data['party_name'],
                'party_company' => $customer?->company ?? ($data['party_company'] ?? null),
                'party_contact' => $data['party_contact'] ?? null,
                'party_phone' => $customer?->phone ?? ($data['party_phone'] ?? null),
                'party_address' => $customer?->address ?? ($data['party_address'] ?? null),
                'party_tax_number' => $data['party_tax_number'] ?? null,
                'company_name' => $data['company_name'] ?? null,
                'company_phone' => $data['company_phone'] ?? null,
                'company_address' => $data['company_address'] ?? null,
                'company_tax_number' => $data['company_tax_number'] ?? null,
                'invoice_date' => $data['invoice_date'],
                'due_date' => $data['due_date'] ?? null,
                'subtotal' => $subtotal,
                'tax_amount' => $tax,
                'tax_label' => $data['tax_label'] ?? null,
                'tax_number' => $data['tax_number'] ?? null,
                'total' => $total,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($lines as $line) {
                $invoice->items()->create([
                    'description' => $line['description'],
                    'quantity' => $line['quantity'],
                    'unit' => $line['unit'] ?? null,
                    'unit_price' => $line['unit_price'],
                    'amount' => $line['amount'],
                    'details' => $line['details'] ?? null,
                ]);
            }

            return $this->loadInvoice($invoice);
        });
    }

    public function createForJob(TransportJob $job, int $userId): Invoice
    {
        return DB::transaction(function () use ($job, $userId) {
            $job = TransportJob::query()->with(['customer', 'estimate'])->lockForUpdate()->findOrFail($job->id);
            if ($job->status->value !== 'completed') {
                throw ValidationException::withMessages([
                    'job' => 'An invoice can only be generated for a completed job.',
                ]);
            }
            if (Invoice::where('transport_job_id', $job->id)->exists()) {
                throw ValidationException::withMessages([
                    'job' => 'An invoice has already been generated for this job.',
                ]);
            }
            if ((float) $job->sell_price <= 0) {
                throw ValidationException::withMessages([
                    'job' => 'A job with no sell price cannot be invoiced.',
                ]);
            }
            $customer = $job->customer;
            $invoice = Invoice::create([
                'invoice_no' => NumberGenerator::generate('SI', Invoice::class),
                'direction' => InvoiceDirection::Receivable,
                'category' => 'job',
                'customer_id' => $customer?->id,
                'transport_job_id' => $job->id,
                'party_name' => $customer?->name ?? 'Customer',
                'party_company' => $customer?->company,
                'party_phone' => $customer?->phone,
                'party_address' => $customer?->address,
                'invoice_date' => today(),
                'subtotal' => $job->sell_price,
                'tax_amount' => 0,
                'total' => $job->sell_price,
                'created_by' => $userId,
            ]);
            $invoice->items()->create([
                'description' => "Transport service for {$job->code}",
                'quantity' => 1,
                'unit' => 'job',
                'unit_price' => $job->sell_price,
                'amount' => $job->sell_price,
                'details' => [
                    'job_code' => $job->code,
                    'pickup' => $job->estimate?->pickup,
                    'drop' => $job->estimate?->destination,
                ],
            ]);

            return $this->loadInvoice($invoice);
        });
    }

    public function show(Invoice $invoice): Invoice
    {
        return $this->loadInvoice($invoice);
    }

    public function recordPayment(array $data, int $userId): Payment
    {
        return DB::transaction(function () use ($data, $userId) {
            $allocations = collect($data['allocations'])->sortBy('invoice_id')->values();
            $allocationTotal = round($allocations->sum(fn ($allocation) => (float) $allocation['amount']), 2);
            $paymentAmount = round((float) $data['amount'], 2);
            if ($allocationTotal !== $paymentAmount) {
                throw ValidationException::withMessages([
                    'amount' => 'Payment amount must equal the sum allocated to invoices.',
                ]);
            }

            $invoices = Invoice::query()
                ->whereIn('id', $allocations->pluck('invoice_id'))
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            if ($invoices->count() !== $allocations->count()) {
                throw ValidationException::withMessages(['allocations' => 'One or more invoices no longer exist.']);
            }

            foreach ($allocations as $allocation) {
                /** @var Invoice $invoice */
                $invoice = $invoices->get($allocation['invoice_id']);
                $expectedDirection = $invoice->direction === InvoiceDirection::Receivable
                    ? PaymentDirection::Received->value
                    : PaymentDirection::Paid->value;
                if ($data['direction'] !== $expectedDirection) {
                    throw ValidationException::withMessages([
                        'direction' => 'Payment direction must match every allocated invoice.',
                    ]);
                }
                $outstanding = round((float) $invoice->total - (float) $invoice->allocations()->sum('amount'), 2);
                if ((float) $allocation['amount'] > $outstanding) {
                    throw ValidationException::withMessages([
                        'allocations' => "Allocation exceeds the outstanding amount for {$invoice->invoice_no}.",
                    ]);
                }
            }

            $payment = Payment::create([
                'payment_no' => NumberGenerator::generate('PAY', Payment::class),
                'direction' => $data['direction'],
                'payment_date' => $data['payment_date'],
                'amount' => $paymentAmount,
                'payment_method' => $data['payment_method'] ?? null,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($allocations as $allocation) {
                InvoicePayment::create([
                    'invoice_id' => $allocation['invoice_id'],
                    'payment_id' => $payment->id,
                    'amount' => round((float) $allocation['amount'], 2),
                ]);
            }

            return $payment->load('allocations.invoice');
        });
    }

    public function paymentList(): LengthAwarePaginator
    {
        return Payment::with('allocations.invoice')->latest('payment_date')->paginate(20);
    }

    public function dashboardSummary(): array
    {
        $balances = DB::table('invoices')
            ->leftJoin('invoice_payments', 'invoice_payments.invoice_id', '=', 'invoices.id')
            ->selectRaw('invoices.direction, invoices.category, invoices.due_date, invoices.total, coalesce(sum(invoice_payments.amount), 0) as paid')
            ->groupBy('invoices.id');

        $rows = DB::query()->fromSub($balances, 'invoice_balances')
            ->selectRaw("
                coalesce(sum(case when direction = 'receivable' and total > paid then total - paid else 0 end), 0) as receivables,
                coalesce(sum(case when direction = 'payable' and total > paid then total - paid else 0 end), 0) as payables,
                coalesce(sum(case when direction = 'receivable' and due_date < ? and total > paid then total - paid else 0 end), 0) as overdue_receivables,
                coalesce(sum(case when direction = 'payable' and due_date < ? and total > paid then total - paid else 0 end), 0) as overdue_payables,
                coalesce(sum(case when direction = 'receivable' and category = 'commission' and total > paid then total - paid else 0 end), 0) as commission_receivable
            ", [today()->toDateString(), today()->toDateString()])
            ->first();

        return [
            'receivables' => (float) $rows->receivables,
            'payables' => (float) $rows->payables,
            'overdue_receivables' => (float) $rows->overdue_receivables,
            'overdue_payables' => (float) $rows->overdue_payables,
            'commission_receivable' => (float) $rows->commission_receivable,
        ];
    }

    private function loadInvoice(Invoice $invoice): Invoice
    {
        return $invoice->load([
            'customer', 'transportJob', 'items',
            'allocations.payment', 'allocations.invoice',
        ])->loadSum('allocations as paid_amount_sum', 'amount');
    }
}
