<?php

namespace App\Http\Resources;

use App\Enums\LoanBorrowerType;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LoanResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isInvestor = $this->borrower_type === LoanBorrowerType::Investor;

        return [
            'id' => $this->id,
            'loan_code' => $this->loan_code,
            'borrower_type' => $this->borrower_type->value,
            'borrower' => $isInvestor
                ? $this->whenLoaded('investor', fn () => [
                    'id' => $this->investor?->id,
                    'code' => $this->investor?->investor_code,
                    'name' => $this->investor?->name,
                    'email' => $this->investor?->email,
                    'phone' => $this->investor?->phone,
                ])
                : $this->whenLoaded('borrower', fn () => [
                    'id' => $this->borrower?->id,
                    'code' => $this->borrower?->borrower_code,
                    'name' => $this->borrower?->name,
                    'email' => $this->borrower?->email,
                    'phone' => $this->borrower?->phone,
                    'address' => $this->borrower?->address,
                ]),
            'investor_id' => $this->investor_id,
            'loan_borrower_id' => $this->loan_borrower_id,
            'amount' => $this->amount,
            'total_repaid' => $this->total_repaid,
            'outstanding_amount' => $this->outstanding_amount,
            'loan_date' => $this->loan_date?->toDateString(),
            'due_date' => $this->due_date?->toDateString(),
            'status' => $this->display_status->value,
            'first_overdue_at' => $this->first_overdue_at?->toISOString(),
            'paid_at' => $this->paid_at?->toISOString(),
            'cancelled_at' => $this->cancelled_at?->toISOString(),
            'notes' => $this->notes,
            'repayments' => LoanRepaymentResource::collection($this->whenLoaded('repayments')),
            'created_at' => $this->created_at,
        ];
    }
}
