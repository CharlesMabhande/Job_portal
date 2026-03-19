<?php
require_once __DIR__ . '/config/config.php';

$pageTitle = 'Find Your Next Career - University Job Portal';
$fullWidth = true;
$db = getDBConnection();

// Filters
$page = max(1, (int)($_GET['page'] ?? 1));
$limit = 10;
$offset = ($page - 1) * $limit;
$search = trim($_GET['search'] ?? '');
$filterType = trim($_GET['job_type'] ?? '');
$filterDept = trim($_GET['department'] ?? '');

// Build WHERE clause
$where = "status = 'Active'";
$params = [];

if ($search !== '') {
    $where .= " AND (title LIKE ? OR description LIKE ? OR department LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($filterType !== '') {
    $where .= " AND job_type = ?";
    $params[] = $filterType;
}
if ($filterDept !== '') {
    $where .= " AND department = ?";
    $params[] = $filterDept;
}

// Total count
$countStmt = $db->prepare("SELECT COUNT(*) AS total FROM jobs WHERE {$where}");
$countStmt->execute($params);
$total = (int)($countStmt->fetch()['total'] ?? 0);
$totalPages = max(1, (int)ceil($total / $limit));

// Fetch jobs
$sql = "SELECT job_id, title, department, location, job_type, created_at, application_deadline, description, max_applications
        FROM jobs WHERE {$where} ORDER BY created_at DESC LIMIT ? OFFSET ?";
$stmt = $db->prepare($sql);
$bindIndex = 1;
foreach ($params as $p) {
    $stmt->bindValue($bindIndex++, $p);
}
$stmt->bindValue($bindIndex++, (int)$limit, PDO::PARAM_INT);
$stmt->bindValue($bindIndex++, (int)$offset, PDO::PARAM_INT);
$stmt->execute();
$jobs = $stmt->fetchAll();

// Fetch filter options
$deptStmt = $db->query("SELECT DISTINCT department FROM jobs WHERE status = 'Active' AND department IS NOT NULL AND department != '' ORDER BY department");
$departments = $deptStmt->fetchAll(PDO::FETCH_COLUMN);

$typeStmt = $db->query("SELECT DISTINCT job_type FROM jobs WHERE status = 'Active' ORDER BY job_type");
$jobTypes = $typeStmt->fetchAll(PDO::FETCH_COLUMN);

// Stats
$statsStmt = $db->query("SELECT COUNT(*) FROM jobs WHERE status = 'Active'");
$totalActiveJobs = (int)$statsStmt->fetchColumn();

$appsStmt = $db->query("SELECT COUNT(*) FROM applications");
$totalApps = (int)$appsStmt->fetchColumn();

$deptCountStmt = $db->query("SELECT COUNT(DISTINCT department) FROM jobs WHERE status = 'Active' AND department IS NOT NULL AND department != ''");
$totalDepts = (int)$deptCountStmt->fetchColumn();

require_once BASE_PATH . '/includes/header.php';

// Helper: days remaining
function daysLeft($deadline) {
    if (empty($deadline)) return null;
    $now = new DateTime();
    $end = new DateTime($deadline);
    $diff = $now->diff($end);
    if ($now > $end) return -1;
    return (int)$diff->days;
}

// Helper: type badge class
function typeBadgeClass($type) {
    $map = [
        'Full-time' => 'badge-fulltime',
        'Part-time' => 'badge-parttime',
        'Contract' => 'badge-contract',
        'Internship' => 'badge-internship',
    ];
    return $map[$type] ?? 'badge-fulltime';
}
?>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row align-items-center g-4 hero-layout">
            <div class="col-lg-6 hero-content">
                <h1>Find the job that fits your life</h1>
                <p class="hero-desc">
                    Join our University and discover career opportunities that make a real difference in your community.
                    We offer competitive benefits, professional growth, and the chance to contribute to essential public services.
                </p>
                <div class="hero-buttons">
                    <a href="#open-positions" class="btn-hero-primary">
                        <i class="fa-solid fa-magnifying-glass"></i> Find job
                    </a>
                    <a href="<?php echo BASE_URL; ?>/login.php" class="btn-hero-outline">
                        <i class="fa-solid fa-location-arrow"></i> Track Application
                    </a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="hero-visual">
                    <div class="hero-visual-inner">
                        <div class="hero-avatar"><i class="fa-solid fa-user-tie"></i></div>
                        <h4>Build Your Career</h4>
                        <p>Apply online, track progress, and manage your profile in one place.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Open Positions Section -->
