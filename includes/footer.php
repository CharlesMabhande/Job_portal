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
                <h5><i class="fa-solid fa-briefcase me-2" style="color: #02FFFD"></i>University Job Portal</h5>
                <p class="footer-desc">
                    Discover career opportunities that make a real difference. We offer competitive benefits,
                    professional growth, and the chance to contribute to essential services.
                </p>
                <div class="d-flex gap-2 footer-social">
                    <a href="#"><i class="bi bi-facebook"></i></a>
                    <a href="#"><i class="bi bi-twitter-x"></i></a>
                    <a href="#"><i class="bi bi-linkedin"></i></a>
                    <a href="#"><i class="bi bi-envelope-fill"></i></a>
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
                <ul class="footer-links">
                    <li><i class="fa-solid fa-location-dot me-2" style="color: #02FFFD"></i> University Campus</li>
                    <li><a href="mailto:careers@university.edu"><i class="bi bi-envelope-fill me-2"></i> careers@university.edu</a></li>
                    <li><i class="fa-solid fa-phone me-2" style="color: #02FFFD"></i> +1 (555) 000-0000</li>
                    <li><i class="fa-solid fa-clock me-2" style="color: #02FFFD"></i> Mon-Fri: 8:00 AM - 5:00 PM</li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between gap-2">
            <div>&copy; <?php echo date('Y'); ?> University Job Portal. All rights reserved.</div>
            <div>
                <i class="bi bi-shield-check me-1"></i> Secure &bull; Responsive &bull; Role-based Access
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
