<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $primaryKey = 'log_id';
    protected $fillable = ['user_id', 'action', 'description'];
    public $timestamps = false;
    protected $dates = ['created_at'];
    protected $casts = ['created_at' => 'datetime'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    public static function logAction(?int $userId, string $action, ?string $description = null): self
    {
        return static::create([
            'user_id' => $userId,
            'action' => $action,
            'description' => $description,
        ]);
    }
}