<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    protected $fillable = ['title', 'model_id', 'custom_instruction_id', 'user_id'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customInstruction()
    {
        return $this->belongsTo(CustomInstruction::class);
    }

    public function messages()
    {
        return $this->hasMany(Message::class);
    }
}
