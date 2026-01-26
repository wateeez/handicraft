# Shipping Calculator - Complete Documentation

## Overview

A production-ready AJAX-based shipping charge calculator for e-commerce applications. The system calculates shipping costs based on product dimensions (length, width, height in cm) and actual weight (in kg), using volumetric weight comparison to determine the final charge.

### Key Features

- ✅ **Volumetric Weight Calculation**: Uses formula `(length + 3) * (width + 3) * (height + 3) / 5000`
- ✅ **Smart Weight Selection**: Automatically uses the greater of actual vs volumetric weight
- ✅ **Database-Driven Rates**: Matches weight to appropriate tier in `shipping_rates` table
- ✅ **AJAX/Fetch API**: Real-time calculations without page reload
- ✅ **Comprehensive Validation**: Server-side and client-side validation
- ✅ **Multiple Shipping Methods**: Support for DHL, Aramex, FedEx, etc.
- ✅ **Cart Calculation**: Calculate shipping for multiple items
- ✅ **Laravel Best Practices**: Form Requests, Services, Models, Controllers
- ✅ **Security**: CSRF protection, input sanitization, SQL injection prevention
- ✅ **Error Handling**: Detailed error messages and logging
- ✅ **Premium UI**: Modern, responsive design with dark mode support

---

## Architecture

### Backend Components

```
app/
├── Models/
│   ├── ShippingRate.php          # Database model for shipping rates
│   └── ShippingMethod.php        # Database model for shipping methods
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       └── ShippingCalculatorController.php  # API endpoints
│   └── Requests/
│       └── CalculateShippingRequest.php          # Validation rules
└── Services/
    └── ShippingCalculatorService.php             # Business logic
```

### Frontend Components

```
public/
├── js/
│   └── shipping-calculator.js    # JavaScript AJAX module
├── css/
│   └── shipping-calculator.css   # Premium styles
└── examples/
    └── shipping-calculator-example.html  # Usage examples
```

### Database Schema

```sql
-- shipping_methods table
CREATE TABLE shipping_methods (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    code VARCHAR(50) UNIQUE NOT NULL,
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    display_order INT DEFAULT 0,
    estimated_days VARCHAR(50)
);

-- shipping_rates table
CREATE TABLE shipping_rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shipping_method_id INT NOT NULL,
    min_weight DECIMAL(10, 2) NOT NULL,
    max_weight DECIMAL(10, 2) NOT NULL,
    price_per_kg_actual DECIMAL(10, 2) NOT NULL,
    price_per_kg_volumetric DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (shipping_method_id) REFERENCES shipping_methods(id)
);
```

---

## API Endpoints

### 1. Calculate Shipping (Single Product)

**Endpoint**: `POST /api/shipping/calculate`

**Request Body**:
```json
{
    "length": 30,
    "width": 20,
    "height": 15,
    "actual_weight": 2.5,
    "shipping_method_id": 1
}
```

**Response** (Success - 200):
```json
{
    "success": true,
    "data": {
        "shipping_charge": 37.50,
        "weight_used": 2.5,
        "weight_type": "actual",
        "volumetric_weight": 1.62,
        "actual_weight": 2.5,
        "rate_tier": {
            "id": 1,
            "min_weight": 0,
            "max_weight": 5,
            "price_per_kg": 15.00
        },
        "shipping_method": {
            "id": 1,
            "name": "DHL Express",
            "code": "dhl_express",
            "estimated_days": "2-3 business days"
        }
    },
    "message": "Shipping charge calculated successfully."
}
```

**Response** (Validation Error - 422):
```json
{
    "success": false,
    "message": "Validation failed.",
    "errors": {
        "length": ["The length field is required."],
        "actual_weight": ["The actual weight must be at least 0.01."]
    }
}
```

### 2. Calculate Cart Shipping (Multiple Items)

**Endpoint**: `POST /api/shipping/calculate-cart`

