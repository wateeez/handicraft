<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\ShippingMethod;
use App\Models\ShippingRate;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Shipping Calculator API Tests
 * 
 * Test suite for shipping calculation endpoints
 */
class ShippingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test shipping method
        $method = ShippingMethod::create([
            'name' => 'Test Express',
            'code' => 'test_express',
            'description' => 'Test shipping method',
            'is_active' => true,
            'display_order' => 1,
            'estimated_days' => '2-3 days',
        ]);

        // Create test shipping rates
        ShippingRate::create([
            'shipping_method_id' => $method->id,
            'min_weight' => 0,
            'max_weight' => 5,
            'price_per_kg_actual' => 15.00,
            'price_per_kg_volumetric' => 18.00,
        ]);

        ShippingRate::create([
            'shipping_method_id' => $method->id,
            'min_weight' => 5,
            'max_weight' => 10,
            'price_per_kg_actual' => 12.00,
            'price_per_kg_volumetric' => 15.00,
        ]);
    }

    /**
     * Test successful shipping calculation
     */
    public function test_calculate_shipping_success()
    {
        $response = $this->postJson('/api/shipping/calculate', [
            'length' => 30,
            'width' => 20,
            'height' => 15,
            'actual_weight' => 2.5,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'shipping_charge',
                    'weight_used',
                    'weight_type',
                    'volumetric_weight',
                    'actual_weight',
                    'rate_tier',
                    'shipping_method',
                ],
                'message',
            ]);

        $this->assertTrue($response['success']);
        $this->assertIsFloat($response['data']['shipping_charge']);
        $this->assertGreaterThan(0, $response['data']['shipping_charge']);
    }

    /**
     * Test calculation with specific shipping method
     */
    public function test_calculate_with_shipping_method()
    {
        $method = ShippingMethod::first();

        $response = $this->postJson('/api/shipping/calculate', [
            'length' => 30,
            'width' => 20,
            'height' => 15,
            'actual_weight' => 2.5,
            'shipping_method_id' => $method->id,
        ]);

        $response->assertStatus(200);
        $this->assertEquals($method->id, $response['data']['shipping_method']['id']);
    }

    /**
     * Test validation errors
     */
    public function test_validation_fails_with_missing_fields()
    {
        $response = $this->postJson('/api/shipping/calculate', [
            'length' => 30,
            // Missing width, height, actual_weight
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['width', 'height', 'actual_weight']);
    }

    /**
     * Test validation with negative values
     */
    public function test_validation_fails_with_negative_values()
    {
        $response = $this->postJson('/api/shipping/calculate', [
            'length' => -5,
            'width' => 20,
            'height' => 15,
            'actual_weight' => 2.5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['length']);
    }

    /**
     * Test validation with zero values
     */
    public function test_validation_fails_with_zero_values()
    {
        $response = $this->postJson('/api/shipping/calculate', [
            'length' => 0,
            'width' => 20,
            'height' => 15,
            'actual_weight' => 2.5,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['length']);
    }

    /**
     * Test volumetric weight calculation
     */
    public function test_volumetric_weight_calculation()
    {
        $response = $this->postJson('/api/shipping/calculate', [
            'length' => 30,
            'width' => 20,
            'height' => 15,
            'actual_weight' => 1.0, // Low actual weight
        ]);

        $response->assertStatus(200);

        // Expected volumetric: (30+3) * (20+3) * (15+3) / 5000 = 2.73
        $expectedVolumetric = (33 * 23 * 18) / 5000;

        $this->assertEquals(
            round($expectedVolumetric, 2),
            $response['data']['volumetric_weight']
        );

        // Should use volumetric weight since it's higher
        $this->assertEquals('volumetric', $response['data']['weight_type']);
    }

    /**
     * Test actual weight is used when higher
     */
    public function test_actual_weight_used_when_higher()
    {
        $response = $this->postJson('/api/shipping/calculate', [
            'length' => 10,
            'width' => 10,
            'height' => 10,
            'actual_weight' => 5.0, // High actual weight
        ]);

        $response->assertStatus(200);
        $this->assertEquals('actual', $response['data']['weight_type']);
        $this->assertEquals(5.0, $response['data']['weight_used']);
    }

    /**
     * Test cart calculation
     */
    public function test_calculate_cart_shipping()
    {
        $response = $this->postJson('/api/shipping/calculate-cart', [
            'items' => [
                [
                    'length' => 30,
                    'width' => 20,
                    'height' => 15,
                    'actual_weight' => 2.5,
                    'quantity' => 2,
                ],
                [
                    'length' => 10,
                    'width' => 10,
                    'height' => 10,
                    'actual_weight' => 0.5,
                    'quantity' => 1,
                ],
            ],
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'shipping_charge',
                    'total_weight',
                    'weight_type',
                    'total_volumetric_weight',
                    'total_actual_weight',
                    'item_count',
                ],
                'message',
            ]);

        $this->assertEquals(2, $response['data']['item_count']);
    }

    /**
     * Test get shipping methods
     */
    public function test_get_shipping_methods()
    {
        $response = $this->getJson('/api/shipping/methods');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'code',
                        'description',
                        'estimated_days',
                        'rates',
                    ],
                ],
                'message',
            ]);

        $this->assertGreaterThan(0, count($response['data']));
    }

    /**
     * Test quick quote
     */
    public function test_quick_quote()
    {
        $response = $this->getJson('/api/shipping/quick-quote?' . http_build_query([
            'length' => 30,
            'width' => 20,
            'height' => 15,
            'actual_weight' => 2.5,
        ]));

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ])
            ->assertJsonStructure([
                'success',
                'data' => [
                    'volumetric_weight',
                    'actual_weight',
                    'chargeable_weight',
                    'weight_type',
                ],
                'message',
            ]);
    }

    /**
     * Test no rate found scenario
     */
    public function test_no_rate_found_for_weight()
    {
        $response = $this->postJson('/api/shipping/calculate', [
            'length' => 30,
            'width' => 20,
            'height' => 15,
            'actual_weight' => 100, // Weight outside defined ranges
        ]);

        $response->assertStatus(422);
        $this->assertFalse($response['success']);
        $this->assertNull($response['data']['shipping_charge']);
    }

    /**
     * Test invalid shipping method ID
     */
    public function test_invalid_shipping_method_id()
    {
        $response = $this->postJson('/api/shipping/calculate', [
            'length' => 30,
            'width' => 20,
            'height' => 15,
            'actual_weight' => 2.5,
            'shipping_method_id' => 9999, // Non-existent ID
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['shipping_method_id']);
    }

    /**
     * Test rate tier selection
     */
    public function test_correct_rate_tier_selected()
    {
        // Test weight in first tier (0-5 kg)
        $response1 = $this->postJson('/api/shipping/calculate', [
            'length' => 10,
            'width' => 10,
            'height' => 10,
            'actual_weight' => 3.0,
        ]);

        $response1->assertStatus(200);
        $this->assertEquals(15.00, $response1['data']['rate_tier']['price_per_kg']);

        // Test weight in second tier (5-10 kg)
        $response2 = $this->postJson('/api/shipping/calculate', [
            'length' => 10,
            'width' => 10,
            'height' => 10,
            'actual_weight' => 7.0,
        ]);

        $response2->assertStatus(200);
        $this->assertEquals(12.00, $response2['data']['rate_tier']['price_per_kg']);
    }

    /**
     * Test shipping charge calculation accuracy with FIXED TIER PRICING
     */
    public function test_shipping_charge_calculation_accuracy()
    {
        $response = $this->postJson('/api/shipping/calculate', [
            'length' => 10,
            'width' => 10,
            'height' => 10,
            'actual_weight' => 3.0,
        ]);

        $response->assertStatus(200);

        // Weight used: 3.0 kg (actual)
        // Fixed tier price: $15.00 (for 0-5kg tier, actual weight)
        // Expected charge: $15.00 (NOT 3.0 × 15.00)
        $this->assertEquals(15.00, $response['data']['shipping_charge']);
    }
}
