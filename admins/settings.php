<?php
session_start();

// Only logged-in teachers can access
if (!isset($_SESSION["user_id"])) {
    header("Location: ../login_teacher.php");
    exit;
}

require_once __DIR__ . '/../config.php';
error_reporting(0);
date_default_timezone_set('Asia/Kolkata');

$err_msg = '';
$success = '';

// ---------- Update profile if form submitted ----------
if (isset($_POST['submit'])) {
    $fname  = trim($_POST['fname'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $dob    = trim($_POST['dob'] ?? '');
    $gender = trim($_POST['gender'] ?? '');

    if ($fname === '' || $email === '' || $dob === '' || $gender === '') {
        $err_msg = 'Please fill in all required fields.';
    } else {
        if ($stmt = $conn->prepare("UPDATE teacher SET fname = ?, dob = ?, gender = ?, email = ? WHERE id = ?")) {
            $id = (int)$_SESSION['user_id'];
            $stmt->bind_param('ssssi', $fname, $dob, $gender, $email, $id);
            if ($stmt->execute()) {
                $success = 'Profile updated successfully.';

                // Update session values so UI reflects changes immediately
                $_SESSION['fname']  = $fname;
                $_SESSION['email']  = $email;
                $_SESSION['dob']    = $dob;
                $_SESSION['gender'] = $gender;
            } else {
                $err_msg = 'Failed to update profile. Please try again.';
            }
            $stmt->close();
        } else {
            $err_msg = 'Server error. Please try again later.';
        }
    }
}

// ---------- Fetch teacher data for display ----------
$teacher = [
    'fname'   => $_SESSION['fname']  ?? '',
    'subject' => $_SESSION['subject'] ?? '',
    'uname'   => $_SESSION['uname']  ?? '',
    'email'   => $_SESSION['email']  ?? '',
    'dob'     => $_SESSION['dob']    ?? '',
    'gender'  => $_SESSION['gender'] ?? ''
];

$id = (int)$_SESSION['user_id'];
if ($stmt = $conn->prepare("SELECT fname, subject, uname, email, dob, gender FROM teacher WHERE id = ? LIMIT 1")) {
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $teacher = $row;
        // Ensure session is in sync
        $_SESSION['fname']   = $row['fname'];
        $_SESSION['subject'] = $row['subject'];
        $_SESSION['uname']   = $row['uname'];
        $_SESSION['email']   = $row['email'];
        $_SESSION['dob']     = $row['dob'];
        $_SESSION['gender']  = $row['gender'];
    }
    $stmt->close();
}

// ---------- Layout header ----------
include_once __DIR__ . '/../header.php';
?>

<div class="row g-4">
  <!-- LEFT: Teacher profile + nav -->
  <aside class="col-lg-3">
    <?php
    $session_img = $_SESSION['img'] ?? '';
    $avatar_url  = '../img/anon.png';
    if (!empty($session_img)) {
        $avatar_url = $session_img;
    }
    ?>
    <style>
      .teacher-profile-card .avatar {
        width: 96px;
        height: 96px;
        border-radius: 50%;
        object-fit: cover;
        border: 3px solid rgba(0,0,0,0.05);
        box-shadow: 0 4px 10px rgba(0,0,0,0.06);
        background: #fff;
      }
      .teacher-profile-card .card-body { text-align:center; padding-top:18px; }
      .teacher-profile-card .nav-link { padding-left:0; padding-right:0; }
      .teacher-profile-card .username { margin-top:8px; margin-bottom:2px; font-size:20px; font-weight:600; }
      .teacher-profile-card .user-uname { color:#6c757d; font-size:13px; margin-bottom:10px; display:block; }
    </style>

    <div class="card mb-3 teacher-profile-card">
      <div class="card-body">
        <img src="<?php echo htmlspecialchars($avatar_url, ENT_QUOTES, 'UTF-8'); ?>"
             alt="avatar"
             class="avatar mb-2">
        <div class="username">
          <?php echo htmlspecialchars($_SESSION['fname'] ?? 'Teacher', ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <div class="user-uname">
          <?php echo htmlspecialchars($_SESSION['uname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
        </div>
        <hr />
        <nav class="nav flex-column text-start">
          <a class="nav-link py-1" href="dash.php">Dashboard</a>
          <a class="nav-link py-1" href="exams.php">Exams</a>
          <a class="nav-link py-1" href="results.php">Results</a>
          <a class="nav-link py-1" href="records.php">Records</a>
          <a class="nav-link py-1" href="messages.php">Messages</a>
          <a class="nav-link py-1 active" href="settings.php">Settings</a>
          <a class="nav-link py-1" href="help.php">Help</a>
          <a class="nav-link py-1 text-danger" href="../logout.php">Log out</a>
        </nav>
      </div>
    </div>
  </aside>

  <!-- RIGHT: Settings / profile form -->
  <section class="col-lg-9">
    <h3 class="mb-3">Settings</h3>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">My Profile</h5>

        <?php if ($err_msg): ?>
          <div class="alert alert-danger py-2">
            <?php echo htmlspecialchars($err_msg, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <?php if ($success): ?>
          <div class="alert alert-success py-2">
            <?php echo htmlspecialchars($success, ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <form action="settings.php" method="post">
          <div class="mb-2">
            <label for="fname" class="form-label mb-1">Full Name</label>
            <input
              class="form-control"
              type="text"
              id="fname"
              name="fname"
              placeholder="Enter your full name"
              value="<?php echo htmlspecialchars($teacher['fname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              minlength="4"
              maxlength="30"
              required
            >
          </div>

          <div class="mb-2">
            <label for="subject" class="form-label mb-1">Subject</label>
            <input
              class="form-control"
              type="text"
              id="subject"
              name="subject"
              value="<?php echo htmlspecialchars($teacher['subject'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              disabled
            >
          </div>

          <div class="mb-2">
            <label for="uname" class="form-label mb-1">Username</label>
            <input
              class="form-control"
              type="text"
              id="uname"
              name="uname"
              value="<?php echo htmlspecialchars($teacher['uname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              disabled
            >
          </div>

          <div class="mb-2">
            <label for="email" class="form-label mb-1">Email</label>
            <input
              class="form-control"
              type="email"
              id="email"
              name="email"
              placeholder="Enter your email"
              value="<?php echo htmlspecialchars($teacher['email'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              minlength="5"
              maxlength="50"
              required
            >
          </div>

          <div class="mb-2">
            <label for="dob" class="form-label mb-1">Date of Birth</label>
            <input
              class="form-control"
              type="date"
              id="dob"
              name="dob"
              value="<?php echo htmlspecialchars($teacher['dob'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              required
            >
          </div>

          <div class="mb-3">
            <label for="gender" class="form-label mb-1">Gender</label>
            <input
              class="form-control"
              type="text"
              id="gender"
              name="gender"
              placeholder="Enter your gender (M or F)"
              value="<?php echo htmlspecialchars($teacher['gender'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
              minlength="1"
              maxlength="10"
              required
            >
          </div>

          <button type="submit" name="submit" class="btn btn-primary">
            Update Profile
          </button>
        </form>
      </div>
    </div>
  </section>
</div>

<?php
include_once __DIR__ . '/../footer.php';
?>
