<?php
if (!isset($_user)) {
    session_start();
    $_user = $_SESSION['user'] ?? null;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="/css/creative.css" rel="stylesheet" type="text/css">
	<link href="/font-awesome/css/font-awesome.css" rel="stylesheet">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
	<script src="/js/app.js"></script>
    <script src="/order/ajax_cart_count.php"></script>
	<style>
		
		/* Category Filter Header Styles */
		.filter-header {
			min-width: 150px;
		}
		.category-filter-section {
            position: relative;
            z-index: 20;
            background: linear-gradient(135deg, #006989, #005a73);
            border-radius: 34px;
            min-width: 180px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(0, 105, 137, 0.3);
            overflow: visible; /* Changed from hidden to visible for dropdown */
        }

        .category-filter-section:hover {
            background: linear-gradient(135deg, #005a73, #004d61);
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 105, 137, 0.4);
        }

.filter-content {
    position: absolute;
    width: 100%;
    left: 0;
    background: #006989;
    border-radius: 0 0 10px 10px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.25);
    max-height: 0;
    overflow: hidden;
    transition: max-height 0.3s ease;
}

.filter-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 10px 20px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    min-height: 44px;
}

.filter-header:hover {
    background: rgba(255, 255, 255, 0.1);
}

.filter-title {
    display: flex;
    align-items: center;
    gap: 12px;
}

.filter-title h4 {
    margin: 0;
    color: white;
    font-size: 1.1rem;
    font-weight: 600;
}

.toggle-arrow {
    font-size: 1.2rem;
    transition: transform 0.3s ease;
    color: rgba(255, 255, 255, 0.8);
}

.category-filter-section.expanded .toggle-arrow {
    transform: rotate(180deg);
    border-radius: 34px;
}

.clear-filter-btn {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    padding: 8px 16px;
    border-radius: 20px;
    text-decoration: none;
    font-size: 0.9rem;
    transition: all 0.3s ease;
    backdrop-filter: blur(10px);
}

.clear-filter-btn:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: translateY(-2px);
}

.category-filter-section.expanded .filter-content {
    max-height: 500px;
}

.category-filter-container {
    max-height: 360px;
    overflow-y: auto;
    padding: 5px 5px;
}

/* Custom scrollbar for category filter */
.category-filter-container::-webkit-scrollbar {
    width: 6px;
}

.category-filter-container::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 3px;
}

.category-filter-container::-webkit-scrollbar-thumb {
    background: rgba(255, 255, 255, 0.3);
    border-radius: 3px;
}

.category-filter-container::-webkit-scrollbar-thumb:hover {
    background: rgba(255, 255, 255, 0.5);
}

.category-filter-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 15px;
    margin-bottom: 5px;
}

.category-card {
    background: rgba(255,255,255,0.12);
    padding: 8px 10px;
    border-radius: 10px;
    text-align: center;
    border: 1px solid rgba(255,255,255,0.18);
    backdrop-filter: blur(8px);
    transition: .25s;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
}

.category-card:hover {
    transform: translateY(-5px);
    background: rgba(255,255,255,0.2);
}

.category-card.active {
    background: rgba(255,255,255,0.28);
    border-color: rgba(255,255,255,0.5);
    box-shadow: 0 0 10px rgba(255,255,255,0.4);
}


.category-link {
    text-decoration: none;
    color: white;
    display: flex;
    align-items: center;
    gap: 8px;
    width: 100%;
}

.category-icon {
    font-size: 1.3rem;
    flex-shrink: 0;
}

.category-name {
    display: block;
    font-weight: 500;
    font-size: 0.85rem;
    flex: 1;
    text-align: left;
}

.product-count {
    display: block;
    font-size: 0.75rem;
    opacity: 0.8;
    background: rgba(255, 255, 255, 0.2);
    padding: 2px 6px;
    border-radius: 8px;
    flex-shrink: 0;
}

.active-filter-indicator {
    background: rgba(255, 255, 255, 0.15);
    padding: 15px 20px;
    border-radius: 10px;
    text-align: center;
    backdrop-filter: blur(10px);
    border-left: 4px solid #ffd700;
    margin-bottom: 20px;
}

