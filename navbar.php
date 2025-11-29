<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="/css/creative.css" rel="stylesheet" type="text/css">
	<link href="/font-awesome/css/font-awesome.css" rel="stylesheet">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
	<script src="/js/app.js"></script>
	<style>
		
		/* Category Filter Header Styles */
		.filter-header {
			min-width: 150px;
		}
		.category-filter-section {
			position: relative;
			z-index: 20;
			background-color:#005a73;
			border-radius: 34px;
			margin-left: 30px;
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

	/* --------------------------------------
    | Search Section Styles
    | -------------------------------------- */
    .search-form {
        display: flex;
        justify-content: center;
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
	</style>
</head>

<body>
	<nav class="navbar" id="mainNav">
		<?php if ($_user?->role == 'Member'): ?>
			<!-- LOGO -->
			<a class="navbar-brand" href="/landing_<?= strtolower($_user->role) ?>.php">
				<img id="logo" src="/images/logo.png" alt="Logo">
			</a>
		<?php endif ?>
		<ul class="navbar-nav large-device-padding medium-device-padding small-device-padding" id="nav-menu">
			<?php if ($_user?->role == 'Member'): ?>
				<li class="nav-item"><a class="nav-link" href="/product/list.php">Product</a></li>
				<li class="nav-item"><a class="nav-link" href="/order/history.php">Track Order</a></li>
				<li class="nav-item"><a class="nav-link" href="/chat/member/chat.php">Live Chat</a></li>
				<li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
			<?php endif ?>

			<?php if ($_user?->role == 'Admin'): ?>
				<li class="nav-item"><a class="nav-link" href="/category/category.php">Manage Category</a></li>
				<li class="nav-item"><a class="nav-link" href="/product/maintenance.php">Manage Products</a></li>
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
			'cart.php',
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
			<div class="action-section">
				<form method="get" action="/product/list.php" class="search-form">
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
					<div class="header-cart">
						<a href="/order/cart.php" class="cart-link">
							<i class="fa fa-shopping-cart" aria-hidden="true" style="font-size: 1.8lh; color:black;"></i>
						</a>
					</div>
				</form>
			</div>
		</section>
	<?php endif ?>
		
<script>
    const filterToggle = document.getElementById('filterToggle');
    const filterContent = document.getElementById('filterContent');
    const filterSection = document.querySelector('.category-filter-section');

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

    $('select').on('change', e => e.target.form.submit());
</script>