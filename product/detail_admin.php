<?php
include '../_base.php';

// ====================================================================
// BACKEND LOGIC
// ====================================================================

auth("Admin","Superadmin");
$productID = req('productID');
$is_new_product_page_load = empty($productID);

$product = new stdClass();
$product->name = ''; 
$product->price = ''; 
$product->stock = ''; 
$product->categoryID = ''; 
$product->description = ''; 
$product->mainPhoto = ''; 
$product->video_url = ''; 
$product->model3d = ''; 

if ($productID) {
    $sql = "SELECT p.*, ph.photoURL AS mainPhoto FROM product p LEFT JOIN productPhoto ph ON p.productID = ph.productID AND ph.is_main = 1 WHERE p.productID = ?";
    $stm = $_db->prepare($sql);
    $stm->execute([$productID]);
    $product = $stm->fetch() ?: redirect('maintenance.php');
}

$cat = $_db->query("SELECT * FROM ProductCategory ORDER BY categoryName")->fetchAll();

function slugify($text) {
    $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
    $text = trim($text, '-');
    $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
    $text = strtolower($text);
    $text = preg_replace('~[^-\w]+~', '', $text);
    if (empty($text)) { return 'n-a'; }
    return $text;
}

// ====================================================================
// IMAGE CROPPING HELPER (Center Crop to Fixed Size - Used for ALL Photos)
// ====================================================================
function resize_and_crop_center($source_path, $destination_path, $target_width = 500, $target_height = 500, $quality = 85) { 
    if (!function_exists('gd_info')) {
        return move_uploaded_file($source_path, $destination_path); // Fallback
    }

    $info = getimagesize($source_path);
    $original_width = $info[0];
    $original_height = $info[1];
    $mime = $info['mime'];

    // 1. Load image resource
    if ($mime == 'image/jpeg' || $mime == 'image/jpg') {
        $image = imagecreatefromjpeg($source_path);
    } elseif ($mime == 'image/gif') {
        $image = imagecreatefromgif($source_path);
    } elseif ($mime == 'image/png') {
        $image = imagecreatefrompng($source_path);
        // Preserve transparency
        imagealphablending($image, true);
        imagesavealpha($image, true);
    } else {
        // This case should ideally be caught by the new validation check before calling this function
        return move_uploaded_file($source_path, $destination_path); 
    }
    
    // 2. Calculate the scaling factor to cover the target area (using MAX ratio)
    $scale = max($target_width / $original_width, $target_height / $original_height);
    
    $scaled_width = (int)($original_width * $scale);
    $scaled_height = (int)($original_height * $scale);
    
    // 3. Determine crop position (centering the image)
    $crop_x = (int) (($scaled_width - $target_width) / 2);
    $crop_y = (int) (($scaled_height - $target_height) / 2);
    
    // 4. Create an intermediate image (resized to cover the target)
    $intermediate_image = imagecreatetruecolor($scaled_width, $scaled_height);

    if ($mime == 'image/png') {
        imagealphablending($intermediate_image, false);
        imagesavealpha($intermediate_image, true);
        $transparent = imagecolorallocatealpha($intermediate_image, 255, 255, 255, 127);
        imagefilledrectangle($intermediate_image, 0, 0, $scaled_width, $scaled_height, $transparent);
    }

    imagecopyresampled($intermediate_image, $image, 0, 0, 0, 0, $scaled_width, $scaled_height, $original_width, $original_height);
    
    // 5. Create the final cropped image resource
    $final_image = imagecreatetruecolor($target_width, $target_height);
    
    if ($mime == 'image/png') {
        imagealphablending($final_image, false);
        imagesavealpha($final_image, true);
        $transparent = imagecolorallocatealpha($final_image, 255, 255, 255, 127);
        imagefilledrectangle($final_image, 0, 0, $target_width, $target_height, $transparent);
    }

    // 6. Copy the centered portion from the intermediate image to the final image
    imagecopy($final_image, $intermediate_image, 0, 0, $crop_x, $crop_y, $target_width, $target_height);

    // 7. Save the final image and apply quality
    $success = false;
    if ($mime == 'image/jpeg' || $mime == 'image/jpg') {
        $success = imagejpeg($final_image, $destination_path, $quality); // <-- JPEG Quality Applied
    } elseif ($mime == 'image/gif') {
        $success = imagegif($final_image, $destination_path);
    } elseif ($mime == 'image/png') {
        $success = imagepng($final_image, $destination_path, 7); // PNG Compression (0-9)
    }

    // 8. Cleanup
    imagedestroy($image);
    imagedestroy($intermediate_image);
    imagedestroy($final_image);

    return $success;
}

