<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceMap extends Model
{
    protected $fillable = [
        'marketplace',
        'our_attr_id',
        'marketplace_attr_name'
    ];

    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'our_attr_id');
    }
}