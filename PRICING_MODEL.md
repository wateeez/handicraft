# ⚠️ IMPORTANT: Shipping Rate Pricing Model

## Fixed Tier Pricing (Current Implementation)

The shipping calculator uses **FIXED TIER PRICING**, not per-kilogram rates.

### How It Works

1. **Calculate Volumetric Weight**: `(L+3) × (W+3) × (H+3) / 5000`
2. **Compare Weights**: `chargeableWeight = max(actualWeight, volumetricWeight)`
3. **Find Matching Tier**: Match the chargeable weight to a tier where `min_weight ≤ weight ≤ max_weight`
4. **Return Fixed Price**: Return the fixed price for that tier (NOT weight × price)

### Database Schema

Despite the column names `price_per_kg_actual` and `price_per_kg_volumetric`, these store **FIXED TIER PRICES**:

```sql
CREATE TABLE shipping_rates (
    id INT PRIMARY KEY AUTO_INCREMENT,
    shipping_method_id INT NOT NULL,
    min_weight DECIMAL(10, 2) NOT NULL,
    max_weight DECIMAL(10, 2) NOT NULL,
    price_per_kg_actual DECIMAL(10, 2) NOT NULL,      -- FIXED PRICE for actual weight
    price_per_kg_volumetric DECIMAL(10, 2) NOT NULL,  -- FIXED PRICE for volumetric weight
    ...
);
```

### Example Calculation

**Input:**
- Length: 30 cm
- Width: 20 cm  
- Height: 15 cm
- Actual Weight: 2.5 kg

**Process:**
```
Step 1: Calculate Volumetric Weight
  = (30+3) × (20+3) × (15+3) / 5000
  = 33 × 23 × 18 / 5000
  = 2.73 kg

Step 2: Determine Chargeable Weight
  = max(2.5, 2.73)
  = 2.73 kg (volumetric is higher)

Step 3: Find Matching Tier
  Tier: 0-5 kg
  - price_per_kg_actual = $15.00
  - price_per_kg_volumetric = $18.00

Step 4: Return Fixed Price
  Since we're using volumetric weight:
  Shipping Charge = $18.00 (FIXED)
  
  NOT: 2.73 × $18.00 = $49.14 ❌
```

### Sample Data

```sql
-- For DHL Express (shipping_method_id = 1)
INSERT INTO shipping_rates VALUES
(1, 1, 0, 5, 15.00, 18.00),    -- 0-5 kg: $15 (actual) or $18 (volumetric)
(2, 1, 5, 10, 25.00, 30.00),   -- 5-10 kg: $25 (actual) or $30 (volumetric)
(3, 1, 10, 20, 40.00, 50.00);  -- 10-20 kg: $40 (actual) or $50 (volumetric)
```

### Examples

| Weight | Type | Tier | Fixed Price |
|--------|------|------|-------------|
| 2.5 kg (actual) | Actual | 0-5 kg | $15.00 |
| 2.73 kg (volumetric) | Volumetric | 0-5 kg | $18.00 |
| 7.0 kg (actual) | Actual | 5-10 kg | $25.00 |
| 8.5 kg (volumetric) | Volumetric | 5-10 kg | $30.00 |

### Why Two Prices Per Tier?

Different pricing for actual vs volumetric weight allows you to:
- Charge more for bulky items (volumetric)
- Charge less for dense items (actual)
- Incentivize efficient packaging

### Code Implementation

```php
// ShippingRate.php
public function calculateCharge(float $weight, bool $isVolumetric = false): float
{
    // Return the fixed price for this tier (NOT multiplied by weight)
    $fixedPrice = $isVolumetric 
        ? $this->price_per_kg_volumetric 
        : $this->price_per_kg_actual;

    return round((float) $fixedPrice, 2);
}
```

---

## Alternative: Per-Kilogram Pricing

If you want to switch to **per-kilogram pricing** (charge = weight × rate):

1. Update `ShippingRate::calculateCharge()`:
```php
public function calculateCharge(float $weight, bool $isVolumetric = false): float
{
    $pricePerKg = $isVolumetric 
        ? $this->price_per_kg_volumetric 
        : $this->price_per_kg_actual;

    return round($weight * $pricePerKg, 2);
}
```

2. Update your database values to be per-kg rates instead of fixed prices.

---

## Summary

✅ **Current Model**: Fixed Tier Pricing  
✅ **Charge Calculation**: Fixed price from matched tier  
✅ **Database Values**: Total price for the tier  
✅ **Example**: 2.73 kg (volumetric) in 0-5 kg tier = **$18.00**

❌ **NOT**: 2.73 × $18.00 = $49.14
