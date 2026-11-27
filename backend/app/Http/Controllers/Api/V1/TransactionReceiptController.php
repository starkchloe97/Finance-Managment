<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\TransactionReceiptResource;
use App\Models\TransactionReceipt;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionReceiptController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $filters = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'direction' => ['nullable', 'in:received,paid'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $receipts = TransactionReceipt::query()
            ->with('creator')
            ->when($filters['search'] ?? null, fn ($query, $search) => $query->where(function ($query) use ($search) {
                $query->where('receipt_no', 'like', "%{$search}%")
                    ->orWhere('party_name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            }))
            ->when($filters['direction'] ?? null, fn ($query, $direction) => $query->where('direction', $direction))
            ->latest('receipt_date')
            ->latest('id')
            ->paginate($filters['per_page'] ?? 25);

        return TransactionReceiptResource::collection($receipts);
    }

    public function show(TransactionReceipt $receipt): TransactionReceiptResource
    {
        return new TransactionReceiptResource($receipt->load('creator'));
    }
}
