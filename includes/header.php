<?php
/**
 * Shared site header — detects logged-in state and shows correct nav CTA
 */
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/auth.php';

$_navUser = auth_user();   // null = guest, array = logged-in payload
$_currentPage = basename($_SERVER['SCRIPT_NAME'] ?? 'index.php');
$_styleVersion = @filemtime(__DIR__ . '/../css/style.css') ?: time();

$_siteSearchItems = [
    ['title' => 'Home', 'category' => 'Page', 'url' => 'index.php', 'text' => 'home careygo logistics cargo delivery transport'],
    ['title' => 'About Us', 'category' => 'Page', 'url' => 'index.php#about-us', 'text' => 'about careygo delivery services efficiency logistics partner'],
    ['title' => 'Services', 'category' => 'Page', 'url' => 'index.php#services', 'text' => 'services business verticals domestic courier ecommerce b2b online sellers express cod international packing'],
    ['title' => 'Our Network', 'category' => 'Page', 'url' => 'index.php#our-network', 'text' => 'network value proposition moneyback on time secure handling call pickup'],
    ['title' => 'Contact Us', 'category' => 'Page', 'url' => 'index.php#contact-us', 'text' => 'contact phone email shipment details support connect with us'],
    ['title' => 'Domestic Courier', 'category' => 'Service', 'url' => 'index.php#services', 'text' => 'domestic courier india secure handling tracking delivery'],
    ['title' => 'International Courier', 'category' => 'Service', 'url' => 'index.php#services', 'text' => 'international courier worldwide shipping customs safe transit timely delivery'],
    ['title' => 'Reverse Pickup', 'category' => 'Service', 'url' => 'index.php#services', 'text' => 'reverse pickup return shipment logistics'],
    ['title' => 'Business to Business (B2B)', 'category' => 'Service', 'url' => 'index.php#services', 'text' => 'business to business b2b corporate industrial commercial shipment logistics'],
    ['title' => 'eCommerce Courier', 'category' => 'Service', 'url' => 'index.php#services', 'text' => 'ecommerce e-commerce courier online seller shipping last mile delivery logistics'],
    ['title' => 'Online Sellers', 'category' => 'Service', 'url' => 'index.php#services', 'text' => 'online sellers cod nationwide delivery courier solutions'],
    ['title' => 'Premium Express Service', 'category' => 'Service', 'url' => 'index.php#services', 'text' => 'premium express priority urgent valuable time sensitive delivery'],
    ['title' => 'Express Service', 'category' => 'Service', 'url' => 'index.php#services', 'text' => 'express service quick affordable courier regular time bound delivery'],
    ['title' => 'Cash on Delivery (COD)', 'category' => 'Service', 'url' => 'index.php#services', 'text' => 'cash on delivery cod payment collection remittance courier'],
    ['title' => 'International Airport to Airport', 'category' => 'Service', 'url' => 'index.php#services', 'text' => 'airport to airport international cargo urgent commercial shipments air freight'],
    ['title' => 'Packing Solutions', 'category' => 'Service', 'url' => 'index.php#services', 'text' => 'packing solutions safe secure damage free transportation'],
    ['title' => 'Blog', 'category' => 'Page', 'url' => 'blog', 'text' => 'blog logistics courier tips ecommerce shipping delivery articles'],
];

