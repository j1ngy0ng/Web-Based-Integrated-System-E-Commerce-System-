<?php
include '../_base.php';

// Authenticated users
auth('Admin','Superadmin');

// Get Code from URL (if editing)
$code_param = req('code'); 
$is_edit = !empty($code_param);

// Default Values
$voucher_code = '';
$discount_type = 'fixed';
$discount_value = '';
$min_spend = '0.00';
$expiry_date = date('Y-m-d', strtotime('+1 month')); // Default to 1 month from now

// Load existing data if editing
if ($is_edit) {
    $stm = $_db->prepare("SELECT * FROM vouchers WHERE voucher_code = ?");
    $stm->execute([$code_param]);
    $v = $stm->fetch();
    
    if (!$v) {
        temp('error', 'Voucher not found.');
        redirect('voucher.php');
    }
    
    $voucher_code = $v->voucher_code;
    $discount_type = $v->discount_type;
    $discount_value = $v->discount_value;
    $min_spend = $v->min_spend;
    $expiry_date = $v->expiry_date;
}

// ----------------------------------------------------------------------------
// POST HANDLING (Save/Update)
// ----------------------------------------------------------------------------
if (is_post()) {
    $voucher_code   = strtoupper(trim(req('voucher_code')));
    $discount_type  = req('discount_type');
    $discount_value = req('discount_value');
    $min_spend      = req('min_spend');
    $expiry_date    = req('expiry_date');

    // Validation
    if ($voucher_code == '') $_err['voucher_code'] = 'Required';
    if ($discount_value <= 0) $_err['discount_value'] = 'Must be greater than 0';
    if ($min_spend < 0) $_err['min_spend'] = 'Cannot be negative';
    if ($expiry_date == '') $_err['expiry_date'] = 'Required';

    // Check duplicate code (Only if adding new)
    if (!$is_edit) {
        $exists = $_db->prepare("SELECT COUNT(*) FROM vouchers WHERE voucher_code = ?");
        $exists->execute([$voucher_code]);
        if ($exists->fetchColumn() > 0) {
            $_err['voucher_code'] = 'Code already exists';
        }
    }

    if (!$_err) {
        if ($is_edit) {
            // Update
            $stm = $_db->prepare("UPDATE vouchers SET discount_type=?, discount_value=?, min_spend=?, expiry_date=? WHERE voucher_code=?");
            $stm->execute([$discount_type, $discount_value, $min_spend, $expiry_date, $code_param]); // Use $code_param for WHERE to prevent changing PK
            temp('info', 'Voucher updated successfully.');
        } else {
            // Insert
            $stm = $_db->prepare("INSERT INTO vouchers (voucher_code, discount_type, discount_value, min_spend, expiry_date) VALUES (?, ?, ?, ?, ?)");
            $stm->execute([$voucher_code, $discount_type, $discount_value, $min_spend, $expiry_date]);
            temp('info', 'Voucher created successfully.');
        }
        redirect('voucher.php');
    }
}

$_title = $is_edit ? 'Edit Voucher' : 'Add Voucher';
include '../navbar.php';
?>

<style>
    /* Consistent Form Styling */
    .form-container { max-width: 600px; margin: 40px auto; background: #fff; padding: 30px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
    .form-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border-bottom: 1px solid #eee; padding-bottom: 15px; }
    .form-header h3 { margin: 0; color: #333; }
    .back-link { text-decoration: none; color: #666; font-weight: 600; font-size: 0.9em; display: flex; align-items: center; gap: 5px; }
    .back-link:hover { color: #007bff; }

    .form-group { margin-bottom: 20px; }
    .form-label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
    .form-control { width: 100%; padding: 10px; border: 1px solid #ced4da; border-radius: 6px; font-size: 1rem; box-sizing: border-box; transition: 0.2s; }
    .form-control:focus { border-color: #007bff; box-shadow: 0 0 0 3px rgba(0,123,255,0.1); outline: none; }
    .err-msg { color: #dc3545; font-size: 0.85em; margin-top: 5px; display: block; }

    .radio-group { display: flex; gap: 20px; margin-top: 5px; }
    .radio-label { display: flex; align-items: center; gap: 5px; cursor: pointer; }

    .btn-submit { width: 100%; padding: 12px; background: #007bff; color: white; border: none; border-radius: 6px; font-size: 1rem; font-weight: 600; cursor: pointer; transition: 0.2s; }
    .btn-submit:hover { background: #0056b3; }
</style>

<div class="form-container">
    <div class="form-header">
        <h3><?= $is_edit ? 'Edit Voucher' : 'Create New Voucher' ?></h3>
        <a href="voucher.php" class="back-link">
            <i class="fa fa-arrow-left"></i> Back to List
        </a>
    </div>

    <form method="post">
        <div class="form-group">
            <label class="form-label">Voucher Code</label>
            <input type="text" name="voucher_code" class="form-control" 
                   value="<?= htmlspecialchars($voucher_code) ?>" 
                   style="text-transform: uppercase;"
                   <?= $is_edit ? 'readonly style="background-color:#e9ecef; cursor:not-allowed;"' : '' ?> 
                   placeholder="e.g. SAVE10">
            <?= err('voucher_code') ?>
        </div>

        <div class="form-group">
            <label class="form-label">Discount Type</label>
            <div class="radio-group">
                <label class="radio-label">
                    <input type="radio" name="discount_type" value="fixed" <?= $discount_type == 'fixed' ? 'checked' : '' ?>>
                    Fixed Amount (RM)
                </label>
                <label class="radio-label">
                    <input type="radio" name="discount_type" value="percent" <?= $discount_type == 'percent' ? 'checked' : '' ?>>
                    Percentage (%)
                </label>
            </div>
        </div>

        <div style="display: flex; gap: 20px;">
            <div class="form-group" style="flex: 1;">
                <label class="form-label">Discount Value</label>
                <input type="number" step="0.01" name="discount_value" class="form-control" 
                       value="<?= htmlspecialchars($discount_value) ?>" placeholder="e.g. 10.00">
                <?= err('discount_value') ?>
            </div>

            <div class="form-group" style="flex: 1;">
                <label class="form-label">Min Spend (RM)</label>
                <input type="number" step="0.01" name="min_spend" class="form-control" 
                       value="<?= htmlspecialchars($min_spend) ?>" placeholder="0.00 for no limit">
                <?= err('min_spend') ?>
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Expiry Date</label>
            <input type="date" name="expiry_date" class="form-control" 
                   value="<?= htmlspecialchars($expiry_date) ?>">
            <?= err('expiry_date') ?>
        </div>

        <button type="submit" class="btn-submit">
            <?= $is_edit ? 'Update Voucher' : 'Create Voucher' ?>
        </button>
    </form>
</div>

<?php include '../footer.php'; ?>