if (is_post()) {
    // --- DELETE PRODUCT LOGIC ---
    if (req('action_type') === 'delete' && $productID) {
        $_db->beginTransaction();
        try {
            $stm_photos = $_db->prepare("SELECT photoURL FROM productPhoto WHERE productID = ?");
            $stm_photos->execute([$productID]);
            $photos_to_delete = $stm_photos->fetchAll();

            $stm_vid = $_db->prepare("SELECT video_url, model3d FROM product WHERE productID = ?");
            $stm_vid->execute([$productID]);
            $media_row = $stm_vid->fetch();

            $stm_delete_photos_db = $_db->prepare("DELETE FROM productPhoto WHERE productID = ?");
            $stm_delete_photos_db->execute([$productID]);

            $stm_delete_product = $_db->prepare("DELETE FROM product WHERE productID = ?");
            $stm_delete_product->execute([$productID]);

            $_db->commit();

            foreach ($photos_to_delete as $photo) {
                if (file_exists("../products/" . $photo->photoURL)) unlink("../products/" . $photo->photoURL);
            }
            // Delete video file
            if ($media_row && $media_row->video_url && file_exists("../products/" . $media_row->video_url)) {
                unlink("../products/" . $media_row->video_url);
            }
            // Delete 3D model file
            if ($media_row && $media_row->model3d && file_exists("../products/" . $media_row->model3d)) {
                unlink("../products/" . $media_row->model3d);
            }

            temp('info', 'Product deleted successfully.');
            redirect('maintenance.php');
        } catch (Exception $e) {
            $_db->rollBack();
            temp('error', 'Error: ' . $e->getMessage());
            redirect('maintenance.php');
        }
    } else { 
        // --- ADD/UPDATE LOGIC ---
        $name = req('name'); $price = req('price'); $stock = req('stock'); $categoryID = req('categoryID'); $description = req('description');

        // --- Validation ---
        if ($name == '') $_err['name'] = 'Required';
        if ($price == '' || $price < 0) $_err['price'] = 'Invalid';
        if ($stock == '' || $stock < 0) $_err['stock'] = 'Invalid';
        if ($categoryID == '') $_err['categoryID'] = 'Required';

        // Video Validation
        if (!empty($_FILES['productVideo']['tmp_name'])) {
            $allowed_video = ['video/mp4', 'video/webm', 'video/ogg'];
            if (!in_array($_FILES['productVideo']['type'], $allowed_video)) {
                $_err['productVideo'] = 'Invalid video format. MP4, WebM, Ogg only.';
            }
        }

        // 3D Model Validation
        if (!empty($_FILES['model3d']['tmp_name'])) {
            $ext = strtolower(pathinfo($_FILES['model3d']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['glb', 'gltf'])) {
                $_err['model3d'] = 'Invalid 3D model format. Only .glb or .gltf allowed.';
            }
        }
        
        // --- IMAGE VALIDATION ---
        $allowed_images = ['image/jpeg', 'image/png', 'image/gif'];
        
        // Main Photo Validation
        if (!empty($_FILES['mainPhoto']['tmp_name'])) {
            $info = getimagesize($_FILES['mainPhoto']['tmp_name']);
            if (!$info || !in_array($info['mime'], $allowed_images)) {
                $_err['mainPhoto'] = 'Invalid main photo format. Only JPG, PNG, or GIF allowed.';
            }
        }
        
        // Additional Photos Validation
        if (!empty($_FILES['finalAdditionalPhotos']['tmp_name']) && is_array($_FILES['finalAdditionalPhotos']['tmp_name'])) {
            $files = $_FILES['finalAdditionalPhotos'];
            foreach($files['tmp_name'] as $tmp_name) {
                if ($tmp_name) {
                    $info = getimagesize($tmp_name);
                    if (!$info || !in_array($info['mime'], $allowed_images)) {
                         $_err['additionalPhotos'] = 'One or more additional photos have an invalid format. Only JPG, PNG, or GIF allowed.';
                         break; // Stop checking further files
                    }
                }
            }
        }
        // --- END IMAGE VALIDATION ---
        
        if (!$_err) {
            $was_newly_added = $is_new_product_page_load;
            $productSlug = slugify($name);
            if ($productSlug === 'n-a') $productSlug = 'product';

            $_db->beginTransaction();
            try {
                // Initialize filenames from existing values
                $video_filename = $product->video_url; 
                $model3d_filename = $product->model3d ?? null;

                // --- 1. HANDLE VIDEO UPLOAD/DELETE ---
                if (!empty($_POST['delete_video_check'])) { // Delete existing
                    if ($video_filename && file_exists("../products/" . $video_filename)) { unlink("../products/" . $video_filename); }
                    $video_filename = null;
                }
                if (!empty($_FILES['productVideo']['tmp_name'])) { // Upload new
                    if ($video_filename && file_exists("../products/" . $video_filename)) { unlink("../products/" . $video_filename); } // Delete old one if replacing
                    $v_ext = pathinfo($_FILES['productVideo']['name'], PATHINFO_EXTENSION);
                    $v_fname = $productSlug . '_video_' . time() . '_' . rand(1000, 9999) . '.' . $v_ext;
                    move_uploaded_file($_FILES['productVideo']['tmp_name'], "../products/$v_fname");
                    $video_filename = $v_fname;
                }

                // --- 2. HANDLE 3D MODEL UPLOAD/DELETE ---
                if (!empty($_POST['delete_model3d_check'])) { // Delete existing
                    if ($model3d_filename && file_exists("../products/" . $model3d_filename)) { unlink("../products/" . $model3d_filename); }
                    $model3d_filename = null;
                }
                if (!empty($_FILES['model3d']['tmp_name'])) { // Upload new
                    if ($model3d_filename && file_exists("../products/" . $model3d_filename)) { unlink("../products/" . $model3d_filename); } // Delete old one if replacing
                    $m_ext = pathinfo($_FILES['model3d']['name'], PATHINFO_EXTENSION);
                    $m_fname = $productSlug . "_3d_" . time() . "." . $m_ext;
                    move_uploaded_file($_FILES['model3d']['tmp_name'], "../products/$m_fname");
                    $model3d_filename = $m_fname;
                }

                // --- 3. SAVE PRODUCT DATA (DB) ---
                if ($productID) {
                    $_db->prepare("
                        UPDATE product 
                        SET name=?, price=?, stock=?, categoryID=?, description=?, video_url=?, model3d=? 
                        WHERE productID=?
                    ")->execute([
                        $name, $price, $stock, $categoryID, $description, $video_filename, $model3d_filename, $productID
                    ]);
                    temp('info', 'Product updated');
                } else {
                    $_db->prepare("
                        INSERT INTO product (name, price, stock, categoryID, description, video_url, model3d)
                        VALUES (?, ?, ?, ?, ?, ?, ?)
                    ")->execute([
                        $name, $price, $stock, $categoryID, $description, $video_filename, $model3d_filename
                    ]);
                    $productID = $_db->lastInsertId();
                    temp('info', 'Product added');
                }

                // --- 4. HANDLE MAIN PHOTO ---
                if (!empty($_FILES['mainPhoto']['tmp_name'])) {
                    $ext = pathinfo($_FILES['mainPhoto']['name'], PATHINFO_EXTENSION);
                    $fname = $productSlug . '_main_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                    
                    $source_path = $_FILES['mainPhoto']['tmp_name'];
                    $destination_path = "../products/$fname";

                    // The main photo is processed and saved as a fixed 500x500 center-cropped square.
                    resize_and_crop_center($source_path, $destination_path, 500, 500); 
                    
                    // Delete old file if updating
                    if (!$was_newly_added && $product->mainPhoto && file_exists("../products/".$product->mainPhoto)) { 
                        unlink("../products/".$product->mainPhoto); 
                    }
                    
                    $exists = $_db->prepare("SELECT * FROM productPhoto WHERE productID=? AND is_main=1");
                    $exists->execute([$productID]);
                    if ($exists->fetch()) { 
                        $_db->prepare("UPDATE productPhoto SET photoURL=? WHERE productID=? AND is_main=1")->execute([$fname, $productID]); 
                    } else { 
                        $_db->prepare("INSERT INTO productPhoto (productID, photoURL, is_main) VALUES (?, ?, 1)")->execute([$productID, $fname]); 
                    }
                }

                // --- 5. HANDLE ADDITIONAL PHOTOS ---
                // Deletion Logic
                if (!empty($_POST['delete_photos'])) {
                    foreach ($_POST['delete_photos'] as $pid) {
                        $old = $_db->prepare("SELECT photoURL FROM productPhoto WHERE photoID=?");
                        $old->execute([$pid]);
                        $row = $old->fetch();
                        if($row && file_exists("../products/".$row->photoURL)) { unlink("../products/".$row->photoURL); }
                        $_db->prepare("DELETE FROM productPhoto WHERE photoID=?")->execute([$pid]);
                    }
                }
                
                // Upload/Resize Logic
                if(!empty($_FILES['finalAdditionalPhotos']['tmp_name'])) {
                    $files = $_FILES['finalAdditionalPhotos'];
                    if (is_array($files['tmp_name']) && !empty($files['tmp_name'][0])) {
                        for($i=0; $i<count($files['tmp_name']); $i++) {
                            if($files['tmp_name'][$i]) {
                                $ext = pathinfo($files['name'][$i], PATHINFO_EXTENSION);
                                $fname = $productSlug . '_add_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
                                
                                $source_path = $files['tmp_name'][$i];
                                $destination_path = "../products/$fname";
                                
                                // Additional photos are processed and saved as fixed 500x500 center-cropped squares.
                                resize_and_crop_center($source_path, $destination_path, 500, 500);

                                $_db->prepare("INSERT INTO productPhoto (productID, photoURL, is_main) VALUES (?, ?, 0)")->execute([$productID, $fname]);
                            }
                        }
                    }
                } 

                $_db->commit();
                
                if ($was_newly_added) { redirect('maintenance.php'); } 
                else { redirect("detail_admin.php?productID=$productID"); }

            } catch (Exception $e) {
                $_db->rollBack();
                temp('error', 'Database or File Save Error: ' . $e->getMessage());
                // Redirect user back to the form with error
                redirect($productID ? "detail_admin.php?productID=$productID" : 'detail_admin.php');
            }
        }
    }
}

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
    
    /* Base Font */
    body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; }

    form.form { max-width:650px; margin:40px auto; background:#fff; padding:30px 40px; border-radius:12px; box-shadow:0 10px 25px rgba(0,0,0,0.08); }
    
    /* --- NEW HEADER STYLE (Option 2) --- */
    .form-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 25px;
        border-bottom: 1px solid #eee;
        padding-bottom: 15px;
    }
    .form-header h3 {
        margin: 0;
        color: var(--text-dark);
        font-size: 1.5rem;
    }
    .top-back-btn {
        text-decoration: none;
        color: #666;
        font-weight: 600;
        font-size: 0.95rem;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        transition: color 0.2s;
        font-family: inherit;
    }
    .top-back-btn:hover {
        color: var(--primary-color);
    }
    /* ----------------------------------- */

    form label.field-label { display:block; margin-bottom:8px; font-weight:600; margin-top:25px; color: var(--text-dark); font-family: inherit; }
    form input[type="text"], form input[type="number"], form select, form textarea { width:100%; padding:12px; border:1px solid var(--gray-border); border-radius:8px; font-size:1rem; box-sizing: border-box; transition: all 0.3s; font-family: inherit; }
    form .err { color: var(--danger-color); font-size:.85rem; margin-top:5px; display: block; font-weight: 500; }
    form section.actions { margin-top:40px; display:flex; gap:15px; justify-content:flex-end; padding-top: 20px; border-top: 1px solid #eee; }
    
    /* Buttons */
    form button { padding:12px 24px; border:none; background-color:var(--primary-color); color:white; font-size:1rem; font-weight:600; border-radius:8px; cursor:pointer; transition: background-color 0.2s; font-family: inherit; }
    form button:hover { background-color: var(--primary-dark); }
    form button[type="reset"] { background-color:#6c757d; }

    /* Photo Upload Styles */
    .hidden-file-input { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); border: 0; }
    .upload-zone { position: relative; width: 100%; height: 280px; background: var(--gray-bg); border: 2px dashed var(--gray-border); border-radius: 12px; display: flex; justify-content: center; align-items: center; cursor: pointer; overflow: hidden; transition: all 0.3s ease; }
    .upload-zone:hover { border-color: var(--primary-color); background: #f0f8ff; }
    .upload-zone img.preview, .upload-zone video.preview { width: 100%; height: 100%; object-fit: contain; padding: 10px; box-sizing: border-box; }
    .upload-zone .placeholder { text-align: center; color: #999; font-family: inherit; }
    .upload-zone .placeholder i { font-size: 48px; margin-bottom: 15px; display: block; color: var(--primary-color); opacity: 0.7; }

    /* Video Zone */
    .video-upload-zone { height: 200px; margin-bottom: 10px; }
    .video-container { position: relative; width: 100%; height: 100%; display: flex; justify-content: center; align-items: center; }
    
    .photo-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(110px, 1fr)); gap: 15px; margin-top: 15px; }
    .photo-card { position: relative; height: 110px; border-radius: 10px; overflow: hidden; border: 2px solid transparent; box-shadow: 0 2px 5px rgba(0,0,0,0.08); background: #fff; transition: all 0.2s; }
    .photo-card img { width: 100%; height: 100%; object-fit: cover; }
    
    /* Overlay for Delete */
    .photo-action-overlay { position: absolute; top: 5px; right: 5px; background: rgba(255, 255, 255, 0.95); border-radius: 50%; width: 32px; height: 32px; display: flex; justify-content: center; align-items: center; cursor: pointer; box-shadow: 0 2px 4px rgba(0,0,0,0.2); transition: all 0.2s; color: var(--danger-color); z-index: 10; }
    .photo-action-overlay:hover { background: var(--danger-color); color: white; transform: scale(1.1); }
    
    .photo-card.existing-photo.is-deleted { border: 2px solid var(--danger-color); opacity: 0.6; }
    .photo-card.existing-photo.is-deleted img { filter: grayscale(100%); }
    .add-photo-card { height: 110px; border: 2px dashed var(--gray-border); border-radius: 10px; display: flex; justify-content: center; align-items: center; flex-direction: column; cursor: pointer; background: var(--gray-bg); color: var(--primary-color); font-weight: 600; transition: all 0.3s; text-align: center; font-size: 0.9rem; font-family: inherit; }
</style>

<script type="module" src="https://unpkg.com/@google/model-viewer/dist/model-viewer.min.js"></script>

<form method="post" class="form" enctype="multipart/form-data" id="productForm">
    
    <div class="form-header">
        <h3><?= $productID ? 'Edit Product' : 'Add New Product' ?></h3>
        <a href="maintenance.php" class="top-back-btn">
            <i class="fa fa-arrow-left"></i> Back to List
        </a>
    </div>

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
    <div style="font-size: 0.9rem; color: #666; margin-bottom: 10px;">(Image output size: **500x500px square**, center-cropped.)</div>
    <input type="file" name="mainPhoto" id="mainPhotoInput" class="hidden-file-input" accept="image/jpeg,image/png,image/gif" onchange="previewMainPhoto(this)">
    <label for="mainPhotoInput" class="upload-zone" id="mainPhotoZone">
        <?php if($product->mainPhoto && file_exists("../products/".$product->mainPhoto)): ?>
            <img src="../products/<?= $product->mainPhoto ?>" class="preview">
        <?php else: ?>
            <div class="placeholder"><i class="fa fa-cloud-upload" aria-hidden="true"></i><span>Click to upload main image</span></div>
        <?php endif ?>
    </label>
    <?= err('mainPhoto') ?>

<label class="field-label">3D Model (GLB)</label>
    
    <input type="checkbox" name="delete_model3d_check" id="delete_model3d_check" value="1" class="hidden-file-input">
    
    <input type="file" name="model3d" id="model3dInput" class="hidden-file-input" accept=".glb,.gltf" onchange="previewModel3d(this)">
    
    <label for="model3dInput" class="upload-zone" id="model3dZone" style="height: 200px;">
        <?php 
            $hasModel = !empty($product->model3d) && file_exists("../products/" . $product->model3d);
            if ($hasModel): 
        ?>
            <div class="video-container" style="width:100%; height:100%;">
                <model-viewer src="../products/<?= $product->model3d ?>" 
                             camera-controls 
                             auto-rotate 
                             style="width: 100%; height: 100%;">
                </model-viewer>
                
                <div class="photo-action-overlay" onclick="removeExistingModel3d(event)" title="Delete Model">
                    <i class="fa fa-trash"></i>
                </div>
            </div>
        <?php else: ?>
            <div class="placeholder">
                <i class="fa fa-cube" aria-hidden="true"></i>
                <span>Click to upload 3D Model</span>
            </div>
        <?php endif ?>
    </label>
    <?= err('model3d') ?>


    <label class="field-label">Product Video (Optional)</label>
    
    <input type="checkbox" name="delete_video_check" id="delete_video_check" value="1" class="hidden-file-input">
    
    <input type="file" name="productVideo" id="videoInput" class="hidden-file-input" accept="video/mp4,video/webm,video/ogg" onchange="previewVideo(this)">
    
    <label for="videoInput" class="upload-zone video-upload-zone" id="videoZone">
        <?php 
            $hasVideo = !empty($product->video_url) && file_exists("../products/" . $product->video_url);
            if ($hasVideo): 
                $videoSrc = "../products/" . $product->video_url;
        ?>
            <div class="video-container" id="videoContainer">
                <video controls src="<?= $videoSrc ?>" class="preview"></video>
                <div class="photo-action-overlay" onclick="removeExistingVideo(event)" title="Delete Video">
                    <i class="fa fa-trash"></i>
                </div>
            </div>
        <?php else: ?>
            <div class="placeholder">
                <i class="fa fa-file-video-o" aria-hidden="true"></i>
                <span>Click to upload video</span>
            </div>
        <?php endif ?>
    </label>
    <?= err('productVideo') ?>

    <label class="field-label">Additional Photos Gallery</label>
    <div style="font-size: 0.9rem; color: #666; margin-bottom: 10px;">(Image output size: **500x500px square**, center-cropped.)</div>

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

        <input type="file" id="triggerAddInput" class="hidden-file-input" accept="image/jpeg,image/png,image/gif" multiple onchange="handleNewFilesSelect(this)">
        <input type="file" name="finalAdditionalPhotos[]" id="finalSubmissionInput" class="hidden-file-input" multiple>

        <label for="triggerAddInput" class="add-photo-card">
            <i class="fa fa-plus" aria-hidden="true"></i> Add Photos
        </label>

    </div>
    <?= err('additionalPhotos') ?>

    <section class="actions">
        <button type="submit"><?= $productID ? 'Update Product' : 'Add Product' ?></button>
        <button type="reset">Reset</button>
    </section>
</form>

<script>
// 1. Preview 3D Model
function previewModel3d(input) {
    const zone = document.getElementById('model3dZone');
    
    // Uncheck delete flag because we are uploading a new one
    document.getElementById('delete_model3d_check').checked = false;

    if (input.files && input.files[0]) {
        const file = input.files[0];
        const url = URL.createObjectURL(file);

        zone.innerHTML = `
            <div class="video-container" style="width:100%; height:100%;">
                <model-viewer src="${url}" camera-controls auto-rotate style="width:100%; height:100%;"></model-viewer>
                <div class="photo-action-overlay" onclick="removeNewModel3d(event)" title="Remove this upload">
                    <i class="fa fa-trash"></i>
                </div>
            </div>
        `;
    }
}

// 2. Remove NEWLY uploaded model
function removeNewModel3d(event) {
    event.preventDefault(); 
    event.stopPropagation();
    
    document.getElementById('model3dInput').value = ''; // Clear input
    resetModel3dPlaceholder();
}

// 3. Remove EXISTING model (Database delete)
function removeExistingModel3d(event) {
    event.preventDefault();
    event.stopPropagation();

    // Mark for deletion in backend
    document.getElementById('delete_model3d_check').checked = true;
    resetModel3dPlaceholder();
}

// Helper to restore the "Click to upload" view
function resetModel3dPlaceholder() {
    const zone = document.getElementById('model3dZone');
    zone.innerHTML = `
        <div class="placeholder">
            <i class="fa fa-cube" aria-hidden="true"></i>
            <span>Click to upload 3D Model (.glb or .gltf)</span>
        </div>
    `;
}

// 1. Main Photo Preview
function previewMainPhoto(input) {
    const zone = document.getElementById('mainPhotoZone');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = (e) => zone.innerHTML = '<img src="' + e.target.result + '" class="preview">';
        reader.readAsDataURL(input.files[0]);
    }
}

// 2. Video Preview (File Selected)
function previewVideo(input) {
    const zone = document.getElementById('videoZone');
    // Ensure delete check is OFF because we are uploading a NEW one
    document.getElementById('delete_video_check').checked = false;
    
    if (input.files && input.files[0]) {
        const file = input.files[0];
        const videoURL = URL.createObjectURL(file);
        
        zone.innerHTML = `
            <div class="video-container">
                <video controls src="${videoURL}" class="preview"></video>
                <div class="photo-action-overlay" onclick="removeNewVideo(event)" title="Remove this upload">
                    <i class="fa fa-trash"></i>
                </div>
            </div>
        `;
    }
}

// 3. Remove NEWLY uploaded video (Reset to placeholder)
function removeNewVideo(event) {
    event.preventDefault(); 
    event.stopPropagation();
    
    const input = document.getElementById('videoInput');
    input.value = ''; // Clear file input
    
    const zone = document.getElementById('videoZone');
    zone.innerHTML = `
        <div class="placeholder">
            <i class="fa fa-file-video-o" aria-hidden="true"></i>
            <span>Click to upload video</span>
        </div>
    `;
}

// 4. Remove EXISTING video (Check hidden delete box, show placeholder)
function removeExistingVideo(event) {
    event.preventDefault();
    event.stopPropagation();

    // Mark for deletion in backend
    document.getElementById('delete_video_check').checked = true;
    
    // Replace with upload placeholder immediately
    const zone = document.getElementById('videoZone');
    zone.innerHTML = `
        <div class="placeholder">
            <i class="fa fa-file-video-o" aria-hidden="true"></i>
            <span>Click to upload video</span>
        </div>
    `;
}

// 5. Additional Photos Logic
function toggleDeleteExisting(photoID) {
    const checkbox = document.getElementById('del-chk-' + photoID);
    const card = document.getElementById('photo-card-' + photoID);
    const icon = card.querySelector('.photo-action-overlay i');
    checkbox.checked = !checkbox.checked;
    if (checkbox.checked) {
        card.classList.add('is-deleted');
        icon.className = 'fa fa-undo'; 
        card.querySelector('.photo-action-overlay').title = "Undo deletion";
    } else {
        card.classList.remove('is-deleted');
        icon.className = 'fa fa-trash'; 
        card.querySelector('.photo-action-overlay').title = "Mark for deletion";
    }
}

function handleNewFilesSelect(input) {
    const grid = document.getElementById('additionalPhotoGrid');
    const addBtnLabel = grid.querySelector('label.add-photo-card');
    
    if(input.files) {
        // Simple client-side check to improve UX, server-side is the definitive check
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        
        Array.from(input.files).forEach(file => {
            if (!allowedTypes.includes(file.type)) {
                alert(`File "${file.name}" has an invalid format (${file.type}). Only JPG, PNG, or GIF are allowed.`);
                return; // Skip this file
            }
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const card = document.createElement('div');
                card.classList.add('photo-card', 'new-preview-card');
                card.style.border = "2px solid #28a745"; 
                card.fileData = file;
                const img = document.createElement('img');
                img.src = e.target.result;
                const removeBtn = document.createElement('div');
                removeBtn.className = 'photo-action-overlay';
                removeBtn.innerHTML = '<i class="fa fa-trash" aria-hidden="true"></i>'; 
                removeBtn.title = "Remove this upload";
                removeBtn.onclick = function() { card.remove(); };
                card.appendChild(img);
                card.appendChild(removeBtn);
                grid.insertBefore(card, addBtnLabel);
            }
            reader.readAsDataURL(file);
        });
    }
    input.value = '';
}

document.getElementById('productForm').addEventListener('submit', function(e) {
    const newPreviewCards = document.querySelectorAll('.new-preview-card');
    const dataTransfer = new DataTransfer(); 
    newPreviewCards.forEach(card => { if (card.fileData) dataTransfer.items.add(card.fileData); });
    const finalInput = document.getElementById('finalSubmissionInput');
    finalInput.files = dataTransfer.files;

    const deleteCheckboxes = document.querySelectorAll('input[name="delete_photos[]"]:checked');
    const deleteVideo = document.getElementById('delete_video_check').checked;
    const deleteModel = document.getElementById('delete_model3d_check').checked;
    
    if (deleteCheckboxes.length > 0 || deleteVideo || deleteModel) {
        if(!confirm(`Warning: You are about to delete ${deleteCheckboxes.length} photo(s), ${deleteVideo ? 'a video, ' : ''} ${deleteModel ? 'a 3D model, ' : ''} . Continue?`)){
            e.preventDefault();
        }
    }
});
</script>

<?php include '../footer.php'; ?>