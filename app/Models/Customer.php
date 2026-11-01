<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'full_name',
        'mobile_number',
        'email',
        'father_name',
    ];

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}