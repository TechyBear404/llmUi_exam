<?php

namespace App\Providers;

use App\Models\CustomInstruction;
use App\Policies\CustomInstructionPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // CustomInstruction::class => CustomInstructionPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
