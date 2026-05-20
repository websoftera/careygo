<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/helpers.php';
require_once __DIR__ . '/lib/banner.php';

$homeBanners = banner_published($pdo);
$homeBannerCount = count($homeBanners);

require_once 'includes/header.php';
?>

    <!-- ===== HERO SECTION ===== -->
    <section class="hero-section hero-carousel position-relative d-flex align-items-center" id="home" data-hero-carousel data-slide-count="<?= (int) $homeBannerCount ?>">
        <?php foreach ($homeBanners as $index => $homeBanner): ?>
        <?php
            $buttonUrl = (string) ($homeBanner['button_url'] ?: '#enquiryModal');
            $isModalButton = banner_is_modal_url($buttonUrl);
        ?>
        <div class="hero-slide <?= $index === 0 ? 'is-active' : '' ?>" data-hero-slide="<?= (int) $index ?>" style="background-image: url('<?= h(banner_image_url($homeBanner['image_path'] ?? null)) ?>');">
            <div class="container position-relative z-1 text-white">
                <div class="row">
                    <div class="col-lg-8 col-xl-7 hero-content">
                        <?php if (!empty($homeBanner['eyebrow'])): ?>
                        <div class="hero-label rounded-pill">
                            <?= h($homeBanner['eyebrow']) ?>
                        </div>
                        <?php endif; ?>
                        <h1 class="hero-title fw-bold text-white">
                            <?= h($homeBanner['title']) ?>
                        </h1>
                        <?php if (!empty($homeBanner['button_text'])): ?>
                        <div class="hero-btn-container">
                            <a href="<?= h($buttonUrl) ?>" class="btn btn-primary-custom" <?= $isModalButton ? 'data-bs-toggle="modal" data-bs-target="' . h($buttonUrl) . '"' : '' ?>>
                                <?= h($homeBanner['button_text']) ?>
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <?php if ($homeBannerCount > 1): ?>
        <div class="hero-carousel-controls">
            <button type="button" class="hero-carousel-arrow" data-hero-prev aria-label="Previous banner">
                <i class="bi bi-chevron-left"></i>
            </button>
            <button type="button" class="hero-carousel-arrow" data-hero-next aria-label="Next banner">
                <i class="bi bi-chevron-right"></i>
            </button>
        </div>
        <?php endif; ?>
    </section>

    <style>
    .hero-carousel {
        background-image: none !important;
        overflow: hidden;
    }
    .hero-carousel::before {
        display: none;
    }
    .hero-slide {
        align-items: center;
        background-position: right center;
        background-repeat: no-repeat;
        background-size: cover;
        display: flex;
        inset: 0;
        opacity: 0;
        pointer-events: none;
        position: absolute;
        transform: scale(1);
        transition: opacity 1200ms ease-in-out, transform 6500ms linear;
        will-change: opacity, transform;
        z-index: 1;
    }
    .hero-slide::before {
        background: linear-gradient(90deg, rgba(31, 23, 18, 0.85) 0%, rgba(20, 35, 60, 0.4) 55%, rgba(0, 0, 0, 0) 100%);
        content: '';
        height: 100%;
        left: 0;
        pointer-events: none;
        position: absolute;
        top: 0;
        width: 100%;
        z-index: 1;
    }
    .hero-slide.is-active {
        opacity: 1;
        pointer-events: auto;
        transform: scale(1.035);
        z-index: 3;
    }
    .hero-slide .container {
        position: relative;
        width: 100%;
        z-index: 2;
    }
    .hero-carousel .hero-content {
        padding-left: 50px !important;
    }
    .hero-slide .hero-label,
    .hero-slide .hero-title,
    .hero-slide .hero-btn-container {
        opacity: 1;
        transform: none;
        transition: none;
    }
    .hero-slide.is-active .hero-label {
        opacity: 1;
        transform: none;
    }
    .hero-slide.is-active .hero-title {
        opacity: 1;
        transform: none;
    }
    .hero-slide.is-active .hero-btn-container {
        opacity: 1;
        transform: none;
    }
    .hero-carousel-controls {
        align-items: center;
        bottom: 34px;
        display: flex;
        gap: 8px;
        opacity: 1;
        position: absolute;
        right: 42px;
        transform: translateY(0);
        transition: opacity 0.2s ease, transform 0.2s ease;
        z-index: 6;
    }
    .hero-carousel-arrow {
        align-items: center;
        background: #fff;
        border: 1px solid #dbe2ee;
        border-radius: 50%;
        color: #001A93;
        display: inline-flex;
        height: 38px;
        justify-content: center;
        transition: all 0.2s ease;
        width: 38px;
    }
    .hero-carousel-arrow:hover {
        background: #001A93;
        color: #fff;
    }
    @media (max-width: 767.98px) {
        .hero-carousel::before {
            display: none;
        }
        .hero-slide {
            background-position: center center;
        }
        .hero-carousel .hero-content {
            padding: 12px 20px !important;
        }
        .hero-slide::before {
            background: linear-gradient(180deg, rgba(82, 60, 48, 0.8) 0%, rgba(26, 47, 76, 0.6) 50%, rgba(0, 0, 0, 0.4) 100%);
        }
        .hero-carousel-controls {
            display: none;
        }
        .hero-carousel-arrow {
            height: 34px;
            width: 34px;
        }
    }
    </style>

    <script>
    (function () {
        const carousel = document.querySelector('[data-hero-carousel]');
        if (!carousel) return;

        const slides = Array.from(carousel.querySelectorAll('[data-hero-slide]'));
        const prev = carousel.querySelector('[data-hero-prev]');
        const next = carousel.querySelector('[data-hero-next]');
        if (slides.length <= 1) return;

        let current = 0;
        let timer = null;
        const intervalMs = 5000;

        function showSlide(index) {
            current = (index + slides.length) % slides.length;
            slides.forEach((slide, slideIndex) => {
                slide.classList.toggle('is-active', slideIndex === current);
            });
        }

        function stopAuto() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        function startAuto() {
            stopAuto();
            timer = setInterval(() => showSlide(current + 1), intervalMs);
        }

        prev?.addEventListener('click', () => {
            showSlide(current - 1);
            startAuto();
        });

        next?.addEventListener('click', () => {
            showSlide(current + 1);
            startAuto();
        });

        carousel.addEventListener('mouseenter', stopAuto);
        carousel.addEventListener('mouseleave', startAuto);
        carousel.addEventListener('focusin', stopAuto);
        carousel.addEventListener('focusout', startAuto);

        showSlide(0);
        startAuto();
    })();
    </script>

    <?php
    $homeServices = [
        [
            'title' => 'Domestic Courier',
            'description' => 'Fast and reliable courier delivery across India with secure handling and tracking support.',
            'image' => 'assets/images/Courier Services.jpg',
        ],
        [
            'title' => 'International Courier',
            'description' => 'Worldwide shipping solutions with customs support, safe transit, and timely delivery.',
            'image' => 'assets/images/International Courier.jpg',
        ],
        [
            'title' => 'Reverse Pickup',
            'description' => 'Easy reverse pickup service for returns, exchanges, and product collection from customers.',
            'image' => 'assets/images/Reverse Pickup.jpg',
        ],
        [
            'title' => 'Business to Business (B2B)',
            'description' => 'Efficient logistics solutions for corporate, industrial, and commercial shipment requirements.',
            'image' => 'assets/images/Business to Business - B2B.jpeg',
        ],
        [
            'title' => 'eCommerce Courier',
            'description' => 'Reliable eCommerce shipping with fast delivery and smooth last-mile logistics support.',
            'image' => 'assets/images/E-Commerce.jpg',
        ],
        [
            'title' => 'Online Sellers',
            'description' => 'Dedicated courier solutions for online sellers with COD and nationwide delivery services.',
            'image' => 'assets/images/Online Sellers - D2C.jpg',
        ],
        [
            'title' => 'Premium Express Service',
            'description' => 'Priority express delivery service for urgent, valuable, and time-sensitive shipments.',
            'image' => 'assets/images/Premium Express Services.jpg',
        ],
        [
            'title' => 'Express Service',
            'description' => 'Quick and affordable express courier service for regular time-bound deliveries.',
            'image' => 'assets/images/Express Services.jpeg',
        ],
        [
            'title' => 'Cash on Delivery (COD)',
            'description' => 'Secure COD services with timely remittance and hassle-free payment collection support.',
            'image' => 'assets/images/Cash on Delivery - COD.jpeg',
        ],
        [
            'title' => 'International Airport to Airport',
            'description' => 'Fast airport-to-airport cargo solutions for urgent international commercial shipments.',
            'image' => 'assets/images/International - Airport to Airport.jpg',
        ],
        [
            'title' => 'Packing Solutions',
            'description' => 'Professional packing solutions ensuring safe, secure, and damage-free transportation.',
            'image' => 'assets/images/Packaging Solutions.jpg',
        ],
    ];
    ?>

    <!-- ===== SERVICES SECTION ===== -->
    <section class="services-section" id="services">
        <div class="container">
            <!-- Top Section: Intro Text (Left) + First 3 Cards (Right) -->
            <div class="row gx-5 mb-4">
                <!-- Left Column: Title and Description -->
                <div class="col-lg-3 mb-5 mb-lg-0 services-intro text-start pe-lg-4 pt-2">
                    <h6 class="text-primary-custom text-uppercase mb-3"
                        style="font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 13px; letter-spacing: 0.5px;">
                        OUR SERVICES</h6>
                    <h2 class="fw-bold mb-4"
                        style="font-family: 'Montserrat', sans-serif; font-size: 42px; color: #1a1a1a; line-height: 1.15; letter-spacing: -1px;">
                        Business <br>Verticals
                    </h2>
                    <p class="mb-5"
                        style="font-family: 'Poppins', sans-serif; font-size: 14px; font-weight: 400; line-height: 1.7; color: #6C757D;">
                        Drive your freight forward with cutting-edge logistics. We deliver smarter supply chain
                        solutions.
                    </p>
                    <a href="#contact-us"
                        class="btn btn-primary-custom cta-arrow-pill rounded-pill text-white px-4 py-2 d-inline-flex align-items-center gap-2 fw-semibold">
                        Connect With Us
                        <span
                            class="icon-circle bg-white rounded-circle d-flex align-items-center justify-content-center"
                            style="width: 20px; height: 20px;">
                            <i class="bi bi-arrow-up-right btn-arrow" style="font-size: 10px;"></i>
                        </span>
                    </a>
                </div>

                <!-- Right Column: First 3 Services Grid -->
                <div class="col-lg-9">
                    <div class="row g-4 h-100">
                        <?php foreach (array_slice($homeServices, 0, 3) as $service): ?>
                        <div class="col-lg-4 col-md-6 col-12">
                            <div class="service-card h-100 text-center">
                                <div class="service-img-wrapper mb-3">
                                    <img src="<?= h($service['image']) ?>" alt="<?= h($service['title']) ?>"
                                        class="img-fluid rounded-3">
                                </div>
                                <h4 class="service-title h6 fw-bold mb-3 text-primary-custom"><?= h($service['title']) ?></h4>
                                <p class="service-desc text-muted mb-0">
                                    <?= h($service['description']) ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Bottom Section: Remaining Cards (4 per row) -->
            <div class="row g-4">
                <?php foreach (array_slice($homeServices, 3) as $service): ?>
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="service-card h-100 text-center">
                        <div class="service-img-wrapper mb-3">
                            <img src="<?= h($service['image']) ?>" alt="<?= h($service['title']) ?>"
                                class="img-fluid rounded-3">
                        </div>
                        <h4 class="service-title h6 fw-bold mb-3 text-primary-custom"><?= h($service['title']) ?></h4>
                        <p class="service-desc text-muted mb-0">
                            <?= h($service['description']) ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- ===== WHY CHOOSE US SECTION ===== -->
    <section class="why-choose-section position-relative" id="our-network">
        <div class="container position-relative z-1">
            <!-- Section Header (Left Aligned) -->
            <div class="row mb-3">
                <div class="col-lg-8">
                    <h6 class="text-primary-custom text-uppercase mb-3"
                        style="font-family: 'Montserrat', sans-serif; font-weight: 700; font-size: 13px; letter-spacing: 1px;">
                        VALUE PROPOSITION</h6>
                    <h2 class="fw-bold mb-2 section-heading"
                        style="color: #2D3748; font-family: 'Montserrat', sans-serif; font-size: 40px; line-height: 1.25; letter-spacing: -0.5px;">
                        Why Choose <span class="text-primary-custom" style="color: #001A93 !important;">CAREYGO<sup
                                class="trademark-sup" style="font-size: 0.35em;">&trade;</sup></span><br>as Your
                        Logistics Partner
                    </h2>
                </div>
            </div>

            <!-- Features Grid (Horizontal Row) -->
            <div class="row g-4 g-lg-5 mt-2 mb-0">
                <!-- Feature 1 -->
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="feature-item h-100 pb-0">
                        <div class="feature-title-group mb-3 text-primary-custom">
                            <h5 class="feature-title fw-bold mb-0">Moneyback Guarantee for</h5>
                            <h5 class="feature-title fw-bold">Premium Express Services</h5>
                        </div>
                        <p class="feature-desc text-muted mb-4" style="line-height: 1.6;">
                            logistics company specializes in managing the transportation, storage, and distribution of
                            goods.
                        </p>
                        <div
                            class="feature-icon-wrapper rounded-4 bg-white d-flex align-items-center justify-content-center">
                            <img src="assets/images/why-choose-icon-1.png" alt="Moneyback Guarantee"
                                class="feature-icon">
                        </div>
                    </div>
                </div>

                <!-- Feature 2 -->
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="feature-item h-100 pb-0">
                        <div class="feature-title-group mb-3 text-primary-custom">
                            <h5 class="feature-title fw-bold mb-0">100% On Time for</h5>
                            <h5 class="feature-title fw-bold">Premium Shipment</h5>
                        </div>
                        <p class="feature-desc text-muted mb-4" style="line-height: 1.6;">
                            logistics company specializes in managing the transportation, storage, and distribution of
                            goods.
                        </p>
                        <div
                            class="feature-icon-wrapper rounded-4 bg-white d-flex align-items-center justify-content-center">
                            <img src="assets/images/why-choose-icon-2.png" alt="100% On Time" class="feature-icon">
                        </div>
                    </div>
                </div>

                <!-- Feature 3 -->
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="feature-item h-100 pb-0">
                        <div class="feature-title-group mb-3 text-primary-custom">
                            <h5 class="feature-title fw-bold mb-0">Secure Handling for</h5>
                            <h5 class="feature-title fw-bold">Fragile Items</h5>
                        </div>
                        <p class="feature-desc text-muted mb-4" style="line-height: 1.6;">
                            logistics company specializes in managing the transportation, storage, and distribution of
                            goods.
                        </p>
                        <div
                            class="feature-icon-wrapper rounded-4 bg-white d-flex align-items-center justify-content-center">
                            <img src="assets/images/why-choose-icon-3.png" alt="Secure Handling" class="feature-icon">
                        </div>
                    </div>
                </div>

                <!-- Feature 4 -->
                <div class="col-lg-3 col-md-6 col-12">
                    <div class="feature-item h-100 pb-0">
                        <div class="feature-title-group mb-3 text-primary-custom">
                            <h5 class="feature-title fw-bold mb-0">Dedicated Call Pickup</h5>
                            <h5 class="feature-title fw-bold">in 30 Seconds</h5>
                        </div>
                        <p class="feature-desc text-muted mb-4" style="line-height: 1.6;">
                            logistics company specializes in managing the transportation, storage, and distribution of
                            goods.
                        </p>
                        <div
                            class="feature-icon-wrapper rounded-4 bg-white d-flex align-items-center justify-content-center">
                            <img src="assets/images/why-choose-icon-4.png" alt="Dedicated Call Pickup"
                                class="feature-icon">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== ABOUT / DELIVERY SECTION ===== -->
    <section class="about-section position-relative" id="about-us" style="margin-top: -45px;">
        <div class="container position-relative z-1 mb-lg-5 pt-0">
            <div class="row align-items-stretch g-4">
                <!-- Left Side: Image -->
                <div class="col-lg-6 mb-4 mb-lg-0">
                    <img src="assets/images/about-image.jpg" alt="About Careygo Delivery"
                        class="img-fluid w-100 shadow-sm h-100" style="object-fit: cover; border-radius: 30px;">
                </div>

                <!-- Right Side: Content -->
                <div class="col-lg-6 position-relative">
                    <div class="position-relative z-2 bg-white h-100 d-flex flex-column justify-content-center shadow-sm p-4 p-lg-5"
                        style="border-radius: 30px;">
                        <div>
                            <h6 class="text-primary-custom fw-bold text-uppercase mb-3"
                                style="font-size: 13px; letter-spacing: 1px;">ABOUT CAREYGO</h6>
                            <h2 class="fw-bold mb-4 text-dark about-heading">
                                Maximizing efficiency <br>in delivery services
                            </h2>
                            <p class="text-muted mb-5 about-desc" style="max-width: 95%;">
                                logistics company specializes in managing the transportation, storage, and distribution
                                of
                                goods. It offers services such as freight forwarding, warehousing, inventory manage
                                supply
                                chain transportation logistic solutions.
                            </p>

                            <!-- Subtle bottom right image overlay for about content -->
                            <img src="assets/images/ABOUT-BG.png" alt="" class="about-bg-image z-0 pe-none">

                            <!-- Bullet Points -->
                            <div class="row mb-5 gy-3 position-relative z-2">
                                <div class="col-sm-6">
                                    <ul class="list-unstyled mb-0 d-flex flex-column gap-3">
                                        <li class="about-feature-item">
                                            <span class="about-icon"><i
                                                    class="bi bi-arrow-right text-primary-custom"></i></span> Safe
                                            Packing
                                        </li>
                                        <li class="about-feature-item">
                                            <span class="about-icon"><i
                                                    class="bi bi-arrow-right text-primary-custom"></i></span> Ship
                                            Everywhere
                                        </li>
                                        <li class="about-feature-item">
                                            <span class="about-icon"><i
                                                    class="bi bi-arrow-right text-primary-custom"></i></span> Zero
                                            Risk
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-sm-6">
                                    <ul class="list-unstyled mb-0 mt-3 mt-sm-0 d-flex flex-column gap-3">
                                        <li class="about-feature-item">
                                            <span class="about-icon"><i
                                                    class="bi bi-arrow-right text-primary-custom"></i></span> In Time
                                            Delivery
                                        </li>
                                        <li class="about-feature-item">
                                            <span class="about-icon"><i
                                                    class="bi bi-arrow-right text-primary-custom"></i></span> Cost
                                            Saving
                                        </li>
                                        <li class="about-feature-item">
                                            <span class="about-icon"><i
                                                    class="bi bi-arrow-right text-primary-custom"></i></span> Cost
                                            Saving
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <!-- CTA Button -->
                            <div class="mt-2 text-start pb-2">
                                <a href="#contact-us"
                                    class="btn btn-primary-custom cta-arrow-pill rounded-pill text-white px-4 py-2 fw-bold d-inline-flex align-items-center position-relative z-2"
                                    style="font-size: 14px; letter-spacing: 0.5px; transition: all 0.3s ease;">
                                    Connect With Us
                                    <span
                                        class="ms-2 d-inline-flex align-items-center justify-content-center bg-white rounded-circle"
                                        style="width: 24px; height: 24px;">
                                        <i class="bi bi-arrow-up-right btn-arrow"></i>
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- ===== CONTACT US SECTION ===== -->
    <section class="contact-section position-relative" id="contact-us">
        <div class="container position-relative z-1">
            <div class="row align-items-stretch g-4">
                <div class="col-lg-7">
                    <div class="contact-form-panel h-100">
                        <h6 class="text-primary-custom fw-bold text-uppercase mb-3 contact-eyebrow">CONTACT US</h6>
                        <h2 class="fw-bold mb-3 text-dark contact-heading">
                            Let us handle your next shipment
                        </h2>
                        <p class="contact-desc mb-4">
                            Share your shipment details and our team will help you choose the right logistics solution.
                        </p>

                        <form class="contact-form" id="contactForm" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="contactName" class="form-label">Full Name</label>
                                    <input type="text" class="form-control" id="contactName" name="name"
                                        placeholder="Your name" minlength="2" maxlength="60"
                                        pattern="^[A-Za-z][A-Za-z\s.'-]{1,59}$" required>
                                    <div class="invalid-feedback">Please enter a valid name without numbers.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="contactPhone" class="form-label">Phone Number</label>
                                    <input type="tel" class="form-control" id="contactPhone" name="phone"
                                        placeholder="98502 96178" inputmode="numeric" maxlength="14"
                                        pattern="^(?:\+91[\s-]?)?[6-9][0-9]{9}$" required>
                                    <div class="invalid-feedback">Please enter a valid 10 digit phone number.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="contactEmail" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="contactEmail" name="email"
                                        placeholder="you@example.com" required>
                                    <div class="invalid-feedback">Please enter a valid email address.</div>
                                </div>
                                <div class="col-md-6">
                                    <label for="contactService" class="form-label">Service Type</label>
                                    <select class="form-select" id="contactService" name="service" required>
                                        <?php foreach ($homeServices as $serviceIndex => $service): ?>
                                        <option <?= $serviceIndex === 0 ? 'selected' : '' ?>><?= h($service['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">Please select a service type.</div>
                                </div>
                                <div class="col-12">
                                    <label for="contactMessage" class="form-label">Message</label>
                                    <textarea class="form-control" id="contactMessage" name="message" rows="4"
                                        placeholder="Pickup city, delivery city, weight, and any special instructions"
                                        minlength="10" required></textarea>
                                    <div class="invalid-feedback">Please add at least 10 characters.</div>
                                </div>
                                <div class="col-12">
                                    <div class="contact-form-status" id="contactFormStatus" role="status" aria-live="polite"></div>
                                </div>
                                <div class="col-12">
                                    <button type="submit"
                                        class="btn btn-primary-custom cta-arrow-pill rounded-pill text-white fw-bold d-inline-flex align-items-center contact-submit">
                                        Send Message
                                        <span class="ms-2 d-inline-flex align-items-center justify-content-center bg-white rounded-circle">
                                            <i class="bi bi-arrow-up-right"></i>
                                        </span>
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="contact-visual h-100">
                        <img src="assets/images/Packaging Solutions.jpg" alt="Careygo customer support"
                            class="img-fluid w-100 h-100">
                        <div class="contact-quick-card">
                            <span class="contact-quick-icon"><i class="bi bi-headset"></i></span>
                            <div>
                                <span class="contact-quick-label">Need help?</span>
                                <a href="tel:9850296178">98502 96178</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

<?php require_once 'includes/footer.php'; ?>
