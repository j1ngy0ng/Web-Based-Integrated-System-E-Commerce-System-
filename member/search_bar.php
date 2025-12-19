<?php

?>

<form method="get" style="display:flex; gap:10px;">
    <input type="text"
           name="q"
           placeholder="<?= htmlspecialchars($placeholder ?? 'Search...') ?>"
           value="<?= htmlspecialchars($q ?? '') ?>"
           style="padding:8px 12px; border-radius:6px; border:1px solid #ccc; min-width:220px;">

    <?php if (!empty($extraFields)): ?>
        <?php foreach ($extraFields as $name => $value): ?>
            <input type="hidden" name="<?= htmlspecialchars($name) ?>"
                   value="<?= htmlspecialchars($value) ?>">
        <?php endforeach; ?>
    <?php endif; ?>

    <button type="submit" class="btn btn-primary">Search</button>
</form>
