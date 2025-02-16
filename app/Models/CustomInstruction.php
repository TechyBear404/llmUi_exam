<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomInstruction extends Model
{
    protected $fillable = [
        'user_id',
        'user_background',
        'user_interests',
        'knowledge_levels',
        'user_goals',
        'assistant_background',
        'assistant_personality',
        'communication_style',
        'response_style',
        'response_format',
        'custom_commands',
    ];

    protected $casts = [
        'user_interests' => 'array',
        'knowledge_levels' => 'array',
        'custom_commands' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
