<?php
$pageTitle = "Shipping Methods & Rates";
include __DIR__ . '/includes/header.php';

// Get all shipping methods from DB injected by controller
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

<div class="admin-content">
    <div class="page-header">
        <h1><i class="fas fa-truck"></i> Shipping Methods</h1>
        <div class="page-actions">
            <button class="btn btn-primary" onclick="showModal('addMethodModal')">
                <i class="fas fa-plus"></i> Add Method
            </button>
            <button class="btn btn-success" onclick="showModal('addRateModal')">
                <i class="fas fa-plus"></i> Add Rate Tier
            </button>
        </div>
    </div>

    <!-- Info Section -->
    <div class="dashboard-section" style="margin-bottom: 2rem;">
        <h2 style="margin-bottom:1.5rem;"><i class="fas fa-info-circle"></i> Shipping Logic</h2>
        <div class="stats-grid" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--color-bg-alt); color: var(--color-primary);"><i
                        class="fas fa-cube"></i></div>
                <div class="stat-content">
                    <div class="stat-value" style="font-size:1.2rem;"><?php echo DIMENSIONAL_FACTOR; ?></div>
                    <div class="stat-label">Dimensional Factor</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--color-bg-alt); color: var(--color-secondary);"><i
                        class="fas fa-box-open"></i></div>
                <div class="stat-content">
                    <div class="stat-value" style="font-size:1.2rem;">+<?php echo PACKAGING_BUFFER; ?> cm</div>
                    <div class="stat-label">Packaging Buffer</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: var(--color-bg-alt); color: var(--color-info);"><i
                        class="fas fa-calculator"></i></div>
                <div class="stat-content">
                    <div class="stat-value" style="font-size:1.2rem;">Max(A, V)</div>
                    <div class="stat-label">Calculation Method</div>
                </div>
            </div>
        </div>
        <p
            style="margin-top:1.5rem; font-size:0.9rem; color:var(--color-text-sub); background:var(--color-bg-alt); padding:1rem; border-radius:var(--radius-sm);">
            <strong>Formula:</strong> Volumetric Weight = ((Length + Buffer) × (Width + Buffer) × (Height + Buffer)) /
            <?php echo DIMENSIONAL_FACTOR; ?>.
            The system uses the greater of Actual Weight or Volumetric Weight for pricing.
        </p>
    </div>

    <div class="methods-list" style="display:grid; gap:2rem;">
        <?php if (empty($methods)): ?>
            <div class="dashboard-section text-center">No shipping methods defined.</div>
        <?php else: ?>
            <?php foreach ($methods as $method): ?>
                <div class="dashboard-section">
                    <div
                        style="display:flex; justify-content:space-between; align-items:center; margin-bottom:1.5rem; border-bottom:1px solid var(--color-border); padding-bottom:1rem;">
                        <div>
                            <h3 style="margin:0;"><?php echo htmlspecialchars($method['name']); ?></h3>
                            <div style="display:flex; gap:0.5rem; margin-top:0.3rem;">
                                <span class="badge badge-info">Code: <?php echo htmlspecialchars($method['code']); ?></span>
                                <?php if ($method['estimated_days']): ?>
                                    <span class="badge badge-secondary"><i class="fas fa-clock"></i>
                                        <?php echo htmlspecialchars($method['estimated_days']); ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div style="display:flex; align-items:center; gap:1rem;">
                            <span class="badge badge-<?php echo $method['is_active'] ? 'success' : 'secondary'; ?>">
                                <?php echo $method['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <form method="POST" action="/admin/shipping/delete">
                                <?php echo csrf_field(); ?>
                                <input type="hidden" name="id" value="<?php echo $method['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger"
                                    onclick="return confirm('Delete this shipping method?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </div>

                    <?php if ($method['description']): ?>
                        <p style="color:var(--color-text-sub); margin-bottom:1.5rem;">
                            <?php echo htmlspecialchars($method['description']); ?>
                        </p>
                    <?php endif; ?>

                    <!-- Rate Tiers -->
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>Weight Range (kg)</th>
                                    <th>Price/kg (Actual)</th>
                                    <th>Price/kg (Volumetric)</th>
                                    <th style="text-align:right;">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($rates[$method['id']])): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">No rate tiers defined for this method.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($rates[$method['id']] as $rate): ?>
                                        <tr>
                                            <td><span style="font-weight:600;"><?php echo $rate['min_weight']; ?> -
                                                    <?php echo $rate['max_weight']; ?></span> kg</td>
                                            <td><?php echo formatPrice($rate['price_per_kg_actual']); ?></td>
                                            <td><?php echo formatPrice($rate['price_per_kg_volumetric']); ?></td>
                                            <td style="text-align:right;">
                                                <form method="POST" action="/admin/shipping/rate/delete">
                                                    <?php echo csrf_field(); ?>
                                                    <input type="hidden" name="id" value="<?php echo $rate['id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Delete this rate tier?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<!-- Add Shipping Method Modal -->
<div id="addMethodModal" class="modal">
    <div class="modal-content">
        <span class="close" onclick="closeModal('addMethodModal')">&times;</span>
        <h2>Add Shipping Method</h2>
        <form method="POST" action="/admin/shipping">
            <?php echo csrf_field(); ?>
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
        <form method="POST" action="/admin/shipping/rate">
            <?php echo csrf_field(); ?>
            <div class="form-group">
                <label>Shipping Method *</label>
                <select name="shipping_method_id" required>
                    <option value="">Select Method</option>
                    <?php foreach ($methods as $method): ?>
                        <option value="<?php echo $method['id']; ?>"><?php echo htmlspecialchars($method['name']); ?>
                        </option>
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

            <div class="form-row">
                <div class="form-group">
                    <label>Price per KG (Actual Weight) *</label>
                    <input type="number" name="price_per_kg_actual" step="0.01" min="0" required placeholder="15.00">
                </div>
                <div class="form-group">
                    <label>Price per KG (Volumetric) *</label>
                    <input type="number" name="price_per_kg_volumetric" step="0.01" min="0" required
                        placeholder="18.00">
                </div>
            </div>
            <div class="info-note">
                <i class="fas fa-info-circle"></i> System will use the greater of (actual weight × price) or (volumetric
                weight × price)
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

    window.onclick = function (event) {
        if (event.target.classList.contains('modal')) {
            event.target.style.display = 'none';
        }
    }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>