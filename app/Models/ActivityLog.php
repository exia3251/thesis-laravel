<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $primaryKey = 'log_id';
    protected $fillable = ['user_id', 'action', 'description', 'ip_address'];
    public $timestamps = false;
    public function user() { return $this->belongsTo(User::class, 'user_id', 'user_id'); }
}