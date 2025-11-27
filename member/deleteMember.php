<?php
include '../_base.php';

// Authenticated users
auth();

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

// Delete member
$stm = $_db->prepare('DELETE FROM user WHERE id = ?');
$stm->execute([$memberID]);

temp('info', 'Member deleted successfully');
redirect('memberList.php');
