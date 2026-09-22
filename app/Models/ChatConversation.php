<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatConversation extends Model
{
    protected $primaryKey = 'conversation_id';

    protected $fillable = ['user_id', 'visitor_token', 'context', 'last_message_at'];

    protected $casts = [
        'context' => 'array',
        'last_message_at' => 'datetime',
    ];

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id', 'conversation_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id')->withTrashed();
    }

    /** Where a multi-step flow has got to, if one is running. */
    public function flow(): ?string
    {
        return $this->context['flow'] ?? null;
    }

    public function clearContext(): void
    {
        $this->update(['context' => null]);
    }
}
