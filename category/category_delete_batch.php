<?php
include '../_base.php';
auth("Admin","Superadmin");

if (!is_post() || empty($_POST['deleteIDs'])) {
    temp('err', 'No categories selected');
    redirect('category.php');
}

$ids = $_POST['deleteIDs'];
$ids = array_map('intval', $ids); // sanitize

$in  = str_repeat('?,', count($ids) - 1) . '?';
$stm = $_db->prepare("DELETE FROM ProductCategory WHERE categoryID IN ($in)");
$stm->execute($ids);

temp('info', count($ids) . " categories deleted successfully");
redirect('category.php');
