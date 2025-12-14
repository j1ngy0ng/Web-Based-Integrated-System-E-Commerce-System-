<?php
include '../_base.php';

// --------------------------------------------------------------------
// Authenticated users
auth();

// --------------------------------------------------------------------
// Get member ID from URL (edit mode) or null (add mode)
$memberID = req('id'); // matches your member list edit links

// Default empty member (Add mode)
$member = new stdClass();
$member->email = '';
$member->name = '';
$member->role = '';
$member->photo = '';
$member->password = '';
$member->failed_attempts = 0;
$member->lock_until = null;
$member->is_active = 1;
$member->phone_number = '';
$member->phone_verified = 0;
$member->reward_points = 0;

// --------------------------------------------------------------------
// If edit mode, fetch member from DB
if ($memberID) {
    $stm = $_db->prepare('SELECT * FROM user WHERE id = ?');
    $stm->execute([$memberID]);
    $m = $stm->fetch();

    if (!$m) {
        temp('err', 'Member not found');
        redirect('memberList.php');
    }

    $member = $m;
}

// --------------------------------------------------------------------
// Handle POST (both Add and Edit)
if (is_post()) {
    $email        = req('email');
    $name         = req('name');
    $role         = req('role');
    $password     = req('password'); // optional on edit
    $phone_number = req('phone_number');
    $is_active    = req('is_active') === '1' ? 1 : 0;
    $reward_points = (int) req('reward_points');

    // ------------------ Validation ------------------
    if ($email == '') {
        $_err['email'] = 'Email is required';
    } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_err['email'] = 'Invalid email format';
    }

    if ($name == '') $_err['name'] = 'Name is required';
    if ($role == '') $_err['role'] = 'Role is required';

    // ------------------ Photo upload ------------------
    if (isset($_FILES['photo']) && $_FILES['photo']['tmp_name']) {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photoName = uniqid() . '.' . $ext;
        move_uploaded_file($_FILES['photo']['tmp_name'], "../photos/$photoName");
    } else {
        $photoName = $member->photo ?? '';
    }

    // ------------------ Database operation ------------------
    if (!$_err) {
        if ($memberID) {
            // ----- Edit mode -----
            if ($password) {
                // Hash new password with SHA1
                $pwdHash = sha1($password);
                $stm = $_db->prepare('
                    UPDATE user 
                    SET email=?, name=?, role=?, password=?, photo=?, phone_number=?, is_active=?, reward_points=?
                    WHERE id=?
                ');
                $stm->execute([$email, $name, $role, $pwdHash, $photoName, $phone_number, $is_active, $reward_points, $memberID]);
            } else {
                // No password change
                $stm = $_db->prepare('
                    UPDATE user 
                    SET email=?, name=?, role=?, photo=?, phone_number=?, is_active=?, reward_points=?
                    WHERE id=?
                ');
                $stm->execute([$email, $name, $role, $photoName, $phone_number, $is_active, $reward_points, $memberID]);
            }
            temp('info', 'Member updated');
        } else {
            // ----- Add mode -----
            if ($password == '') {
                $_err['password'] = 'Password is required';
            } else {
                // Hash password with SHA1
                $pwdHash = sha1($password);
                $stm = $_db->prepare('
                    INSERT INTO user 
                    (email, name, role, password, photo, phone_number, is_active, failed_attempts, lock_until, phone_verified, reward_points)
                    VALUES (?,?,?,?,?,?,?,?,?,?,?)
                ');
                $stm->execute([
                    $email,
                    $name,
                    $role,
                    $pwdHash,
                    $photoName,
                    $phone_number,
                    $is_active,
                    0,      // failed_attempts
                    null,   // lock_until
                    0,      // phone_verified
                    $reward_points
                ]);
                temp('info', 'Member added');
            }
        }

        if (!$_err) redirect('memberList.php');
    }
}


// --------------------------------------------------------------------
// Prepare $GLOBALS for html_text / html_textarea helpers
$GLOBALS['email'] = $_POST['email'] ?? $member->email;
$GLOBALS['name']  = $_POST['name'] ?? $member->name;
$GLOBALS['role']  = $_POST['role'] ?? $member->role;
$GLOBALS['password'] = '';
$GLOBALS['phone_number'] = $_POST['phone_number'] ?? $member->phone_number;
$GLOBALS['is_active'] = $_POST['is_active'] ?? $member->is_active;

?>

<?php
$_title = $memberID ? 'Edit Member' : 'Add Member';
include '../navbar.php';
?>

<link
    rel="stylesheet"
    href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css" />
<script
    src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>

<style>
    form.form {
        max-width: 500px;
        margin: 50px auto;
        background: #fff;
        padding: 30px 40px;
        border-radius: 12px;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
    }

    form label {
        display: block;
        margin-top: 20px;
        font-weight: 600;
    }

    form input[type="text"],
    form input[type="email"],
    form input[type="password"],
    form select {
        width: 95%;
        padding: 10px 14px;
        border: 1px solid #ccc;
        border-radius: 8px;
        font-size: 1rem;
    }

    form .err {
        color: #e74c3c;
        font-size: 0.85rem;
        margin-top: 4px;
    }

    form section {
        margin-top: 30px;
        display: flex;
        gap: 10px;
        justify-content: flex-end;
    }

    form button {
        padding: 10px 20px;
        border: none;
        background-color: #007BFF;
        color: white;
        font-size: 1rem;
        font-weight: 600;
        border-radius: 8px;
        cursor: pointer;
    }

    form button[type="reset"] {
        background-color: #6c757d;
    }

    #image-tools button:disabled {
        opacity: 0.6;
        filter: brightness(1.2);
        /* slightly brighter */
        cursor: not-allowed;
    }

    #start-crop.active {
        background-color: #0056b3;
        /* darker blue */
    }
