<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'country_id'
    ];

    protected $hidden = [
        // 'created_at',
        // 'updated_at',
        // 'deleted_at'
    ];

    protected $attributes = [
    ];

    protected $with = ['country'];
    protected function casts(): array
    {
        return [
        ];
    }
    
    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}