try {
    $blogSearchStmt = $pdo->query("
        SELECT title, slug, excerpt, content
        FROM blogs
        WHERE status = 'published' AND (published_at IS NULL OR published_at <= NOW())
        ORDER BY COALESCE(published_at, created_at) DESC
        LIMIT 8
    ");
    foreach ($blogSearchStmt->fetchAll(PDO::FETCH_ASSOC) as $blogItem) {
        $_siteSearchItems[] = [
            'title' => $blogItem['title'],
            'category' => 'Blog',
            'url' => 'blog/' . rawurlencode($blogItem['slug']),
            'text' => trim(($blogItem['excerpt'] ?? '') . ' ' . strip_tags($blogItem['content'] ?? '')),
        ];
    }
} catch (Exception $e) {}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <base href="<?= htmlspecialchars(SITE_URL . '/') ?>">
    <title><?= htmlspecialchars($pageTitle ?? 'Careygo Logistics') ?></title>
    <?php if (!empty($metaDescription)): ?>
    <meta name="description" content="<?= htmlspecialchars($metaDescription) ?>">
    <?php endif; ?>
    <?php if (!empty($metaKeywords)): ?>
    <meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>">
    <?php endif; ?>
    <?php if (!empty($canonicalUrl)): ?>
    <link rel="canonical" href="<?= htmlspecialchars($canonicalUrl) ?>">
    <?php endif; ?>
    <meta property="og:title" content="<?= htmlspecialchars($pageTitle ?? 'Careygo Logistics') ?>">
    <?php if (!empty($metaDescription)): ?>
    <meta property="og:description" content="<?= htmlspecialchars($metaDescription) ?>">
    <?php endif; ?>
    <?php if (!empty($metaImage)): ?>
    <meta property="og:image" content="<?= htmlspecialchars($metaImage) ?>">
    <?php endif; ?>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css?v=<?= (int) $_styleVersion ?>">
</head>

<body>

    <!-- ===== TOP BAR ===== -->
    <div class="top-bar py-2">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8 d-flex gap-4 top-info">
                    <a href="mailto:info@careygo.in"><i class="bi bi-envelope-fill"></i> info@careygo.in</a>
                    <span class="d-none d-lg-flex"><i class="bi bi-geo-alt-fill"></i> 250 Main Street, 2nd Floor, USA</span>
                    <a href="tel:9850296178"><i class="bi bi-telephone-fill"></i> 98502 96178</a>
                </div>
                <div class="col-md-4 d-flex justify-content-end gap-2 social-icons">
                    <a href="#" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <a href="#" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <a href="#" aria-label="X (Twitter)"><i class="bi bi-twitter-x"></i></a>
                    <a href="#" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- ===== MAIN NAVIGATION ===== -->
    <nav class="navbar navbar-expand-xl main-navbar py-3">
        <div class="container align-items-center">
            <!-- Logo -->
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <img src="assets/images/Main-Careygo-logo-blue.png" alt="CAREYGO Logo" class="brand-logo"
                    width="520" height="193">
            </a>

            <!-- Mobile Toggle -->
            <div class="d-flex align-items-center gap-2 d-xl-none">
                <!-- Mobile: show compact auth button before toggler -->
                <?php if ($_navUser): ?>
                <a href="customer/dashboard.php" class="nav-user-avatar-sm" title="My Dashboard">
                    <?= strtoupper(substr($_navUser['name'] ?? 'U', 0, 1)) ?>
                </a>
                <?php else: ?>
                <a href="login.php" class="nav-login-btn-sm mobile-auth-modal-link" data-auth-url="login.php?modal=1">
                    Sign In
                </a>
                <?php endif; ?>
                <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse"
                    data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
            </div>

            <!-- Navbar Links & Actions -->
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav mx-auto mb-3 mb-xl-0 nav-links-wrapper px-4 py-2 mt-3 mt-xl-0">
                    <li class="nav-item px-1 px-xl-2">
                        <a class="nav-link <?= $_currentPage === 'index.php' ? 'active' : '' ?>" href="index.php">HOME</a>
                    </li>
                    <li class="nav-item px-1 px-xl-2">
                        <a class="nav-link" href="index.php#about-us">ABOUT US</a>
                    </li>
                    <li class="nav-item px-1 px-xl-2">
                        <a class="nav-link" href="index.php#services">SERVICES</a>
                    </li>
                    <li class="nav-item px-1 px-xl-2">
                        <a class="nav-link <?= in_array($_currentPage, ['blog.php', 'blog-detail.php'], true) ? 'active' : '' ?>" href="blog">BLOG</a>
                    </li>
                    <li class="nav-item px-1 px-xl-2">
                        <a class="nav-link" href="public-tracking.php">TRACKING</a>
                    </li>
                    <li class="nav-item px-1 px-xl-2">
                        <a class="nav-link" href="<?= $_navUser ? 'customer/new-booking.php' : 'login.php' ?>">PICKUP</a>
                    </li>
                    <li class="nav-item px-1 px-xl-2">
                        <a class="nav-link" href="<?= $_navUser ? 'customer/new-booking.php' : 'login.php' ?>">RATE CALCULATOR</a>
                    </li>
                    <li class="nav-item px-1 px-xl-2">
                        <a class="nav-link" href="index.php#contact-us">CONTACT US</a>
                    </li>
                </ul>

                <div class="d-flex justify-content-center align-items-center gap-3 nav-actions">
                    <div class="site-search-wrap">
                        <button class="btn search-btn rounded-circle shadow-none" type="button" aria-label="Search" id="siteSearchToggle" aria-expanded="false" aria-controls="siteSearchPanel">
                            <i class="bi bi-search"></i>
                        </button>
                        <div class="site-search-panel" id="siteSearchPanel" aria-hidden="true">
                            <form class="site-search-form" id="siteSearchForm" role="search">
                                <i class="bi bi-search"></i>
                                <input type="text" id="siteSearchInput" placeholder="Search services, pages, blogs..." autocomplete="off">
                                <button type="button" id="siteSearchClose" aria-label="Close search"><i class="bi bi-x-lg"></i></button>
                            </form>
                            <div class="site-search-results" id="siteSearchResults">
                                <div class="site-search-empty">Type a service or keyword to search.</div>
                            </div>
                        </div>
                    </div>

                    <?php if ($_navUser): ?>
                    <!-- ── Logged-in user dropdown ── -->
                    <div class="dropdown nav-user-dropdown">
                        <button class="nav-user-pill dropdown-toggle" type="button"
                            data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="nav-user-pill-avatar">
                                <?= strtoupper(substr($_navUser['name'] ?? 'U', 0, 1)) ?>
                            </span>
                            <span class="nav-user-pill-name d-none d-xl-inline">
                                <?= htmlspecialchars(explode(' ', $_navUser['name'] ?? 'User')[0]) ?>
                            </span>
                            <i class="bi bi-chevron-down nav-user-pill-chevron"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end nav-user-menu shadow-sm">
                            <li class="nav-user-menu-header">
                                <div class="nav-user-menu-avatar">
                                    <?= strtoupper(substr($_navUser['name'] ?? 'U', 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="nav-user-menu-name"><?= htmlspecialchars($_navUser['name'] ?? '') ?></div>
                                    <div class="nav-user-menu-role">Customer</div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            <?php
                            $dashLink = ($_navUser['role'] === 'admin')
                                ? 'admin/dashboard.php'
                                : (($_navUser['status'] === 'approved') ? 'customer/dashboard.php' : 'customer/pending.php');
                            ?>
                            <li>
                                <a class="dropdown-item nav-user-menu-item" href="<?= $dashLink ?>">
                                    <i class="bi bi-grid-1x2"></i> My Dashboard
                                </a>
                            </li>
                            <?php if ($_navUser['role'] === 'customer' && $_navUser['status'] === 'approved'): ?>
                            <li>
                                <a class="dropdown-item nav-user-menu-item" href="customer/new-booking.php">
                                    <i class="bi bi-plus-circle"></i> New Booking
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item nav-user-menu-item" href="customer/profile.php">
                                    <i class="bi bi-person-circle"></i> My Profile
                                </a>
                            </li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item nav-user-menu-item nav-user-menu-logout" href="auth/logout.php">
                                    <i class="bi bi-box-arrow-right"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>

                    <?php else: ?>
                    <!-- ── Guest: Sign In ── -->
                    <a href="login.php"
                        class="btn btn-primary-custom rounded-pill d-flex align-items-center gap-2 fw-semibold nav-login-btn">
                        Sign In
                        <span class="icon-circle bg-white rounded-circle d-flex align-items-center justify-content-center">
                            <i class="bi bi-arrow-up-right btn-arrow"></i>
                        </span>
                    </a>
                    <?php endif; ?>

                </div><!-- /nav-actions -->
            </div><!-- /collapse -->
        </div>
    </nav>

    <div class="mobile-header-actions" aria-label="Quick actions">
        <div class="mobile-header-actions-inner">
            <a href="public-tracking.php" class="mobile-header-action">
                <img src="assets/images/mobile-actions/tracking-icon.png" alt="" class="mobile-action-img mobile-action-img-tracking" decoding="async">
                <span>Tracking</span>
            </a>
            <a href="<?= $_navUser ? 'customer/new-booking.php' : 'login.php' ?>" class="mobile-header-action<?= $_navUser ? '' : ' mobile-auth-modal-link' ?>"<?= $_navUser ? '' : ' data-auth-url="login.php?modal=1"' ?>>
                <img src="assets/images/mobile-actions/pickup-icon.png" alt="" class="mobile-action-img mobile-action-img-pickup" decoding="async">
                <span>Pickup</span>
            </a>
            <a href="<?= $_navUser ? 'customer/new-booking.php' : 'login.php' ?>" class="mobile-header-action<?= $_navUser ? '' : ' mobile-auth-modal-link' ?>"<?= $_navUser ? '' : ' data-auth-url="login.php?modal=1"' ?>>
                <img src="assets/images/mobile-actions/rate-calculator-icon.png" alt="" class="mobile-action-img mobile-action-img-rate" decoding="async">
                <span>Rate Calculator</span>
            </a>
        </div>
    </div>

    <style>
    .site-search-wrap {
        position: relative;
    }
    .site-search-panel {
        background: #fff;
        border: 1px solid #dbe2ee;
        border-radius: 12px;
        box-shadow: 0 18px 45px rgba(16, 32, 74, 0.16);
        opacity: 0;
        padding: 12px;
        pointer-events: none;
        position: absolute;
        right: 0;
        top: calc(100% + 12px);
        transform: translateY(8px);
        transition: opacity 0.18s ease, transform 0.18s ease;
        width: min(420px, calc(100vw - 32px));
        z-index: 1080;
    }
    .site-search-panel.is-open {
        opacity: 1;
        pointer-events: auto;
        transform: translateY(0);
    }
    .site-search-form {
        align-items: center;
        border: 1px solid #dbe2ee;
        border-radius: 10px;
        display: flex;
        gap: 10px;
        padding: 9px 10px;
    }
    .site-search-form i {
        color: #001A93;
    }
    .site-search-form input {
        border: 0;
        flex: 1;
        font: inherit;
        min-width: 0;
        outline: 0;
    }
    .site-search-form button {
        align-items: center;
        background: transparent;
        border: 0;
        color: #6b7280;
        display: inline-flex;
        height: 28px;
        justify-content: center;
        padding: 0;
        width: 28px;
    }
    .site-search-results {
        max-height: 330px;
        overflow-x: hidden;
        overflow-y: auto;
        padding-top: 8px;
    }
    .site-search-item {
        align-items: flex-start;
        border-radius: 10px;
        color: #07133b;
        display: flex;
        gap: 10px;
        padding: 10px;
        text-decoration: none;
    }
    .site-search-item:hover,
    .site-search-item.is-active {
        background: #f3f6ff;
        color: #001A93;
    }
    .site-search-icon {
        align-items: center;
        background: rgba(0, 26, 147, 0.08);
        border-radius: 8px;
        color: #001A93;
        display: inline-flex;
        flex: 0 0 32px;
        height: 32px;
        justify-content: center;
        width: 32px;
    }
    .site-search-item > span:last-child {
        flex: 1;
        min-width: 0;
    }
    .site-search-title {
        display: block;
        font-size: 13px;
        font-weight: 700;
        line-height: 1.25;
        overflow-wrap: anywhere;
    }
    .site-search-meta {
        color: #6b7280;
        display: block;
        font-size: 11px;
        margin-top: 2px;
    }
    .site-search-empty {
        color: #6b7280;
        font-size: 13px;
        padding: 14px 10px 8px;
    }
    @media (max-width: 1199.98px) {
        .site-search-wrap {
            width: 100%;
        }
        .site-search-panel {
            left: 50%;
            right: auto;
            top: calc(100% + 10px);
            transform: translate(-50%, 8px);
            width: min(420px, calc(100vw - 32px));
        }
        .site-search-panel.is-open {
            transform: translate(-50%, 0);
        }
    }
    </style>

    <script>
    window.CAREYGO_SEARCH_ITEMS = <?= json_encode($_siteSearchItems, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?>;
    (function () {
        const toggle = document.getElementById('siteSearchToggle');
        const panel = document.getElementById('siteSearchPanel');
        const input = document.getElementById('siteSearchInput');
        const results = document.getElementById('siteSearchResults');
        const close = document.getElementById('siteSearchClose');
        const form = document.getElementById('siteSearchForm');
        const items = Array.isArray(window.CAREYGO_SEARCH_ITEMS) ? window.CAREYGO_SEARCH_ITEMS : [];
        let activeIndex = -1;

        if (!toggle || !panel || !input || !results) return;

        function iconFor(category) {
            if (category === 'Service') return 'bi-truck';
            if (category === 'Blog') return 'bi-journal-text';
            return 'bi-file-earmark-text';
        }

        function normalize(value) {
            return String(value || '').toLowerCase().replace(/\s+/g, ' ').trim();
        }

        function setOpen(open) {
            panel.classList.toggle('is-open', open);
            panel.setAttribute('aria-hidden', open ? 'false' : 'true');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open) {
                setTimeout(() => input.focus(), 30);
                render(input.value);
            }
        }

        function score(item, query) {
            const title = normalize(item.title);
            const category = normalize(item.category);
            const text = normalize(item.text);
            const compactTitle = title.replace(/[^a-z0-9]/g, '');
            const haystack = `${title} ${category} ${text}`;
            const words = normalize(query).split(' ').filter(Boolean);
            if (!words.length) return 0;
            let points = 0;
            words.forEach((word) => {
                const shortWord = word.length < 3;
                const titleWords = title.split(' ').filter(Boolean);
                if (title === word) points += 80;
                if (title.startsWith(word)) points += 70;
                if (titleWords.some((titleWord) => titleWord.startsWith(word))) points += 55;
                if (compactTitle.includes(word.replace(/[^a-z0-9]/g, ''))) points += 45;
                if (title.includes(word)) points += 40;
                if (!shortWord && category.includes(word)) points += 18;
                if (!shortWord && text.includes(word)) points += 12;
                if (!shortWord && haystack.includes(word)) points += 6;
            });
            return points;
        }

        function render(query) {
            const q = normalize(query);
            activeIndex = -1;
            if (!q) {
                results.innerHTML = '<div class="site-search-empty">Type a service or keyword to search.</div>';
                return;
            }
            const matches = items
                .map((item) => ({ item, points: score(item, q) }))
                .filter((entry) => entry.points > 0)
                .sort((a, b) => b.points - a.points || String(a.item.title).localeCompare(String(b.item.title)))
                .slice(0, 8);

            if (!matches.length) {
                results.innerHTML = '<div class="site-search-empty">No matching content found.</div>';
                return;
            }

            results.innerHTML = matches.map(({ item }, index) => `
                <a class="site-search-item" data-search-result="${index}" href="${item.url}">
                    <span class="site-search-icon"><i class="bi ${iconFor(item.category)}"></i></span>
                    <span>
                        <span class="site-search-title">${escapeHtml(item.title)}</span>
                        <span class="site-search-meta">${escapeHtml(item.category)}</span>
                    </span>
                </a>
            `).join('');
        }

        function escapeHtml(value) {
            return String(value || '').replace(/[&<>"']/g, (char) => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            }[char]));
        }

        function resultLinks() {
            return Array.from(results.querySelectorAll('.site-search-item'));
        }

        function setActive(index) {
            const links = resultLinks();
            activeIndex = links.length ? (index + links.length) % links.length : -1;
            links.forEach((link, i) => link.classList.toggle('is-active', i === activeIndex));
        }

        toggle.addEventListener('click', () => setOpen(!panel.classList.contains('is-open')));
        close.addEventListener('click', () => setOpen(false));
        input.addEventListener('input', () => render(input.value));

        form.addEventListener('submit', (event) => {
            event.preventDefault();
            const links = resultLinks();
            if (activeIndex >= 0 && links[activeIndex]) {
                links[activeIndex].click();
            } else if (links[0]) {
                links[0].click();
            }
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'ArrowDown') {
                event.preventDefault();
                setActive(activeIndex + 1);
            } else if (event.key === 'ArrowUp') {
                event.preventDefault();
                setActive(activeIndex - 1);
            } else if (event.key === 'Escape') {
                setOpen(false);
            }
        });

        document.addEventListener('click', (event) => {
            if (!panel.contains(event.target) && !toggle.contains(event.target)) {
                setOpen(false);
            }
        });
    })();
    </script>
