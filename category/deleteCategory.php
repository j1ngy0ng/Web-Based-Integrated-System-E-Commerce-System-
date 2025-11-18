<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth();

// --------------------------------------------------------------------
// Get categoryID from URL
$categoryID = req('categoryID');

if (!$categoryID) {
    temp('err', 'Invalid category ID');
    redirect('category.php');
}

// --------------------------------------------------------------------
// Delete category
$stm = $_db->prepare('DELETE FROM ProductCategory WHERE categoryID = ?');
$stm->execute([$categoryID]);

temp('info', 'Category deleted successfully');
redirect('category.php');
