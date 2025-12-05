
<?php
include '_base.php';

include 'navbar.php';
?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Chillax</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            overflow-x: hidden;
            background: linear-gradient(135deg, #2c8ba8ff 0%, #7ec9dfff 100%);
        }

        /* Hero Section */
        .hero {
            height: 76vh;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .hero::before {
            content: '';
            position: absolute;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: moveBackground 20s linear infinite;
        }

        @keyframes moveBackground {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        .hero-content {
            text-align: center;
            color: white;
            z-index: 1;
            animation: fadeInUp 1s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .hero h1 {
            font-size: 5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .hero p {
            font-size: 1.8rem;
            opacity: 0.95;
            animation: fadeInUp 1s ease-out 0.3s both;
        }

        /* Story Section */
        .story-section {
            min-height: 100vh;
            background: white;
            padding: 100px 20px;
            position: relative;
        }

        .story-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .story-title {
            text-align: center;
            font-size: 3rem;
            color: #006989;
            margin-bottom: 60px;
        }

        .story-item {
            display: flex;
            align-items: center;
            margin-bottom: 100px;
            opacity: 0;
            transform: translateX(-50px);
            transition: all 0.8s ease-out;
        }

        .story-item.visible {
            opacity: 1;
            transform: translateX(0);
        }

        .story-item:nth-child(even) {
            flex-direction: row-reverse;
            transform: translateX(50px);
        }

        .story-item:nth-child(even).visible {
            transform: translateX(0);
        }

        .story-icon {
            flex: 0 0 200px;
            height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 5rem;
            background: linear-gradient(135deg, #006989 0%, #009688 100%);
            border-radius: 50%;
            margin: 0 40px;
            animation: float 3s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-20px); }
        }

        .story-content {
            flex: 1;
        }

        .story-content h3 {
            font-size: 2rem;
            color: #006989;
            margin-bottom: 15px;
        }

        .story-content p {
            font-size: 1.2rem;
            line-height: 1.8;
            color: #555;
        }

        /* Location Section */
        .location-section {
            min-height: 100vh;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            padding: 100px 20px;
        }

        .location-container {
            max-width: 1200px;
            margin: 0 auto;
        }

        .location-title {
            text-align: center;
            font-size: 3rem;
            color: #006989;
            margin-bottom: 60px;
        }

        .location-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            align-items: start;
        }

        .location-info {
            background: white;
            padding: 40px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .location-info h3 {
            font-size: 2rem;
            color: #006989;
            margin-bottom: 30px;
        }

        .info-item {
            display: flex;
            align-items: start;
            margin-bottom: 25px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 10px;
            transition: transform 0.3s ease;
        }

        .info-item:hover {
            transform: translateX(10px);
        }

        .info-icon {
            font-size: 1.8rem;
            margin-right: 20px;
            color: #667eea;
        }

        .info-text h4 {
            font-size: 1.2rem;
            color: #333;
            margin-bottom: 8px;
        }

        .info-text p {
            font-size: 1rem;
            color: #666;
            line-height: 1.6;
        }

        .map-container {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            height: 500px;
        }

        .map-container iframe {
            width: 100%;
            height: 100%;
            border: none;
        }

        @media (max-width: 768px) {
            .hero h1 {
                font-size: 3rem;
            }

            .hero p {
                font-size: 1.2rem;
            }

            .story-item {
                flex-direction: column !important;
                text-align: center;
            }

            .story-icon {
                margin: 0 0 30px 0;
            }

            .location-content {
                grid-template-columns: 1fr;
            }

            .story-title, .location-title {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body>
    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Chillax</h1>
            <p>Where Chill Meets Relax</p>
        </div>
    </section>

    <!-- Story Section -->
    <section class="story-section">
        <div class="story-container">
            <h2 class="story-title">Our Story</h2>
            
            <div class="story-item">
                <div class="story-icon">🌿</div>
                <div class="story-content">
                    <h3>Born from Wellness</h3>
                    <p>Chillax was founded with a simple mission: to bring harmony between healthcare and beauty. We believe that true beauty comes from within, and wellness is the foundation of confidence.</p>
                </div>
            </div>

            <div class="story-item">
                <div class="story-icon">💆</div>
                <div class="story-content">
                    <h3>The Art of Relaxation</h3>
                    <p>In today's fast-paced world, we understand the importance of taking a moment to breathe. Our carefully curated products help you create your personal sanctuary of peace and self-care.</p>
                </div>
            </div>

            <div class="story-item">
                <div class="story-icon">✨</div>
                <div class="story-content">
                    <h3>Your Journey to Radiance</h3>
                    <p>Every product we offer is selected with care, ensuring it meets our high standards for quality, effectiveness, and sustainability. Your wellness journey is our passion.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Location Section -->
    <section class="location-section">
        <div class="location-container">
            <h2 class="location-title">Visit Us</h2>
            
            <div class="location-content">
                <div class="location-info">
                    <h3>Find Your Calm</h3>
                    
                    <div class="info-item">
                        <div class="info-icon">📍</div>
                        <div class="info-text">
                            <h4>Our Location</h4>
                            <p><a href="https://www.google.com/maps/place/TAR+UMT+Cyber+Centre+(CITC)/@3.2139482,101.7239983,17z/data=!3m1!4b1!4m6!3m5!1s0x31cc386bb5535b83:0x2d08b6c5b6fd0155!8m2!3d3.2139482!4d101.7265786!16s%2Fg%2F1tz937n9?hl=en-US&entry=ttu&g_ep=EgoyMDI1MTIwMi4wIKXMDSoASAFQAw%3D%3D" 
                            target="_blank">TAR UMT Cyber Centre (CITC)<br>Jalan Malinja, Taman Bunga Raya<br>53100 Kuala Lumpur</a></p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">🕐</div>
                        <div class="info-text">
                            <h4>Operating Hours</h4>
                            <p><strong>Monday - Friday:</strong> 9:00 AM - 8:00 PM<br>
                            <strong>Saturday:</strong> 10:00 AM - 6:00 PM<br>
                            <strong>Sunday:</strong> 11:00 AM - 5:00 PM</p>
                        </div>
                    </div>

                    <div class="info-item">
                        <div class="info-icon">📞</div>
                        <div class="info-text">
                            <h4>Contact Us</h4>
                            <p>
                                Phone: <a href="tel:+60123456789">+6012-345-6789</a><br>
                                Email: <a href="mailto:info@chillax.com">info@chillax.com</a>
                            </p>
                        </div>
                    </div>
                </div>

                <div class="map-container">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3983.5429158452657!2d101.72399831014506!3d3.2139481967477175!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x31cc386bb5535b83%3A0x2d08b6c5b6fd0155!2sTAR%20UMT%20Cyber%20Centre%20(CITC)!5e0!3m2!1sen!2sus!4v1764943660607!5m2!1sen!2sus" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>
        </div>
    </section>

    <script>
        // Intersection Observer for story animations
        const observerOptions = {
            threshold: 0.2,
            rootMargin: '0px 0px -100px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('visible');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.story-item').forEach(item => {
            observer.observe(item);
        });

        // Smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
    </script>
</body>
</html>
<?php
include 'footer.php';
?>