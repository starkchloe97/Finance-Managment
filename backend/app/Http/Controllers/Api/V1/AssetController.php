<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AssetRequest;
use App\Http\Resources\AssetResource;
use App\Models\Asset;
use App\Services\AssetService;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    public function __construct(
        private AssetService $service
    ) {}

    public function index(Request $request)
    {
        return AssetResource::collection(
            $this->service->paginate(
                $request->only([
                    'search',
                    'asset_type',
                    'status',
                    'per_page',
                ])
            )
        );
    }

    public function store(
        AssetRequest $request
    ) {
        $asset = $this->service->create(
            $request->validated(),
            $request->user()?->id
        );

        return new AssetResource($asset);
    }

    public function show(
        Asset $asset
    ) {
        return new AssetResource($asset);
    }

    public function update(
        AssetRequest $request,
        Asset $asset
    ) {
        return new AssetResource(
            $this->service->update(
                $asset,
                $request->validated()
            )
        );
    }

    public function destroy(
        Asset $asset
    ) {
        $this->service->delete($asset);

        return response()->json([
            'message' => 'Asset deleted.',
        ]);
    }
}