<section id="open-positions" class="open-section py-5">
    <div class="container">
        <div class="text-center mb-4">
            <span class="section-label">Open Positions</span>
            <h2 class="section-title">Current Vacancies</h2>
            <p class="section-subtitle mx-auto">
                Explore available positions across our departments and contribute to delivering quality services and excellence.
            </p>
        </div>

        <div class="filter-inline-card mb-4">
            <div class="filter-inline-title">
                <i class="fa-solid fa-filter me-2"></i> Apply Filters to Easily Find Your Dream Job
            </div>
            <form method="get" action="<?php echo BASE_URL; ?>/index.php#open-positions" class="row g-2 align-items-end">
                <div class="col-12 col-md-4">
                    <label class="form-label small fw-bold">Job Title or Keyword</label>
                    <input type="text" class="form-control form-control-sm" name="search" value="<?php echo escape($search); ?>" placeholder="Type at least 2 characters...">
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold">Department</label>
                    <select class="form-select form-select-sm" name="department">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo escape($dept); ?>" <?php echo $filterDept === $dept ? 'selected' : ''; ?>><?php echo escape($dept); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-3">
                    <label class="form-label small fw-bold">Job Type</label>
                    <select class="form-select form-select-sm" name="job_type">
                        <option value="">All Job Types</option>
                        <?php foreach ($jobTypes as $type): ?>
                            <option value="<?php echo escape($type); ?>" <?php echo $filterType === $type ? 'selected' : ''; ?>><?php echo escape($type); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-grid">
                    <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-magnifying-glass me-1"></i> Search</button>
                    <?php if ($search !== '' || $filterType !== '' || $filterDept !== ''): ?>
                        <a href="<?php echo BASE_URL; ?>/index.php#open-positions" class="btn-clear-filters mt-2"><i class="fa-solid fa-xmark me-1"></i> Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <div class="results-strip mb-3">
            <i class="fa-solid fa-circle-dot me-2"></i> Found <strong><?php echo $total; ?></strong> job<?php echo $total !== 1 ? 's' : ''; ?> matching your criteria
        </div>

        <?php if (empty($jobs)): ?>
            <div class="text-center py-5">
                <i class="fa-solid fa-magnifying-glass display-5 text-muted"></i>
                <h5 class="mt-3">No jobs found</h5>
                <p class="text-muted">Try adjusting your search filters or check back later for new openings.</p>
            </div>
        <?php endif; ?>

        <div class="row g-3">
            <?php foreach ($jobs as $job):
                $days = daysLeft($job['application_deadline']);
                $badgeClass = typeBadgeClass($job['job_type']);
            ?>
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="job-card">
                        <div class="job-card-header">
                            <div class="job-title">
                                <a href="<?php echo BASE_URL; ?>/job.php?job_id=<?php echo (int)$job['job_id']; ?>">
                                    <?php echo escape($job['title']); ?>
                                </a>
                            </div>
                            <?php if ($days !== null && $days >= 0): ?>
                                <span class="badge-deadline <?php echo $days <= 5 ? 'urgent' : ''; ?>"><?php echo $days; ?>d left</span>
                            <?php endif; ?>
                        </div>
                        <div class="job-card-body">
                            <p class="job-description"><?php echo escape($job['description']); ?></p>
                            <div class="job-meta">
                                <?php if (!empty($job['location'])): ?>
                                    <span class="meta-item"><i class="fa-solid fa-location-dot"></i><?php echo escape($job['location']); ?></span>
                                <?php endif; ?>
                                <span class="meta-item badge-type <?php echo $badgeClass; ?>"><?php echo escape($job['job_type']); ?></span>
                                <?php if (!empty($job['max_applications']) && (int)$job['max_applications'] > 1): ?>
                                    <span class="meta-item badge-positions"><i class="fa-solid fa-users"></i><?php echo (int)$job['max_applications']; ?> Positions</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="job-card-footer">
                            <div class="small text-muted">
                                <?php if (!empty($job['department'])): ?>
                                    <i class="fa-solid fa-building me-1"></i><?php echo escape($job['department']); ?>
                                <?php endif; ?>
                            </div>
                            <a class="btn-view-details" href="<?php echo BASE_URL; ?>/job.php?job_id=<?php echo (int)$job['job_id']; ?>">
                                View Details <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalPages > 1): ?>
            <nav class="mt-4 d-flex justify-content-center">
                <ul class="pagination mb-0">
                    <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo BASE_URL; ?>/index.php?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&job_type=<?php echo urlencode($filterType); ?>&department=<?php echo urlencode($filterDept); ?>#open-positions">
                                <i class="fa-solid fa-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($p = max(1, $page - 2); $p <= min($totalPages, $page + 2); $p++): ?>
                        <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="<?php echo BASE_URL; ?>/index.php?page=<?php echo $p; ?>&search=<?php echo urlencode($search); ?>&job_type=<?php echo urlencode($filterType); ?>&department=<?php echo urlencode($filterDept); ?>#open-positions"><?php echo $p; ?></a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="<?php echo BASE_URL; ?>/index.php?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&job_type=<?php echo urlencode($filterType); ?>&department=<?php echo urlencode($filterDept); ?>#open-positions">
                                <i class="fa-solid fa-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</section>

