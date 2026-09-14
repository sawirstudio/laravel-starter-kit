<?php

declare(strict_types=1);

namespace App\Providers;

use App\Models\OrgInvitation;
use App\Models\OrgUser;
use App\Models\Role;
use App\Models\User;
use Carbon\CarbonImmutable;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\SecuritySchemes\HttpSecurityScheme;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Sleep;
use Illuminate\Validation\Rules\Password;

final class AppServiceProvider extends ServiceProvider
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
            'user' => User::class,
            'role' => Role::class,
            'org_user' => OrgUser::class,
            'org_invitation' => OrgInvitation::class,
        ]);

        Gate::define('viewApiDocs', fn (User $user): bool => $user->is_super_admin);

        // @codeCoverageIgnoreStart
        Scramble::configure()
            ->withDocumentTransformers(function (OpenApi $openApi): void {
                $openApi->secure(
                    (new HttpSecurityScheme('bearer'))->as('http')
                );
            });
        // @codeCoverageIgnoreEnd
    }
}
