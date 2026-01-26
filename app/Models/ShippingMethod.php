<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * ShippingMethod Model
 * 
 * Represents shipping carriers/methods (DHL, Aramex, FedEx, etc.)
 *
 * @property int $id
 * @property string $name
 * @property string $code
 * @property string|null $description
 * @property bool $is_active
 * @property int $display_order
 * @property string|null $estimated_days
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ShippingMethod extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shipping_methods';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
        'display_order',
        'estimated_days',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_active' => 'boolean',
        'display_order' => 'integer',
    ];

    /**
     * Get all shipping rates for this method.
     *
     * @return HasMany
     */
    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    /**
     * Scope to only include active shipping methods.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get ordered list of active shipping methods.
     *
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getActiveOrdered()
    {
        return self::active()
            ->orderBy('display_order')
            ->get();
    }
}
