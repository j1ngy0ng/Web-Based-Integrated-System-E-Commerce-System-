<?php
include '../_base.php';

// ====================================================================
// BACKEND LOGIC
// ====================================================================

auth("Admin");
$productID = req('productID');

// ADDITION 1: Flag to know if we are in "add" mode or "edit" mode
$is_new_product_page_load = empty($productID); // True if productID is not set when the page initially loads

$product = new stdClass();
$product->name = ''; $product->price = ''; $product->stock = ''; $product->categoryID = ''; $product->description = ''; $product->mainPhoto = '';

if ($productID) {
    $sql = "SELECT p.*, ph.photoURL AS mainPhoto FROM product p LEFT JOIN productPhoto ph ON p.productID = ph.productID AND ph.is_main = 1 WHERE p.productID = ?";
    $stm = $_db->prepare($sql);
    $stm->execute([$productID]);
    $product = $stm->fetch() ?: redirect('maintenance.php');
}

$cat = $_db->query("SELECT * FROM ProductCategory ORDER BY categoryName")->fetchAll();

/**
 * Helper function to create a URL-friendly slug from a string.
 * Used for generating clean filenames.
 */
function slugify($text) {
    $text = preg_replace('~[^\\pL\d]+~u', '-', $text); // Replace non letter or digits by -
    $text = trim($text, '-'); // Trim dashes from start and end
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text); // Transliterate non-ASCII characters
    $text = strtolower($text); // Convert to lowercase
    $text = preg_replace('~[^-\w]+~', '', $text); // Remove unwanted characters
    if (empty($text)) {
        return 'n-a'; // Fallback if text becomes empty
    }
    return $text;
}


if (is_post()) {
    // --- DELETE PRODUCT LOGIC ---
    if (req('action_type') === 'delete' && $productID) {
        $_db->beginTransaction();
        try {
            $stm_photos = $_db->prepare("SELECT photoURL FROM productPhoto WHERE productID = ?");
            $stm_photos->execute([$productID]);
            $photos_to_delete = $stm_photos->fetchAll();

            $stm_delete_photos_db = $_db->prepare("DELETE FROM productPhoto WHERE productID = ?");
            $stm_delete_photos_db->execute([$productID]);

            $stm_delete_product = $_db->prepare("DELETE FROM product WHERE productID = ?");
            $stm_delete_product->execute([$productID]);

            $_db->commit();

            foreach ($photos_to_delete as $photo) {
                $filePath = "../products/" . $photo->photoURL;
                if (file_exists($filePath)) {
                    unlink($filePath);
                }
            }

            temp('info', 'Product and all associated photos deleted successfully.');
            redirect('maintenance.php');

        } catch (Exception $e) {
            $_db->rollBack();
            temp('error', 'Error deleting product: ' . $e->getMessage());
            redirect('maintenance.php'); // Redirect to maintenance list even on error
        }
    } else { // --- NORMAL ADD/UPDATE PRODUCT LOGIC ---
        $name = req('name'); $price = req('price'); $stock = req('stock'); $categoryID = req('categoryID'); $description = req('description');
        if ($name == '') $_err['name'] = 'Required';
        if ($price == '' || $price < 0) $_err['price'] = 'Invalid';
        if ($stock == '' || $stock < 0) $_err['stock'] = 'Invalid';
        if ($categoryID == '') $_err['categoryID'] = 'Required';

        if (!$_err) { // If NO validation errors
            // Use the flag set at the top to determine if this was an 'add new' operation
            $was_newly_added = $is_new_product_page_load;

            if ($productID) {
                $_db->prepare("UPDATE product SET name=?, price=?, stock=?, categoryID=?, description=? WHERE productID=?")->execute([$name, $price, $stock, $categoryID, $description, $productID]);
                temp('info', 'Product updated');
            } else {
                $_db->prepare("INSERT INTO product (name, price, stock, categoryID, description) VALUES (?, ?, ?, ?, ?)")->execute([$name, $price, $stock, $categoryID, $description]);
                $productID = $_db->lastInsertId();
                temp('info', 'Product added');
                // The $was_newly_added flag is already correctly set if $productID was initially empty.
            }

            // Generate a product slug for filenames
            $productSlug = slugify($name);
            if ($productSlug === 'n-a' && $productID && isset($product->name)) {
                $productSlug = slugify($product->name);
            }
            if ($productSlug === 'n-a') {
                $productSlug = 'product'; // Final fallback
            }


            // Handle Main Photo
            if (!empty($_FILES['mainPhoto']['tmp_name'])) {
                $ext = pathinfo($_FILES['mainPhoto']['name'], PATHINFO_EXTENSION);
                $fname = $productSlug . '_main_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                move_uploaded_file($_FILES['mainPhoto']['tmp_name'], "../products/$fname");
                // MODIFICATION 2: Only delete old photo if not a new product. New products don't have old photos to replace.
                if (!$was_newly_added && $product->mainPhoto && file_exists("../products/".$product->mainPhoto)) { unlink("../products/".$product->mainPhoto); }
                $exists = $_db->prepare("SELECT * FROM productPhoto WHERE productID=? AND is_main=1");
                $exists->execute([$productID]);
                if ($exists->fetch()) { $_db->prepare("UPDATE productPhoto SET photoURL=? WHERE productID=? AND is_main=1")->execute([$fname, $productID]); }
                else { $_db->prepare("INSERT INTO productPhoto (productID, photoURL, is_main) VALUES (?, ?, 1)")->execute([$productID, $fname]); }
            }

            // Handle Deletion of Existing Photos
            if (!empty($_POST['delete_photos'])) {
                foreach ($_POST['delete_photos'] as $pid) {
                    $old = $_db->prepare("SELECT photoURL FROM productPhoto WHERE photoID=?");
                    $old->execute([$pid]);
                    $row = $old->fetch();
                    if($row && file_exists("../products/".$row->photoURL)) { unlink("../products/".$row->photoURL); }
                    $_db->prepare("DELETE FROM productPhoto WHERE photoID=?")->execute([$pid]);
                }
            }

            // Handle New Additional Photos
            if(!empty($_FILES['finalAdditionalPhotos']['tmp_name'])) {
                $files = $_FILES['finalAdditionalPhotos'];
                if (is_array($files['tmp_name']) && count($files['tmp_name']) > 0 && !empty($files['tmp_name'][0])) {
                    for($i=0; $i<count($files['tmp_name']); $i++) {
                        if($files['tmp_name'][$i]) {
                            $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                            $fname = $productSlug . '_add_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                            move_uploaded_file($files['tmp_name'][$i], "../products/$fname");
                            $_db->prepare("INSERT INTO productPhoto (productID, photoURL, is_main) VALUES (?, ?, 0)")->execute([$productID, $fname]);
                        }
                    }
                }
            }

            // MODIFICATION 3: This is the critical redirect logic
            if ($was_newly_added) {
                redirect('maintenance.php'); // Redirect to maintenance.php if a new product was just added
            } else {
                redirect("detail_admin.php?productID=$productID"); // Stay on the detail_admin.php (edit) page if an existing product was updated
            }
        }
        // IMPORTANT: If there ARE errors ($_err is NOT empty),
        // we intentionally DO NOT redirect here.
        // This allows the form to render again with the $_POST values
        // and $_err messages.
    }
}

