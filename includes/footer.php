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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="index.php#about-us">About Us</a></li>
                        <li><a href="index.php#services">Services</a></li>
                        <li><a href="index.php#our-network">Our Network</a></li>
                        <li><a href="blog">Blogs</a></li>
                        <li><a href="index.php#contact-us">Contact Us</a></li>
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
                            <a href="tel:9850296178">98502 96178</a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Mobile Footer Content -->
        <div class="container position-relative z-2 d-md-none footer-mobile-v2">
            <div class="footer-row">
                <a href="index.php#about-us">About Us</a> <span class="sep">|</span>
                <a href="index.php#services">Services</a> <span class="sep">|</span>
                <a href="index.php#our-network">Our Network</a> <span class="sep">|</span>
                <a href="blog">Blog</a> <span class="sep">|</span>
                <a href="index.php#contact-us">Contact</a>
            </div>
            <div class="footer-row">
                <span>© 2026 CAREYGO - All Rights Reserved.&nbsp;<a href="index.php#home" class="websoftera-text">Careygo</a></span>
            </div>
        </div>

        <!-- Bottom Copyright Bar -->
        <div class="footer-bottom-bar text-center py-3 mt-4 d-none d-md-block">
            <p class="mb-0 text-light-gray" style="font-size: 13px; letter-spacing: 0.5px;">© 2026 CAREYGO - All Rights Reserved.&nbsp;<a href="index.php#home" class="websoftera-text">Careygo</a>
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
                        <div class="mb-2">
                            <select class="form-select" id="enquiryService" name="service" aria-label="Service type" required>
                                <option selected>Courier Services</option>
                                <option>E-Commerce Services</option>
                                <option>Business to Business - B2B</option>
                                <option>Online Sellers - D2C</option>
                                <option>Premium Express Services</option>
                                <option>Express Services</option>
                                <option>Reverse Pickup</option>
                                <option>Cash on Delivery - COD</option>
                                <option>International Courier</option>
                                <option>International - Airport to Airport</option>
                                <option>Packaging Solutions</option>
                            </select>
                            <div class="invalid-feedback">Please select a service type.</div>
                        </div>
                        <div class="mb-2">
                            <textarea class="form-control" id="enquiryMessage" name="message" rows="3"
                                placeholder="Pickup city, delivery city, weight, and any special instructions"
                                aria-label="Message" minlength="10" required></textarea>
                            <div class="invalid-feedback">Please add at least 10 characters.</div>
                        </div>
                        <button type="submit" class="btn btn-primary-custom cta-arrow-pill enquiry-submit">
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

    <?php if (function_exists('auth_user') && !auth_user()): ?>
    <!-- Mobile Auth Popup -->
    <div class="modal fade mobile-auth-modal" id="mobileAuthModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <button type="button" class="btn-close mobile-auth-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <iframe id="mobileAuthFrame" title="Careygo account access" loading="lazy"></iframe>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- WhatsApp Floating Button -->
    <a href="https://wa.me/919850296178?text=Hi%20Careygo%2C%20I%20would%20like%20to%20know%20more%20about%20your%20courier%20services."
        class="whatsapp-float"
        target="_blank"
        rel="noopener noreferrer"
        aria-label="Chat with Careygo on WhatsApp">
        <i class="bi bi-whatsapp"></i>
    </a>

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

        (function initServiceDropdowns() {
            const selects = document.querySelectorAll("#contactService, #enquiryService");
            const openDropdowns = [];

            function closeDropdown(dropdown) {
                dropdown.classList.remove("is-open");
                dropdown.querySelector(".cg-service-select__button").setAttribute("aria-expanded", "false");
            }

            function closeAllDropdowns(except) {
                openDropdowns.forEach(function (dropdown) {
                    if (dropdown !== except) closeDropdown(dropdown);
                });
            }

            selects.forEach(function (select) {
                const dropdown = document.createElement("div");
                const button = document.createElement("button");
                const menu = document.createElement("div");
                const menuId = select.id + "CustomMenu";

                dropdown.className = "cg-service-select";
                button.type = "button";
                button.className = "cg-service-select__button";
                button.setAttribute("aria-haspopup", "listbox");
                button.setAttribute("aria-expanded", "false");
                button.setAttribute("aria-controls", menuId);
                menu.className = "cg-service-select__menu";
                menu.id = menuId;
                menu.setAttribute("role", "listbox");

                function syncLabel() {
                    const selectedOption = select.options[select.selectedIndex] || select.options[0];
                    button.textContent = selectedOption ? selectedOption.text : "";
                    menu.querySelectorAll(".cg-service-select__option").forEach(function (optionButton) {
                        const isSelected = optionButton.dataset.value === select.value;
                        optionButton.classList.toggle("is-selected", isSelected);
                        optionButton.setAttribute("aria-selected", isSelected ? "true" : "false");
                    });
                }

                Array.from(select.options).forEach(function (option) {
                    const optionButton = document.createElement("button");
                    optionButton.type = "button";
                    optionButton.className = "cg-service-select__option";
                    optionButton.textContent = option.text;
                    optionButton.dataset.value = option.value;
                    optionButton.setAttribute("role", "option");
                    optionButton.addEventListener("click", function () {
                        select.value = option.value;
                        select.dispatchEvent(new Event("change", { bubbles: true }));
                        syncLabel();
                        closeDropdown(dropdown);
                        button.focus();
                    });
                    menu.appendChild(optionButton);
                });

                button.addEventListener("click", function () {
                    const isOpen = dropdown.classList.toggle("is-open");
                    closeAllDropdowns(dropdown);
                    button.setAttribute("aria-expanded", isOpen ? "true" : "false");
                });

                button.addEventListener("keydown", function (event) {
                    if (event.key === "Escape") {
                        closeDropdown(dropdown);
                    }
                    if (event.key === "ArrowDown" || event.key === "Enter" || event.key === " ") {
                        event.preventDefault();
                        dropdown.classList.add("is-open");
                        button.setAttribute("aria-expanded", "true");
                        const selected = menu.querySelector(".cg-service-select__option.is-selected") || menu.querySelector(".cg-service-select__option");
                        if (selected) selected.focus();
                    }
                });

                menu.addEventListener("keydown", function (event) {
                    const options = Array.from(menu.querySelectorAll(".cg-service-select__option"));
                    const currentIndex = options.indexOf(document.activeElement);

                    if (event.key === "Escape") {
                        closeDropdown(dropdown);
                        button.focus();
                    }
                    if (event.key === "ArrowDown" || event.key === "ArrowUp") {
                        event.preventDefault();
                        const step = event.key === "ArrowDown" ? 1 : -1;
                        const nextIndex = (currentIndex + step + options.length) % options.length;
                        options[nextIndex].focus();
                    }
                });

                select.classList.add("cg-service-select-native");
                select.insertAdjacentElement("afterend", dropdown);
                dropdown.appendChild(button);
                dropdown.appendChild(menu);
                openDropdowns.push(dropdown);
                syncLabel();

                if (select.form) {
                    select.form.addEventListener("reset", function () {
                        window.setTimeout(syncLabel, 0);
                    });
                }
            });

            document.addEventListener("click", function (event) {
                if (!event.target.closest(".cg-service-select")) {
                    closeAllDropdowns(null);
                }
            });
        })();

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

            mainNav.addEventListener("hidden.bs.collapse", function () {
                mainNav.classList.remove("show", "collapsing");
                mainNav.style.height = "";
                mainNav.style.display = "";
                document.querySelectorAll('[data-bs-target="#mainNav"]').forEach(function (toggle) {
                    toggle.setAttribute("aria-expanded", "false");
                    toggle.classList.add("collapsed");
                });
            });

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

                    mainNav.classList.remove("show", "collapsing");
                    mainNav.style.height = "";
                    mainNav.style.display = "";
                    document.querySelectorAll('[data-bs-target="#mainNav"]').forEach(function (toggle) {
                        toggle.setAttribute("aria-expanded", "false");
                        toggle.classList.add("collapsed");
                    });

                    const navCollapse = bootstrap.Collapse.getOrCreateInstance(mainNav, {
                        toggle: false
                    });
                    navCollapse.hide();
                });
            });

            const enquiryModal = document.getElementById("enquiryModal");
            if (enquiryModal) {
                const enquiryModalInstance = bootstrap.Modal.getOrCreateInstance(enquiryModal);
                document.querySelectorAll(".service-img-wrapper, .service-title").forEach(function (trigger) {
                    trigger.setAttribute("role", "button");
                    trigger.setAttribute("tabindex", "0");
                    trigger.addEventListener("click", function () {
                        enquiryModalInstance.show();
                    });
                    trigger.addEventListener("keydown", function (event) {
                        if (event.key === "Enter" || event.key === " ") {
                            event.preventDefault();
                            enquiryModalInstance.show();
                        }
                    });
                });
            }

            const mobileAuthModal = document.getElementById("mobileAuthModal");
            const mobileAuthFrame = document.getElementById("mobileAuthFrame");
            if (mobileAuthModal && mobileAuthFrame) {
                const authModalInstance = bootstrap.Modal.getOrCreateInstance(mobileAuthModal);
                const resizeAuthFrame = function () {
                    try {
                        const doc = mobileAuthFrame.contentDocument;
                        if (!doc) return;
                        const height = Math.ceil(doc.documentElement.scrollHeight);
                        const maxHeight = Math.max(320, window.innerHeight - 24);
                        mobileAuthFrame.style.height = Math.min(height, maxHeight) + "px";
                    } catch (error) {}
                };

                document.querySelectorAll(".mobile-auth-modal-link").forEach(function (link) {
                    link.addEventListener("click", function (event) {
                        if (window.innerWidth > 767) return;
                        event.preventDefault();
                        mobileAuthFrame.src = link.dataset.authUrl || link.getAttribute("href") || "login.php?modal=1";
                        authModalInstance.show();
                    });
                });

                mobileAuthFrame.addEventListener("load", function () {
                    resizeAuthFrame();
                    setTimeout(resizeAuthFrame, 150);
                });

                window.addEventListener("resize", resizeAuthFrame);
                window.addEventListener("message", function (event) {
                    if (event.origin !== window.location.origin) return;
                    const height = Number(event.data && event.data.careygoAuthHeight);
                    if (!height) return;
                    const maxHeight = Math.max(320, window.innerHeight - 24);
                    mobileAuthFrame.style.height = Math.min(Math.ceil(height), maxHeight) + "px";
                });

                mobileAuthModal.addEventListener("hidden.bs.modal", function () {
                    mobileAuthFrame.removeAttribute("src");
                    mobileAuthFrame.style.height = "";
                });
            }
        });
    </script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            if (window.careygoMobileAuthBound) return;
            window.careygoMobileAuthBound = true;

            document.addEventListener("click", function (event) {
                const link = event.target.closest(".mobile-auth-modal-link");
                if (!link || window.innerWidth > 767) return;

                const modalEl = document.getElementById("mobileAuthModal");
                const frame = document.getElementById("mobileAuthFrame");
                if (!modalEl || !frame) return;

                event.preventDefault();
                frame.src = link.dataset.authUrl || link.getAttribute("href") || "login.php?modal=1";
                if (typeof bootstrap !== "undefined") {
                    bootstrap.Modal.getOrCreateInstance(modalEl).show();
                } else {
                    modalEl.style.display = "block";
                    modalEl.removeAttribute("aria-hidden");
                    modalEl.setAttribute("aria-modal", "true");
                    modalEl.classList.add("show");
                    document.body.classList.add("modal-open");
                    if (!document.querySelector(".mobile-auth-backdrop")) {
                        const backdrop = document.createElement("div");
                        backdrop.className = "modal-backdrop fade show mobile-auth-backdrop";
                        document.body.appendChild(backdrop);
                    }
                }
            });

            document.addEventListener("click", function (event) {
                const close = event.target.closest('[data-bs-dismiss="modal"], .mobile-auth-backdrop');
                if (!close) return;

                const modalEl = document.getElementById("mobileAuthModal");
                const frame = document.getElementById("mobileAuthFrame");
                if (!modalEl || !modalEl.classList.contains("show")) return;

                if (typeof bootstrap !== "undefined") return;

                event.preventDefault();
                modalEl.classList.remove("show");
                modalEl.style.display = "none";
                modalEl.setAttribute("aria-hidden", "true");
                modalEl.removeAttribute("aria-modal");
                document.body.classList.remove("modal-open");
                document.querySelectorAll(".mobile-auth-backdrop").forEach(function (backdrop) {
                    backdrop.remove();
                });
                if (frame) {
                    frame.removeAttribute("src");
                    frame.style.height = "";
                }
            });

            window.addEventListener("message", function (event) {
                if (event.origin !== window.location.origin) return;
                const frame = document.getElementById("mobileAuthFrame");
                if (!frame) return;

                const height = Number(event.data && event.data.careygoAuthHeight);
                if (!height) return;

                const maxHeight = Math.max(320, window.innerHeight - 24);
                frame.style.height = Math.min(Math.ceil(height), maxHeight) + "px";
            });

            const modalEl = document.getElementById("mobileAuthModal");
            const frame = document.getElementById("mobileAuthFrame");
            if (modalEl && frame) {
                modalEl.addEventListener("hidden.bs.modal", function () {
                    frame.removeAttribute("src");
                    frame.style.height = "";
                });
            }
        });
    </script>

</body>

</html>