**Request Body**:
```json
{
    "items": [
        {
            "length": 30,
            "width": 20,
            "height": 15,
            "actual_weight": 2.5,
            "quantity": 2
        },
        {
            "length": 10,
            "width": 10,
            "height": 10,
            "actual_weight": 0.5,
            "quantity": 1
        }
    ],
    "shipping_method_id": 1
}
```

**Response**:
```json
{
    "success": true,
    "data": {
        "shipping_charge": 75.00,
        "total_weight": 5.5,
        "weight_type": "actual",
        "total_volumetric_weight": 3.24,
        "total_actual_weight": 5.5,
        "item_count": 2
    },
    "message": "Shipping charge calculated successfully."
}
```

### 3. Get Shipping Methods

**Endpoint**: `GET /api/shipping/methods`

**Response**:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "DHL Express",
            "code": "dhl_express",
            "description": "Fast international shipping",
            "estimated_days": "2-3 business days",
            "rates": [
                {
                    "id": 1,
                    "min_weight": 0,
                    "max_weight": 5,
                    "price_per_kg_actual": 15.00,
                    "price_per_kg_volumetric": 18.00
                }
            ]
        }
    ],
    "message": "Shipping methods retrieved successfully."
}
```

### 4. Quick Quote

**Endpoint**: `GET /api/shipping/quick-quote?length=30&width=20&height=15&actual_weight=2.5`

**Response**:
```json
{
    "success": true,
    "data": {
        "volumetric_weight": 1.62,
        "actual_weight": 2.5,
        "chargeable_weight": 2.5,
        "weight_type": "actual"
    },
    "message": "Quick quote calculated."
}
```

---

## JavaScript Usage

### Basic Usage

```javascript
// Initialize calculator
const calculator = new ShippingCalculator({
    baseUrl: '/api/shipping'
});

// Calculate shipping
const result = await calculator.calculate({
    length: 30,
    width: 20,
    height: 15,
    actual_weight: 2.5,
    shipping_method_id: 1
});

console.log('Shipping Charge:', result.data.shipping_charge);
```

### Form Integration

```javascript
// Automatic form binding
const form = new ShippingCalculatorForm('#shippingForm', {
    baseUrl: '/api/shipping',
    resultContainer: '.shipping-result',
    autoCalculate: false  // Set to true for real-time calculation
});
```

### With Callbacks

```javascript
const calculator = new ShippingCalculator({
    baseUrl: '/api/shipping',
    onCalculate: (data) => {
        console.log('Success:', data);
        // Update UI with shipping charge
    },
    onError: (error) => {
        console.error('Error:', error);
        // Show error message
    },
    onLoading: (isLoading) => {
        // Show/hide loading spinner
    }
});
```

### Cart Calculation

```javascript
const cartItems = [
    { length: 30, width: 20, height: 15, actual_weight: 2.5, quantity: 2 },
    { length: 10, width: 10, height: 10, actual_weight: 0.5, quantity: 1 }
];

const result = await calculator.calculateCart(cartItems, 1);
```

### Using Axios (Alternative)

```javascript
// If you prefer Axios over Fetch API
import axios from 'axios';

const response = await axios.post('/api/shipping/calculate', {
    length: 30,
    width: 20,
    height: 15,
    actual_weight: 2.5,
    shipping_method_id: 1
}, {
    headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
    }
});

console.log(response.data);
```

---

## HTML Integration

### Basic Form

```html
<!DOCTYPE html>
<html>
<head>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="stylesheet" href="/css/shipping-calculator.css">
</head>
<body>
    <div class="shipping-calculator">
        <h2 class="shipping-calculator-title">Calculate Shipping</h2>
        
        <form id="shippingForm" class="shipping-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="length">Length <span class="unit">(cm)</span></label>
                    <input type="number" name="length" required>
                </div>
                <div class="form-group">
                    <label for="width">Width <span class="unit">(cm)</span></label>
                    <input type="number" name="width" required>
                </div>
                <div class="form-group">
                    <label for="height">Height <span class="unit">(cm)</span></label>
                    <input type="number" name="height" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="actual_weight">Weight <span class="unit">(kg)</span></label>
                <input type="number" name="actual_weight" required>
            </div>
            
            <button type="submit" class="btn-calculate">Calculate Shipping</button>
        </form>
        
        <div class="shipping-result"></div>
    </div>

    <script src="/js/shipping-calculator.js"></script>
    <script>
        new ShippingCalculatorForm('#shippingForm');
    </script>