<!-- Stats Section -->
<section class="stats-section">
    <div class="container">
        <div class="row g-4">
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalActiveJobs; ?>+</div>
                    <div class="stat-label">Active Positions</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalApps > 0 ? number_format($totalApps) : '0'; ?>+</div>
                    <div class="stat-label">Applications Processed</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-number">95%</div>
                    <div class="stat-label">Candidate Satisfaction</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalDepts; ?>+</div>
                    <div class="stat-label">Active Departments</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Benefits Section -->
<section class="benefits-section">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-label">Why Join Us</span>
            <h2 class="section-title">Comprehensive Benefits Package</h2>
            <p class="section-subtitle mx-auto">
                We believe in taking care of our employees with a robust benefits package that supports your health, financial security, and overall well-being.
            </p>
        </div>

        <div class="row g-4">
            <div class="col-sm-6 col-lg-4">
                <div class="benefit-card">
                    <div class="icon-box blue"><i class="fa-solid fa-heart-pulse"></i></div>
                    <h4>Health Insurance</h4>
                    <p>Comprehensive medical, dental, and vision coverage for you and your family.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="benefit-card">
                    <div class="icon-box teal"><i class="fa-solid fa-piggy-bank"></i></div>
                    <h4>Pension &amp; Provident</h4>
                    <p>Access to pensions, retirement contributions, and provident fund schemes.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="benefit-card">
                    <div class="icon-box amber"><i class="fa-solid fa-calendar-check"></i></div>
                    <h4>Paid Time Off</h4>
                    <p>Annual leave, compassionate leave, sick leave, and all public holidays.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="benefit-card">
                    <div class="icon-box purple"><i class="fa-solid fa-graduation-cap"></i></div>
                    <h4>Professional Development</h4>
                    <p>Training programs, certifications, workshops, and tuition reimbursement.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="benefit-card">
                    <div class="icon-box rose"><i class="fa-solid fa-scale-balanced"></i></div>
                    <h4>Work-Life Balance</h4>
                    <p>Flexible working arrangements and supportive work environment.</p>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="benefit-card">
                    <div class="icon-box sky"><i class="fa-solid fa-chart-line"></i></div>
                    <h4>Career Growth</h4>
                    <p>Clear pathways for advancement and opportunities for professional growth.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Feature: Meaningful Work -->
