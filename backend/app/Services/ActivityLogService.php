<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\BankTransaction;
use App\Models\CompanyCapitalDraftActivity;
use App\Models\InvoicePayment;
use App\Models\Payment;
use App\Models\TransportJobActivity;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class ActivityLogService
{
    public const TRACKED_SUBJECTS = [
        'Asset',
        'BankAccount',
        'BankTransaction',
        'CompanyCapitalAccount',
        'CompanyCapitalDraft',
        'CompanyCapitalTransaction',
        'ContractVehicle',
        'Customer',
        'Estimate',
        'FinancialAdjustment',
        'Investment',
        'InvestmentAllocation',
        'InvestmentSettlement',
        'Investor',
        'InvestorProfitDistribution',
        'Invoice',
        'Loan',
        'LoanBorrower',
        'LoanRepayment',
        'Payment',
        'TransportJob',
        'TransportJobExpense',
        'VehicleContract',
        'VehicleDailyReport',
    ];

    private const TRACKED_FIELDS = [
        'name',
        'code',
        'title',
        'bank_name',
        'party_name',
        'internal_notes',
        'notes',
        'invoice_no',
        'payment_no',
        'transaction_code',
        'amount',
        'total',
        'subtotal',
        'tax_amount',
        'opening_balance',
        'current_balance',
        'balance_after',
        'status',
        'direction',
        'type',
        'category',
        'available',
        'payment_method',
        'quantity',
        'unit_price',
        'invoice_date',
        'due_date',
        'payment_date',
        'transaction_date',
        'expense_date',
    ];

    public function record(Model $model, string $event): void
    {
        if (
            (app()->runningInConsole() && ! app()->runningUnitTests()) ||
            $model instanceof ActivityLog ||
            $model instanceof TransportJobActivity ||
            $model instanceof CompanyCapitalDraftActivity ||
            $model instanceof User ||
            ! in_array(class_basename($model), self::TRACKED_SUBJECTS, true)
        ) {
            return;
        }

        $action = match ($event) {
            'created' => 'created',
            'updated' => 'updated',
            'deleted' => 'deleted',
            'restored' => 'restored',
            default => null,
        };

        if (! $action) {
            return;
        }

        $changes = $this->changes($model, $action);
        $context = $this->context($model);
        if ($action === 'updated' && $changes === [] && $context['changes'] === []) {
            return;
        }
        $changes = array_merge($changes, $context['changes']);
        $type = class_basename($model);
        $identifier = $this->identifier($model);
        $description = Str::headline($action).' '.Str::headline($type);
        if ($identifier !== null) {
            $description .= ': '.$identifier;
        }
        if ($context['description'] !== null) {
            $description .= ' — '.$context['description'];
        }

        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $type,
            'subject_id' => (string) $model->getKey(),
            'description' => $description,
            'changes' => $changes ?: null,
        ]);
    }

    private function changes(Model $model, string $action): array
    {
        $attributes = $model->getAttributes();
        $fields = match ($action) {
            'created', 'restored' => array_keys($attributes),
            'deleted' => array_keys($attributes),
            default => array_keys($model->getChanges()),
        };

        $changes = [];
        foreach (array_intersect(self::TRACKED_FIELDS, $fields) as $field) {
            $new = $attributes[$field] ?? null;
            $old = $model->getRawOriginal($field);
            if ($action === 'created' || $action === 'restored') {
                $changes[$field] = ['new' => $new];
            } elseif ($action === 'deleted') {
                $changes[$field] = ['old' => $old ?? $new];
            } else {
                $changes[$field] = ['old' => $old, 'new' => $new];
            }
        }

        return $changes;
    }

    private function identifier(Model $model): ?string
    {
        foreach (['invoice_no', 'payment_no', 'transaction_code', 'code', 'name', 'title'] as $field) {
            $value = $model->getAttribute($field);
            if (is_scalar($value) && (string) $value !== '') {
                return (string) $value;
            }
        }

        return null;
    }

    /**
     * Add the related account or invoice name without exposing account numbers.
     *
     * @return array{description: ?string, changes: array<string, array{new: string}>}
     */
    private function context(Model $model): array
    {
        if ($model instanceof BankTransaction) {
            $bankName = $model->bankAccount?->name;
            $transactionDescription = $model->description;

            return [
                'description' => $bankName,
                'changes' => array_filter([
                    'bank_account' => $bankName ? ['new' => $bankName] : null,
                    'transaction_details' => $transactionDescription ? ['new' => $transactionDescription] : null,
                ]),
            ];
        }

        if ($model instanceof Payment) {
            $bankName = $model->bankAccount?->name;

            return [
                'description' => $bankName,
                'changes' => $bankName ? ['bank_account' => ['new' => $bankName]] : [],
            ];
        }

        if ($model instanceof InvoicePayment) {
            $invoiceNo = $model->invoice?->invoice_no;
            $paymentNo = $model->payment?->payment_no;
            $bankName = $model->payment?->bankAccount?->name;
            $parts = array_filter([$invoiceNo, $paymentNo, $bankName]);

            return [
                'description' => $parts ? implode(' · ', $parts) : null,
                'changes' => array_filter([
                    'invoice' => $invoiceNo ? ['new' => $invoiceNo] : null,
                    'bank_account' => $bankName ? ['new' => $bankName] : null,
                ]),
            ];
        }

        return ['description' => null, 'changes' => []];
    }
}
