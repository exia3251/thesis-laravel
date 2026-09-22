<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    public const ROLE_USER = 'user';
    public const ROLE_BOT  = 'bot';

    protected $primaryKey = 'message_id';

    protected $fillable = ['conversation_id', 'role', 'body', 'intent_key', 'payload'];

    protected $casts = ['payload' => 'array'];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id', 'conversation_id');
    }

    /** A visitor message nothing matched: the feed the back office reads. */
    public function scopeUnanswered($query)
    {
        return $query->where('role', self::ROLE_USER)->whereNull('intent_key');
    }
}
