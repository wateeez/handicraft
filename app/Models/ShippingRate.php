<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * ShippingRate Model
 * 
 * Represents shipping rate tiers based on weight ranges.
 * Used to calculate shipping costs based on actual or volumetric weight.
 *
 * @property int $id
 * @property int $shipping_method_id
 * @property float $min_weight
 * @property float $max_weight
 * @property float $price_per_kg_actual
 * @property float $price_per_kg_volumetric
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ShippingRate extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'shipping_rates';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'shipping_method_id',
        'min_weight',
        'max_weight',
        'price_per_kg_actual',
        'price_per_kg_volumetric',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'min_weight' => 'decimal:2',
        'max_weight' => 'decimal:2',
        'price_per_kg_actual' => 'decimal:2',
        'price_per_kg_volumetric' => 'decimal:2',
    ];

    /**
     * Get the shipping method that owns this rate.
     *
     * @return BelongsTo
     */
    public function shippingMethod(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class);
    }

    /**
     * Find the applicable shipping rate for a given weight and shipping method.
     *
     * @param float $weight The weight to match against rate tiers
     * @param int|null $shippingMethodId Optional shipping method ID to filter by
     * @return ShippingRate|null
     */
    public static function findByWeight(float $weight, ?int $shippingMethodId = null): ?self
    {
        $query = self::where('min_weight', '<=', $weight)
            ->where('max_weight', '>=', $weight);

        if ($shippingMethodId) {
            $query->where('shipping_method_id', $shippingMethodId);
        }

        return $query->first();
    }

    /**
     * Calculate shipping charge based on weight type.
     * 
     * NOTE: Despite the column names (price_per_kg_*), these are FIXED TIER PRICES,
     * not per-kilogram rates. The price is returned as-is for the matched tier.
     *
     * @param float $weight The weight value (not used in calculation, only for tier matching)
     * @param bool $isVolumetric Whether the weight is volumetric
     * @return float Fixed price for this tier
     */
    public function calculateCharge(float $weight, bool $isVolumetric = false): float
    {
        // Return the fixed price for this tier (not multiplied by weight)
        $fixedPrice = $isVolumetric
            ? $this->price_per_kg_volumetric
            : $this->price_per_kg_actual;

        return round((float) $fixedPrice, 2);
    }
}
