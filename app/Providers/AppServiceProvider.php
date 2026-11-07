<?php

namespace App\Providers;

use App\Observers\ActivityLogObserver;
use App\Support\ActivityLogger;
use App\Support\Video\Providers\VimeoVideoProvider;
use App\Support\Video\Providers\YoutubeVideoProvider;
use App\Support\Video\VideoMetadataService;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(VideoMetadataService::class, function (): VideoMetadataService {
            return new VideoMetadataService([
                new YoutubeVideoProvider,
                new VimeoVideoProvider,
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        foreach (ActivityLogger::auditableModels() as $model) {
            $model::observe(ActivityLogObserver::class);
        }
    }
}
