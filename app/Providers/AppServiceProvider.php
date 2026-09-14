<?php

namespace App\Providers;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Sleep;
use Illuminate\Validation\Rules\Password;

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
        Model::unguard();
        Model::shouldBeStrict();
        Model::automaticallyEagerLoadRelationships();
        DB::prohibitDestructiveCommands(app()->isProduction());
        Date::use(CarbonImmutable::class);
        Vite::useAggressivePrefetching();
        Http::preventStrayRequests();
        Sleep::fake();
        URL::forceHttps();
        Password::defaults(fn (): ?Password => app()->isProduction() ? Password::min(12)->letters()->mixedCase()->numbers()->symbols()->max(255)->uncompromised() : null);

        Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
        ]);
    }
}
