<?php
include_once __DIR__ . '/../_base.php';

// Get form data
$id           = req('id'); // hidden input for editing
$event_date   = req('event_date');
$name         = req('name');
$description  = req('description');
$event_time   = req('event_time') ?: null;
$is_promo     = req('is_promo') ?: 0;
$product_ids  = req('product_id') ?? []; 

try {
    // Start transaction
    $_db->beginTransaction();

    if ($id) {
        // Update existing event
        $stmt = $_db->prepare("
            UPDATE product_event 
            SET name=?, description=?, event_time=?, is_promo=? 
            WHERE id=?
        ");
        $stmt->execute([$name, $description, $event_time, $is_promo, $id]);

        $event_id = $id;

        // Delete old promo products first
        $_db->prepare("DELETE FROM product_event_item WHERE event_id=?")
             ->execute([$event_id]);

    } else {
        // Insert new event
        $stmt = $_db->prepare("
            INSERT INTO product_event (event_date, event_time, name, description, is_promo)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$event_date, $event_time, $name, $description, $is_promo]);
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

    // Commit
    $_db->commit();

    header("Location: ../landing_admin.php?success=1");
    exit;

} catch (PDOException $e) {
    $_db->rollBack();
    die("Error saving event: " . $e->getMessage());
}
