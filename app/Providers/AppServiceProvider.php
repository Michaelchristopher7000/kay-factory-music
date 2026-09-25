<?php

namespace App\Providers;

use App\Models\Contract;
use App\Models\Distribution;
use App\Models\Release;
use App\Models\RoyaltyPayment;
use App\Models\RoyaltyStatement;
use App\Observers\ContractObserver;
use App\Observers\DistributionObserver;
use App\Observers\ReleaseObserver;
use App\Observers\RoyaltyPaymentObserver;
use App\Observers\RoyaltyStatementObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Contract::observe(ContractObserver::class);
        Release::observe(ReleaseObserver::class);
        Distribution::observe(DistributionObserver::class);
        RoyaltyStatement::observe(RoyaltyStatementObserver::class);
        RoyaltyPayment::observe(RoyaltyPaymentObserver::class);

        $this->configurePasswordDefaults();
        $this->enforceHttpsInProduction();
    }

    protected function configurePasswordDefaults(): void
    {
        Password::defaults(function () {
            $rule = Password::min((int) config('kfm.password.min_length', 12));

            if (config('kfm.password.require_mixed_case', false)) {
                $rule->mixedCase();
            }
            if (config('kfm.password.require_numbers', false)) {
                $rule->numbers();
            }
            if (config('kfm.password.require_symbols', false)) {
                $rule->symbols();
            }
            if (config('kfm.password.check_compromised', false)) {
                $rule->uncompromised();
            }

            return $rule;
        });
    }

    /**
     * Force HTTPS URLs in production.
     * Only runs when APP_ENV=production, so local XAMPP development is untouched.
     */
    protected function enforceHttpsInProduction(): void
    {
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }
    }
}