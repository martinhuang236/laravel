<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Link extends Model
{
    use SoftDeletes;

    use HasFactory;

    protected $attributes = [
        'expires_at' => 'timestamp'
    ];

    protected $fillable = [
        'user_id',
        'original_url',
        'short_code',
        'expires_at',
        'is_permanent',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function purchases()
    {
        return $this->hasMany(Purchase::class);
    }


    public function isPermanent(): bool
    {
        return 1 === intval($this->is_permanent);
    }


    public function isValid(): bool
    {
        if ($this->is_permanent) {
            return true;
        }
        if (time() < strtotime($this->expires_at)) {
            return true;
        }

        return false;
    }
}
