<?php
include '../_base.php';

// Authenticated users
auth("Admin", "Superadmin");

// Get member ID from URL
$memberID = req('id');

if (!$memberID) {
    temp('err', 'Invalid member ID');
    redirect('memberList.php');
}

// Check if member exists
$stm = $_db->prepare('SELECT * FROM user WHERE id = ?');
$stm->execute([$memberID]);
$member = $stm->fetch();

if (!$member) {
    temp('err', 'Member not found');
    redirect('memberList.php');
}

// Delete photo if exists
if ($member->photo && file_exists("../uploads/{$member->photo}")) {
    unlink("../uploads/{$member->photo}");
}

// Soft Delete member
$stm = $_db->prepare('
    UPDATE user
    SET is_active = -1
    WHERE id = ?
');
$stm->execute([$memberID]);

temp('info', 'Member set to inactive successfully');
redirect('memberList.php');