</body>
</html>
```

### Laravel Blade Template

```blade
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="shipping-calculator">
        <h2 class="shipping-calculator-title">Calculate Shipping Cost</h2>
        
        <form id="shippingForm" class="shipping-form">
            @csrf
            
            <div class="form-row">
                <div class="form-group">
                    <label for="length">Length <span class="unit">(cm)</span></label>
                    <input type="number" name="length" id="length" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="width">Width <span class="unit">(cm)</span></label>
                    <input type="number" name="width" id="width" step="0.01" min="0.01" required>
                </div>
                <div class="form-group">
                    <label for="height">Height <span class="unit">(cm)</span></label>
                    <input type="number" name="height" id="height" step="0.01" min="0.01" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="actual_weight">Actual Weight <span class="unit">(kg)</span></label>
                <input type="number" name="actual_weight" id="actual_weight" step="0.01" min="0.01" required>
            </div>
            
            <div class="form-group">
                <label for="shipping_method_id">Shipping Method</label>
                <select name="shipping_method_id" id="shipping_method_id">
                    <option value="">Select method...</option>
                    @foreach($shippingMethods as $method)
                        <option value="{{ $method->id }}">{{ $method->name }} - {{ $method->estimated_days }}</option>
                    @endforeach
                </select>
            </div>
            
            <button type="submit" class="btn-calculate">Calculate Shipping</button>
        </form>
        
        <div class="shipping-result"></div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/shipping-calculator.js') }}"></script>
<script>
    new ShippingCalculatorForm('#shippingForm', {
        baseUrl: '{{ url('/api/shipping') }}'
    });
</script>
@endpush
```

---

## Calculation Logic

### Volumetric Weight Formula

```
volumetric_weight = (length + 3) * (width + 3) * (height + 3) / 5000
```

**Example**:
- Length: 30 cm
- Width: 20 cm
- Height: 15 cm

```
volumetric_weight = (30 + 3) * (20 + 3) * (15 + 3) / 5000
                  = 33 * 23 * 18 / 5000
                  = 13,662 / 5000
                  = 2.73 kg
```

### Weight Selection

The system compares actual weight vs volumetric weight and uses the **greater value** for pricing:

```php
$chargeableWeight = max($actualWeight, $volumetricWeight);
```

**Example 1** (Actual weight is higher):
- Actual: 5 kg
- Volumetric: 2.73 kg
- **Used**: 5 kg (actual)

**Example 2** (Volumetric weight is higher):
- Actual: 1 kg
- Volumetric: 2.73 kg
- **Used**: 2.73 kg (volumetric)

### Rate Matching

The system finds the appropriate rate tier where:
```
min_weight <= chargeable_weight <= max_weight
```

Then calculates the charge:
```php
$shippingCharge = $chargeableWeight * $pricePerKg;
```

---

## Security Considerations

### CSRF Protection

All POST requests require CSRF token:

```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

```javascript
headers: {
    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
}
```

### Input Validation

**Server-side** (CalculateShippingRequest):
- Required fields validation
- Numeric type validation
- Min/max value constraints
- Database existence checks

**Client-side** (JavaScript):
- HTML5 input validation
- Custom validation before submission
- Error message display

### SQL Injection Prevention

Uses Laravel's Eloquent ORM with parameter binding:

```php
ShippingRate::where('min_weight', '<=', $weight)
    ->where('max_weight', '>=', $weight)
    ->first();
```

### XSS Prevention

Output escaping in JavaScript:

```javascript
escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
```

---

## Testing

