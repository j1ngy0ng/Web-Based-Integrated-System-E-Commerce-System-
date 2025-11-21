<!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<link href="/css/creative.css" rel="stylesheet" type="text/css">
	<link href="/font-awesome/css/font-awesome.css" rel="stylesheet">
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
	<script src="/js/app.js"></script>
</head>

<body>
	<nav class="navbar" id="mainNav">

		<ul class="navbar-nav large-device-padding medium-device-padding small-device-padding" id="nav-menu">
			<?php if ($_user?->role == 'Member'): ?>
				<li class="nav-item"><a class="nav-link" href="../product/detail.php">Product</a></li>
				<li class="nav-item"><a class="nav-link" href="/order/history.php">Track Order</a></li>
				<li class="nav-item"><a class="nav-link" href="contact.php">Contact Us</a></li>
			<?php endif ?>

			<?php if ($_user?->role == 'Admin'): ?>
				<li class="nav-item"><a class="nav-link" href="/category/category.php">Manage Category</a></li>
				<li class="nav-item"><a class="nav-link" href="manageProducts.php">Manage Products</a></li>
				<li class="nav-item"><a class="nav-link" href="/order/maintenance.php">Manage Orders</a></li>
				<li class="nav-item"><a class="nav-link" href="manageAccount.php">Manage Account</a></li>
			<?php endif ?>

			<li class="nav-item"><a class="nav-link"  href="/user/profile.php">Profile</a></li>
			<li class="nav-item"><a class="nav-link"  href="/user/password.php">Password</a></li>
			<li class="nav-item">
				<a class="nav-link" id="logout" href="../logout.php">Logout</a>
			</li>



		</ul>


	</nav>

	<?php if ($_user?->role == 'Member'): ?>
		<!-- Search and Cart Section -->
		<section class="search-cart-section large-device-padding medium-device-padding small-device-padding">
			<a class="navbar-brand" href="/landing_<?=strtolower($_user->role)?>.php">
				<img id="logo" src="/images/logo.png" alt="Logo">
			</a>

			<div class="search-container">
				<input type="text" placeholder="Search products..." class="search-box">
				<button class="search-btn"><i class="fa fa-search" aria-hidden="true"></i></button>
			</div>

			<div class="header-cart">
				<a href="/order/cart.php" class="cart-link">
					<i class="fa fa-shopping-cart" aria-hidden="true" style="font-size: 1.5lh; color:black;"></i>
				</a>
			</div>
		<?php endif ?>

		</section>