.filtered-count {
    opacity: 0.9;
    font-size: 0.9rem;
}

/* No Products State */
.no-products {
    text-align: center;
    padding: 60px 20px;
    background: #f8f9fa;
    border-radius: 15px;
    margin: 30px 0;
}

.no-products-icon {
    font-size: 4rem;
    margin-bottom: 20px;
}

.no-products h3 {
    color: #6c757d;
    margin-bottom: 10px;
}

.no-products p {
    color: #868e96;
    margin-bottom: 20px;
}

/* ===== Global pagination buttons ===== */
.btn {
    padding: 10px 18px;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    display: inline-block;
}

.btn-primary {
    background: #007BFF;
    color: #fff;
}

.btn-secondary {
    background: #6c757d;
    color: #fff;
}

.btn-danger {
    background: #dc3545;
    color: #fff;
}

.btn-success {
    background: #28a745;
    color: #fff;
}


	/* --------------------------------------
    | Search Section Styles
    | -------------------------------------- */
    .action-section {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        gap: 15px;
    }

    .search-form {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 100%;
        gap: 15px;
        flex-wrap: wrap; /* Allow wrapping on smaller screens */
    }

    .search-box {
		display: flex;
		align-items: center;
		background: white;
		border: 1px solid #ccc;
		padding: 5px 10px;
	}

    .search-box:focus-within {
        box-shadow: 0 4px 15px rgba(0, 105, 137, 0.4);
    }

    .search-input {
        flex-grow: 1;
        padding: 12px 15px;
        border: none;
        font-size: 1rem;
        outline: none;
    }

    .search-btn, .clear-search-btn {
        padding: 12px 15px;
        border: none;
        color: white;
        font-weight: 600;
        cursor: pointer;
        transition: background-color 0.3s ease;
        border-radius: 14px;
    }

    .search-btn {
        background-color: #006989;
        white-space: nowrap;
    }

    .search-btn:hover {
        background-color: #005a73;
    }

    .clear-search-btn {
        background-color: #dc3545; /* Red for clear */
        text-decoration: none;
    }
    
    .clear-search-btn:hover {
        background-color: #c82333;
    }

	/* --- 4. HEADER ACTIONS (Cart & Heart) --- */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 20px; /* Space between Heart and Cart */
            margin-left: 20px;
        }

        .action-link {
            text-decoration: none;
            transition: transform 0.2s;
            display: flex;
            align-items: center;
            position: relative;
        }

        .action-link:hover {
            transform: scale(1.1); /* Subtle zoom on hover */
        }
	</style>
</head>