### Manual Testing

1. **Valid Calculation**:
```bash
curl -X POST http://localhost/api/shipping/calculate \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-token" \
  -d '{
    "length": 30,
    "width": 20,
    "height": 15,
    "actual_weight": 2.5
  }'
```

2. **Validation Error**:
```bash
curl -X POST http://localhost/api/shipping/calculate \
  -H "Content-Type: application/json" \
  -H "X-CSRF-TOKEN: your-token" \
  -d '{
    "length": -5,
    "width": 20,
    "height": 15,
    "actual_weight": 2.5
  }'
```

### PHPUnit Tests

Create `tests/Feature/ShippingCalculatorTest.php`:

```php
<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

class ShippingCalculatorTest extends TestCase
{
    use RefreshDatabase;

    public function test_calculate_shipping_success()
    {
        $response = $this->postJson('/api/shipping/calculate', [
            'length' => 30,
            'width' => 20,
            'height' => 15,
            'actual_weight' => 2.5,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'shipping_charge',
                    'weight_used',
                    'weight_type',
                    'volumetric_weight',
                    'actual_weight',
                ],
                'message',
            ]);
    }

    public function test_validation_fails_with_invalid_input()
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
}
```

---

## Troubleshooting

### Common Issues

**1. CSRF Token Mismatch**
```
Solution: Ensure meta tag is present and token is sent in headers
```

**2. No Shipping Rate Found**
```
Solution: Check that shipping_rates table has appropriate weight ranges
```

**3. JavaScript Not Loading**
```
Solution: Verify file path and check browser console for errors
```

**4. Calculation Returns 0**
```
Solution: Check that price_per_kg values are set in database
```

### Debug Mode

Enable detailed logging in `.env`:

```env
APP_DEBUG=true
LOG_LEVEL=debug
```

Check logs:
```bash
tail -f storage/logs/laravel.log
```

---

## Performance Optimization

### Database Indexing

```sql
CREATE INDEX idx_weight_range ON shipping_rates(min_weight, max_weight);
CREATE INDEX idx_active_methods ON shipping_methods(is_active, display_order);
```

### Caching

Cache shipping methods:

```php
$methods = Cache::remember('shipping_methods', 3600, function () {
    return ShippingMethod::active()->with('rates')->get();
});
```

### Response Compression

Enable gzip in `.htaccess`:

```apache
<IfModule mod_deflate.c>
    AddOutputFilterByType DEFLATE application/json
</IfModule>
```

---

## Deployment Checklist

- [ ] Run migrations: `php artisan migrate`
- [ ] Seed shipping data: `php artisan db:seed --class=ShippingSeeder`
- [ ] Clear cache: `php artisan cache:clear`
- [ ] Optimize routes: `php artisan route:cache`
- [ ] Set `APP_DEBUG=false` in production
- [ ] Configure CORS if needed
- [ ] Test all API endpoints
- [ ] Verify CSRF protection
- [ ] Check SSL certificate
- [ ] Monitor error logs

---

## Support & Maintenance

### Updating Shipping Rates

```php
// Update rate via admin panel or directly
ShippingRate::where('id', 1)->update([
    'price_per_kg_actual' => 16.00,
    'price_per_kg_volumetric' => 19.00,
]);
```

### Adding New Shipping Method

```php
$method = ShippingMethod::create([
    'name' => 'UPS Express',
    'code' => 'ups_express',
    'description' => 'Fast UPS shipping',
    'estimated_days' => '1-2 business days',
    'is_active' => true,
]);

// Add rates
$method->rates()->create([
    'min_weight' => 0,
    'max_weight' => 5,
    'price_per_kg_actual' => 20.00,
    'price_per_kg_volumetric' => 23.00,
]);
```

---

## License

This shipping calculator is part of your e-commerce application and follows your project's license.

## Version

**Version**: 1.0.0  
**Last Updated**: January 26, 2026  
**Laravel Version**: 10.x+  
**PHP Version**: 8.1+
