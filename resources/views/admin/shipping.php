<?php
$pageTitle = "Shipping Methods & Rates";
include __DIR__ . '/includes/header.php';

// Handle shipping method operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_method') {
        $name = sanitize($_POST['name']);
        $code = sanitize($_POST['code']);
        $description = sanitize($_POST['description'] ?? '');
        $estimatedDays = sanitize($_POST['estimated_days'] ?? '');
        $displayOrder = intval($_POST['display_order'] ?? 0);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        
        $db->execute(
            "INSERT INTO shipping_methods (name, code, description, estimated_days, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)",
            [$name, $code, $description, $estimatedDays, $displayOrder, $isActive]
        );
        
        Session::setFlash('success', 'Shipping method added successfully');
        redirect('/admin/shipping.php');
    }
    
    elseif ($action === 'add_rate') {
        $methodId = intval($_POST['shipping_method_id']);
        $minWeight = floatval($_POST['min_weight']);
        $maxWeight = floatval($_POST['max_weight']);
        $pricePerKgActual = floatval($_POST['price_per_kg_actual']);
        $pricePerKgVolumetric = floatval($_POST['price_per_kg_volumetric']);
        $basePrice = floatval($_POST['base_price'] ?? 0);
        
        $db->execute(
            "INSERT INTO shipping_rates (shipping_method_id, min_weight, max_weight, price_per_kg_actual, price_per_kg_volumetric, base_price) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [$methodId, $minWeight, $maxWeight, $pricePerKgActual, $pricePerKgVolumetric, $basePrice]
        );
        
        Session::setFlash('success', 'Shipping rate added successfully');
        redirect('/admin/shipping.php');
    }
    
    elseif ($action === 'delete_method') {
        $id = intval($_POST['id']);
        $db->execute("DELETE FROM shipping_methods WHERE id = ?", [$id]);
        Session::setFlash('success', 'Shipping method deleted');
        redirect('/admin/shipping.php');
    }
    
    elseif ($action === 'delete_rate') {
        $id = intval($_POST['id']);
        $db->execute("DELETE FROM shipping_rates WHERE id = ?", [$id]);
        Session::setFlash('success', 'Shipping rate deleted');
        redirect('/admin/shipping.php');
    }
}

// Get all shipping methods
$methods = $db->fetchAll("SELECT * FROM shipping_methods ORDER BY display_order ASC, name ASC");

// Get rates for each method
$rates = [];
foreach ($methods as $method) {
    $rates[$method['id']] = $db->fetchAll(
        "SELECT * FROM shipping_rates WHERE shipping_method_id = ? ORDER BY min_weight ASC",
        [$method['id']]
    );
}
?>

<div class="shipping-admin">
    <div class="info-section">
        <h2><i class="fas fa-info-circle"></i> Shipping Calculation System</h2>
        <div class="info-grid">
            <div class="info-card">
                <h4>Dimensional Factor</h4>
                <p><?php echo DIMENSIONAL_FACTOR; ?></p>
                <small>Used for volumetric weight calculation</small>
            </div>
            <div class="info-card">
                <h4>Packaging Buffer</h4>
                <p><?php echo PACKAGING_BUFFER; ?> cm</p>
                <small>Added to each dimension (L, W, H)</small>
            </div>
            <div class="info-card">
                <h4>Calculation Method</h4>
                <p>Max(Actual, Volumetric)</p>
                <small>Greater weight is used for pricing</small>
            </div>
        </div>
        <p class="formula">
            <strong>Formula:</strong> Volumetric Weight = (Length × Width × Height) / <?php echo DIMENSIONAL_FACTOR; ?>
        </p>
    </div>

    <div class="page-actions">
        <button class="btn btn-primary" onclick="showModal('addMethodModal')">
            <i class="fas fa-plus"></i> Add Shipping Method
        </button>
        <button class="btn btn-success" onclick="showModal('addRateModal')">
            <i class="fas fa-plus"></i> Add Rate Tier
        </button>
    </div>

    <!-- Shipping Methods List -->
    <div class="methods-list">
        <?php foreach ($methods as $method): ?>
            <div class="method-item">
                <div class="method-header">
                    <div class="method-info">
                        <h3><?php echo htmlspecialchars($method['name']); ?></h3>
                        <span class="code-badge">Code: <?php echo htmlspecialchars($method['code']); ?></span>
                        <?php if ($method['estimated_days']): ?>
                            <span class="delivery-badge"><i class="fas fa-clock"></i> <?php echo htmlspecialchars($method['estimated_days']); ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="method-actions">
                        <span class="badge badge-<?php echo $method['is_active'] ? 'success' : 'secondary'; ?>">
                            <?php echo $method['is_active'] ? 'Active' : 'Inactive'; ?>
                        </span>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="action" value="delete_method">
                            <input type="hidden" name="id" value="<?php echo $method['id']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this shipping method?')">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                    </div>
                </div>
                
                <?php if ($method['description']): ?>
                    <p class="method-description"><?php echo htmlspecialchars($method['description']); ?></p>
                <?php endif; ?>
                
                <!-- Rate Tiers -->
                <?php if (!empty($rates[$method['id']])): ?>
                    <div class="rates-table">
                        <h4>Rate Tiers:</h4>
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Weight Range (kg)</th>
                                    <th>Base Price</th>
                                    <th>Price/kg (Actual Weight)</th>
                                    <th>Price/kg (Volumetric)</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($rates[$method['id']] as $rate): ?>
                                    <tr>
                                        <td><?php echo $rate['min_weight']; ?> - <?php echo $rate['max_weight']; ?> kg</td>
                                        <td><?php echo formatPrice($rate['base_price']); ?></td>
                                        <td><?php echo formatPrice($rate['price_per_kg_actual']); ?></td>
                                        <td><?php echo formatPrice($rate['price_per_kg_volumetric']); ?></td>
                                        <td>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="action" value="delete_rate">
                                                <input type="hidden" name="id" value="<?php echo $rate['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Delete this rate?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="no-rates">No rate tiers defined for this method</p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Add Shipping Method Modal -->