</style>


<form method="post" class="form" enctype="multipart/form-data">

    <label for="email">Email</label>
    <?= html_text('email', 'type="email" maxlength="100"') ?>
    <?= err('email') ?>

    <label for="name">Name</label>
    <?= html_text('name', 'maxlength="100"') ?>
    <?= err('name') ?>

    <label for="role">Role</label>
    <select name="role">
        <option value="">Select role</option>
        <option value="Admin" <?= $GLOBALS['role'] == 'Admin' ? 'selected' : '' ?>>Admin</option>
        <option value="Member" <?= $GLOBALS['role'] == 'Member' ? 'selected' : '' ?>>Member</option>
    </select>
    <?= err('role') ?>

    <label for="phone_number">Phone Number</label>
    <?= html_text('phone_number', 'maxlength="20"') ?>

    <label for="reward_points">Reward Points</label>
    <input type="number" name="reward_points" value="<?= $member->reward_points ?>" min="0">

    <label for="is_active">Status</label>
    <select name="is_active">
        <option value="1" <?= $member->is_active ? 'selected' : '' ?>>Active</option>
        <option value="0" <?= !$member->is_active ? 'selected' : '' ?>>Inactive</option>
    </select>


    <label for="password"><?= $memberID ? 'New Password (leave blank to keep)' : 'Password' ?></label>
    <?= html_text('password', 'type="password" maxlength="100"') ?>
    <?= err('password') ?>

    <label for="photo">Photo</label>

    <style>
        #drop-area {
            border: 2px dashed #aaa;
            padding: 20px;
            text-align: center;
            border-radius: 12px;
            cursor: pointer;
            background: #fafafa;
        }


        #drop-area.highlight {
            border-color: #007bff;
            background: #eef5ff;
        }

        #preview-img {
            max-width: 100%;
            max-height: 300px;
            border-radius: 10px;
            margin-top: 10px;
            display: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, .15);
        }
    </style>

    <div id="drop-area">
        <p>Drag & Drop photo here OR click to choose OR take a photo</p>
        <img
            id="preview-img"
            src="<?= $member->photo ? '../photos/' . $member->photo : '' ?>"
            style="<?= $member->photo ? '' : 'display:none;' ?>">
    </div>

    <div id="image-tools" style="margin-top:10px; display:none;">
        <button type="button" onclick="rotateImage(90)">Rotate ⟳</button>
        <button type="button" onclick="rotateImage(-90)">Rotate ⟲</button>
        <button type="button" onclick="flipImage('h')">Flip ↔</button>
        <button type="button" onclick="flipImage('v')">Flip ↕</button>
        <button type="button" id="start-crop">Crop</button>
        <button type="button" id="apply-crop" style="display:none;">Apply Crop</button>
        <button type="button" id="cancel-crop" style="display:none;">Cancel</button>
    </div>

    <button type="button" id="capture-btn">Capture Photo</button>
    <video id="camera-stream" autoplay style="display:none; width:300px; margin-top:10px;"></video>
    <canvas id="camera-canvas" style="display:none;"></canvas>

    <input type="file"
        name="photo"
        id="fileElem"
        accept="image/*"
        capture="environment"
        style="display:none;">


    <?php if ($member->photo): ?>
        <div id="saved-photo" style="margin-top:15px; text-align:center;">
            <p style="font-size:0.9rem; color:#666;">Current Photo</p>

            <img
                id="current-photo"
                src="../photos/<?= htmlspecialchars($member->photo) ?>"
                alt="Photo"
                style="max-width:100%; max-height:300px; border-radius:10px; box-shadow:0 4px 12px rgba(0,0,0,.15);">

            <div style="margin-top:10px;">
                <button type="button" id="edit-current-btn">Edit Current Photo</button>
            </div>
        </div>
    <?php endif; ?>

    <section>
        <button type="submit"><?= $memberID ? 'Update' : 'Add' ?></button>
        <button type="reset">Reset</button>
    </section>

    <script>
        let cropper = null;
        let isCropping = false;
        let currentImage = new Image();
        let rotation = 0,
            flipH = false,
            flipV = false;

        const dropArea = document.getElementById("drop-area");
        const fileInput = document.getElementById("fileElem");
        const previewImg = document.getElementById("preview-img");
        const canvas = document.getElementById("camera-canvas");
        const video = document.getElementById("camera-stream");

        const startCropBtn = document.getElementById("start-crop");
        const applyCropBtn = document.getElementById("apply-crop");
        const cancelCropBtn = document.getElementById("cancel-crop");
        const editCurrentBtn = document.getElementById("edit-current-btn");
        const currentPhoto = document.getElementById("current-photo");

        // ------------------ Drag & Drop ------------------
        ["dragenter", "dragover"].forEach(evt => {
            dropArea.addEventListener(evt, e => {
                e.preventDefault();
                dropArea.classList.add("highlight");
            });
        });
        ["dragleave", "drop"].forEach(evt => {
            dropArea.addEventListener(evt, e => {
                e.preventDefault();
                dropArea.classList.remove("highlight");
            });
        });
        dropArea.addEventListener("drop", e => {
            e.preventDefault();
            if (isCropping) return;
            const file = e.dataTransfer.files[0];
            if (file) loadImage(file);
        });
        dropArea.addEventListener("click", () => {
            if (!isCropping) fileInput.click();
        });
        fileInput.addEventListener("change", () => {
            if (fileInput.files.length > 0) loadImage(fileInput.files[0]);
        });

