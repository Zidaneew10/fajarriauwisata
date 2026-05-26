<?php
// app/Models/SparePartRequest.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SparePartRequest extends Model
{
    protected $fillable = [
        'user_id', 'part_name', 'quantity', 'unit',
        'reason', 'bus_info', 'status',
        'admin_notes', 'reviewed_by', 'reviewed_at',
    ];

    protected $casts = ['reviewed_at' => 'datetime'];

public function driver()
{
    // Tambahkan kata 'return'
    return $this->belongsTo(User::class, 'user_id');
}

public function reviewer()
{
    // Tambahkan kata 'return'
    return $this->belongsTo(User::class, 'reviewed_by');
}

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}
