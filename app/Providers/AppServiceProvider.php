<?php

namespace App\Providers;

use App\Contracts\HashableInterface;
use App\Services\PasswordHasher;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    
    public function register(): void
    {
        $this->app->singleton(HashableInterface::class, function () {
            return new PasswordHasher(rounds: 12);
        });
    }

  
    public function boot(): void
    {
        \Illuminate\Support\Facades\Schema::defaultStringLength(191);
    }
}