function loadImage(file) {
    const reader = new FileReader();
    reader.onload = e => {
        previewImg.src = e.target.result;
        previewImg.style.display = "block";
        document.getElementById("image-tools").style.display = "block";
        if (document.getElementById("saved-photo")) document.getElementById("saved-photo").style.display = "none";
        currentImage.src = e.target.result;
        rotation = 0;
        flipH = false;
        flipV = false;

        // IMPORTANT: Attach original file to input so PHP sees it
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
    };
    reader.readAsDataURL(file);
}

        // ------------------ Rotate / Flip ------------------
        function redrawImage() {
            currentImage.onload = () => {
                const ctx = canvas.getContext("2d");
                let w = currentImage.width,
                    h = currentImage.height;
                if (rotation % 180 !== 0) {
                    canvas.width = h;
                    canvas.height = w;
                } else {
                    canvas.width = w;
                    canvas.height = h;
                }

                ctx.clearRect(0, 0, canvas.width, canvas.height);
                ctx.save();
                ctx.translate(canvas.width / 2, canvas.height / 2);
                ctx.rotate(rotation * Math.PI / 180);
                ctx.scale(flipH ? -1 : 1, flipV ? -1 : 1);
                ctx.drawImage(currentImage, -w / 2, -h / 2);
                ctx.restore();

                const dataUrl = canvas.toDataURL("image/png");
                previewImg.src = dataUrl;
                canvas.toBlob(blob => {
                    const file = new File([blob], "edited.png", {
                        type: "image/png"
                    });
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    fileInput.files = dt.files;
                });
            };
            currentImage.onload();
        }

        function rotateImage(deg) {
            rotation = (rotation + deg) % 360;
            redrawImage();
        }

        function flipImage(type) {
            if (type === 'h') flipH = !flipH;
            if (type === 'v') flipV = !flipV;
            redrawImage();
        }

        // ------------------ Crop ------------------
        startCropBtn.addEventListener("click", () => {
            if (!previewImg.src) return;
            isCropping = true;
            previewImg.style.borderRadius = "0";
            disableEditButtons(true);
            startCropBtn.classList.add("active");
            if (cropper) cropper.destroy();
            cropper = new Cropper(previewImg, {
                viewMode: 1,
                dragMode: "move",
                aspectRatio: 1,
                autoCropArea: 0.8,
                background: false,
                zoomable: true,
                movable: true
            });
            applyCropBtn.style.display = "inline-block";
            cancelCropBtn.style.display = "inline-block";
        });
        applyCropBtn.addEventListener("click", () => {
            if (!cropper) return;

            const c = cropper.getCroppedCanvas({
                width: 400,
                height: 400
            });

            const dataUrl = c.toDataURL("image/png");

            // Update preview
            previewImg.src = dataUrl;

            // Update currentImage for future edits
            currentImage.src = dataUrl;

            // Convert to file for PHP
            c.toBlob(blob => {
                const file = new File([blob], "cropped.png", {
                    type: "image/png"
                });
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;
            });

            cropper.destroy();
            cropper = null;
            previewImg.style.borderRadius = "10px";
            disableEditButtons(false);
            startCropBtn.classList.remove("active");
            isCropping = false;
            applyCropBtn.style.display = "none";
            cancelCropBtn.style.display = "none";
        });

        cancelCropBtn.addEventListener("click", () => {
            if (cropper) cropper.destroy();
            cropper = null;
            disableEditButtons(false);
            startCropBtn.classList.remove("active");
            isCropping = false;
            applyCropBtn.style.display = "none";
            cancelCropBtn.style.display = "none";
        });

        // ------------------ Edit current photo ------------------
        if (editCurrentBtn) {
            editCurrentBtn.addEventListener("click", () => {
                currentImage = new Image();
                currentImage.crossOrigin = "anonymous";
                currentImage.src = currentPhoto.src;
                currentImage.onload = () => {
                    rotation = 0;
                    flipH = false;
                    flipV = false;
                    redrawImage();
                    previewImg.style.display = "block";
                    document.getElementById("image-tools").style.display = "block";
                    document.getElementById("saved-photo").style.display = "none";
                };
            });
        }

        // ------------------ Disable other buttons while cropping ------------------
        function disableEditButtons(disabled) {
            document.querySelectorAll("#image-tools button:not(#start-crop):not(#apply-crop):not(#cancel-crop)").forEach(b => b.disabled = disabled);
        }



        document.getElementById("apply-crop").addEventListener("click", () => {
            if (!cropper) return;

            const canvas = cropper.getCroppedCanvas({
                width: 400,
                height: 400,
                imageSmoothingQuality: "high"
            });

            const dataUrl = canvas.toDataURL("image/png");
            previewImg.src = dataUrl;

            // Convert to file for PHP
            canvas.toBlob(blob => {
                const file = new File([blob], "cropped.png", {
                    type: "image/png"
                });
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;
            });

            cropper.destroy();
            cropper = null;

            // restore rounding AFTER crop
            previewImg.style.borderRadius = "10px";

            disableEditButtons(false);

            document.getElementById("apply-crop").style.display = "none";
            document.getElementById("cancel-crop").style.display = "none";
        });


        document.getElementById("cancel-crop").addEventListener("click", () => {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }

            disableEditButtons(false);


            document.getElementById("apply-crop").style.display = "none";
            document.getElementById("cancel-crop").style.display = "none";
        });

        function disableEditButtons(disabled) {
            document.querySelectorAll(
                "#image-tools button:not(#start-crop):not(#apply-crop):not(#cancel-crop)"
            ).forEach(btn => btn.disabled = disabled);
        }





        if (editCurrentBtn) {
            editCurrentBtn.addEventListener("click", () => {
                const img = new Image();
                img.crossOrigin = "anonymous";
                img.src = currentPhoto.src;

                img.onload = () => {

                    // Load image into editor state
                    currentImage = img;
                    rotation = 0;
                    flipH = false;
                    flipV = false;

                    //  DRAW IMMEDIATELY
                    redrawImage();

                    // Show editor UI
                    previewImg.style.display = "block";
                    document.getElementById("image-tools").style.display = "block";

                    // Hide old photo
                    document.getElementById("saved-photo").style.display = "none";
                };
            });
        }

        const captureBtn = document.getElementById("capture-btn");

