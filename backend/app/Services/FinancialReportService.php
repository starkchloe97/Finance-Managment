<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Customer;
use App\Models\Investor;
use App\Models\Invoice;
use App\Models\TransportJobExpense;
use Illuminate\Support\Collection;

class FinancialReportService
{
    public function __construct(private CompanyCapitalService $capital) {}

    public function build(string $from, string $to): array
    {
        $assets = Asset::query()->orderBy('asset_code')->get();
        $investors = Investor::query()
            ->withSum(['investments as period_invested' => fn ($query) => $query->whereBetween('investment_date', [$from, $to])], 'amount')
            ->withSum(['investments as capital_outstanding' => fn ($query) => $query->whereIn('status', ['active', 'matured'])], 'amount')
            ->withCount(['investments as period_investment_count' => fn ($query) => $query->whereBetween('investment_date', [$from, $to])])
            ->orderBy('name')
            ->get();

        $customers = Customer::query()
            ->withCount(['jobs as period_job_count' => fn ($query) => $query->whereBetween('job_date', [$from, $to])])
            ->withSum(['jobs as period_revenue' => fn ($query) => $query->whereBetween('job_date', [$from, $to])], 'sell_price')
            ->withSum(['jobs as period_cost' => fn ($query) => $query->whereBetween('job_date', [$from, $to])], 'cost_price')
            ->withSum(['jobs as period_extra_costs' => fn ($query) => $query->whereBetween('job_date', [$from, $to])], 'extra_costs')
            ->withSum(['jobs as period_profit' => fn ($query) => $query->whereBetween('job_date', [$from, $to])], 'final_profit')
            ->orderBy('name')
            ->get();

        $invoices = Invoice::query()
            ->select([
                'id', 'invoice_no', 'direction', 'category', 'party_name',
                'invoice_date', 'due_date', 'total',
            ])
            ->withSum('allocations as period_paid', 'amount')
            ->whereBetween('invoice_date', [$from, $to])
            ->latest('invoice_date')
            ->get()
            ->map(function (Invoice $invoice): array {
                $paid = round((float) $invoice->period_paid, 2);
                $outstanding = round(max((float) $invoice->total - $paid, 0), 2);

                return [
                    'id' => $invoice->id,
                    'invoice_no' => $invoice->invoice_no,
                    'direction' => $invoice->direction?->value,
                    'category' => $invoice->category,
                    'party_name' => $invoice->party_name,
                    'invoice_date' => $invoice->invoice_date?->toDateString(),
                    'due_date' => $invoice->due_date?->toDateString(),
                    'total' => (float) $invoice->total,
                    'paid' => $paid,
                    'outstanding' => $outstanding,
                    'overdue' => $outstanding > 0 && $invoice->due_date?->isBefore(today()),
                ];
            });

        $expenses = TransportJobExpense::query()
            ->with('transportJob:id,code')
            ->whereBetween('expense_date', [$from, $to])
            ->orderByDesc('expense_date')
            ->get()
            ->map(fn (TransportJobExpense $expense): array => [
                'id' => $expense->id,
                'job_code' => $expense->transportJob?->code,
                'title' => $expense->title,
                'category' => $expense->category?->value,
                'expense_date' => $expense->expense_date?->toDateString(),
                'amount' => (float) $expense->amount,
            ]);

        $assetItems = $assets->map(fn (Asset $asset): array => [
            'asset_code' => $asset->asset_code,
            'name' => $asset->name,
            'asset_type' => $asset->asset_type?->value,
            'status' => $asset->status?->value,
            'purchase_date' => $asset->purchase_date?->toDateString(),
            'purchase_price' => (float) $asset->purchase_price,
            'current_value' => (float) $asset->current_value,
        ]);
        $investorItems = $investors->map(fn (Investor $investor): array => [
            'investor_code' => $investor->investor_code,
            'name' => $investor->name,
            'investments_in_period' => (int) $investor->period_investment_count,
            'invested_in_period' => (float) $investor->period_invested,
            'capital_outstanding' => (float) $investor->capital_outstanding,
        ]);
        $customerItems = $customers->map(fn (Customer $customer): array => [
            'customer_code' => $customer->code,
            'name' => $customer->name,
            'jobs' => (int) $customer->period_job_count,
            'revenue' => (float) $customer->period_revenue,
            'cost' => round((float) $customer->period_cost + (float) $customer->period_extra_costs, 2),
            'profit' => (float) $customer->period_profit,
        ]);

        $receivableInvoices = $invoices->where('direction', 'receivable')->values();
        $payableInvoices = $invoices->where('direction', 'payable')->values();
        $commissionInvoices = $receivableInvoices->where('category', 'commission')->values();
        $administrativePayables = $payableInvoices->where('category', 'administrative')->values();

        return [
            'period' => ['from' => $from, 'to' => $to],
            'assets' => [
                'as_of' => today()->toDateString(),
                'totals' => [
                    'count' => $assetItems->count(),
                    'purchase_value' => round($assetItems->sum('purchase_price'), 2),
                    'current_value' => round($assetItems->sum('current_value'), 2),
                ],
                'by_status' => $assetItems->groupBy('status')->map->count()->all(),
                'items' => $assetItems->values()->all(),
            ],
            'investors' => [
                'totals' => [
                    'count' => $investorItems->count(),
                    'invested_in_period' => round($investorItems->sum('invested_in_period'), 2),
                    'capital_outstanding' => round($investorItems->sum('capital_outstanding'), 2),
                ],
                'items' => $investorItems->values()->all(),
            ],
            'customers' => [
                'totals' => [
                    'count' => $customerItems->count(),
                    'jobs' => $customerItems->sum('jobs'),
                    'revenue' => round($customerItems->sum('revenue'), 2),
                    'cost' => round($customerItems->sum('cost'), 2),
                    'profit' => round($customerItems->sum('profit'), 2),
                ],
                'items' => $customerItems->values()->all(),
            ],
            'receivables' => $this->invoiceSummary($receivableInvoices),
            'payables' => [
                ...$this->invoiceSummary($payableInvoices),
                'administrative' => $this->invoiceSummary($administrativePayables),
            ],
            'commission_receivables' => $this->invoiceSummary($commissionInvoices),
            'operational_expenses' => [
                'total' => round($expenses->sum('amount'), 2),
                'by_category' => $expenses->groupBy('category')->map(fn (Collection $rows) => [
                    'count' => $rows->count(),
                    'amount' => round($rows->sum('amount'), 2),
                ])->all(),
                'items' => $expenses->values()->all(),
            ],
            'company_capital' => $this->capital->snapshot(),
        ];
    }

    private function invoiceSummary(Collection $invoices): array
    {
        return [
            'count' => $invoices->count(),
            'total' => round($invoices->sum('total'), 2),
            'paid' => round($invoices->sum('paid'), 2),
            'outstanding' => round($invoices->sum('outstanding'), 2),
            'overdue' => round($invoices->where('overdue', true)->sum('outstanding'), 2),
            'items' => $invoices->values()->all(),
        ];
    }
}
