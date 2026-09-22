<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * One thing the assistant knows how to answer. Either it carries fixed text
 * in `answer`, or it names a `handler` that looks something up at the moment
 * the question is asked.
 */
class ChatIntent extends Model
{
    protected $primaryKey = 'intent_id';

    protected $fillable = [
        'intent_key', 'category', 'label', 'keywords',
        'handler', 'answer', 'requires_login',
        'is_suggested', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'requires_login' => 'boolean',
        'is_suggested' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /** @return array<int,string> */
    public function keywordList(): array
    {
        return array_values(array_filter(preg_split('/[\s,]+/', mb_strtolower($this->keywords)) ?: []));
    }
}
