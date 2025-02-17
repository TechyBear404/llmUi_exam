<?php

namespace App\Providers;

use App\Models\Conversation;
use App\Models\CustomInstruction;
use App\Models\Message;
use App\Policies\ConversationPolicy;
use App\Policies\CustomInstructionPolicy;
use App\Policies\MessagePolicy;
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
        Message::class => MessagePolicy::class,
        CustomInstruction::class => CustomInstructionPolicy::class,
        Conversation::class => ConversationPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
