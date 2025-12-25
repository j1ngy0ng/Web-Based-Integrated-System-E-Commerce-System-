<?php
include_once __DIR__ . '/../_base.php';
session_start();

$year  = req('year');
$month = req('month');



// ================= Get form data =================
$id          = req('id'); // hidden input for editing
$event_date  = req('event_date');
$name        = req('name');
$description = req('description');
$start_time  = req('start_time') ?: null;
$end_time    = req('end_time') ?: null;
$is_promo    = req('is_promo') ?: 0;
$product_ids = req('product_id') ?? [];

// ================= Validation =================
$errors = [];

// General required fields
if (!$event_date) $errors[] = "Event date is required.";
if (!$name) $errors[] = "Event name is required.";

// Start/end time validation
$time_error = '';
if ($start_time && $end_time && $start_time >= $end_time) {
    $time_error = "End time must be after start time.";
}

if ($time_error) {
    $_SESSION['old_input'] = $_POST;
    $_SESSION['event_errors'] = [$time_error]; // only time error
    header("Location: ../landing_admin.php");
    exit;
}

try {
    // Start transaction
    $_db->beginTransaction();

    if ($id) {
        // Update existing event
        $stmt = $_db->prepare("
            UPDATE product_event 
            SET name=?, description=?, start_time=?, end_time=?, is_promo=? 
            WHERE id=?
        ");
        $stmt->execute([$name, $description, $start_time, $end_time, $is_promo, $id]);

        $event_id = $id;

        // Delete old promo products first
        $_db->prepare("DELETE FROM product_event_item WHERE event_id=?")
            ->execute([$event_id]);
    } else {
        // Insert new event
        $stmt = $_db->prepare("
            INSERT INTO product_event (event_date, start_time, end_time, name, description, is_promo)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$event_date, $start_time, $end_time, $name, $description, $is_promo]);
        $event_id = $_db->lastInsertId();
    }

    // Insert promo products if event is a promotion
    if ($is_promo && !empty($product_ids)) {
        $stm_item = $_db->prepare("
            INSERT INTO product_event_item (event_id, product_id)
            VALUES (?, ?)
        ");
        foreach ($product_ids as $pid) {
            $stm_item->execute([$event_id, $pid]);
        }
    }

    $_db->commit();

    header("Location: ../landing_admin.php?year=$year&month=$month&success=1");

    exit;
} catch (PDOException $e) {
    $_db->rollBack();

    $_SESSION['event_errors'] = ["Database error: " . $e->getMessage()];
    $_SESSION['old_input'] = $_POST;
    header("Location: ../landing_admin.php");
    exit;
}