<section class="feature-section alt-bg">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6">
                <div class="feature-content">
                    <span class="section-label">Make a Difference</span>
                    <h2>Meaningful Work With Real Impact</h2>
                    <p>
                        At our University, every role contributes directly to the well-being of our community.
                        Whether you are in engineering, finance, planning, health, or academic services, your
                        work helps deliver essential services and improve life for students and staff.
                    </p>
                    <ul class="feature-list">
                        <li>Access cutting-edge tools and resources</li>
                        <li>Contribute to academic excellence</li>
                        <li>Work with a dedicated professional team</li>
                        <li>Make a real difference in your community</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="feature-image">
                    <h3><i class="bi bi-lightning-charge me-2"></i>Innovation & Excellence</h3>
                    <p>
                        You will be part of a professional, multi-disciplinary team committed to
                        transparency, accountability, and service excellence in higher education.
                    </p>
                    <div class="mt-3 d-flex gap-3 flex-wrap">
                        <span class="badge bg-white bg-opacity-25 px-3 py-2"><i class="bi bi-check-circle me-1"></i> Research</span>
                        <span class="badge bg-white bg-opacity-25 px-3 py-2"><i class="bi bi-check-circle me-1"></i> Teaching</span>
                        <span class="badge bg-white bg-opacity-25 px-3 py-2"><i class="bi bi-check-circle me-1"></i> Community</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Feature: Stability -->
<section class="feature-section">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-6 order-lg-2">
                <div class="feature-content">
                    <span class="section-label">Your Future</span>
                    <h2>Stability &amp; Professional Development</h2>
                    <p>
                        A career with our University offers the stability of institutional employment, access to
                        pension and social security benefits, and a structured working environment guided by clear
                        policies and procedures.
                    </p>
                    <ul class="feature-list">
                        <li>Competitive salary packages</li>
                        <li>Clear pathways for career advancement</li>
                        <li>Annual professional development budget</li>
                        <li>Comprehensive pension schemes</li>
                    </ul>
                </div>
            </div>
            <div class="col-lg-6 order-lg-1">
                <div class="feature-image" style="background: linear-gradient(135deg, #0d7a70 0%, #2b6cb0 60%, #1e3a5f 100%);">
                    <h3><i class="bi bi-trophy me-2"></i>Growth & Advancement</h3>
                    <p>
                        We invest in our staff through continuous training, skills development programmes,
                        and opportunities for internal advancement, enabling you to build a long-term, rewarding career.
                    </p>
                    <div class="mt-3 d-flex gap-3 flex-wrap">
                        <span class="badge bg-white bg-opacity-25 px-3 py-2"><i class="bi bi-check-circle me-1"></i> Training</span>
                        <span class="badge bg-white bg-opacity-25 px-3 py-2"><i class="bi bi-check-circle me-1"></i> Mentorship</span>
                        <span class="badge bg-white bg-opacity-25 px-3 py-2"><i class="bi bi-check-circle me-1"></i> Leadership</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <h2>Ready to Start Your Career Journey?</h2>
        <p>Join our University and make a difference in your community today.</p>
        <div class="d-flex gap-3 justify-content-center flex-wrap" style="position:relative;z-index:2;">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="<?php echo BASE_URL; ?>/register.php" class="btn-hero-primary">
                    <i class="fa-solid fa-user-plus"></i> Apply Now
                </a>
                <a href="<?php echo BASE_URL; ?>/login.php" class="btn-hero-outline">
                    <i class="fa-solid fa-right-to-bracket"></i> Login
                </a>
            <?php else: ?>
                <a href="#open-positions" class="btn-hero-primary">
                    <i class="fa-solid fa-magnifying-glass"></i> Browse Open Positions
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>