<style>
    /* --- COMPACT ADMIN NAVBAR --- */
    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        /* Reduced padding to give more room for text */
        padding: 10px 20px; 
        background-color: #fff;
        box-shadow: 0 2px 15px rgba(0,0,0,0.05);
        width: 100%;
        box-sizing: border-box; /* Ensures padding doesn't overflow width */
    }

    .navbar-brand img {
        height: 40px; 
        width: auto;
        display: block;
    }

    #nav-menu {
        display: flex;
        align-items: center;
        list-style: none;
        margin: 0;
        padding: 0;
        /* Reduced gap to fit all items */
        gap: 15px; 
    }

    .nav-item {
        margin: 0;
        flex-shrink: 0; /* Prevents items from squishing */
    }

    .nav-link {
        text-decoration: none;
        color: #4a5568;
        font-weight: 600; /* Made slightly bolder for readability */
        /* Smaller font size to fit long text */
        font-size: 0.90rem; 
        transition: all 0.2s ease;
        white-space: nowrap;
        padding: 5px 0; /* Increases hit area without taking width */
    }

    .nav-link:hover {
        color: #006989;
        transform: translateY(-1px);
    }

    #logout {
        background-color: #000;
        color: #fff !important;
        /* Compact button padding */
        padding: 6px 18px; 
        border-radius: 50px;
        font-weight: 600;
        box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        font-size: 0.85rem;
    }

    #logout:hover {
        background-color: #333;
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(0,0,0,0.2);
    }

    /* --- RESPONSIVE FIX --- */
    /* If screen is smaller than 1400px (Laptop), switch to vertical mode 
       to prevent "out of fit" / overflowing issues */
    @media (max-width: 1400px) {
        .navbar {
            flex-direction: column;
            gap: 15px;
            padding: 15px;
        }
        
        #nav-menu {
            flex-wrap: wrap; /* Allows items to wrap if needed */
            justify-content: center;
            gap: 12px 20px; /* Row gap 12px, Column gap 20px */
            width: 100%;
        }

        .nav-link {
            font-size: 0.85rem; /* Can be slightly larger in this mode */
        }
    }

    /* PRICE FILTER BUTTON */
    .price-filter-button-container {
        margin-right: 7px;
    }

    .price-filter-btn {
        display: flex;
        align-items: center;
        gap: 2px;
        padding: 20px 20px;
        background: linear-gradient(135deg, #006989, #005a73);
        color: white;
        border: none;
        border-radius: 20px;
        font-weight: 600;
        font-size: 0.95rem;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 4px 15px rgba(0, 105, 137, 0.3);
        white-space: nowrap;
    }

    .price-filter-btn:hover {
        background: linear-gradient(135deg, #005a73, #004d61);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 105, 137, 0.4);
    }

    .price-filter-btn:active {
        transform: translateY(0);
    }

    .price-filter-btn .fa-filter {
        font-size: 1.1rem;
    }

    .filter-active-indicator {
        color: #ffd700;
        font-size: 1.5rem;
        margin-left: 5px;
        animation: pulse 2s infinite;
    }

    @keyframes pulse {
        0% { opacity: 1; }
        50% { opacity: 0.5; }
        100% { opacity: 1; }
    }

    /* PRICE FILTER MODAL */
    .price-filter-modal {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        z-index: 1000;
    }

    .modal-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(5px);
    }

    .modal-content {
        position: relative;
        background: white;
        width: 90%;
        max-width: 450px;
        margin: 50px auto;
        border-radius: 20px;
        box-shadow: 0 25px 50px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        animation: modalSlideIn 0.3s ease;
    }

    @keyframes modalSlideIn {
        from {
            opacity: 0;
            transform: translateY(-50px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .modal-header {
        background: linear-gradient(135deg, #006989, #005a73);
        color: white;
        padding: 20px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .modal-header h3 {
        margin: 0;
        font-size: 1.4rem;
        font-weight: 600;
    }

    .modal-close {
        background: none;
        border: none;
        color: white;
        font-size: 2rem;
        cursor: pointer;
        line-height: 1;
        padding: 0;
        width: 30px;
        height: 30px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        transition: background-color 0.3s;
    }

    .modal-close:hover {
        background: rgba(255, 255, 255, 0.2);
    }

    .modal-body {
        padding: 25px;
    }

    .price-range-display {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 12px;
        margin-bottom: 25px;
        text-align: center;
    }

    .range-text {
        font-size: 1.1rem;
        color: #333;
    }

    .range-text strong {
        color: #006989;
    }

    /* Slider Styles */
    .slider-container {
        margin-bottom: 20px;
    }

    .slider-wrapper {
        position: relative;
        height: 40px;
        margin: 30px 0 40px;
    }

    .price-slider {
        position: absolute;
        width: 100%;
        height: 6px;
        background: transparent;
        pointer-events: none;
        -webkit-appearance: none;
        appearance: none;
    }

    .price-slider::-webkit-slider-thumb {
        pointer-events: auto;
        -webkit-appearance: none;
        appearance: none;
        width: 24px;
        height: 24px;
        background: white;
        border-radius: 50%;
        cursor: pointer;
        box-shadow: 0 2px 10px rgba(0,0,0,0.3);
        border: 3px solid #006989;
        transition: all 0.2s;
    }

    .price-slider::-webkit-slider-thumb:hover {
        transform: scale(1.1);
        box-shadow: 0 4px 15px rgba(0,0,0,0.4);
    }

    .price-slider::-moz-range-thumb {
        width: 24px;
        height: 24px;
        background: white;
        border-radius: 50%;
        cursor: pointer;
        border: 3px solid #006989;
    }

    .slider-track {
        position: absolute;
        top: 50%;
        left: 0;
        right: 0;
        height: 6px;
        background: #e0e0e0;
        border-radius: 3px;
        transform: translateY(-50%);
        z-index: -1;
    }

    .slider-track::after {
        content: '';
        position: absolute;
        left: calc(var(--slider-min, 0) * 1%);
        right: calc(100% - var(--slider-max, 100) * 1%);
        height: 100%;
        background: linear-gradient(90deg, #006989, #00b4d8);
        border-radius: 3px;
    }

    .price-inputs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 25px;
    }

    .input-group {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .input-group label {
        color: #555;
        font-size: 0.95rem;
        font-weight: 500;
    }

    .price-input {
        padding: 12px 15px;
        border: 2px solid #e0e0e0;
        background: #f8f9fa;
        color: #333;
        border-radius: 10px;
        font-size: 1rem;
        outline: none;
        transition: all 0.3s;
    }

    .price-input:focus {
        border-color: #006989;
        background: white;
        box-shadow: 0 0 0 3px rgba(0, 105, 137, 0.1);
    }

    .preset-buttons {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 10px;
        margin-top: 15px;
    }

    .preset-btn {
        padding: 10px 15px;
        background: #f0f0f0;
        border: 2px solid transparent;
        border-radius: 10px;
        color: #555;
        font-weight: 500;
        cursor: pointer;
        transition: all 0.3s;
        font-size: 0.9rem;
    }

    .preset-btn:hover {
        background: #e0e0e0;
    }

    .preset-btn.active {
        background: #006989;
        color: white;
        border-color: #005a73;
    }

    .modal-footer {
        display: flex;
        justify-content: space-between;
        padding: 20px 25px;
        background: #f8f9fa;
        border-top: 1px solid #e0e0e0;
    }

    .btn-clear, .btn-apply {
        padding: 12px 30px;
        border: none;
        border-radius: 12px;
        font-weight: 600;
        font-size: 1rem;
        cursor: pointer;
        transition: all 0.3s;
    }

    .btn-clear {
        background: #e0e0e0;
        color: #666;
    }

    .btn-clear:hover {
        background: #d0d0d0;
    }

    .btn-apply {
        background: linear-gradient(135deg, #006989, #005a73);
        color: white;
        box-shadow: 0 4px 15px rgba(0, 105, 137, 0.3);
    }

    .btn-apply:hover {
        background: linear-gradient(135deg, #005a73, #004d61);
        transform: translateY(-2px);
        box-shadow: 0 6px 20px rgba(0, 105, 137, 0.4);
    }

    /* Responsive */
    @media (max-width: 768px) {
        .price-filter-button-container {
            margin-right: 10px;
        }
        
        .price-filter-btn {
            padding: 8px 15px;
            font-size: 0.9rem;
        }
        
        .price-filter-btn span {
            display: none;
        }
        
        .price-filter-btn .fa-filter {
            font-size: 1.2rem;
        }
        
        .modal-content {
            width: 95%;
            margin: 20px auto;
        }
        
        .price-inputs {
            grid-template-columns: 1fr;
            gap: 15px;
        }
        
        .preset-buttons {
            grid-template-columns: 1fr;
        }
    }

    .cart-link {
        position: relative;
        display: inline-block;
    }

    .cart-counter {
        position: absolute;
        top: -8px;
        right: -8px;
        background-color: #dc3545;
        color: white;
        border-radius: 50%;
        width: 20px;
        height: 20px;
        font-size: 12px;
        font-weight: bold;
        display: flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        transition: all 0.3s ease;
    }

    .cart-counter.pulse {
        animation: pulse 0.5s ease-in-out;
    }

    @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.2); }
        100% { transform: scale(1); }
    }
</style>

<body>
	<nav class="navbar" id="mainNav">
		<!-- LOGO -->
			<a class="navbar-brand" href="/landing_<?= strtolower($_user->role) ?>.php">
				<img id="logo" src="/images/logo.png" alt="Logo">
			</a>
		<ul id="nav-menu">
			<?php if ($_user?->role == 'Member'): ?>
				<li class="nav-item"><a class="nav-link" href="/product/list.php">Product</a></li>
				<li class="nav-item"><a class="nav-link" href="/order/history.php">Track Order</a></li>
				<li class="nav-item"><a class="nav-link" href="/chat/member/chat.php">Live Chat</a></li>
				<li class="nav-item"><a class="nav-link" href="/about_us.php">About Us</a></li>
			<?php endif ?>

			<?php if ($_user?->role == 'Admin'): ?>
				<li class="nav-item"><a class="nav-link" href="/category/category.php">Manage Category</a></li>
				<li class="nav-item"><a class="nav-link" href="/product/maintenance.php">Manage Products</a></li>
				<li class="nav-item"><a class="nav-link" href="/product/voucher.php">Vouchers</a></li>
				<li class="nav-item"><a class="nav-link" href="/order/maintenance.php">Manage Orders</a></li>
				<li class="nav-item"><a class="nav-link" href="/refund/refund_list.php">Manage Refunds</a></li>
				<li class="nav-item"><a class="nav-link" href="/chat/admin/chat_list.php">Manage Chat</a></li>
				<li class="nav-item"><a class="nav-link" href="/member/memberList.php">Manage Account</a></li>
			<?php endif ?>

			<li class="nav-item"><a class="nav-link"  href="/user/profile.php">Profile</a></li>
			<li class="nav-item"><a class="nav-link"  href="/user/password.php">Password</a></li>
			<li class="nav-item">
				<a class="nav-link" id="logout" href="/logout.php">Logout</a>
			</li>
		</ul>
	</nav>
	
	<?php
		// Determine if we should show the search-cart-section
		$showSearchSection = true;

		// List of pages where we DON'T want the search-cart-section
		$excludedPages = [
			'payment.php',
			'process-payment.php',
			'success.php',
			// Add other pages where you don't want search/category section
		];

		// Get current page filename
		$currentPage = basename($_SERVER['PHP_SELF']);

		// Hide section on excluded pages
		if (in_array($currentPage, $excludedPages)) {
			$showSearchSection = false;
		}
	?>
		<?php if ($_user?->role == 'Member' && $showSearchSection): ?>
			<section class="search-cart-section large-device-padding medium-device-padding small-device-padding">
			<!-- SEARCH SECTION -->
				<form method="get" action="/product/list.php" class="search-form">
                <div class="action-section">
                    <div class="price-filter-button-container">
                        <button type="button" class="price-filter-btn" id="openPriceFilter" title="Filter by Price">
                            <i class="fa fa-filter" aria-hidden="true"></i>
                            <?php if (($minPrice ?? 0) > 0 || ($maxPrice ?? 1000) < 1000): ?>
                                <span class="filter-active-indicator">•</span>
                            <?php endif; ?>
                        </button>
                    </div>
					<div class="search-box">
						<input type="text"
							name="search"
							value="<?= htmlspecialchars($searchQuery ?? '') ?>"
							placeholder="Search products"
							class="search-input">

						<button type="submit" class="search-btn">🔍</button>

						<?php if (($currentCategory ?? false) || ($searchQuery ?? '')): ?>
							<a href="list.php" class="clear-search-btn">🗑️</a>
						<?php endif; ?>
					</div>

					<input type="hidden" name="category" value="<?= htmlspecialchars($currentCategory ?? '') ?>">

					<!-- CATEGORY FILTER SECTION -->
					 <?php
					// Load categories if not already loaded (for pages that don't have it)
					if (!isset($categories) && $_user?->role == 'Member') {
						$categoryStm = $_db->query('SELECT * FROM productcategory ORDER BY categoryName');
						$categories = $categoryStm->fetchAll();
						
						// Count all products
						$countStm = $_db->query('SELECT COUNT(*) FROM product');
						$allProductsCount = $countStm->fetchColumn();
					}
					?>

					<?php if (isset($categories) && !empty($categories)): ?>
						<div class="category-filter-section">
							<div class="filter-header" id="filterToggle">
								<div class="filter-title">
									<h4 id="filterTitle">Filter by Category</h4>
									<span class="toggle-arrow">▼</span>
								</div>
							</div>

							<div class="filter-content" id="filterContent">
								<div class="category-filter-container">
									<div class="category-filter-grid">

										<!-- ALL PRODUCTS -->
										<div class="category-card <?= !$currentCategory ? 'active' : '' ?>">
											<a href="/product/list.php<?= ($searchQuery ?? '') ? '?search=' . urlencode($searchQuery) : '' ?>"
											class="category-link">
												<div class="category-icon">📦</div>
												<span class="category-name">All Products</span>
												<span class="product-count"><?= $allProductsCount ?? 0?></span>
											</a>
										</div>

										<!-- DYNAMIC CATEGORIES -->
										<?php if (isset($categories)): ?>
											<?php foreach ($categories as $cat): ?>
												<?php
												$countStm = $_db->prepare('SELECT COUNT(*) FROM product WHERE categoryID = ?');
												$countStm->execute([$cat->categoryID]);
												$productCount = $countStm->fetchColumn();
												?>
												<div class="category-card <?= $currentCategory == $cat->categoryID ? 'active' : '' ?>">
													<a href="/product/list.php?category=<?= $cat->categoryID ?><?= ($searchQuery ?? '') ? '&search=' . urlencode($searchQuery) : '' ?>"
													class="category-link">
														<div class="category-icon">
															<?php 
															$icons = [
																'Skincare' => '✨',
																'Hair Care' => '💇',
																'Body Care' => '🧴',
																'Health Supplements' => '💊',
																'Medical Devices' => '🏥',
																'Personal Hygiene' => '🧼',
																'Fragrances' => '🌸',
																'Makeup' => '💄',
																'Men\'s Grooming' => '🧔',
																'Wellness Essentials' => '🌿'
															];
															echo $icons[$cat->categoryName] ?? '📁';
															?>
														</div>
														<span class="category-name"><?= htmlspecialchars($cat->categoryName) ?></span>
														<span class="product-count"><?= $productCount ?></span>
													</a>
												</div>
											<?php endforeach; ?>
										<?php endif; ?>
									</div>
								</div>
							</div>
						</div>
					<?php endif; ?>
					<!-- CART -->
                    <div class="header-actions">
                        <a href="/product/favorites.php" class="action-link" title="My Favorites">
                            <i class="fa fa-heart" aria-hidden="true" style="font-size: 1.8lh; color: #dc3545;"></i>
                        </a>
                        
                        <a href="/order/cart.php" class="action-link cart-link" title="Shopping Cart">
                            <i class="fa fa-shopping-cart" aria-hidden="true" style="font-size: 1.8lh; color: #000;"></i>
                            <span class="cart-counter" id="live-cart-count">0</span>
                        </a>
                    </div>
				</form>
			</div>
		</section>
        <!-- PRICE FILTER MODAL -->
    <div class="price-filter-modal" id="priceFilterModal">
        <div class="modal-overlay" id="modalOverlay"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h3>Filter by Price</h3>
                <button class="modal-close" id="closePriceFilter">&times;</button>
            </div>
            <div class="modal-body">
                <div class="price-range-display">
                    <div class="range-text">
                        <span>Price Range: <strong>RM <span id="minPriceDisplay"><?= $minPrice ?? 0 ?></span> - 
                        RM <span id="maxPriceDisplay"><?= $maxPrice ?? 1000 ?></span></strong></span>
                    </div>
                </div>
                
                <div class="slider-container">
                    <div class="slider-wrapper">
                        <input type="range" 
                               id="minPriceSlider" 
                               min="0" 
                               max="1000" 
                               value="<?= $minPrice ?? 0 ?>" 
                               class="price-slider">
                        <input type="range" 
                               id="maxPriceSlider" 
                               min="0" 
                               max="1000" 
                               value="<?= $maxPrice ?? 1000 ?>" 
                               class="price-slider">
                        <div class="slider-track"></div>
                    </div>
                    
                    <div class="price-inputs">
                        <div class="input-group">
                            <label>Minimum Price (RM)</label>
                            <input type="number" 
                                   id="minPriceInput" 
                                   min="0" 
                                   max="1000" 
                                   value="<?= $minPrice ?? 0 ?>" 
                                   class="price-input">
                        </div>
                        <div class="input-group">
                            <label>Maximum Price (RM)</label>
                            <input type="number" 
                                   id="maxPriceInput" 
                                   min="0" 
                                   max="1000" 
                                   value="<?= $maxPrice ?? 1000 ?>" 
                                   class="price-input">
                        </div>
                    </div>
                    
                    <div class="preset-buttons">
                        <button type="button" class="preset-btn" data-min="0" data-max="50">Under RM50</button>
                        <button type="button" class="preset-btn" data-min="50" data-max="100">RM50-100</button>
                        <button type="button" class="preset-btn" data-min="100" data-max="200">RM100-200</button>
                        <button type="button" class="preset-btn" data-min="200" data-max="500">RM200-500</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn-clear" id="clearPriceFilter">Clear</button>
                <button class="btn-apply" id="applyPriceFilter">Apply Filter</button>
            </div>
        </div>
    </div>
	<?php endif ?>
		
<script>
    const filterToggle = document.getElementById('filterToggle');
    const filterContent = document.getElementById('filterContent');
    const filterSection = document.querySelector('.category-filter-section');
    const priceFilterModal = document.getElementById('priceFilterModal');
    const openPriceFilterBtn = document.getElementById('openPriceFilter');
    const closePriceFilterBtn = document.getElementById('closePriceFilter');
    const modalOverlay = document.getElementById('modalOverlay');
    const minPriceSlider = document.getElementById('minPriceSlider');
    const maxPriceSlider = document.getElementById('maxPriceSlider');
    const minPriceInput = document.getElementById('minPriceInput');
    const maxPriceInput = document.getElementById('maxPriceInput');
    const minPriceDisplay = document.getElementById('minPriceDisplay');
    const maxPriceDisplay = document.getElementById('maxPriceDisplay');
    const applyPriceFilterBtn = document.getElementById('applyPriceFilter');
    const clearPriceFilterBtn = document.getElementById('clearPriceFilter');
    const presetButtons = document.querySelectorAll('.preset-btn');

    // Check saved state from sessionStorage
    const savedState = sessionStorage.getItem('filterExpanded');
    
    // Only expand if explicitly saved as 'true', otherwise keep collapsed
    if (savedState === 'true') {
        filterSection.classList.add('expanded');
    }

    filterToggle.addEventListener('click', function(e) {
        // Don't toggle if clicking the clear filter button
        if (e.target.closest('.clear-filter-btn')) return;

        filterSection.classList.toggle('expanded');

        // Save state to sessionStorage
        sessionStorage.setItem(
            'filterExpanded',
            filterSection.classList.contains('expanded')
        );
    });
	// Change title to selected category
	document.addEventListener("DOMContentLoaded", () => {
		const filterTitle = document.getElementById("filterTitle");
		const activeCard = document.querySelector(".category-card.active .category-name");

		if (activeCard) {
			filterTitle.textContent = activeCard.textContent.trim();
		}
	});

    openPriceFilterBtn.addEventListener('click', () => {
        priceFilterModal.style.display = 'block';
        document.body.style.overflow = 'hidden';
        updateSliderTrack();
    });

    function closeModal() {
        priceFilterModal.style.display = 'none';
        document.body.style.overflow = 'auto';
    }

    closePriceFilterBtn.addEventListener('click', closeModal);
    modalOverlay.addEventListener('click', closeModal);

    // Close modal with Escape key
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && priceFilterModal.style.display === 'block') {
            closeModal();
        }
    });

    function updateSliderTrack() {
        const minPercent = (minPriceSlider.value / 1000) * 100;
        const maxPercent = (maxPriceSlider.value / 1000) * 100;
        document.documentElement.style.setProperty('--slider-min', minPercent);
        document.documentElement.style.setProperty('--slider-max', maxPercent);
    }

    function updatePriceDisplay() {
        minPriceDisplay.textContent = minPriceSlider.value;
        maxPriceDisplay.textContent = maxPriceSlider.value;
        minPriceInput.value = minPriceSlider.value;
        maxPriceInput.value = maxPriceSlider.value;
        updateSliderTrack();
    }

    minPriceSlider.addEventListener('input', () => {
        if (parseInt(minPriceSlider.value) > parseInt(maxPriceSlider.value)) {
            maxPriceSlider.value = minPriceSlider.value;
        }
        updatePriceDisplay();
    });

    maxPriceSlider.addEventListener('input', () => {
        if (parseInt(maxPriceSlider.value) < parseInt(minPriceSlider.value)) {
            minPriceSlider.value = maxPriceSlider.value;
        }
        updatePriceDisplay();
    });

    minPriceInput.addEventListener('change', () => {
        let value = Math.max(0, Math.min(1000, parseInt(minPriceInput.value) || 0));
        minPriceSlider.value = value;
        if (value > parseInt(maxPriceSlider.value)) {
            maxPriceSlider.value = value;
        }
        updatePriceDisplay();
    });

    maxPriceInput.addEventListener('change', () => {
        let value = Math.max(0, Math.min(1000, parseInt(maxPriceInput.value) || 1000));
        maxPriceSlider.value = value;
        if (value < parseInt(minPriceSlider.value)) {
            minPriceSlider.value = value;
        }
        updatePriceDisplay();
    });

    presetButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const min = btn.dataset.min;
            const max = btn.dataset.max;
            minPriceSlider.value = min;
            maxPriceSlider.value = max;
            updatePriceDisplay();
            
            presetButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    clearPriceFilterBtn.addEventListener('click', () => {
        minPriceSlider.value = 0;
        maxPriceSlider.value = 1000;
        updatePriceDisplay();
        presetButtons.forEach(b => b.classList.remove('active'));
    });

    applyPriceFilterBtn.addEventListener('click', () => {
        const minPrice = minPriceSlider.value;
        const maxPrice = maxPriceSlider.value;
        
        // Build URL to list.php
        let url = '/product/list.php?min_price=' + minPrice + '&max_price=' + maxPrice;
        
        // Preserve existing search query if present
        const searchInput = document.querySelector('input[name="search"]');
        if (searchInput && searchInput.value) {
            url += '&search=' + encodeURIComponent(searchInput.value);
        }
        
        // Preserve category if present
        const categoryInput = document.querySelector('input[name="category"]');
        if (categoryInput && categoryInput.value) {
            url += '&category=' + encodeURIComponent(categoryInput.value);
        }
        
        closeModal();
        window.location.href = url;
    });

    document.addEventListener('DOMContentLoaded', () => {
        const currentMin = parseInt(minPriceSlider.value);
        const currentMax = parseInt(maxPriceSlider.value);
        
        presetButtons.forEach(btn => {
            const btnMin = parseInt(btn.dataset.min);
            const btnMax = parseInt(btn.dataset.max);
            if (currentMin === btnMin && currentMax === btnMax) {
                btn.classList.add('active');
            }
        });
        
        updateSliderTrack();
    });

    function updateCartCount() {
        $.ajax({
            url: '/order/ajax_cart_count.php',
            method: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#live-cart-count').text(response.count);
                }
            },
            error: function() {
                console.log('Failed to update cart count');
            }
        });
    }

    // Update immediately on page load
    $(document).ready(function() {
        updateCartCount();
    });

    // Update every 5 seconds (optional - for real-time updates)
    setInterval(updateCartCount, 5000);

    $('select').on('change', e => e.target.form.submit());
</script>