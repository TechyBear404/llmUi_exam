<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Conversation extends Model
{
    protected $fillable = [
        'title',
        'user_id',
        'model_id',
        'custom_instruction_id',
        'context_length',
        'max_contecxt_length',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customInstruction(): BelongsTo
    {
        return $this->belongsTo(CustomInstruction::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
