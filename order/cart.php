<?php
include '../_base.php';

// ----------------------------------------------------------------------------

if (is_post()) {
    $btn = req('btn');
    if ($btn == 'clear') {
        clear_cart();
        redirect('?');
    }

    $id   = req('id');
    $unit = req('unit');
    update_cart($id, $unit);
    redirect();
}

// ----------------------------------------------------------------------------

$_title = 'Order | Shopping Cart';
include '../navbar.php';

$cart = get_cart();
$count = 0;
$total = 0;
?>

<style>
.cart-page-container {
    max-width: 1000px;
    margin: 0 auto;
    padding: 30px 20px;
	min-height: 70vh;
}

.cart-header {
    text-align: center;
    margin-bottom: 30px;
}

.cart-header h1 {
    color: #2d3748;
    font-size: 2.2em;
    margin-bottom: 10px;
}

.cart-summary {
    background: white;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0, 105, 137, 0.1);
    overflow: hidden;
}

.cart-table {
    width: 100%;
    border-collapse: collapse;
}

.cart-table th {
    background: #006989;
    color: white;
    padding: 15px;
    text-align: left;
    font-weight: 600;
}

.cart-table td {
    padding: 20px 15px;
    border-bottom: 1px solid #e8f4f8;
    vertical-align: middle;
}

.cart-item-image {
    width: 80px;
    height: 80px;
    border-radius: 8px;
    overflow: hidden;
    background: #f8fafc;
}

.cart-item-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.cart-item-name {
    font-weight: 600;
    color: #2d3748;
    font-size: 1.1em;
}

.cart-item-price {
    color: #006989;
    font-weight: 600;
    font-size: 1.1em;
}

.quantity-form select {
    padding: 8px 12px;
    border: 2px solid #e2e8f0;
    border-radius: 6px;
    background: white;
    font-size: 0.9em;
}

.cart-subtotal {
    color: #2d3748;
    font-weight: 600;
    font-size: 1.1em;
}

.cart-total {
    background: #f8fafc;
    padding: 20px;
    text-align: right;
    font-size: 1.3em;
    font-weight: 700;
    color: #006989;
}

.cart-actions {
    display: flex;
    gap: 15px;
    justify-content: center;
    margin-top: 30px;
    flex-wrap: wrap;
}

.btn {
    padding: 12px 30px;
    border: none;
    border-radius: 8px;
    font-size: 1em;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.btn-primary {
    background: #006989;
    color: white;
}

.btn-primary:hover {
    background: #005672;
    transform: translateY(-2px);
}

.btn-secondary {
    background: #e53e3e;
    color: white;
}

.btn-secondary:hover {
    background: #c53030;
    transform: translateY(-2px);
}

.btn-outline {
    background: transparent;
    color: #006989;
    border: 2px solid #006989;
}

.btn-outline:hover {
    background: #006989;
    color: white;
    transform: translateY(-2px);
}

.empty-cart {
    text-align: center;
    padding: 60px 20px;
    color: #718096;
}

.empty-cart h3 {
    font-size: 1.5em;
    margin-bottom: 10px;
    color: #2d3748;
}
</style>

<div class="cart-page-container">
    <div class="cart-header">
        <h1>🛒 Shopping Cart</h1>
        <p>Review your items and proceed to checkout</p>
    </div>
    
    <?php if ($cart): ?>
        <div class="cart-summary">
            <table class="cart-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Price</th>
                        <th>Quantity</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cart as $id => $unit): 
                        $stm = $_db->prepare('SELECT * FROM product WHERE productID = ?');
                        $stm->execute([$id]);
                        $p = $stm->fetch();
                        if (!$p) continue;
                        
                        $subtotal = $p->price * $unit;
                        $count += $unit;
                        $total += $subtotal; 

                        // Generate quantity options
                        $units_options = ['' => 'Select quantity'];
                        $max_units = min($p->stock, 10);
                        for ($i = 1; $i <= $max_units; $i++) {
                            $units_options[$i] = $i . ' unit' . ($i > 1 ? 's' : '');
                        }
                        
                        $main_photo = get_main_photo($id);
                    ?>
                    <tr>
                        <td>
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <div class="cart-item-image">
                                    <?php if (!empty($main_photo)): ?>
                                        <img src="/products/<?= $main_photo ?>" alt="<?= $p->name ?>">
                                    <?php else: ?>
                                        <div style="width: 100%; height: 100%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; font-size: 0.8em;">
                                            No Image
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="cart-item-name"><?= $p->name ?></div>
                            </div>
                        </td>
                        <td class="cart-item-price">RM <?= number_format($p->price, 2) ?></td>
                        <td>
                            <form method="post" class="quantity-form">
                                <?= html_hidden('id', "value='$p->productID'") ?>
                                <?= html_select('unit', $units_options, '', $unit) ?>
                            </form>
                        </td>
                        <td class="cart-subtotal">RM <?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="cart-total">
                Total (<?= $count ?> items): RM <?= number_format($total, 2) ?>
            </div>
        </div>
        
        <div class="cart-actions">
            <button class="btn btn-secondary" data-post="?btn=clear">Clear Cart</button>
            <?php if ($_user?->role == 'Member'): ?>
                <button class="btn btn-primary" data-get="../payment/payment.php">Proceed to Checkout</button>
            <?php else: ?>
                <a href="/login.php" class="btn btn-outline">Login to Checkout</a>
            <?php endif; ?>
        </div>
    <?php else: ?>
        <div class="empty-cart">
            <h3>Your cart is empty</h3>
            <p>Browse our products and add some items to your cart!</p>
            <a href="../product/list.php" class="btn btn-primary">Start Shopping</a>
        </div>
    <?php endif; ?>
