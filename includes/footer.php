    <!-- ===== FOOTER SECTION ===== -->
    <footer class="footer-section position-relative pt-5 mx-auto">
        <!-- Optional faint world map background pattern -->
        <div class="footer-bg-map"></div>

        <!-- Desktop Footer Content -->
        <div class="container position-relative z-2 mt-4 px-lg-4 d-none d-md-block">
            <div class="row gx-lg-5 mb-4 pb-2">
                <!-- Column 1: Logo & Company Info -->
                <div class="col-lg-3 col-md-6 mb-5 mb-lg-0 footer-col pe-lg-4">
                    <img src="assets/images/Main-Careygo-logo-blue.png" alt="CAREYGO Logo" class="footer-logo mb-5"
                        style="filter: brightness(0) invert(1);">
                    <div class="footer-socials d-flex gap-3 mt-4 position-relative z-2">
                        <a href="#" class="social-link" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                        <a href="#" class="social-link" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                        <a href="#" class="social-link" aria-label="X (Twitter)"><i class="bi bi-twitter-x"></i></a>
                        <a href="#" class="social-link" aria-label="LinkedIn"><i class="bi bi-linkedin"></i></a>
                    </div>
                </div>

                <!-- Column 2: Useful Links -->
                <div class="col-lg-3 col-md-6 mb-5 mb-lg-0 footer-col footer-col-divider footer-useful-links ps-lg-5">
                    <h5 class="fw-semibold mb-4 footer-heading d-flex align-items-center gap-2" style="color: #009cff;">
                        <i class="bi bi-arrow-right-circle fs-5" style="font-weight: 200;"></i> Useful Link
                    </h5>
                    <ul class="list-unstyled footer-links mb-0 d-flex flex-column gap-3">
                        <li><a href="#">Home</a></li>
                        <li><a href="#">About Us</a></li>
                        <li><a href="#">Services</a></li>
                        <li><a href="#">Our Network</a></li>
                        <li><a href="#">Blogs</a></li>
                        <li><a href="#">Contact Us</a></li>
                    </ul>
                </div>

                <!-- Column 3: Working Hours -->
                <div class="col-lg-3 col-md-6 mb-5 mb-lg-0 footer-col footer-col-divider ps-lg-5">
                    <h5 class="fw-semibold mb-4 footer-heading d-flex align-items-center gap-2" style="color: #009cff;">
                        <i class="bi bi-arrow-right-circle fs-5" style="font-weight: 200;"></i> Working Hours
                    </h5>
                    <ul class="list-unstyled footer-links text-light-gray mb-0 d-flex flex-column gap-3">
                        <li>Mon to Fri : 9:00 AM – 5:00 PM</li>
                        <li>Saturday : 10:00 AM – 6:00 PM</li>
                        <li>Sunday Closed</li>
                    </ul>
                </div>

                <!-- Column 4: Contact Info -->
                <div class="col-lg-3 col-md-6 footer-col ps-lg-5">
                    <h5 class="fw-semibold mb-4 footer-heading d-flex align-items-center gap-2" style="color: #009cff;">
                        <i class="bi bi-arrow-right-circle fs-5" style="font-weight: 200;"></i> Say Hello
                    </h5>
                    <ul class="list-unstyled footer-contact text-light-gray mb-0 d-flex flex-column gap-3">
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-envelope mt-1 text-white"></i>
                            <a href="mailto:info@careygo.in">info@careygo.in</a>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-geo-alt mt-1 text-white"></i>
                            <span class="d-inline-block">250 Main Street, 2nd Floor. USA</span>
                        </li>
                        <li class="d-flex align-items-start gap-2">
                            <i class="bi bi-telephone mt-1 text-white"></i>
                            <a href="tel:+919850296178">+91 98502 96178</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Mobile Footer Content -->
        <div class="container position-relative z-2 d-md-none footer-mobile-v2">
            <div class="footer-row">
                <a href="#">About Us</a> <span class="sep">|</span> <a href="#">Returns & Refunds</a> <span
                    class="sep">|</span> <a href="#">Privacy Policy</a> <span class="sep">|</span> <a href="#">FAQ</a>
            </div>
            <div class="footer-row">
                <a href="tel:+919850296178"><i class="bi bi-telephone-fill"></i> +91 98502 96178</a>
                <span class="sep">|</span>
                <a href="mailto:info@careygo.in"><i class="bi bi-envelope-fill"></i> info@careygo.in</a>
            </div>
            <div class="footer-row">
                <span>© 2026 CAREYGO - All Rights Reserved.&nbsp;<a href="https://websoftera.com" target="_blank" class="websoftera-text">Websoftera</a></span>
            </div>
        </div>

        <!-- Bottom Copyright Bar -->
        <div class="footer-bottom-bar text-center py-3 mt-4 d-none d-md-block">
            <p class="mb-0 text-light-gray" style="font-size: 13px; letter-spacing: 0.5px;">Copyright @ <?php echo date('Y'); ?>. CAREYGO
            </p>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button onclick="scrollToTop()" id="backToTop" class="back-to-top" title="Go to top">
        <i class="bi bi-chevron-up"></i>
    </button>

    <!-- Back to Top Script -->
    <script>
        window.onscroll = function () {
            var btn = document.getElementById("backToTop");
            if (document.body.scrollTop > 300 || document.documentElement.scrollTop > 300) {
                btn.style.display = "flex";
            } else {
                btn.style.display = "none";
            }
        };

        function scrollToTop() {
            const c = document.documentElement.scrollTop || document.body.scrollTop;
            if (c > 0) {
                window.requestAnimationFrame(scrollToTop);
                window.scrollTo(0, c - c / 12 - 5);
            }
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
