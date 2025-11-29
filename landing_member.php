<?php
include '_base.php';

// Get hero/banner images (you'll need to create a banners table or use static images)
$banners = [
    ['image' => '/images/banner1.jpg', 'title' => 'Summer Sale', 'subtitle' => 'Up to 50% Off', 'link' => '/product/list.php'],
    ['image' => '/images/banner2.jpg', 'title' => 'New Arrivals', 'subtitle' => 'Check Out Latest Products', 'link' => '/product/list.php'],
    ['image' => '/images/banner3.jpg', 'title' => 'Special Deals', 'subtitle' => 'Limited Time Offers', 'link' => '/product/list.php']
];

// Get top selling products (by quantity sold)
$topSellingStm = $_db->query('
    SELECT p.*, 
           COALESCE(SUM(od.quantity), 0) as total_sold,
           c.categoryName
    FROM product p
    LEFT JOIN order_item od ON p.productID = od.productID
    LEFT JOIN productcategory c ON p.categoryID = c.categoryID
    WHERE p.stock > 0
    GROUP BY p.productID
    ORDER BY total_sold DESC
    LIMIT 10
');
$topSelling = $topSellingStm->fetchAll();

// Get new arrivals (latest products)
$newArrivalsStm = $_db->query('
    SELECT p.*, c.categoryName
    FROM product p
    LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID
    WHERE p.stock > 0
    ORDER BY p.productID DESC
    LIMIT 8
');
$newArrivals = $newArrivalsStm->fetchAll();

// Get featured/hot products (can be based on views, ratings, or manually selected)
$featuredStm = $_db->query('
    SELECT p.*, c.categoryName
    FROM product p
    LEFT JOIN ProductCategory c ON p.categoryID = c.categoryID
    WHERE p.stock > 0
    ORDER BY RAND()
    LIMIT 8
');
$featured = $featuredStm->fetchAll();

// Get all categories
$categoriesStm = $_db->query('SELECT * FROM ProductCategory ORDER BY categoryName');
$categories = $categoriesStm->fetchAll();

$_title = 'Home - Welcome';
include 'navbar.php';
?>

<style>
/* Hero Slider */
.hero-slider {
    position: relative;
    width: 100%;
    height: 500px;
    overflow: hidden;
    margin-bottom: 40px;
}

.hero-slide {
    display: none;
    width: 100%;
    height: 100%;
    position: relative;
    animation: fadeIn 0.5s;
}

.hero-slide.active {
    display: block;
}

.hero-slide img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}

.hero-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: linear-gradient(to right, rgba(0,0,0,0.7) 0%, rgba(0,0,0,0.3) 100%);
    display: flex;
    align-items: center;
    padding: 0 80px;
}

.hero-content {
    color: white;
    max-width: 600px;
}

.hero-content h1 {
    font-size: 3.5rem;
    margin-bottom: 15px;
    font-weight: 700;
}

.hero-content p {
    font-size: 1.5rem;
    margin-bottom: 25px;
}

.hero-btn {
    background: #006989;
    color: white;
    padding: 15px 40px;
    text-decoration: none;
    border-radius: 5px;
    font-size: 1.1rem;
    display: inline-block;
    transition: all 0.3s;
}

.hero-btn:hover {
    background: #005a73;
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,105,137,0.3);
}

.slider-controls {
    position: absolute;
    bottom: 30px;
    left: 50%;
    transform: translateX(-50%);
    display: flex;
    gap: 10px;
    z-index: 10;
}

.slider-dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: rgba(255,255,255,0.5);
    border: none;
    cursor: pointer;
    transition: all 0.3s;
}

.slider-dot.active {
    background: white;
    width: 30px;
    border-radius: 6px;
}

.slider-arrow {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(255,255,255,0.3);
    color: white;
    border: none;
    font-size: 2rem;
    padding: 15px 20px;
    cursor: pointer;
    transition: all 0.3s;
    z-index: 10;
}

.slider-arrow:hover {
    background: rgba(255,255,255,0.5);
}

.slider-arrow.prev {
    left: 20px;
}

.slider-arrow.next {
    right: 20px;
}

