<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $primaryKey = 'log_id';
    public $timestamps = false;
    
    protected $fillable = [
        'user_id', 'action', 'table_name', 'record_id',
        'description', 'old_value', 'new_value',
        'ip_address', 'user_agent', 'created_at'
    ];

    public function user() {
        return $this->belongsTo(User::class, 'user_id', 'user_id');
    }

    // Log activity
    public static function logActivity($action, $description, $tableName = null, $recordId = null) {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'table_name' => $tableName,
            'record_id' => $recordId,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now()
        ]);
    }
}