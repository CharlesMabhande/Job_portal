<?php
require_once __DIR__ . '/config/config.php';

if (isset($_SESSION['user_id'])) {
    // Logged-in users can still read the guide, no redirect needed.
}

$pageTitle = 'User Guide - Lupane State University Job Portal';
$error = null;
$registerFlow = (($_GET['next'] ?? '') === 'register');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $registerFlow) {
    requireCSRFToken();
    $ack = isset($_POST['guide_ack']) && $_POST['guide_ack'] === '1';
    if (!$ack) {
        $error = 'Please confirm that you have read the guide before continuing.';
    } else {
        $_SESSION['registration_guide_ack'] = 1;
        redirect('/register.php');
    }
}

require_once BASE_PATH . '/includes/header.php';
?>

<div class="container py-4">
    <style>
        .guide-brand {
            color: #c61f26 !important;
        }
        .guide-side-link {
            border-radius: 8px;
            transition: background-color .15s ease, color .15s ease, transform .15s ease;
        }
        .guide-side-link:hover,
        .guide-side-link:focus {
            background: rgba(198, 31, 38, 0.12);
            color: #8e1116;
            transform: translateX(2px);
        }
        .guide-side-link.active {
            background: rgba(198, 31, 38, 0.16);
            color: #7a0f14;
            font-weight: 600;
        }
        .guide-hero-card {
            border: 1px solid #e9b3b6;
            border-left: 4px solid #c61f26;
            border-radius: 14px;
            text-align: center;
            padding: 1rem;
            background: #fff;
            height: 100%;
            box-shadow: 0 2px 8px rgba(198, 31, 38, 0.08);
        }
        a.guide-hero-card-link {
            text-decoration: none;
            color: inherit;
            display: block;
        }
        a.guide-hero-card-link:hover .guide-hero-card {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(198, 31, 38, 0.16);
        }
        .guide-hero-card {
            transition: transform .15s ease, box-shadow .15s ease;
        }
        .guide-hero-icon {
            width: 46px;
            height: 46px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(198, 31, 38, 0.12);
            color: #c61f26;
            font-size: 1.2rem;
            margin-bottom: .6rem;
        }
        .guide-step-card {
            border: 1px solid #e9b3b6;
            border-left: 4px solid #c61f26;
            border-radius: 12px;
            padding: .9rem;
            background: #fff;
            margin-bottom: .75rem;
            box-shadow: 0 2px 8px rgba(198, 31, 38, 0.08);
        }
        .guide-step-num {
            width: 28px;
            height: 28px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: #c61f26;
            color: #fff;
            font-weight: 600;
            font-size: .85rem;
            margin-right: .5rem;
            flex-shrink: 0;
        }
        .guide-step-title {
            font-weight: 600;
            margin: 0;
        }
        .guide-step-head {
            display: flex;
            align-items: center;
            margin-bottom: .35rem;
        }
        .guide-icon-list {
            display: grid;
            gap: .75rem;
        }
        .guide-icon-item {
            border: 1px solid #ead0d2;
            border-left: 4px solid #c61f26;
            border-radius: 10px;
            padding: .75rem .85rem;
            background: #fff;
            display: flex;
            gap: .65rem;
            align-items: flex-start;
        }
        .guide-icon-badge {
            width: 32px;
            height: 32px;
            border-radius: 999px;
            flex-shrink: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background: rgba(198, 31, 38, 0.12);
            color: #c61f26;
            font-size: .95rem;
        }
        .guide-icon-item p {
            margin: 0;
            color: #5f6368;
            font-size: .9rem;
        }
        .guide-subcard {
            border: 1px solid #ead0d2;
            border-left: 4px solid #c61f26;
            border-radius: 10px;
            background: #fff;
            padding: .8rem .9rem;
            margin-bottom: .7rem;
            box-shadow: 0 2px 8px rgba(198, 31, 38, 0.08);
        }
        .guide-subcard:last-child {
            margin-bottom: 0;
        }
    </style>
    <div class="row g-4">
        <div class="col-12 col-lg-3">
            <div class="card shadow-sm border-0 position-sticky" style="top: 90px;">
                <div class="card-body">
                    <h2 class="h6 mb-3"><i class="bi bi-list-ul me-2"></i>Contents</h2>
                    <div class="list-group list-group-flush small">
                        <a class="list-group-item list-group-item-action px-0 guide-side-link" href="#overview"><i class="bi bi-house-door me-2"></i>Overview</a>
                        <a class="list-group-item list-group-item-action px-0 guide-side-link" href="#getting-started"><i class="bi bi-play-circle me-2"></i>Getting Started</a>
                        <a class="list-group-item list-group-item-action px-0 guide-side-link" href="#applying-jobs"><i class="bi bi-send-check me-2"></i>Applying for Jobs</a>
                        <a class="list-group-item list-group-item-action px-0 guide-side-link" href="#managing-applications"><i class="bi bi-folder-check me-2"></i>Managing Applications</a>
                        <a class="list-group-item list-group-item-action px-0 guide-side-link" href="#profile-management"><i class="bi bi-person-vcard me-2"></i>Profile Management</a>
                        <a class="list-group-item list-group-item-action px-0 guide-side-link" href="#dashboard-overview"><i class="bi bi-grid-1x2 me-2"></i>Dashboard Overview</a>
                        <a class="list-group-item list-group-item-action px-0 guide-side-link" href="#tips"><i class="bi bi-stars me-2"></i>Tips & Best Practices</a>
                        <a class="list-group-item list-group-item-action px-0 guide-side-link" href="#troubleshooting"><i class="bi bi-wrench-adjustable me-2"></i>Troubleshooting</a>
                        <a class="list-group-item list-group-item-action px-0 guide-side-link" href="#faqs"><i class="bi bi-question-circle me-2"></i>FAQs</a>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-lg-9">
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body p-4">
                    <h1 class="h2 mb-2"><i class="bi bi-journal-richtext me-2 guide-brand"></i>User Guide</h1>
                    <p class="text-muted mb-0">
                        Welcome to the Lupane State University Job Portal User Guide.
                        This guide explains how public visitors and candidates use this system.
                    </p>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-circle me-1"></i><?php echo escape($error); ?>
                </div>
            <?php endif; ?>

            <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                    <a href="<?php echo BASE_URL; ?>/user-guide.php?next=register" class="guide-hero-card-link" aria-label="Create account">
                        <div class="guide-hero-card">
                            <div class="guide-hero-icon"><i class="bi bi-person-plus"></i></div>
                            <h3 class="h6 mb-1">Create Account</h3>
                            <p class="small text-muted mb-0">Start your journey here.</p>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-md-4">
                    <a href="<?php echo BASE_URL; ?>/login.php" class="guide-hero-card-link" aria-label="Login">
                        <div class="guide-hero-card">
                            <div class="guide-hero-icon"><i class="bi bi-box-arrow-in-right"></i></div>
                            <h3 class="h6 mb-1">Login</h3>
                            <p class="small text-muted mb-0">Access your candidate dashboard.</p>
                        </div>
                    </a>
                </div>
                <div class="col-12 col-md-4">
                    <a href="<?php echo BASE_URL; ?>/index.php#open-positions" class="guide-hero-card-link" aria-label="Browse jobs">
                        <div class="guide-hero-card">
                            <div class="guide-hero-icon"><i class="bi bi-briefcase"></i></div>
                            <h3 class="h6 mb-1">Browse Jobs</h3>
                            <p class="small text-muted mb-0">Find matching vacancies fast.</p>
                        </div>
                    </a>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4" id="overview">
                <div class="card-body p-4">
                    <h2 class="h4">Overview</h2>
                    <p>
                        The portal helps public visitors discover jobs and helps candidates
                        create accounts, complete profiles, and submit applications.
                    </p>
                    <div class="alert alert-primary small mb-0">
                        <strong>Good news:</strong> once your profile is complete, applying is fast because your saved details are reused across applications.
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4" id="getting-started">
                <div class="card-body p-4">
                    <h2 class="h4">Getting Started (Public / Candidate)</h2>
                    <div class="guide-step-card">
                        <div class="guide-step-head"><span class="guide-step-num">1</span><p class="guide-step-title">Open Jobs</p></div>
                        <p class="small text-muted mb-0">Go to <strong>Jobs</strong> and browse active vacancies on the home page.</p>
                    </div>
                    <div class="guide-step-card">
                        <div class="guide-step-head"><span class="guide-step-num">2</span><p class="guide-step-title">Filter Vacancies</p></div>
                        <p class="small text-muted mb-0">Use title, department, job type, and vacancy scope filters to narrow results.</p>
                    </div>
                    <div class="guide-step-card">
                        <div class="guide-step-head"><span class="guide-step-num">3</span><p class="guide-step-title">Read Guide, Then Register</p></div>
                        <p class="small text-muted mb-0">Review this User Guide and continue to registration.</p>
                    </div>
                    <div class="guide-step-card">
                        <div class="guide-step-head"><span class="guide-step-num">4</span><p class="guide-step-title">Login</p></div>
                        <p class="small text-muted mb-0">Create your candidate account, then sign in.</p>
                    </div>
                    <div class="guide-step-card mb-0">
                        <div class="guide-step-head"><span class="guide-step-num">5</span><p class="guide-step-title">Complete Profile</p></div>
                        <p class="small text-muted mb-0">Fill profile sections and upload required PDFs before applying.</p>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4" id="applying-jobs">
                <div class="card-body p-4">
                    <h2 class="h4">Applying for Jobs</h2>
                    <h3 class="h6 mt-3">Prerequisites</h3>
                    <ul>
                        <li>You must be registered and logged in as Candidate.</li>
                        <li>Your profile should be complete.</li>
                        <li>You must have both required profile documents uploaded.</li>
                    </ul>
                    <h3 class="h6 mt-3">Application Steps</h3>
                    <div class="guide-step-card">
                        <div class="guide-step-head"><span class="guide-step-num">1</span><p class="guide-step-title">Browse Jobs</p></div>
                        <p class="small text-muted mb-0">Open a vacancy card and review job details.</p>
                    </div>
                    <div class="guide-step-card">
                        <div class="guide-step-head"><span class="guide-step-num">2</span><p class="guide-step-title">Check Requirements</p></div>
                        <p class="small text-muted mb-0">Confirm department, job type, eligibility, and deadline.</p>
                    </div>
                    <div class="guide-step-card">
                        <div class="guide-step-head"><span class="guide-step-num">3</span><p class="guide-step-title">Apply</p></div>
                        <p class="small text-muted mb-0">Click <strong>Apply</strong> on the selected vacancy.</p>
                    </div>
                    <div class="guide-step-card mb-0">
                        <div class="guide-step-head"><span class="guide-step-num">4</span><p class="guide-step-title">Verify Submission</p></div>
                        <p class="small text-muted mb-0">Check the entry in <strong>My Applications</strong>.</p>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4" id="managing-applications">
                <div class="card-body p-4">
                    <h2 class="h4">Managing Applications (Candidate)</h2>
                    <div class="guide-subcard">Use <strong>My Applications</strong> to track statuses.</div>
                    <div class="guide-subcard">Watch for updates such as review progress and interview scheduling.</div>
                    <div class="guide-subcard">Keep profile information current before each new application.</div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4" id="profile-management">
                <div class="card-body p-4">
                    <h2 class="h4">Profile Management (Candidate)</h2>
                    <p>Use <strong>Profile</strong> to maintain:</p>
                    <div class="guide-subcard">Personal details</div>
                    <div class="guide-subcard">Professional qualifications</div>
                    <div class="guide-subcard">O-Level / A-Level entries</div>
                    <div class="guide-subcard">Other certifications</div>
                    <div class="guide-subcard">Work experience</div>
                    <div class="guide-subcard">References</div>
                    <div class="guide-subcard">Documents (CV and qualifications PDF)</div>
                    <div class="alert alert-warning small mb-0">
                        Document rules: PDF only, <strong>2 MB max</strong> for CV and <strong>2 MB max</strong> for combined certified copies of certificates.
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4" id="dashboard-overview">
                <div class="card-body p-4">
                    <h2 class="h4">Dashboard Overview</h2>
                    <p class="text-muted">Your candidate dashboard is your command center after login.</p>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <div class="guide-hero-card text-start">
                                <div class="guide-step-head mb-2">
                                    <span class="guide-hero-icon mb-0 me-2"><i class="bi bi-speedometer2"></i></span>
                                    <h3 class="h6 mb-0">Dashboard Home</h3>
                                </div>
                                <p class="small text-muted mb-0">View quick account status and recent activity at a glance.</p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="guide-hero-card text-start">
                                <div class="guide-step-head mb-2">
                                    <span class="guide-hero-icon mb-0 me-2"><i class="bi bi-briefcase"></i></span>
                                    <h3 class="h6 mb-0">Jobs</h3>
                                </div>
                                <p class="small text-muted mb-0">Browse vacancies and open full job details before applying.</p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="guide-hero-card text-start">
                                <div class="guide-step-head mb-2">
                                    <span class="guide-hero-icon mb-0 me-2"><i class="bi bi-file-earmark-check"></i></span>
                                    <h3 class="h6 mb-0">My Applications</h3>
                                </div>
                                <p class="small text-muted mb-0">Track each submission and monitor status changes.</p>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="guide-hero-card text-start">
                                <div class="guide-step-head mb-2">
                                    <span class="guide-hero-icon mb-0 me-2"><i class="bi bi-person-lines-fill"></i></span>
                                    <h3 class="h6 mb-0">Profile</h3>
                                </div>
                                <p class="small text-muted mb-0">Update personal details, qualifications, references, and documents.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4" id="tips">
                <div class="card-body p-4">
                    <h2 class="h4">Tips & Best Practices</h2>
                    <div class="guide-icon-list">
                        <div class="guide-icon-item">
                            <span class="guide-icon-badge"><i class="bi bi-alarm"></i></span>
                            <div>
                                <h3 class="h6 mb-1">Apply Early</h3>
                                <p>Avoid last-minute issues by submitting before deadline pressure.</p>
                            </div>
                        </div>
                        <div class="guide-icon-item">
                            <span class="guide-icon-badge"><i class="bi bi-bullseye"></i></span>
                            <div>
                                <h3 class="h6 mb-1">Tailor Your Profile</h3>
                                <p>Update experience and qualifications to match each role.</p>
                            </div>
                        </div>
                        <div class="guide-icon-item">
                            <span class="guide-icon-badge"><i class="bi bi-check2-square"></i></span>
                            <div>
                                <h3 class="h6 mb-1">Verify Every Submission</h3>
                                <p>Confirm new applications appear in <strong>My Applications</strong>.</p>
                            </div>
                        </div>
                        <div class="guide-icon-item">
                            <span class="guide-icon-badge"><i class="bi bi-telephone-forward"></i></span>
                            <div>
                                <h3 class="h6 mb-1">Keep Contacts Current</h3>
                                <p>Ensure phone and email are active for interview communication.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4" id="troubleshooting">
                <div class="card-body p-4">
                    <h2 class="h4">Troubleshooting</h2>
                    <div class="guide-subcard"><strong>Upload failed:</strong> ensure PDF format and max 2 MB limit.</div>
                    <div class="guide-subcard"><strong>Cannot apply:</strong> complete profile and required documents first.</div>
                    <div class="guide-subcard"><strong>Status not visible:</strong> refresh and check <strong>My Applications</strong>.</div>
                    <div class="guide-subcard"><strong>Login issue:</strong> verify email/password and reset if needed.</div>
                </div>
            </div>

            <div class="card shadow-sm border-0 mb-4" id="faqs">
                <div class="card-body p-4">
                    <h2 class="h4">FAQs</h2>
                    <div class="guide-subcard">
                        <h3 class="h6 mb-1">Can I apply for multiple jobs?</h3>
                        <p class="mb-0 text-muted">Yes, you can apply to multiple positions if you qualify.</p>
                    </div>
                    <div class="guide-subcard">
                        <h3 class="h6 mb-1">Can I use mobile?</h3>
                        <p class="mb-0 text-muted">Yes, the portal is responsive and works on phones and tablets.</p>
                    </div>
                    <div class="guide-subcard">
                        <h3 class="h6 mb-1">Do I need to re-enter profile data for each application?</h3>
                        <p class="mb-0 text-muted">No. Your profile data is reused, but keep it current before applying.</p>
                    </div>
                </div>
            </div>

            <?php if (!isset($_SESSION['user_id']) && $registerFlow): ?>
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-body p-4">
                        <h2 class="h5 mb-3">Continue to Registration</h2>
                        <form method="post" class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                            <input type="hidden" name="csrf_token" value="<?php echo escape($csrf); ?>">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="guide_ack" id="guide-ack" value="1" required>
                                <label class="form-check-label" for="guide-ack">
                                    I have read and understood this user guide manual.
                                </label>
                            </div>
                            <button type="submit" class="btn btn-primary">
                                Proceed to Candidate Registration <i class="bi bi-arrow-right ms-1"></i>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function () {
    var links = Array.prototype.slice.call(document.querySelectorAll('.guide-side-link'));
    if (!links.length) return;
    var map = [];
    links.forEach(function (a) {
        var id = (a.getAttribute('href') || '').replace('#', '');
        if (!id) return;
        var sec = document.getElementById(id);
        if (!sec) return;
        map.push({ link: a, section: sec });
    });
    if (!map.length) return;
    function setActive(currentId) {
        map.forEach(function (item) {
            item.link.classList.toggle('active', item.section.id === currentId);
        });
    }
    function onScroll() {
        var y = window.scrollY + 130;
        var current = map[0].section.id;
        map.forEach(function (item) {
            if (item.section.offsetTop <= y) current = item.section.id;
        });
        setActive(current);
    }
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
})();
</script>

<?php require_once BASE_PATH . '/includes/footer.php'; ?>

