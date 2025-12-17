// photo-editor.js
document.addEventListener("DOMContentLoaded", () => {
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
    const captureBtn = document.getElementById("capture-btn");

    // ---------- Drag & Drop ----------
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
            const tools = document.getElementById("image-tools");
            if (tools) tools.style.display = "block";
            if (document.getElementById("saved-photo")) document.getElementById("saved-photo").style.display = "none";

            currentImage.src = e.target.result;
            rotation = 0;
            flipH = false;
            flipV = false;

            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
        };
        reader.readAsDataURL(file);
    }

    // ---------- Rotate / Flip ----------
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
                const file = new File([blob], "edited.png", { type: "image/png" });
                const dt = new DataTransfer();
                dt.items.add(file);
                fileInput.files = dt.files;
            });
        };
        currentImage.onload();
    }

    window.rotateImage = (deg) => { rotation = (rotation + deg) % 360; redrawImage(); };
    window.flipImage = (type) => { if (type === 'h') flipH = !flipH; if (type === 'v') flipV = !flipV; redrawImage(); };

    // ---------- Crop ----------
    startCropBtn?.addEventListener("click", () => {
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

    applyCropBtn?.addEventListener("click", () => {
        if (!cropper) return;
        const c = cropper.getCroppedCanvas({ width: 400, height: 400 });
        const dataUrl = c.toDataURL("image/png");
        previewImg.src = dataUrl;
        currentImage.src = dataUrl;
        c.toBlob(blob => {
            const file = new File([blob], "cropped.png", { type: "image/png" });
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

    cancelCropBtn?.addEventListener("click", () => {
        if (cropper) cropper.destroy();
        cropper = null;
        disableEditButtons(false);
        startCropBtn?.classList.remove("active");
        isCropping = false;
        applyCropBtn.style.display = "none";
        cancelCropBtn.style.display = "none";
    });

    // ---------- Edit current photo ----------
    editCurrentBtn?.addEventListener("click", () => {
        const img = new Image();
        img.crossOrigin = "anonymous";
        img.src = currentPhoto.src;
        img.onload = () => {
            currentImage = img;
            rotation = 0;
            flipH = false;
            flipV = false;
            redrawImage();
            previewImg.style.display = "block";
            document.getElementById("image-tools").style.display = "block";
            document.getElementById("saved-photo").style.display = "none";
        };
    });

    // ---------- Capture ----------
    captureBtn?.addEventListener("click", async () => {
        if (video.style.display === "none") {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true });
                video.srcObject = stream;
                video.style.display = "block";
                captureBtn.textContent = "Take Photo";
            } catch (err) {
                alert("Cannot access camera: " + err.message);
            }
        } else {
            const ctx = canvas.getContext("2d");
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

            const dataUrl = canvas.toDataURL("image/png");
            video.srcObject.getTracks().forEach(track => track.stop());
            video.style.display = "none";
            captureBtn.textContent = "Capture Photo";

            previewImg.src = dataUrl;
            previewImg.style.display = "block";
            document.getElementById("image-tools").style.display = "block";
            currentImage.src = dataUrl;
            rotation = 0;
            flipH = false;
            flipV = false;

            const blob = await (await fetch(dataUrl)).blob();
            const file = new File([blob], "captured.png", { type: "image/png" });
            const dt = new DataTransfer();
            dt.items.add(file);
            fileInput.files = dt.files;
        }
    });

    function disableEditButtons(disabled) {
        document.querySelectorAll("#image-tools button:not(#start-crop):not(#apply-crop):not(#cancel-crop)").forEach(b => b.disabled = disabled);
    }
});