<div id="addMethodModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addMethodModal')">&times;</span>
        <h2>Add Shipping Method</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add_method">
            <div class="form-group">
                <label>Method Name * (e.g., DHL Express, Aramex)</label>
                <input type="text" name="name" required placeholder="DHL Express">
            </div>
            <div class="form-group">
                <label>Code * (Unique identifier)</label>
                <input type="text" name="code" required placeholder="dhl_express">
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="2" placeholder="Fast international shipping"></textarea>
            </div>
            <div class="form-group">
                <label>Estimated Delivery Time</label>
                <input type="text" name="estimated_days" placeholder="2-3 business days">
            </div>
            <div class="form-group">
                <label>Display Order</label>
                <input type="number" name="display_order" value="0" min="0">
            </div>
            <div class="form-group">
                <label class="checkbox-label">
                    <input type="checkbox" name="is_active" checked>
                    Active
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Add Method</button>
        </form>
    </div>
</div>

<!-- Add Rate Modal -->
<div id="addRateModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addRateModal')">&times;</span>
        <h2>Add Rate Tier</h2>
        <form method="POST">
            <input type="hidden" name="action" value="add_rate">
            <div class="form-group">
                <label>Shipping Method *</label>
                <select name="shipping_method_id" required>
                    <option value="">Select Method</option>
                    <?php foreach ($methods as $method): ?>
                        <option value="<?php echo $method['id']; ?>"><?php echo htmlspecialchars($method['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Min Weight (kg) *</label>
                    <input type="number" name="min_weight" step="0.01" min="0" required placeholder="0">
                </div>
                <div class="form-group">
                    <label>Max Weight (kg) *</label>
                    <input type="number" name="max_weight" step="0.01" min="0" required placeholder="5">
                </div>
            </div>
            <div class="form-group">
                <label>Base/Flat Price *</label>
                <input type="number" name="base_price" step="0.01" min="0" value="0" required>
                <small>Fixed cost regardless of weight</small>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Price per KG (Actual Weight) *</label>
                    <input type="number" name="price_per_kg_actual" step="0.01" min="0" required placeholder="15.00">
                </div>
                <div class="form-group">
                    <label>Price per KG (Volumetric) *</label>
                    <input type="number" name="price_per_kg_volumetric" step="0.01" min="0" required placeholder="18.00">
                </div>
            </div>
            <div class="info-note">
                <i class="fas fa-info-circle"></i> System will use the greater of (actual weight × price) or (volumetric weight × price)
            </div>
            <button type="submit" class="btn btn-primary">Add Rate Tier</button>
        </form>
    </div>
</div>

<script>
function showModal(modalId) {
    document.getElementById(modalId).style.display = 'block';
}

function closeModal(modalId) {
    document.getElementById(modalId).style.display = 'none';
}

window.onclick = function(event) {
    if (event.target.classList.contains('modal')) {
        event.target.style.display = 'none';
    }
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
