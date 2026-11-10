<?php

namespace App\Providers;

use App\Services\ActivityLogService;
use Illuminate\Database\Eloquent\Model as EloquentModel;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Event::listen('eloquent.*', function (string $event, array $payload): void {
            if (! preg_match('/^eloquent\.(created|updated|deleted|restored):/', $event, $matches)) {
                return;
            }

            $model = $payload[0] ?? null;
            if ($model instanceof EloquentModel) {
                app(ActivityLogService::class)->record($model, $matches[1]);
            }
        });
    }
}
