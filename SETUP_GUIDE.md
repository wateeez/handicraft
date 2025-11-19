# E-COMMERCE QUICK SETUP GUIDE

## Step-by-Step Installation Instructions

### 1. Database Setup

**Create the database:**
```sql
CREATE DATABASE ecommerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

**Import the schema:**
```bash
# Windows PowerShell
Get-Content database\schema.sql | mysql -u root -p ecommerce_db

# Or use phpMyAdmin to import database/schema.sql
```

### 2. Configuration

**Edit `config/database_config.php`:**
```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_db');
define('DB_USER', 'root');        // Your MySQL username
define('DB_PASS', '');            // Your MySQL password
```

### 3. Directory Permissions

**Create upload directories:**
```powershell
New-Item -Path "public\uploads\products" -ItemType Directory -Force
New-Item -Path "public\uploads\categories" -ItemType Directory -Force
```

### 4. Access the Application

**Frontend (Customer Site):**
```
http://localhost/shop.php
```

**Admin Panel:**
```
http://localhost/admin/login.php
Username: admin
Password: admin123
```

### 5. Initial Configuration

After logging into admin panel:

1. **Add Categories**
   - Go to Admin → Categories
   - Click "Add Category"
   - Example: Electronics, Fashion, Home & Garden

2. **Add Subcategories**
   - Click "Add Subcategory"
   - Select parent category
   - Example: Under Electronics → Laptops, Phones, Accessories

3. **Configure Shipping Methods**
   - Go to Admin → Shipping
   - Default methods (DHL, Aramex, etc.) are pre-configured
   - Add rate tiers for each method
   - Example for DHL:
     * 0-5 kg: Base $10, Actual $15/kg, Volumetric $18/kg
     * 5-10 kg: Base $10, Actual $12/kg, Volumetric $15/kg

4. **Add Products**
   
   **Option A - Manual:**
   - Go to Admin → Products → Add Product
   - Fill in all details
   - Upload images
   - Enter dimensions (cm) and weight (kg)
   
   **Option B - Bulk Upload:**
   - Go to Admin → Products → Bulk Upload
   - Download CSV template
   - Fill in product data
   - Upload completed CSV

5. **Create Discount Coupons** (Optional)
   - Go to Admin → Coupons
   - Example:
     * Code: WELCOME10
     * Type: Percentage
     * Value: 10
     * Min Purchase: $50

## Testing the System

### Test Product with Shipping Calculation

**Add a test product:**
- Name: Sample Product
- Price: $99.99
- Dimensions: 30cm × 20cm × 10cm
- Weight: 2.5 kg

**Shipping Calculation:**
1. Shipping dimensions: (30+8) × (20+8) × (10+8) = 38 × 28 × 18 cm
2. Volumetric weight: (38 × 28 × 18) / 5000 = 3.83 kg
3. Chargeable weight: max(2.5kg actual, 3.83kg volumetric) = **3.83 kg**
4. For DHL (0-5kg tier): $10 base + (3.83 × $18) = **$78.94**

### Test Checkout Flow

1. Add products to cart
2. Go to Cart → Proceed to Checkout
3. Fill in customer and address information
4. Select shipping method (costs will be calculated)
5. Apply coupon code (if any)
6. Select payment method
7. Place order
8. View order confirmation

## Common Issues & Solutions

### Issue: "Database connection failed"
**Solution:**
- Check MySQL service is running
- Verify credentials in `config/database_config.php`
- Ensure database exists

### Issue: "Images not uploading"
**Solution:**
```powershell
# Set proper permissions
icacls "public\uploads" /grant Users:F /T
```

### Issue: "Session errors"
**Solution:**
```powershell
# Ensure session directory exists
New-Item -Path "C:\Windows\Temp" -ItemType Directory -Force
```

### Issue: "Cannot access admin panel"
**Solution:**
- Clear browser cache and cookies
- Try password reset (run this SQL):
```sql
UPDATE admin_users SET password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'admin';
-- This resets password to: admin123
```

## Production Deployment Checklist

- [ ] Change default admin password
- [ ] Update database credentials
- [ ] Set `APP_ENV` to 'production' in config
- [ ] Disable error display (set in init.php)
- [ ] Configure SSL certificate
- [ ] Set up email notifications
- [ ] Configure payment gateway credentials
- [ ] Set up automated backups
- [ ] Test all functionalities
- [ ] Configure shipping rates for your region
- [ ] Add actual product images
- [ ] Update contact information
- [ ] Set tax rates for your jurisdiction

## File Structure Reference

```
Essential Files:
├── shop.php              # Main storefront entry point
├── admin/
│   ├── login.php         # Admin login
│   ├── dashboard.php     # Admin home
│   ├── products.php      # Product management
│   ├── bulk-upload.php   # CSV import
│   ├── categories.php    # Category management
│   └── shipping.php      # Shipping configuration
├── config/
│   └── database_config.php  # Database settings
└── database/
    └── schema.sql        # Database structure
```

## Support Resources

**Documentation:**
- Main README: `ECOMMERCE_README.md`
- This Setup Guide: `SETUP_GUIDE.md`

**Database Schema:**
- Location: `database/schema.sql`
- Contains all tables and sample data

**Sample Data:**
- Default admin user
- Sample shipping methods and rates
- Sample FAQs
- Sample payment providers

## Next Steps

1. ✅ Complete database setup
2. ✅ Configure database connection
3. ✅ Login to admin panel
4. ✅ Add categories and subcategories
5. ✅ Configure shipping methods
6. ✅ Add your first product
7. ✅ Test the checkout process
8. ✅ Customize appearance (CSS)
9. ✅ Add company information
10. ✅ Go live!

---

**Need Help?**
- Review the main README for detailed feature documentation
- Check error logs in browser console
- Verify all prerequisites are installed
- Ensure PHP 7.4+ and MySQL 5.7+ are running

**Good Luck! 🚀**
