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
                        <li><a href="#home">Home</a></li>
                        <li><a href="#about-us">About Us</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="#our-network">Our Network</a></li>
                        <li><a href="#">Blogs</a></li>
                        <li><a href="#contact-us">Contact Us</a></li>
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
                <span>© 2026 CAREYGO - All Rights Reserved.&nbsp;<a href="https://websoftera.com" target="_blank" class="websoftera-text">Websoftera</a></span>
            </div>
        </div>

        <!-- Bottom Copyright Bar -->
        <div class="footer-bottom-bar text-center py-3 mt-4 d-none d-md-block">
            <p class="mb-0 text-light-gray" style="font-size: 13px; letter-spacing: 0.5px;">© 2026 CAREYGO - All Rights Reserved.&nbsp;<a href="https://websoftera.com" target="_blank" class="websoftera-text">Websoftera</a>
            </p>
        </div>
    </footer>

    <!-- Enquiry Popup -->
    <div class="modal fade enquiry-modal" id="enquiryModal" tabindex="-1" aria-labelledby="enquiryModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="btn-close enquiry-close" data-bs-dismiss="modal"
                    aria-label="Close"></button>
                <div class="modal-body">
                    <div class="enquiry-logo-wrap">
                        <img src="assets/images/Main-Careygo-logo-blue.png" alt="CAREYGO Logo"
                            class="enquiry-logo">
                    </div>
                    <p class="enquiry-label text-uppercase mb-0" id="enquiryModalLabel">Connect With Us</p>

                    <form class="enquiry-form" id="enquiryForm" novalidate>
                        <div class="mb-2">
                            <input type="text" class="form-control" id="enquiryName" name="name"
                                placeholder="Full name" aria-label="Full name"
                                pattern="^[A-Za-z][A-Za-z\s.'-]{1,59}$" required>
                            <div class="invalid-feedback">Please enter a valid name without numbers.</div>
                        </div>
                        <div class="mb-2">
                            <input type="email" class="form-control" id="enquiryEmail" name="email"
                                placeholder="Email address" aria-label="Email address" required>
                            <div class="invalid-feedback">Please enter a valid email address.</div>
                        </div>
                        <div class="mb-2">
                            <input type="tel" class="form-control" id="enquiryPhone" name="phone"
                                placeholder="10 digit mobile number" aria-label="Phone number" inputmode="numeric" maxlength="10"
                                pattern="^[6-9][0-9]{9}$" required>
                            <div class="invalid-feedback">Please enter a valid 10 digit phone number.</div>
                        </div>
                        <button type="submit" class="btn btn-primary-custom enquiry-submit">
                            Send Enquiry
                            <span
                                class="icon-circle bg-white rounded-circle d-inline-flex align-items-center justify-content-center">
                                <i class="bi bi-arrow-up-right btn-arrow"></i>
                            </span>
                        </button>
                        <div class="enquiry-form-status" id="enquiryFormStatus" role="status" aria-live="polite"></div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Back to Top Button -->
    <button id="backToTop" class="back-to-top" title="Go to top" type="button" aria-label="Scroll to top">
        <i class="bi bi-chevron-up"></i>
    </button>

    <!-- Back to Top Script -->
    <script>
        const backToTopBtn = document.getElementById("backToTop");

        window.addEventListener("scroll", function () {
            backToTopBtn.style.display = window.scrollY > 300 ? "flex" : "none";
        }, { passive: true });

        backToTopBtn.addEventListener("click", function () {
            window.scrollTo({
                top: 0,
                behavior: "smooth"
            });
        });

        const contactForm = document.getElementById("contactForm");
        if (contactForm) {
            const nameInput = document.getElementById("contactName");
            const phoneInput = document.getElementById("contactPhone");

            function validateContactName() {
                const value = nameInput.value.trim();
                const isValid = /^[A-Za-z][A-Za-z\s.'-]{1,59}$/.test(value);
                nameInput.setCustomValidity(isValid ? "" : "Please enter a valid name without numbers.");
            }

            function validateContactPhone() {
                const value = phoneInput.value.trim();
                const isValid = /^(?:\+91[\s-]?)?[6-9][0-9]{9}$/.test(value);
                phoneInput.setCustomValidity(isValid ? "" : "Please enter a valid 10 digit phone number.");
            }

            nameInput.addEventListener("input", function () {
                nameInput.value = nameInput.value.replace(/[0-9]/g, "");
                validateContactName();
            });

            phoneInput.addEventListener("input", function () {
                phoneInput.value = phoneInput.value.replace(/[^\d+\s-]/g, "");
                validateContactPhone();
            });

            contactForm.addEventListener("submit", function (event) {
                event.preventDefault();
                event.stopPropagation();

                const status = document.getElementById("contactFormStatus");
                validateContactName();
                validateContactPhone();

                if (!contactForm.checkValidity()) {
                    contactForm.classList.add("was-validated");
                    if (status) {
                        status.className = "contact-form-status";
                        status.textContent = "";
                    }
                    return;
                }

                contactForm.classList.remove("was-validated");
                contactForm.reset();

                if (status) {
                    status.className = "contact-form-status is-success";
                    status.textContent = "Thank you. Our team will contact you shortly.";
                }
            });
        }

        const enquiryForm = document.getElementById("enquiryForm");
        if (enquiryForm) {
            const enquiryName = document.getElementById("enquiryName");
            const enquiryPhone = document.getElementById("enquiryPhone");

            function validateEnquiryName() {
                const value = enquiryName.value.trim();
                const isValid = /^[A-Za-z][A-Za-z\s.'-]{1,59}$/.test(value);
                enquiryName.setCustomValidity(isValid ? "" : "Please enter a valid name without numbers.");
            }

            function validateEnquiryPhone() {
                const value = enquiryPhone.value.trim();
                const isValid = /^[6-9][0-9]{9}$/.test(value);
                enquiryPhone.setCustomValidity(isValid ? "" : "Please enter a valid 10 digit phone number.");
            }

            enquiryName.addEventListener("input", function () {
                enquiryName.value = enquiryName.value.replace(/[0-9]/g, "");
                validateEnquiryName();
            });

            enquiryPhone.addEventListener("input", function () {
                enquiryPhone.value = enquiryPhone.value.replace(/\D/g, "").slice(0, 10);
                validateEnquiryPhone();
            });

            enquiryForm.addEventListener("submit", function (event) {
                event.preventDefault();
                event.stopPropagation();

                const status = document.getElementById("enquiryFormStatus");
                validateEnquiryName();
                validateEnquiryPhone();

                if (!enquiryForm.checkValidity()) {
                    enquiryForm.classList.add("was-validated");
                    if (status) {
                        status.className = "enquiry-form-status";
                        status.textContent = "";
                    }
                    return;
                }

                enquiryForm.classList.remove("was-validated");
                enquiryForm.reset();

                if (status) {
                    status.className = "enquiry-form-status is-success";
                    status.textContent = "Thank you. Our team will contact you shortly.";
                }
            });

            const enquiryModal = document.getElementById("enquiryModal");
            if (enquiryModal) {
                enquiryModal.addEventListener("hidden.bs.modal", function () {
                    const status = document.getElementById("enquiryFormStatus");
                    enquiryForm.classList.remove("was-validated");
                    enquiryForm.reset();
                    if (status) {
                        status.className = "enquiry-form-status";
                        status.textContent = "";
                    }
                });
            }
        }
    </script>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const mainNav = document.getElementById("mainNav");
            if (!mainNav || typeof bootstrap === "undefined") return;

            document.querySelectorAll(".nav-link, .footer-links a").forEach(function (link) {
                link.addEventListener("click", function (event) {
                    if (link.getAttribute("href") === "#home") {
                        event.preventDefault();
                        window.scrollTo({
                            top: 0,
                            behavior: "smooth"
                        });
                    }

                    if (mainNav.contains(link) && link.classList.contains("nav-link")) {
                        mainNav.querySelectorAll(".nav-link").forEach(function (navLink) {
                            navLink.classList.remove("active");
                        });
                        link.classList.add("active");
                    }

                    if (!mainNav.contains(link) || window.innerWidth >= 1200) return;

                    const navCollapse = bootstrap.Collapse.getOrCreateInstance(mainNav, {
                        toggle: false
                    });
                    navCollapse.hide();
                });
            });
        });
    </script>

</body>

</html>
