<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomInstruction extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'user_id',
        'user_background',
        'user_interests',
        'knowledge_levels',
        'user_goals',
        'assistant_background',
        'assistant_tone',
        'response_style',
        'response_format',
        'custom_commands',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
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
