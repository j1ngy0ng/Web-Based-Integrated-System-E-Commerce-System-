// ============================================================================
// General Functions
// ============================================================================



// ============================================================================
// Page Load (jQuery)
// ============================================================================

$(() => {

    // Autofocus
    $('form :input:not(button):first').focus();
    $('.err:first').prev().focus();
    $('.err:first').prev().find(':input:first').focus();
    
    // Confirmation message
    $('[data-confirm]').on('click', e => {
        const text = e.target.dataset.confirm || 'Are you sure?';
        if (!confirm(text)) {
            e.preventDefault();
            e.stopImmediatePropagation();
        }
    });

    // Initiate GET request
    $('[data-get]').on('click', e => {
        e.preventDefault();
        const url = e.target.dataset.get;
        location = url || location;
    });

    // Initiate POST request
    $('[data-post]').on('click', e => {
        e.preventDefault();
        const url = e.target.dataset.post;
        const f = $('<form>').appendTo(document.body)[0];
        f.method = 'POST';
        f.action = url || location;
        f.submit();
    });

    // Reset form
    $('[type=reset]').on('click', e => {
        e.preventDefault();
        location = location;
    });

    // Auto uppercase
    $('[data-upper]').on('input', e => {
        const a = e.target.selectionStart;
        const b = e.target.selectionEnd;
        e.target.value = e.target.value.toUpperCase();
        e.target.setSelectionRange(a, b);
    });

    // Photo preview
    $('label.upload input[type=file]').on('change', e => {
        const f = e.target.files[0];
        const img = $(e.target).siblings('img')[0];

        if (!img) return;

        img.dataset.src ??= img.src;

        if (f?.type.startsWith('image/')) {
            img.src = URL.createObjectURL(f);
        }
        else {
            img.src = img.dataset.src;
            e.target.value = '';
        }
    });

});

// ============================================================================
// Shopping Cart
// ============================================================================
function initImageModal() {
    let currentImageIndex = 0;
    let images = [];
    
    function collectImages() {
        images = [];
        
        // Add main photo if exists
        const mainImage = document.getElementById('mainProductImage');
        if (mainImage) {
            images.push({
                src: mainImage.dataset.fullsize || mainImage.src,
                alt: mainImage.alt,
                index: 0
            });
        }
        
        // Add additional photos
        const thumbnails = document.querySelectorAll('.thumbnail');
        thumbnails.forEach(thumb => {
            images.push({
                src: thumb.dataset.fullsize || thumb.src,
                alt: thumb.alt,
                index: parseInt(thumb.dataset.index)
            });
        });
        
        images.sort((a, b) => a.index - b.index);
    }
    
    function openModal(index) {
        currentImageIndex = index;
        const image = images[currentImageIndex];
        
        if (image) {
            const modal = document.getElementById("imageModal");
            const modalImg = document.getElementById("modalImage");
            const captionText = document.querySelector(".modal-caption");
            const counter = document.getElementById("imageCounter");
            
            modal.style.display = "block";
            modalImg.src = image.src;
            captionText.innerHTML = image.alt;
            counter.innerHTML = `${currentImageIndex + 1} / ${images.length}`;
            
            updateNavigation();
        }
    }
    
    function updateNavigation() {
        const prevBtn = document.getElementById("prevBtn");
        const nextBtn = document.getElementById("nextBtn");
        
        prevBtn.style.display = images.length > 1 ? "block" : "none";
        nextBtn.style.display = images.length > 1 ? "block" : "none";
    }
    
    function nextImage() {
        currentImageIndex = (currentImageIndex + 1) % images.length;
        openModal(currentImageIndex);
    }
    
    function prevImage() {
        currentImageIndex = (currentImageIndex - 1 + images.length) % images.length;
        openModal(currentImageIndex);
    }
    
    function initEventListeners() {
        const modal = document.getElementById("imageModal");
        const modalImg = document.getElementById("modalImage");
        const captionText = document.querySelector(".modal-caption");
        const counter = document.getElementById("imageCounter");
        
        document.querySelectorAll('.clickable-main, .thumbnail').forEach(img => {
            img.addEventListener('click', function() {
                collectImages();
                const index = parseInt(this.dataset.index);
                openModal(index);
            });
        });
        
        document.getElementById('nextBtn').addEventListener('click', nextImage);
        document.getElementById('prevBtn').addEventListener('click', prevImage);
        
        document.querySelector('.modal-close').addEventListener('click', function() {
            modal.style.display = "none";
        });
        
        modal.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        });
        
        document.addEventListener('keydown', function(event) {
            if (modal.style.display === "block") {
                if (event.key === 'Escape') {
                    modal.style.display = "none";
                } else if (event.key === 'ArrowRight') {
                    nextImage();
                } else if (event.key === 'ArrowLeft') {
                    prevImage();
                }
            }
        });
    }
    
    // Initialize everything
    collectImages();
    initEventListeners();
}

function initCartHandlers() {
    $('select[name="unit"]').on('change', function(e) {
        if (this.value !== '') {
            e.target.form.submit();
        }
    });

    $(document).on('click', '#clearBtn', function() {
        $('#unit').val('');
        $(this).closest('form').submit();
    });

    $(document).on('click', '#addToCartBtn', function(e) {
        const form = $(this).closest('form');
        const select = form.find('select[name="unit"]');
        
        if (select.val() === '') {
            e.preventDefault();
            alert('Please select a quantity first');
            select.focus();
        }
    });
    
    initImageModal();
}