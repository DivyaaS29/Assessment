<?php
// students/settings.php - profile/settings page (Bootstrap layout)

session_start();
if (!isset($_SESSION["uname"])) {
    header("Location: ../login_student.php");
    exit;
}

require_once __DIR__ . '/../config.php';
error_reporting(0);

$uname = $_SESSION['uname'];

// ---------------------------
// Handle profile update
// ---------------------------
$updateSuccess = false;
$updateError   = '';

if (isset($_POST['submit'])) {
    $fname        = trim($_POST['fname'] ?? '');
    $email        = trim($_POST['email'] ?? '');
    $dob          = trim($_POST['dob'] ?? '');
    $gender       = trim($_POST['gender'] ?? '');
    $phone        = trim($_POST['phone'] ?? '');
    $linkedin_url = trim($_POST['linkedin_url'] ?? '');
    $portfolio_url= trim($_POST['portfolio_url'] ?? '');

    if ($fname === '' || $email === '' || $dob === '' || $gender === '') {
        $updateError = 'Full name, email, date of birth and gender are required.';
    } else {
        if ($stmt = $conn->prepare("
            UPDATE student 
            SET fname = ?, email = ?, dob = ?, gender = ?, phone = ?, linkedin_url = ?, portfolio_url = ?
            WHERE uname = ?
        ")) {
            $stmt->bind_param(
                'ssssssss',
                $fname, $email, $dob, $gender, $phone, $linkedin_url, $portfolio_url, $uname
            );
            if ($stmt->execute()) {
                $updateSuccess = true;
                // update session so UI reflects instantly
                $_SESSION['fname']         = $fname;
                $_SESSION['email']         = $email;
                $_SESSION['dob']           = $dob;
                $_SESSION['gender']        = $gender;
                $_SESSION['phone']         = $phone;
                $_SESSION['linkedin_url']  = $linkedin_url;
                $_SESSION['portfolio_url'] = $portfolio_url;
            } else {
                $updateError = 'Failed to update profile. Please try again.';
            }
            $stmt->close();
        } else {
            $updateError = 'Server error. Please try again later.';
        }
    }
}

// ---------------------------
// Fetch student data
// ---------------------------
$student = [
    'fname'         => $_SESSION['fname']         ?? '',
    'uname'         => $_SESSION['uname']         ?? '',
    'email'         => $_SESSION['email']         ?? '',
    'dob'           => $_SESSION['dob']           ?? '',
    'gender'        => $_SESSION['gender']        ?? '',
    'phone'         => $_SESSION['phone']         ?? '',
    'linkedin_url'  => $_SESSION['linkedin_url']  ?? '',
    'portfolio_url' => $_SESSION['portfolio_url'] ?? '',
    'avatar'        => '',
    'cv_path'       => ''
];

if ($stmt = $conn->prepare("SELECT fname, uname, email, dob, gender, phone, linkedin_url, portfolio_url, avatar, cv_path FROM student WHERE uname = ? LIMIT 1")) {
    $stmt->bind_param('s', $uname);
    $stmt->execute();
    $stmt->bind_result($sfname, $suname, $semail, $sdob, $sgender, $sphone, $sll, $spurl, $savatar, $scv);
    if ($stmt->fetch()) {
        $student['fname']         = $sfname;
        $student['uname']         = $suname;
        $student['email']         = $semail;
        $student['dob']           = $sdob;
        $student['gender']        = $sgender;
        $student['phone']         = $sphone;
        $student['linkedin_url']  = $sll;
        $student['portfolio_url'] = $spurl;
        $student['avatar']        = $savatar;
        $student['cv_path']       = $scv;
    }
    $stmt->close();
}

// ---------------------------
// Quick stats for sidebar
// ---------------------------
$totalExams = 0;
if ($s = $conn->prepare("SELECT COUNT(1) FROM exm_list")) {
    $s->execute();
    $s->bind_result($totalExams);
    $s->fetch();
    $s->close();
}

$totalAttempts = 0;
if ($s = $conn->prepare("SELECT COUNT(1) FROM atmpt_list WHERE uname = ?")) {
    $s->bind_param("s", $uname);
    $s->execute();
    $s->bind_result($totalAttempts);
    $s->fetch();
    $s->close();
}

$totalMessages = 0;
if ($s = $conn->prepare("SELECT COUNT(1) FROM message")) {
    $s->execute();
    $s->bind_result($totalMessages);
    $s->fetch();
    $s->close();
}

// ---------------------------
// Flash messages from upload_* redirects
// ---------------------------
$flashUpdated = $_GET['updated'] ?? '';
$flashError   = $_GET['error']   ?? '';

// include header (opens <main class="container my-4">)
include_once __DIR__ . '/../header.php';
?>

<div class="row g-4">
  <!-- LEFT: Sidebar -->
  <aside class="col-lg-3">
    <?php
    // Avatar resolution: use DB avatar if present, else defaults
    // upload_avatar.php stores absolute URL like: /AssessmentPage/uploads/avatars/...
    $avatar_url = '/AssessmentPage/img/mp.png'; // default male

    if (!empty($student['avatar'])) {
        $avatar_url = $student['avatar'];
    } else {
        // Optional: gender-based default
        if (!empty($student['gender'])) {
            $g = strtoupper(trim($student['gender']));
            if ($g === 'F' || $g === 'FEMALE') {
                $avatar_url = '/AssessmentPage/img/fp.png';
            }
        }
    }
    ?>
    <style>
      .sa-profile-card .avatar {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(0,0,0,0.05);
        box-shadow: 0 4px 10px rgba(0,0,0,0.06);
        background: #fff;
      }
      .sa-profile-card .card-body { text-align:center; padding-top:18px; }
      .sa-profile-card .nav-link { padding-left:0; padding-right:0; }
      .sa-profile-card .username { margin-top:8px; margin-bottom:2px; font-size:20px; font-weight:600; }
      .sa-profile-card .user-uname { color:#6c757d; font-size:13px; margin-bottom:10px; display:block; }
    </style>

    <div class="card mb-3 sa-profile-card">
      <div class="card-body">
        <img src="<?php echo htmlspecialchars($avatar_url, ENT_QUOTES, 'UTF-8'); ?>"
             alt="avatar"
             class="avatar mb-2">
        <div class="username">
          <?php echo htmlspecialchars($student['fname'] ?: 'Student', ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="user-uname">
          <?php echo htmlspecialchars($student['uname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <hr />
        <nav class="nav flex-column text-start">
          <a class="nav-link py-1" href="dash.php">Dashboard</a>
          <a class="nav-link py-1" href="exams.php">Exams</a>
          <a class="nav-link py-1" href="results.php">Results</a>
          <a class="nav-link py-1" href="messages.php">Messages</a>
          <a class="nav-link py-1 active" href="settings.php">Settings</a>
          <a class="nav-link py-1" href="help.php">Help</a>
          <a class="nav-link py-1 text-danger" href="../logout.php">Log out</a>
        </nav>
      </div>
    </div>

    <div class="card">
      <div class="card-body">
        <h6 class="card-title">Quick Stats</h6>
        <ul class="list-unstyled mb-0">
          <li class="mb-2"><strong><?php echo (int)$totalExams; ?></strong> <span class="text-muted">Exams</span></li>
          <li class="mb-2"><strong><?php echo (int)$totalAttempts; ?></strong> <span class="text-muted">Attempts</span></li>
          <li class="mb-2"><strong><?php echo (int)$totalMessages; ?></strong> <span class="text-muted">Announcements</span></li>
        </ul>
      </div>
    </div>
  </aside>

  <!-- RIGHT: Main -->
  <section class="col-lg-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">Settings</h3>
      <a href="dash.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
    </div>

    <?php if ($updateSuccess): ?>
      <div class="alert alert-success py-2">
        Profile updated successfully!
      </div>
    <?php elseif ($updateError !== ''): ?>
      <div class="alert alert-danger py-2">
        <?php echo htmlspecialchars($updateError, ENT_QUOTES, 'UTF-8'); ?>
      </div>
    <?php elseif ($flashUpdated === 'avatar'): ?>
      <div class="alert alert-success py-2">
        Profile photo updated successfully!
      </div>
    <?php elseif ($flashUpdated === 'cv'): ?>
      <div class="alert alert-success py-2">
        CV uploaded successfully!
      </div>
    <?php elseif ($flashError !== ''): ?>
      <div class="alert alert-danger py-2">
        There was a problem with your upload (<?php echo htmlspecialchars($flashError, ENT_QUOTES, 'UTF-8'); ?>).
      </div>
    <?php endif; ?>

    <!-- Profile card -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title mb-3">My Profile</h5>

        <div class="row g-4">
          <div class="col-md-8">
            <form method="post" action="settings.php" class="row g-3">
              <div class="col-md-6">
                <label for="fname" class="form-label">Full Name</label>
                <input type="text"
                       class="form-control"
                       id="fname"
                       name="fname"
                       minlength="4"
                       maxlength="30"
                       required
                       value="<?php echo htmlspecialchars($student['fname'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>

              <div class="col-md-6">
                <label for="uname" class="form-label">Username</label>
                <input type="text"
                       class="form-control"
                       id="uname"
                       name="uname"
                       value="<?php echo htmlspecialchars($student['uname'], ENT_QUOTES, 'UTF-8'); ?>"
                       disabled>
              </div>

              <div class="col-md-6">
                <label for="email" class="form-label">Email</label>
                <input type="email"
                       class="form-control"
                       id="email"
                       name="email"
                       minlength="5"
                       maxlength="50"
                       required
                       value="<?php echo htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>

              <div class="col-md-6">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="text"
                       class="form-control"
                       id="phone"
                       name="phone"
                       maxlength="20"
                       value="<?php echo htmlspecialchars($student['phone'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>

              <div class="col-md-4">
                <label for="dob" class="form-label">Date of Birth</label>
                <input type="date"
                       class="form-control"
                       id="dob"
                       name="dob"
                       required
                       value="<?php echo htmlspecialchars($student['dob'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>

              <div class="col-md-4">
                <label for="gender" class="form-label">Gender (M/F)</label>
                <input type="text"
                       class="form-control"
                       id="gender"
                       name="gender"
                       minlength="1"
                       maxlength="1"
                       required
                       value="<?php echo htmlspecialchars($student['gender'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>

              <div class="col-md-12">
                <label for="linkedin_url" class="form-label">LinkedIn URL</label>
                <input type="url"
                       class="form-control"
                       id="linkedin_url"
                       name="linkedin_url"
                       placeholder="https://www.linkedin.com/in/username"
                       value="<?php echo htmlspecialchars($student['linkedin_url'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>

              <div class="col-md-12">
                <label for="portfolio_url" class="form-label">Portfolio URL</label>
                <input type="url"
                       class="form-control"
                       id="portfolio_url"
                       name="portfolio_url"
                       placeholder="https://your-portfolio.com"
                       value="<?php echo htmlspecialchars($student['portfolio_url'], ENT_QUOTES, 'UTF-8'); ?>">
              </div>

              <div class="col-12 mt-2">
                <button type="submit" name="submit" class="btn btn-primary">
                  Update Profile
                </button>
              </div>
            </form>
          </div>

          <!-- Avatar upload -->
          <div class="col-md-4 text-center">
            <img src="<?php echo htmlspecialchars($avatar_url, ENT_QUOTES, 'UTF-8'); ?>"
                 alt="Current photo"
                 class="rounded-circle mb-3"
                 style="width:120px;height:120px;object-fit:cover;">
            <form action="upload_avatar.php" method="post" enctype="multipart/form-data" class="d-grid gap-2">
              <input type="file" name="avatar" class="form-control form-control-sm" accept="image/*" required>
              <button type="submit" class="btn btn-sm btn-outline-primary">Upload New Photo</button>
            </form>
            <div class="small text-muted mt-2">
              Max 2MB, JPG/PNG.
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- CV / Resume card -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title mb-3">CV / Resume</h5>

        <?php if (!empty($student['cv_path'])): ?>
          <p class="mb-2">
            Current CV:
            <a href="<?php echo htmlspecialchars($student['cv_path'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank">
              View / Download
            </a>
          </p>
        <?php else: ?>
          <p class="text-muted mb-2">No CV uploaded yet.</p>
        <?php endif; ?>

        <form action="upload_cv.php" method="post" enctype="multipart/form-data" class="row g-3">
          <div class="col-md-8">
            <input type="file" name="cv_file" class="form-control" accept=".pdf,.doc,.docx" required>
          </div>
          <div class="col-md-4 d-grid">
            <button type="submit" class="btn btn-outline-primary">
              Upload / Replace CV
            </button>
          </div>
        </form>
        <div class="small text-muted mt-2">
          Accepted formats: PDF, DOC, DOCX. Max 5MB.
        </div>
      </div>
    </div>

  </section>
</div>

<?php
include_once __DIR__ . '/../footer.php';
?>
