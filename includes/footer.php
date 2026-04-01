<?php
/**
 * Modern multi-column footer.
 */
$fullWidth = $fullWidth ?? false;
?>
<?php if (!$fullWidth): ?>
</main>
<?php endif; ?>

<footer class="jp-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-start gap-3 mb-3">
                    <img src="<?php echo SITE_LOGO_URL; ?>" class="footer-brand-logo flex-shrink-0" alt="<?php echo escape(SITE_LOGO_ALT); ?>">
                    <h5 class="mb-0 pt-1">Lupane State University Job Portal</h5>
                </div>
                <p class="footer-desc">
                    Discover career opportunities that make a real difference. We offer competitive benefits,
                    professional growth, and the chance to contribute to essential services.
                </p>
                <div class="d-flex gap-2 footer-social">
                    <?php if (SITE_SOCIAL_FACEBOOK !== ''): ?>
                    <a href="<?php echo escape(SITE_SOCIAL_FACEBOOK); ?>" target="_blank" rel="noopener noreferrer" aria-label="Lupane State University on Facebook"><i class="bi bi-facebook"></i></a>
                    <?php endif; ?>
                    <?php if (SITE_SOCIAL_X !== ''): ?>
                    <a href="<?php echo escape(SITE_SOCIAL_X); ?>" target="_blank" rel="noopener noreferrer" aria-label="Lupane State University on X"><i class="bi bi-twitter-x"></i></a>
                    <?php endif; ?>
                    <?php if (SITE_SOCIAL_INSTAGRAM !== ''): ?>
                    <a href="<?php echo escape(SITE_SOCIAL_INSTAGRAM); ?>" target="_blank" rel="noopener noreferrer" aria-label="Lupane State University on Instagram"><i class="bi bi-instagram"></i></a>
                    <?php endif; ?>
                    <?php if (SITE_SOCIAL_LINKEDIN !== ''): ?>
                    <a href="<?php echo escape(SITE_SOCIAL_LINKEDIN); ?>" target="_blank" rel="noopener noreferrer" aria-label="Lupane State University on LinkedIn"><i class="bi bi-linkedin"></i></a>
                    <?php endif; ?>
                    <a href="mailto:<?php echo escape(SITE_CONTACT_EMAIL); ?>" aria-label="Email <?php echo escape(SITE_CONTACT_EMAIL); ?>"><i class="bi bi-envelope-fill"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6">
                <h5>Quick Links</h5>
                <ul class="footer-links">
                    <li><a href="<?php echo BASE_URL; ?>/index.php"><i class="bi bi-chevron-right"></i> Find Jobs</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/login.php"><i class="bi bi-chevron-right"></i> Track Application</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/register.php"><i class="bi bi-chevron-right"></i> Register</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/login.php"><i class="bi bi-chevron-right"></i> Login</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5>For Candidates</h5>
                <ul class="footer-links">
                    <li><a href="<?php echo BASE_URL; ?>/index.php"><i class="bi bi-chevron-right"></i> Browse Vacancies</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/register.php"><i class="bi bi-chevron-right"></i> Create Account</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/login.php"><i class="bi bi-chevron-right"></i> Application Status</a></li>
                    <li><a href="<?php echo BASE_URL; ?>/login.php"><i class="bi bi-chevron-right"></i> My Profile</a></li>
                </ul>
            </div>

            <div class="col-lg-3 col-md-6">
                <h5>Contact</h5>
                <ul class="footer-links footer-contact-list">
                    <li class="mb-2">
                        <i class="fa-solid fa-location-dot me-2 mt-1" style="color: #c61f26"></i>
                        <span><?php echo nl2br(escape(SITE_CONTACT_ADDRESS)); ?></span>
                    </li>
                    <li class="mb-2">
                        <i class="fa-solid fa-phone me-2" style="color: #c61f26"></i>
                        <span>Tel: <?php echo escape(SITE_CONTACT_PHONE); ?></span>
                    </li>
                    <li class="mb-2">
                        <i class="fa-solid fa-fax me-2" style="color: #c61f26"></i>
                        <span>Fax: <?php echo escape(SITE_CONTACT_FAX); ?></span>
                    </li>
                    <li>
                        <a href="mailto:<?php echo escape(SITE_CONTACT_EMAIL); ?>"><i class="bi bi-envelope-fill me-2"></i><?php echo escape(SITE_CONTACT_EMAIL); ?></a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between gap-2">
            <div>&copy; <?php echo date('Y'); ?> Lupane State University Job Portal. All rights reserved.</div>
            <div>
                <i class="bi bi-shield-check me-1"></i> Secure &bull; Responsive &bull; Role-based Access
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