captureBtn.addEventListener("click", async () => {
    if (video.style.display === "none") {
        // Start camera
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: true });
            video.srcObject = stream;
            video.style.display = "block";
            captureBtn.textContent = "Take Photo";
        } catch (err) {
            alert("Cannot access camera: " + err.message);
        }
    } else {
        // Take snapshot
        const ctx = canvas.getContext("2d");
        canvas.width = video.videoWidth;
        canvas.height = video.videoHeight;
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

        const dataUrl = canvas.toDataURL("image/png");

        // Stop camera
        const tracks = video.srcObject.getTracks();
        tracks.forEach(track => track.stop());
        video.style.display = "none";
        captureBtn.textContent = "Capture Photo";

        // Show preview
        previewImg.src = dataUrl;
        previewImg.style.display = "block";
        document.getElementById("image-tools").style.display = "block";

        // Update currentImage for edits
        currentImage.src = dataUrl;
        rotation = 0; flipH = false; flipV = false;

        // Convert to file for PHP
        const blob = await (await fetch(dataUrl)).blob();
        const file = new File([blob], "captured.png", { type: "image/png" });
        const dt = new DataTransfer();
        dt.items.add(file);
        fileInput.files = dt.files;
    }
});

    </script>

</form>

<?php include '../footer.php'; ?>