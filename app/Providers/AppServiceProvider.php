<?php

namespace App\Providers;

use App\Contracts\MatchAiProvider;
use App\Contracts\PushNotificationService;
use App\Services\MatchAi\Providers\LogMatchAiProvider;
use App\Services\MatchAi\Providers\NullMatchAiProvider;
use App\Services\Push\LogPushService;
use App\Services\Push\OneSignalPushService;
use Carbon\CarbonImmutable;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            PushNotificationService::class,
            $this->app->isProduction()
                ? OneSignalPushService::class
                : LogPushService::class,
        );

        $this->app->bind(
            MatchAiProvider::class,
            match (config('services.match_ai.provider')) {
                'log' => LogMatchAiProvider::class,
                default => NullMatchAiProvider::class,
            },
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRateLimiting();
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Model::shouldBeStrict(! $this->app->isProduction());

        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            $this->app->isProduction(),
        );

        Password::defaults(fn (): ?Password => $this->app->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null
        );
    }

    /**
     * Configure rate limiters for password reset routes.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('forgot-password', function (Request $request) {
            $email = strtolower(trim($request->input('email', '')));

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(3)->by('forgot:'.$email),
            ];
        });

        RateLimiter::for('verify-reset-code', function (Request $request) {
            $email = strtolower(trim($request->input('email', '')));

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(5)->by('verify:'.$email),
            ];
        });

        RateLimiter::for('reset-password', function (Request $request) {
            $email = strtolower(trim($request->input('email', '')));

            return [
                Limit::perMinute(5)->by($request->ip()),
                Limit::perMinute(5)->by('reset:'.$email),
            ];
        });
    }
}
