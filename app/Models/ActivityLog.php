<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $fillable = [
        'admin_id',
        'activity',
        'type',
        'details',
        'laporan_id',
        'is_read'
    ];

    protected $casts = [
        'details' => 'array',
        'created_at' => 'datetime:d M Y, H:i',
        'updated_at' => 'datetime:d M Y, H:i'
    ];

    // Update relation name to 'admin' instead of 'user'
    public function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }

    public function laporan()
    {
        return $this->belongsTo(Laporan::class, 'laporan_id');
    }
}