/* Section Styles */
.section {
    margin: 60px 0;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 15px;
    border-bottom: 2px solid #006989;
}

.section-header h2 {
    font-size: 2rem;
    color: #333;
}

.section-controls {
    display: flex;
    gap: 10px;
}

.filter-btn {
    padding: 8px 20px;
    border: 1px solid #006989;
    background: white;
    color: #006989;
    border-radius: 5px;
    cursor: pointer;
    transition: all 0.3s;
}

.filter-btn.active {
    background: #006989;
    color: white;
}

.filter-btn:hover {
    background: #006989;
    color: white;
}

/* Product Grid */
.products-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
    gap: 25px;
}

.product-card {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    transition: all 0.3s;
    cursor: pointer;
    position: relative;
}

.product-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,0,0,0.15);
}

.product-card-image {
    width: 100%;
    height: 250px;
    overflow: hidden;
    position: relative;
}

.product-card-image img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s;
}

.product-card:hover .product-card-image img {
    transform: scale(1.1);
}

.product-badge {
    position: absolute;
    top: 10px;
    right: 10px;
    background: #e74c3c;
    color: white;
    padding: 5px 15px;
    border-radius: 20px;
    font-size: 0.85rem;
    font-weight: 600;
}

.product-ranking {
    position: absolute;
    top: 10px;
    left: 10px;
    background: #f39c12;
    color: white;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.2rem;
    font-weight: 700;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.product-card-content {
    padding: 20px;
}

.product-card-title {
    font-size: 1.1rem;
    margin-bottom: 10px;
    color: #333;
    font-weight: 600;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.product-card-category {
    color: #666;
    font-size: 0.85rem;
    margin-bottom: 10px;
}

.product-card-price {
    font-size: 1.3rem;
    color: #006989;
    font-weight: 700;
    margin-bottom: 10px;
}

.product-card-stock {
    font-size: 0.85rem;
    padding: 5px 10px;
    border-radius: 5px;
    display: inline-block;
}

.product-card-stock.in-stock {
    background: #e8f8f0;
    color: #27ae60;
}

.product-card-stock.low-stock {
    background: #fef5e7;
    color: #f39c12;
}

.product-sales-info {
    margin-top: 10px;
    padding-top: 10px;
    border-top: 1px solid #eee;
    font-size: 0.85rem;
    color: #666;
}

/* Product Slider */
.product-slider-container {
    position: relative;
    overflow: hidden;
}

.product-slider {
    display: flex;
    gap: 25px;
    overflow-x: auto;
    scroll-behavior: smooth;
    padding: 10px 0;
    scrollbar-width: none;
}

.product-slider::-webkit-scrollbar {
    display: none;
}

.product-slide {
    flex: 0 0 280px;
}

.slider-nav {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    background: white;
    border: 1px solid #ddd;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    z-index: 10;
    transition: all 0.3s;
}

.slider-nav:hover {
    background: #006989;
    color: white;
}

.slider-nav.prev-slide {
    left: -20px;
}

.slider-nav.next-slide {
    right: -20px;
}

/* Categories Section */
.categories-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 40px;
}

.category-card {
    background: linear-gradient(135deg, #006989 0%, #008fb3 100%);
    color: white;
    padding: 30px;
    border-radius: 10px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    text-decoration: none;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 20px rgba(0,105,137,0.3);
}

.category-card h3 {
    margin: 0;
    font-size: 1.2rem;
}

/* Animations */
@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Responsive */
@media (max-width: 768px) {
    .hero-slider {
        height: 300px;
    }
    
    .hero-content h1 {
        font-size: 2rem;
    }
    
    .hero-content p {
        font-size: 1rem;
    }
    
    .hero-overlay {
        padding: 0 20px;
    }
    
    .products-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
    }
    
    .section-header {
        flex-direction: column;
        align-items: flex-start;
        gap: 15px;
    }
}
</style>

<!-- Hero Slider -->
<div class="hero-slider">
    <?php foreach ($banners as $index => $banner): ?>
        <div class="hero-slide <?= $index === 0 ? 'active' : '' ?>">
            <img src="<?= $banner['image'] ?>" alt="<?= $banner['title'] ?>" onerror="this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22800%22 height=%22500%22%3E%3Crect fill=%22%23ddd%22 width=%22800%22 height=%22500%22/%3E%3Ctext fill=%22%23999%22 x=%2250%25%22 y=%2250%25%22 text-anchor=%22middle%22 dy=%22.3em%22 font-size=%2230%22%3EBanner Image%3C/text%3E%3C/svg%3E'">
            <div class="hero-overlay">
                <div class="hero-content">
                    <h1><?= $banner['title'] ?></h1>
                    <p><?= $banner['subtitle'] ?></p>
                    <a href="<?= $banner['link'] ?>" class="hero-btn">Shop Now</a>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    
    <button class="slider-arrow prev" onclick="changeSlide(-1)">‹</button>
    <button class="slider-arrow next" onclick="changeSlide(1)">›</button>
    
    <div class="slider-controls">
        <?php foreach ($banners as $index => $banner): ?>
            <button class="slider-dot <?= $index === 0 ? 'active' : '' ?>" onclick="goToSlide(<?= $index ?>)"></button>
        <?php endforeach; ?>
    </div>
</div>

<div class="container">
    <!-- Categories Section -->
    <div class="section">
        <div class="section-header">
            <h2>🏷️ Shop by Category</h2>
        </div>
        <div class="categories-grid">
            <a href="/product/list.php" class="category-card">
                <h3>All Products</h3>
            </a>
            <?php foreach ($categories as $cat): ?>
                <a href="/product/list.php?category=<?= $cat->categoryID ?>" class="category-card">
                    <h3><?= $cat->categoryName ?></h3>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Top Selling Products -->
    <div class="section">
        <div class="section-header">
            <h2>🏆 Top Selling Products</h2>
            <div class="section-controls">
                <button class="filter-btn active" onclick="filterTopSelling('all')">All</button>
                <?php foreach ($categories as $cat): ?>
                    <button class="filter-btn" onclick="filterTopSelling('<?= $cat->categoryID ?>')"><?= $cat->categoryName ?></button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="products-grid" id="topSellingGrid">
            <?php foreach ($topSelling as $index => $p): ?>
                <?php
                $main_photo = get_main_photo($p->productID);
                $stock_status = $p->stock <= 5 ? 'low-stock' : 'in-stock';
                $stock_text = $p->stock <= 5 ? "Only {$p->stock} left!" : "In Stock";
                ?>
                <div class="product-card" data-category="<?= $p->categoryID ?>" onclick="window.location.href='/product/detail.php?id=<?= $p->productID ?>'">
                    <div class="product-card-image">
                        <?php if (!empty($main_photo)): ?>
                            <img src="/products/<?= $main_photo ?>" alt="<?= $p->name ?>">
                        <?php else: ?>
                            <div style="width: 100%; height: 100%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">No Image</div>
                        <?php endif; ?>
                        <div class="product-ranking">#<?= $index + 1 ?></div>
                        <?php if ($p->stock <= 5): ?>
                            <div class="product-badge">Hot</div>
                        <?php endif; ?>
                    </div>
                    <div class="product-card-content">
                        <div class="product-card-title"><?= $p->name ?></div>
                        <div class="product-card-category"><?= $p->categoryName ?></div>
                        <div class="product-card-price">RM <?= number_format($p->price, 2) ?></div>
                        <div class="product-card-stock <?= $stock_status ?>"><?= $stock_text ?></div>
                        <div class="product-sales-info">
                            📦 <?= $p->total_sold ?> sold
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- New Arrivals Slider -->
    <div class="section">
        <div class="section-header">
            <h2>✨ New Arrivals</h2>
            <a href="/product/list.php" style="color: #006989; text-decoration: none;">View All →</a>
        </div>
        <div class="product-slider-container">
            <button class="slider-nav prev-slide" onclick="slideProducts('newArrivals', -1)">‹</button>
            <div class="product-slider" id="newArrivals">
                <?php foreach ($newArrivals as $p): ?>
                    <?php
                    $main_photo = get_main_photo($p->productID);
                    $stock_status = $p->stock <= 5 ? 'low-stock' : 'in-stock';
                    $stock_text = $p->stock <= 5 ? "Only {$p->stock} left!" : "In Stock";
                    ?>
                    <div class="product-slide">
                        <div class="product-card" onclick="window.location.href='/product/detail.php?id=<?= $p->productID ?>'">
                            <div class="product-card-image">
                                <?php if (!empty($main_photo)): ?>
                                    <img src="/products/<?= $main_photo ?>" alt="<?= $p->name ?>">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">No Image</div>
                                <?php endif; ?>
                                <div class="product-badge">New</div>
                            </div>
                            <div class="product-card-content">
                                <div class="product-card-title"><?= $p->name ?></div>
                                <div class="product-card-category"><?= $p->categoryName ?></div>
                                <div class="product-card-price">RM <?= number_format($p->price, 2) ?></div>
                                <div class="product-card-stock <?= $stock_status ?>"><?= $stock_text ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="slider-nav next-slide" onclick="slideProducts('newArrivals', 1)">›</button>
        </div>
    </div>

    <!-- Featured Products Slider -->
    <div class="section">
        <div class="section-header">
            <h2>🔥 Featured Products</h2>
            <a href="/product/list.php" style="color: #006989; text-decoration: none;">View All →</a>
        </div>
        <div class="product-slider-container">
            <button class="slider-nav prev-slide" onclick="slideProducts('featured', -1)">‹</button>
            <div class="product-slider" id="featured">
                <?php foreach ($featured as $p): ?>
                    <?php
                    $main_photo = get_main_photo($p->productID);
                    $stock_status = $p->stock <= 5 ? 'low-stock' : 'in-stock';
                    $stock_text = $p->stock <= 5 ? "Only {$p->stock} left!" : "In Stock";
                    ?>
                    <div class="product-slide">
                        <div class="product-card" onclick="window.location.href='/product/detail.php?id=<?= $p->productID ?>'">
                            <div class="product-card-image">
                                <?php if (!empty($main_photo)): ?>
                                    <img src="/products/<?= $main_photo ?>" alt="<?= $p->name ?>">
                                <?php else: ?>
                                    <div style="width: 100%; height: 100%; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #999;">No Image</div>
                                <?php endif; ?>
                                <div class="product-badge">Featured</div>
                            </div>
                            <div class="product-card-content">
                                <div class="product-card-title"><?= $p->name ?></div>
                                <div class="product-card-category"><?= $p->categoryName ?></div>
                                <div class="product-card-price">RM <?= number_format($p->price, 2) ?></div>
                                <div class="product-card-stock <?= $stock_status ?>"><?= $stock_text ?></div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <button class="slider-nav next-slide" onclick="slideProducts('featured', 1)">›</button>
        </div>
    </div>
</div>

<script>
// Hero Slider
let currentSlide = 0;
const slides = document.querySelectorAll('.hero-slide');
const dots = document.querySelectorAll('.slider-dot');

function showSlide(n) {
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    if (n >= slides.length) currentSlide = 0;
    if (n < 0) currentSlide = slides.length - 1;
    
    slides[currentSlide].classList.add('active');
    dots[currentSlide].classList.add('active');
}

function changeSlide(n) {
    currentSlide += n;
    showSlide(currentSlide);
}

function goToSlide(n) {
    currentSlide = n;
    showSlide(currentSlide);
}

// Auto slide
setInterval(() => {
    currentSlide++;
    showSlide(currentSlide);
}, 5000);

// Product Slider
function slideProducts(sliderId, direction) {
    const slider = document.getElementById(sliderId);
    const slideWidth = 305; // 280px + 25px gap
    slider.scrollLeft += slideWidth * direction;
}

// Filter Top Selling Products
function filterTopSelling(category) {
    const products = document.querySelectorAll('#topSellingGrid .product-card');
    const buttons = document.querySelectorAll('.section-controls .filter-btn');
    
    // Update active button
    buttons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Filter products
    products.forEach(product => {
        if (category === 'all' || product.dataset.category === category) {
            product.style.display = 'block';
        } else {
            product.style.display = 'none';
        }
    });
}
</script>

<?php
include 'footer.php';
?>