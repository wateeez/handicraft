<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CalculateShippingRequest;
use App\Services\ShippingCalculatorService;
use App\Models\ShippingMethod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * ShippingCalculatorController
 * 
 * API Controller for calculating shipping charges based on product dimensions and weight.
 * Handles AJAX requests from the frontend and returns structured JSON responses.
 * 
 * NOTE: The system uses FIXED TIER PRICING. Each weight range has a fixed price,
 * not a per-kilogram rate. The price_per_kg_* columns store the fixed tier price.
 */
class ShippingCalculatorController extends Controller
{
    /**
     * @var ShippingCalculatorService
     */
    protected ShippingCalculatorService $shippingCalculator;

    /**
     * Create a new controller instance.
     *
     * @param ShippingCalculatorService $shippingCalculator
     */
    public function __construct(ShippingCalculatorService $shippingCalculator)
    {
        $this->shippingCalculator = $shippingCalculator;
    }

    /**
     * Calculate shipping charge for a single product.
     *
     * Receives product dimensions (length, width, height in cm) and actual weight (in kg).
     * Calculates volumetric weight using: (length + 3) * (width + 3) * (height + 3) / 5000
     * Uses the greater of actual vs volumetric weight to determine the tier.
     * Returns the FIXED PRICE for that tier (not weight × price).
     *
     * @param CalculateShippingRequest $request
     * @return JsonResponse
     * 
     * @example Request:
     * POST /api/shipping/calculate
     * {
     *     "length": 30,
     *     "width": 20,
     *     "height": 15,
     *     "actual_weight": 2.5,
     *     "shipping_method_id": 1 (optional)
     * }
     * 
     * @example Response:
     * {
     *     "success": true,
     *     "data": {
     *         "shipping_charge": 18.00,
     *         "weight_used": 2.73,
     *         "weight_type": "volumetric",
     *         "volumetric_weight": 2.73,
     *         "actual_weight": 2.5,
     *         "rate_tier": {
     *             "id": 1,
     *             "min_weight": 0,
     *             "max_weight": 5,
     *             "price_per_kg": 18.00
     *         },
     *         "shipping_method": {
     *             "id": 1,
     *             "name": "DHL Express",
     *             "code": "dhl_express",
     *             "estimated_days": "2-3 business days"
     *         }
     *     },
     *     "message": "Shipping charge calculated successfully."
     * }
     */
    public function calculate(CalculateShippingRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $result = $this->shippingCalculator->calculate(
            (float) $validated['length'],
            (float) $validated['width'],
            (float) $validated['height'],
            (float) $validated['actual_weight'],
            $validated['shipping_method_id'] ?? null
        );

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json([
            'success' => $result['success'],
            'data' => [
                'shipping_charge' => $result['shipping_charge'],
                'weight_used' => $result['weight_used'],
                'weight_type' => $result['weight_type'],
                'volumetric_weight' => $result['volumetric_weight'],
                'actual_weight' => $result['actual_weight'],
                'rate_tier' => $result['rate_tier'],
                'shipping_method' => $result['shipping_method'],
            ],
            'message' => $result['message'],
        ], $statusCode);
    }

    /**
     * Calculate shipping for multiple items (cart calculation).
     *
     * @param Request $request
     * @return JsonResponse
     * 
     * @example Request:
     * POST /api/shipping/calculate-cart
     * {
     *     "items": [
     *         {
     *             "length": 30,
     *             "width": 20,
     *             "height": 15,
     *             "actual_weight": 2.5,
     *             "quantity": 2
     *         },
     *         {
     *             "length": 10,
     *             "width": 10,
     *             "height": 10,
     *             "actual_weight": 0.5,
     *             "quantity": 1
     *         }
     *     ],
     *     "shipping_method_id": 1
     * }
     */
    public function calculateCart(Request $request): JsonResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.length' => 'required|numeric|min:0.01',
            'items.*.width' => 'required|numeric|min:0.01',
            'items.*.height' => 'required|numeric|min:0.01',
            'items.*.actual_weight' => 'required|numeric|min:0.01',
            'items.*.quantity' => 'nullable|integer|min:1',
            'shipping_method_id' => 'nullable|integer|exists:shipping_methods,id',
        ]);

        $result = $this->shippingCalculator->calculateForMultipleItems(
            $request->input('items'),
            $request->input('shipping_method_id')
        );

        $statusCode = $result['success'] ? 200 : 422;

        return response()->json([
            'success' => $result['success'],
            'data' => $result,
            'message' => $result['message'],
        ], $statusCode);
    }

    /**
     * Get available shipping methods with their rate tiers.
     *
     * @return JsonResponse
     */
    public function getMethods(): JsonResponse
    {
        $methods = ShippingMethod::active()
            ->with([
                'rates' => function ($query) {
                    $query->orderBy('min_weight');
                }
            ])
            ->orderBy('display_order')
            ->get()
            ->map(function ($method) {
                return [
                    'id' => $method->id,
                    'name' => $method->name,
                    'code' => $method->code,
                    'description' => $method->description,
                    'estimated_days' => $method->estimated_days,
                    'rates' => $method->rates->map(function ($rate) {
                        return [
                            'id' => $rate->id,
                            'min_weight' => (float) $rate->min_weight,
                            'max_weight' => (float) $rate->max_weight,
                            'price_per_kg_actual' => (float) $rate->price_per_kg_actual,
                            'price_per_kg_volumetric' => (float) $rate->price_per_kg_volumetric,
                        ];
                    }),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $methods,
            'message' => 'Shipping methods retrieved successfully.',
        ]);
    }

    /**
     * Get a quick quote without full validation (for preview purposes).
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function quickQuote(Request $request): JsonResponse
    {
        $length = (float) $request->input('length', 0);
        $width = (float) $request->input('width', 0);
        $height = (float) $request->input('height', 0);
        $actualWeight = (float) $request->input('actual_weight', 0);

        if ($length <= 0 || $width <= 0 || $height <= 0 || $actualWeight <= 0) {
            return response()->json([
                'success' => false,
                'data' => null,
                'message' => 'Invalid dimensions or weight provided.',
            ], 400);
        }

        // Calculate volumetric weight
        $volumetricWeight = $this->shippingCalculator->calculateVolumetricWeight(
            $length,
            $width,
            $height
        );

        $chargeableWeight = max($actualWeight, $volumetricWeight);
        $weightType = $chargeableWeight === $volumetricWeight ? 'volumetric' : 'actual';

        return response()->json([
            'success' => true,
            'data' => [
                'volumetric_weight' => $volumetricWeight,
                'actual_weight' => round($actualWeight, 2),
                'chargeable_weight' => round($chargeableWeight, 2),
                'weight_type' => $weightType,
            ],
            'message' => 'Quick quote calculated.',
        ]);
    }
}