// Ensure $GLOBALS variables are populated correctly for rendering the form
// If there are POST errors, use the POST values. Otherwise, use product data.
$GLOBALS['name'] = $_POST['name'] ?? $product->name;
$GLOBALS['price'] = $_POST['price'] ?? $product->price;
$GLOBALS['stock'] = $_POST['stock'] ?? $product->stock;
$GLOBALS['categoryID'] = $_POST['categoryID'] ?? $product->categoryID;
$GLOBALS['description'] = $_POST['description'] ?? $product->description;

$_title = $productID ? 'Edit Product' : 'Add Product';
include '../navbar.php';
?>

<style>
    :root { --primary-color: #007BFF; --primary-dark: #0056b3; --danger-color: #dc3545; --gray-border: #ced4da; --gray-bg: #f8f9fa; --text-dark: #333; }
    form.form { max-width:650px; margin:40px auto; background:#fff; padding:30px 40px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.08); }
    form label.field-label { display:block; margin-bottom:8px; font-weight:600; margin-top:25px; color: var(--text-dark); }
    form input[type="text"], form input[type="number"], form select, form textarea { width:100%; padding:12px; border:1px solid var(--gray-border); border-radius:8px; font-size:1rem; box-sizing: border-box; transition: all 0.3s; }
    form input:focus, form select:focus, form textarea:focus { outline: none; border-color: var(--primary-color); box-shadow: 0 0 0 3px rgba(0,123,255,0.1); }
    form .err { color: var(--danger-color); font-size:.85rem; margin-top:5px; display: block; font-weight: 500; }
    form section.actions { margin-top:40px; display:flex; gap:15px; justify-content:flex-end; padding-top: 20px; border-top: 1px solid #eee; }
    form button { padding:12px 24px; border:none; background-color:var(--primary-color); color:white; font-size:1rem; font-weight:600; border-radius:8px; cursor:pointer; transition: background-color 0.2s; }
    form button:hover { background-color: var(--primary-dark); }
    form button[type="reset"] { background-color:#6c757d; }

    /* New Back Button Style */
    form .button-back {
        padding:12px 24px;
        border:1px solid var(--gray-border);
        background-color:#f0f0f0;
        color:var(--text-dark);
        font-size:1rem;
        font-weight:600;
        border-radius:8px;
        cursor:pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        transition: background-color 0.2s, border-color 0.2s;
    }
    form .button-back:hover {
        background-color:#e0e0e0;
        border-color:#c0c0c0;
    }

    /* --- PHOTO STYLES --- */
    .hidden-file-input { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
    .main-photo-upload-zone { position: relative; width: 100%; height: 280px; background: var(--gray-bg); border: 2px dashed var(--gray-border); border-radius: 12px; display: flex; justify-content: center; align-items: center; cursor: pointer; overflow: hidden; transition: all 0.3s ease; }
    .main-photo-upload-zone:hover { border-color: var(--primary-color); background: #f0f8ff; }
    .main-photo-upload-zone img.preview { width: 100%; height: 100%; object-fit: contain; padding: 10px; box-sizing: border-box; }
    .main-photo-upload-zone .placeholder { text-align: center; color: #999; }
    .main-photo-upload-zone .placeholder i { font-size: 48px; margin-bottom: 15px; display: block; color: var(--primary-color); opacity: 0.7; }

    .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 15px; margin-top: 15px; }
    .photo-card { position: relative; height: 110px; border-radius: 10px; overflow: hidden; border: 2px solid transparent; box-shadow: 0 2px 5px rgba(0,0,0,0.08); background: #fff; transition: all 0.2s; }
    .photo-card img { width: 100%; height: 100%; object-fit: cover; }

    /* Action Overlay (Trash for existing, X for new) */
    .photo-action-overlay { position: absolute; top: 5px; right: 5px; background: rgba(255, 255, 255, 0.95); border-radius: 50%; width: 32px; height: 32px; display: flex; justify-content: center; align-items: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.2); transition: all 0.2s; color: var(--danger-color); }
    .photo-action-overlay:hover { background: var(--danger-color); color: white; transform: scale(1.1); }
    .photo-card.existing-photo.is-deleted { border: 2px solid var(--danger-color); opacity: 0.6; }
    .photo-card.existing-photo.is-deleted img { filter: grayscale(100%); }

    /* Add Button */
    .add-photo-card { height: 110px; border: 2px dashed var(--gray-border); border-radius: 10px; display: flex; justify-content: center; align-items: center; flex-direction: column; cursor: pointer; background: var(--gray-bg); color: var(--primary-color); font-weight: 600; transition: all 0.3s; text-align: center; font-size: 0.9rem; }
    .add-photo-card i { font-size: 24px; margin-bottom: 8px; }
    .add-photo-card:hover { border-color: var(--primary-color); background: #f0f8ff; }
</style>

<form method="post" class="form" enctype="multipart/form-data" id="productForm">

    <label class="field-label">Product Name</label>
    <?= html_text('name', 'maxlength="100"') ?> <?= err('name') ?>

    <div style="display: flex; gap: 20px;">
        <div style="flex: 1;">
            <label class="field-label">Price (RM)</label>
            <input type="number" step="0.01" name="price" value="<?= $GLOBALS['price'] ?>"> <?= err('price') ?>
        </div>
        <div style="flex: 1;">
            <label class="field-label">Stock</label>
            <input type="number" name="stock" value="<?= $GLOBALS['stock'] ?>"> <?= err('stock') ?>
        </div>
    </div>

    <label class="field-label">Category</label>
    <select name="categoryID">
        <option value="">-- Select Category --</option>
        <?php foreach ($cat as $c): ?>
            <option value="<?= $c->categoryID ?>" <?= $GLOBALS['categoryID']==$c->categoryID?'selected':'' ?>> <?= htmlspecialchars($c->categoryName) ?> </option>
        <?php endforeach ?>
    </select>
    <?= err('categoryID') ?>

    <label class="field-label">Description</label>
    <?= html_textarea('description','maxlength="500" rows="4"') ?> <?= err('description') ?>

    <label class="field-label">Main Photo</label>
    <input type="file" name="mainPhoto" id="mainPhotoInput" class="hidden-file-input" accept="image/*" onchange="previewMainPhoto(this)">
    <label for="mainPhotoInput" class="main-photo-upload-zone" id="mainPhotoZone">
        <?php if($product->mainPhoto && file_exists("../products/".$product->mainPhoto)): ?>
            <img src="../products/<?= $product->mainPhoto ?>" class="preview">
        <?php else: ?>
            <div class="placeholder"><i class="fa fa-cloud-upload" aria-hidden="true"></i><span>Click to upload main image</span></div>
        <?php endif ?>
    </label>

    <label class="field-label">Additional Photos Gallery</label>
    <div style="font-size: 0.9rem; color: #666; margin-bottom: 10px;">
        Mark existing photos to delete, or remove new uploads before saving.
    </div>

    <div class="photo-grid" id="additionalPhotoGrid">
        <?php if($productID){
            $photos = $_db->prepare("SELECT * FROM productPhoto WHERE productID=? AND is_main=0 ORDER BY photoID");
            $photos->execute([$productID]);
            foreach($photos->fetchAll() as $ph): if(file_exists("../products/".$ph->photoURL)): ?>
            <div class="photo-card existing-photo" id="photo-card-<?= $ph->photoID ?>">
                <img src="../products/<?= $ph->photoURL ?>">
                <input type="checkbox" name="delete_photos[]" id="del-chk-<?= $ph->photoID ?>" value="<?= $ph->photoID ?>" class="hidden-file-input">
                <div class="photo-action-overlay" onclick="toggleDeleteExisting(<?= $ph->photoID ?>)" title="Mark for deletion">
                    <i class="fa fa-trash" aria-hidden="true"></i>
                </div>
            </div>
        <?php endif; endforeach; } ?>

        <input type="file" id="triggerAddInput" class="hidden-file-input" accept="image/*" multiple onchange="handleNewFilesSelect(this)">

        <input type="file" name="finalAdditionalPhotos[]" id="finalSubmissionInput" class="hidden-file-input" multiple>

        <label for="triggerAddInput" class="add-photo-card">
            <i class="fa fa-plus" aria-hidden="true"></i> Add Photos
        </label>
        </div>

    <section class="actions">
        <a href="maintenance.php" class="button-back">Back to Products</a>
        <button type="submit"><?= $productID ? 'Update Product' : 'Add Product' ?></button>
        <button type="reset">Reset</button>
    </section>
</form>

<script>
// 1. Main Photo Preview
function previewMainPhoto(input) {
    const zone = document.getElementById('mainPhotoZone');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => zone.innerHTML = '<img src="' + e.target.result + '" class="preview">';
        reader.readAsDataURL(input.files[0]);
    }
}

// 2. Existing Photo Deletion Toggle
function toggleDeleteExisting(photoID) {
    const checkbox = document.getElementById('del-chk-' + photoID);
    const card = document.getElementById('photo-card-' + photoID);
    const icon = card.querySelector('.photo-action-overlay i');
    checkbox.checked = !checkbox.checked;
    if (checkbox.checked) {
        card.classList.add('is-deleted');
        icon.className = 'fa fa-undo'; // Changed to 'fa fa-undo' for older Font Awesome versions
        card.querySelector('.photo-action-overlay').title = "Undo deletion";
    } else {
        card.classList.remove('is-deleted');
        icon.className = 'fa fa-trash'; // Changed back to 'fa fa-trash' for older Font Awesome versions
        card.querySelector('.photo-action-overlay').title = "Mark for deletion";
    }
}

// 3. Handle New File Selections
function handleNewFilesSelect(input) {
    const grid = document.getElementById('additionalPhotoGrid');
    const addBtnLabel = grid.querySelector('label.add-photo-card');

    if(input.files) {
        Array.from(input.files).forEach(file => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const card = document.createElement('div');
                card.classList.add('photo-card', 'new-preview-card');
                card.style.border = "2px solid #28a745"; // Green border for new files
                card.fileData = file;

                const img = document.createElement('img');
                img.src = e.target.result;

                const removeBtn = document.createElement('div');
                removeBtn.className = 'photo-action-overlay';
                removeBtn.innerHTML = '<i class="fa fa-trash" aria-hidden="true"></i>'; // Consistent trash icon
                removeBtn.title = "Remove this upload";

                removeBtn.onclick = function() {
                    card.remove();
                };

                card.appendChild(img);
                card.appendChild(removeBtn);
                grid.insertBefore(card, addBtnLabel);
            }
            reader.readAsDataURL(file);
        });
    }
    input.value = '';
}

// 4. Form Submission Interceptor for Add/Update
document.getElementById('productForm').addEventListener('submit', function(e) {
    const form = this;

    // A. Gather all file objects currently attached to visible '.new-preview-card' elements
    const newPreviewCards = document.querySelectorAll('.new-preview-card');
    const dataTransfer = new DataTransfer(); // A "virtual" file input container

    newPreviewCards.forEach(card => {
        if (card.fileData) {
           dataTransfer.items.add(card.fileData);
        }
    });

    // B. Stuff these gathered files into the hidden final input
    const finalInput = document.getElementById('finalSubmissionInput');
    finalInput.files = dataTransfer.files;

    // C. Confirmation alert for deletions of *existing* additional photos
    const deleteCheckboxes = document.querySelectorAll('input[name="delete_photos[]"]:checked');
    if (deleteCheckboxes.length > 0) {
        if(!confirm(`Warning: You are about to permanently delete ${deleteCheckboxes.length} existing photo(s).\n\nContinue?`)){
            e.preventDefault();
            return;
        }
    }
});
</script>

<?php include '../footer.php'; ?>