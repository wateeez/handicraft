# Shipping Calculator - Quick Start Guide

## Installation & Setup

### Step 1: Verify Files

Ensure all files are in place:

```
✓ app/Models/ShippingRate.php
✓ app/Models/ShippingMethod.php
✓ app/Http/Controllers/Api/ShippingCalculatorController.php
✓ app/Http/Requests/CalculateShippingRequest.php
✓ app/Services/ShippingCalculatorService.php
✓ routes/api.php
✓ public/js/shipping-calculator.js
✓ public/css/shipping-calculator.css
✓ tests/Feature/ShippingCalculatorTest.php
```

### Step 2: Database Setup

Your database already has the required tables from the existing schema:
- `shipping_methods`
- `shipping_rates`

Verify data exists:

```sql
SELECT * FROM shipping_methods WHERE is_active = 1;
SELECT * FROM shipping_rates;
```

### Step 3: Test the API

Run the test suite:

```bash
php artisan test --filter ShippingCalculatorTest
```

Or test manually with curl:

```bash
curl -X POST http://localhost:8000/api/shipping/calculate \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "length": 30,
    "width": 20,
    "height": 15,
    "actual_weight": 2.5
  }'
```

### Step 4: Add to Your Frontend

#### Option A: Include in Blade Template

```blade
@extends('layouts.app')

@section('styles')
<link rel="stylesheet" href="{{ asset('css/shipping-calculator.css') }}">
@endsection

@section('content')
<div class="shipping-calculator">
    <h2 class="shipping-calculator-title">Calculate Shipping Cost</h2>
    
    <form id="shippingForm" class="shipping-form">
        @csrf
        
        <div class="form-row">
            <div class="form-group">
                <label>Length <span class="unit">(cm)</span></label>
                <input type="number" name="length" step="0.01" min="0.01" required>
            </div>
            <div class="form-group">
                <label>Width <span class="unit">(cm)</span></label>
                <input type="number" name="width" step="0.01" min="0.01" required>
            </div>
            <div class="form-group">
                <label>Height <span class="unit">(cm)</span></label>
                <input type="number" name="height" step="0.01" min="0.01" required>
            </div>
        </div>
        
        <div class="form-group">
            <label>Weight <span class="unit">(kg)</span></label>
            <input type="number" name="actual_weight" step="0.01" min="0.01" required>
        </div>
        
        <button type="submit" class="btn-calculate">Calculate Shipping</button>
    </form>
    
    <div class="shipping-result"></div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('js/shipping-calculator.js') }}"></script>
<script>
    new ShippingCalculatorForm('#shippingForm');
</script>
@endpush
```

#### Option B: Standalone HTML Page

Open `public/examples/shipping-calculator-example.html` in your browser to see working examples.

### Step 5: Integration Examples

#### Product Detail Page

```javascript
// On product detail page, calculate shipping for current product
document.addEventListener('DOMContentLoaded', () => {
    const calculator = new ShippingCalculator();
    
    // Get product dimensions from page
    const productData = {
        length: parseFloat(document.getElementById('product-length').value),
        width: parseFloat(document.getElementById('product-width').value),
        height: parseFloat(document.getElementById('product-height').value),
        actual_weight: parseFloat(document.getElementById('product-weight').value)
    };
    
    // Calculate shipping
    calculator.calculate(productData).then(result => {
        document.getElementById('shipping-cost').textContent = 
            `$${result.data.shipping_charge.toFixed(2)}`;
    });
});
```

#### Shopping Cart

```javascript
// Calculate total shipping for all cart items
async function updateCartShipping() {
    const calculator = new ShippingCalculator();
    
    // Get cart items from your cart system
    const cartItems = getCartItems(); // Your function
    
    const result = await calculator.calculateCart(cartItems);
    
    document.getElementById('cart-shipping').textContent = 
        `$${result.data.shipping_charge.toFixed(2)}`;
}
```

#### Checkout Page

```javascript
// Update shipping when method changes
document.getElementById('shipping-method').addEventListener('change', async (e) => {
    const calculator = new ShippingCalculator();
    const cartItems = getCartItems();
    
    const result = await calculator.calculateCart(cartItems, e.target.value);
    
    updateCheckoutTotal(result.data.shipping_charge);
});
```

## Common Use Cases

### 1. Real-time Calculation

```javascript
new ShippingCalculatorForm('#myForm', {
    autoCalculate: true,  // Calculate as user types
    debounceDelay: 500    // Wait 500ms after typing stops
});
```

### 2. Custom Success Handler

```javascript
const calculator = new ShippingCalculator({
    onCalculate: (data) => {
        // Custom UI update
        showNotification(`Shipping: $${data.data.shipping_charge}`);
        updateOrderSummary(data.data);
    }
});
```

### 3. Error Handling

```javascript
const calculator = new ShippingCalculator({
    onError: (error) => {
        if (error.statusCode === 422) {
            // Validation errors
            showValidationErrors(error.errors);
        } else {
            // Server error
            showErrorMessage('Unable to calculate shipping. Please try again.');
        }
    }
});
```

## API Quick Reference

### Calculate Single Product
```
POST /api/shipping/calculate
Body: { length, width, height, actual_weight, shipping_method_id? }
```

### Calculate Cart
```
POST /api/shipping/calculate-cart
Body: { items: [...], shipping_method_id? }
```

### Get Methods
```
GET /api/shipping/methods
```

### Quick Quote
```
GET /api/shipping/quick-quote?length=30&width=20&height=15&actual_weight=2.5
```

## Troubleshooting

### Issue: "CSRF token mismatch"
**Solution**: Add meta tag to your layout:
```html
<meta name="csrf-token" content="{{ csrf_token() }}">
```

### Issue: "No shipping rate found"
**Solution**: Check your `shipping_rates` table has appropriate weight ranges.

### Issue: JavaScript not working
**Solution**: Check browser console for errors and verify file paths.

### Issue: Calculation returns 0
**Solution**: Verify `price_per_kg_actual` and `price_per_kg_volumetric` are set in database.

## Next Steps

1. ✅ Test the API endpoints
2. ✅ Integrate into your product pages
3. ✅ Add to shopping cart
4. ✅ Include in checkout process
5. ✅ Customize the UI to match your design
6. ✅ Set up monitoring and logging

## Support

For detailed documentation, see `SHIPPING_CALCULATOR_README.md`

For examples, open `public/examples/shipping-calculator-example.html`

For tests, run `php artisan test --filter ShippingCalculatorTest`
