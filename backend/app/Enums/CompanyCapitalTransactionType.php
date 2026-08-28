<?php

namespace App\Enums;

enum CompanyCapitalTransactionType: string
{
    case OpeningBalance = 'opening_balance';
    case LoanIssued = 'loan_issued';
    case LoanRepayment = 'loan_repayment';
    case LoanCancelled = 'loan_cancelled';
    case CapitalAdded = 'capital_added';
    case CapitalWithdrawn = 'capital_withdrawn';
    case CapitalReserved = 'capital_reserved';
    case CapitalMadeAvailable = 'capital_made_available';
}
