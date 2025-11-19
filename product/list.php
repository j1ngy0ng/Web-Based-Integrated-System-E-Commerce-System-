<?php
include '../_base.php';

// ----------------------------------------------------------------------------

if (is_post()) {
    $id   = req('id');
    $unit = req('unit');
    update_cart($id, $unit);
    redirect();
}

$arr = $_db->query('SELECT * FROM product ORDER BY productID DESC');

// ----------------------------------------------------------------------------

$_title = 'Product | List';
include '../navbar.php';
?>

<div class="container">
    <div id="products">
        <?php foreach ($arr as $p): ?>
            <?php
            $cart = get_cart();
            $id   = $p->productID;
            $unit = $cart[$p->productID] ?? 0;

            $max_units = min($p->stock, 10);
            for ($i = 1; $i <= $max_units; $i++) {
                $stock_options[$i] = $i . ' unit' . ($i > 1 ? 's' : '');
            }
            
            if ($p->stock == 0) {
                $stock_options = ['' => 'Out of Stock'];
            }

            $main_photo = get_main_photo($p->productID);
            ?>
            <div class="product">
                <div class="product-image">
                    <?php if (!empty($main_photo)): ?>
                        <img src="/products/<?= $main_photo ?>" 
                            data-get="/product/detail.php?id=<?= $p->productID ?>" 
                            alt="<?= $p->name ?>">
                    <?php else: ?>
                        <div class="photo-placeholder">No Image</div>
                    <?php endif; ?>
                    <div class="product-badge">Hot</div>
                </div>
                <div class="product-info">
                    <div class="product-name"><?= $p->name ?></div>
                    <div class="product-price">RM <?= number_format($p->price, 2) ?></div>
                    <div class="product-stock <?= $p->stock > 0 ? 'in-stock' : 'out-of-stock' ?>">
                        <?= $p->stock > 0 ? 'In Stock' : 'Out of Stock' ?>
                    </div>
                    <form method="post" class="product-form">
                        <?= html_hidden('id', "value='$p->productID'") ?>
                        <?= html_select('unit', $stock_options, '') ?>
                        <?php if ($unit): ?>
                            <span class="cart-indicator">✅ <?= $unit ?> in cart</span>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        <?php endforeach ?>
    </div>
</div>

<script>
    $('select').on('change', e => e.target.form.submit());
</script>

<?php
include '../footer.php';