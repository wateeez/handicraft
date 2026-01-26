<?php

namespace App\Services;

use App\Models\ShippingRate;
use App\Models\ShippingMethod;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

/**
 * ShippingCalculatorService
 * 
 * Service class for calculating shipping charges based on product dimensions and weight.
 * Implements the volumetric weight formula: (length + 3) * (width + 3) * (height + 3) / 5000
 * 
 * Uses the greater of actual weight vs volumetric weight to determine the tier.
 * Returns FIXED TIER PRICE (not per-kg rate) from the matched shipping_rates tier.
 */
class ShippingCalculatorService
{
    /**
     * Volumetric divisor constant (industry standard for air freight).
     */
    protected const VOLUMETRIC_DIVISOR = 5000;

    /**
     * Dimensional padding in cm (added to each dimension).
     */
    protected const DIMENSION_PADDING = 3;

    /**
     * Calculate shipping charge for a product.
     *
     * @param float $length Length in centimeters
     * @param float $width Width in centimeters
     * @param float $height Height in centimeters
     * @param float $actualWeight Actual weight in kilograms
     * @param int|null $shippingMethodId Optional shipping method ID
     * @return array{
     *     success: bool,
     *     shipping_charge: float|null,
     *     weight_used: float,
     *     weight_type: string,
     *     volumetric_weight: float,
     *     actual_weight: float,
     *     rate_tier: array|null,
     *     shipping_method: array|null,
     *     message: string
     * }
     */
    public function calculate(
        float $length,
        float $width,
        float $height,
        float $actualWeight,
        ?int $shippingMethodId = null
    ): array {
        try {
            // Validate inputs
            $this->validateInputs($length, $width, $height, $actualWeight);

            // Calculate volumetric weight using the formula:
            // (length + 3) * (width + 3) * (height + 3) / 5000
            $volumetricWeight = $this->calculateVolumetricWeight($length, $width, $height);

            // Determine which weight to use for pricing (greater of the two)
            $chargeableWeight = max($actualWeight, $volumetricWeight);
            $weightType = $chargeableWeight === $volumetricWeight ? 'volumetric' : 'actual';
            $isVolumetric = $weightType === 'volumetric';

            // Find applicable shipping rate
            $shippingRate = ShippingRate::findByWeight($chargeableWeight, $shippingMethodId);

            if (!$shippingRate) {
                Log::warning('No shipping rate found for weight', [
                    'chargeable_weight' => $chargeableWeight,
                    'shipping_method_id' => $shippingMethodId,
                ]);

                return [
                    'success' => false,
                    'shipping_charge' => null,
                    'weight_used' => round($chargeableWeight, 2),
                    'weight_type' => $weightType,
                    'volumetric_weight' => round($volumetricWeight, 2),
                    'actual_weight' => round($actualWeight, 2),
                    'rate_tier' => null,
                    'shipping_method' => null,
                    'message' => 'No shipping rate available for the calculated weight. Please contact support.',
                ];
            }

            // Calculate shipping charge
            $shippingCharge = $shippingRate->calculateCharge($chargeableWeight, $isVolumetric);

            // Get shipping method details
            $shippingMethod = $shippingRate->shippingMethod;

            Log::info('Shipping charge calculated', [
                'length' => $length,
                'width' => $width,
                'height' => $height,
                'actual_weight' => $actualWeight,
                'volumetric_weight' => $volumetricWeight,
                'chargeable_weight' => $chargeableWeight,
                'weight_type' => $weightType,
                'shipping_charge' => $shippingCharge,
            ]);

            return [
                'success' => true,
                'shipping_charge' => $shippingCharge,
                'weight_used' => round($chargeableWeight, 2),
                'weight_type' => $weightType,
                'volumetric_weight' => round($volumetricWeight, 2),
                'actual_weight' => round($actualWeight, 2),
                'rate_tier' => [
                    'id' => $shippingRate->id,
                    'min_weight' => (float) $shippingRate->min_weight,
                    'max_weight' => (float) $shippingRate->max_weight,
                    'price_per_kg' => $isVolumetric
                        ? (float) $shippingRate->price_per_kg_volumetric
                        : (float) $shippingRate->price_per_kg_actual,
                ],
                'shipping_method' => $shippingMethod ? [
                    'id' => $shippingMethod->id,
                    'name' => $shippingMethod->name,
                    'code' => $shippingMethod->code,
                    'estimated_days' => $shippingMethod->estimated_days,
                ] : null,
                'message' => 'Shipping charge calculated successfully.',
            ];

        } catch (InvalidArgumentException $e) {
            Log::error('Invalid shipping calculation input', [
                'error' => $e->getMessage(),
                'length' => $length,
                'width' => $width,
                'height' => $height,
                'actual_weight' => $actualWeight,
            ]);

            return [
                'success' => false,
                'shipping_charge' => null,
                'weight_used' => 0,
                'weight_type' => 'unknown',
                'volumetric_weight' => 0,
                'actual_weight' => round($actualWeight, 2),
                'rate_tier' => null,
                'shipping_method' => null,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Calculate volumetric weight using the formula:
     * (length + 3) * (width + 3) * (height + 3) / 5000
     *
     * @param float $length Length in centimeters
     * @param float $width Width in centimeters
     * @param float $height Height in centimeters
     * @return float Volumetric weight in kilograms
     */
    public function calculateVolumetricWeight(float $length, float $width, float $height): float
    {
        $volumetricWeight = (
            ($length + self::DIMENSION_PADDING) *
            ($width + self::DIMENSION_PADDING) *
            ($height + self::DIMENSION_PADDING)
        ) / self::VOLUMETRIC_DIVISOR;

        return round($volumetricWeight, 2);
    }

    /**
     * Calculate shipping for multiple products/items.
     *
     * @param array $items Array of items with length, width, height, actual_weight, quantity
     * @param int|null $shippingMethodId Optional shipping method ID
     * @return array
     */
    public function calculateForMultipleItems(array $items, ?int $shippingMethodId = null): array
    {
        $totalActualWeight = 0;
        $totalVolumetricWeight = 0;

        foreach ($items as $item) {
            $quantity = $item['quantity'] ?? 1;

            $totalActualWeight += ($item['actual_weight'] ?? 0) * $quantity;

            if (isset($item['length'], $item['width'], $item['height'])) {
                $volumetricWeight = $this->calculateVolumetricWeight(
                    $item['length'],
                    $item['width'],
                    $item['height']
                );
                $totalVolumetricWeight += $volumetricWeight * $quantity;
            }
        }

        // Use the first item's dimensions for the calculation if calculating as single shipment
        // Or calculate based on totals
        $chargeableWeight = max($totalActualWeight, $totalVolumetricWeight);
        $weightType = $chargeableWeight === $totalVolumetricWeight ? 'volumetric' : 'actual';
        $isVolumetric = $weightType === 'volumetric';

        $shippingRate = ShippingRate::findByWeight($chargeableWeight, $shippingMethodId);

        if (!$shippingRate) {
            return [
                'success' => false,
                'shipping_charge' => null,
                'total_weight' => round($chargeableWeight, 2),
                'weight_type' => $weightType,
                'total_volumetric_weight' => round($totalVolumetricWeight, 2),
                'total_actual_weight' => round($totalActualWeight, 2),
                'item_count' => count($items),
                'message' => 'No shipping rate available for the total weight.',
            ];
        }

        $shippingCharge = $shippingRate->calculateCharge($chargeableWeight, $isVolumetric);

        return [
            'success' => true,
            'shipping_charge' => $shippingCharge,
            'total_weight' => round($chargeableWeight, 2),
            'weight_type' => $weightType,
            'total_volumetric_weight' => round($totalVolumetricWeight, 2),
            'total_actual_weight' => round($totalActualWeight, 2),
            'item_count' => count($items),
            'message' => 'Shipping charge calculated successfully.',
        ];
    }

    /**
     * Validate calculation inputs.
     *
     * @param float $length
     * @param float $width
     * @param float $height
     * @param float $actualWeight
     * @throws InvalidArgumentException
     */
    protected function validateInputs(
        float $length,
        float $width,
        float $height,
        float $actualWeight
    ): void {
        if ($length <= 0) {
            throw new InvalidArgumentException('Length must be greater than zero.');
        }
        if ($width <= 0) {
            throw new InvalidArgumentException('Width must be greater than zero.');
        }
        if ($height <= 0) {
            throw new InvalidArgumentException('Height must be greater than zero.');
        }
        if ($actualWeight <= 0) {
            throw new InvalidArgumentException('Actual weight must be greater than zero.');
        }
    }
}
