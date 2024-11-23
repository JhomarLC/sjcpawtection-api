<?php

namespace App\Providers;

use App\Models\PetOwner;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Schema;
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
        Schema::defaultStringLength(191);
        ResetPassword::createUrlUsing(function ($petowner, string $token) {
            return match (true) {
                // $user instanceof Admin => 'http://admin.our-website/reset-password' . '?token=' . $token . '&email=' . urlencode($user->email),
                $petowner instanceof PetOwner => 'http://192.168.100.86:8080/reset-password' . '?token=' . $token . '&email=' . urlencode($petowner->email),
                // other user types
                default => throw new \Exception("Invalid user type"),
            };
        });
    }
}