</div>

<script>
    // ============================================================================
    // Shopping Cart
    // ============================================================================
    function initImageModal() {
        let currentImageIndex = 0;
        let images = [];
        
        function collectImages() {
            images = [];
            
            // Add main photo if exists
            const mainImage = document.getElementById('mainProductImage');
            if (mainImage) {
                images.push({
                    src: mainImage.dataset.fullsize || mainImage.src,
                    alt: mainImage.alt,
                    index: 0
                });
            }
            
            // Add additional photos
            const thumbnails = document.querySelectorAll('.thumbnail');
            thumbnails.forEach(thumb => {
                images.push({
                    src: thumb.dataset.fullsize || thumb.src,
                    alt: thumb.alt,
                    index: parseInt(thumb.dataset.index)
                });
            });
            
            images.sort((a, b) => a.index - b.index);
        }
        
        function openModal(index) {
            currentImageIndex = index;
            const image = images[currentImageIndex];
            
            if (image) {
                const modal = document.getElementById("imageModal");
                const modalImg = document.getElementById("modalImage");
                const captionText = document.querySelector(".modal-caption");
                const counter = document.getElementById("imageCounter");
                
                modal.style.display = "block";
                modalImg.src = image.src;
                captionText.innerHTML = image.alt;
                counter.innerHTML = `${currentImageIndex + 1} / ${images.length}`;
                
                updateNavigation();
            }
        }
        
        function updateNavigation() {
            const prevBtn = document.getElementById("prevBtn");
            const nextBtn = document.getElementById("nextBtn");
            
            prevBtn.style.display = images.length > 1 ? "block" : "none";
            nextBtn.style.display = images.length > 1 ? "block" : "none";
        }
        
        function nextImage() {
            currentImageIndex = (currentImageIndex + 1) % images.length;
            openModal(currentImageIndex);
        }
        
        function prevImage() {
            currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
            openModal(currentImageIndex);
        }
        
        function initEventListeners() {
            const modal = document.getElementById("imageModal");
            const modalImg = document.getElementById("modalImage");
            const captionText = document.querySelector(".modal-caption");
            const counter = document.getElementById("imageCounter");
            
            document.querySelectorAll('.clickable-main, .thumbnail').forEach(img => {
                img.addEventListener('click', function() {
                    collectImages();
                    const index = parseInt(this.dataset.index);
                    openModal(index);
                });
            });
            
            document.getElementById('nextBtn').addEventListener('click', nextImage);
            document.getElementById('prevBtn').addEventListener('click', prevImage);
            
            document.querySelector('.modal-close').addEventListener('click', function() {
                modal.style.display = "none";
            });
            
            modal.addEventListener('click', function(event) {
                if (event.target === modal) {
                    modal.style.display = "none";
                }
            });
            
            document.addEventListener('keydown', function(event) {
                if (modal.style.display === "block") {
                    if (event.key === 'Escape') {
                        modal.style.display = "none";
                    } else if (event.key === 'ArrowRight') {
                        nextImage();
                    } else if (event.key === 'ArrowLeft') {
                        prevImage();
                    }
                }
            });
        }
        
        // Initialize everything
        collectImages();
        initEventListeners();
    }

    function initCartHandlers() {
        $('select[name="unit"]').on('change', function(e) {
            if (this.value !== '') {
                e.target.form.submit();
            }
        });

        $(document).on('click', '#clearBtn', function() {
            $('#unit').val('');
            $(this).closest('form').submit();
        });

        $(document).on('click', '#addToCartBtn', function(e) {
            const form = $(this).closest('form');
            const select = form.find('select[name="unit"]');
            
            if (select.val() === '') {
                e.preventDefault();
                alert('Please select a quantity first');
                select.focus();
            }
        });

        $(document).on('click', '.product-image img[data-get]', function(e) {
            e.preventDefault();
            const url = $(this).data('get');
            if (url) {
                window.location.href = url;
            }
        });
        
        initImageModal();
    }
    $('select').on('change', function(e) {
        if (this.value !== '') {
            e.target.form.submit();
        }
    });
</script>

<?php
include '../footer.php';