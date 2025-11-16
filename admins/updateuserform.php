<?php
session_start();

// Only logged-in teachers can access
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login_teacher.php");
    exit;
}

require_once __DIR__ . '/../config.php';
error_reporting(0);
date_default_timezone_set('Asia/Kolkata');

$err_msg = '';
$success = '';
$student = null;
$id = null;

// ---------- Save updated student ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_user'])) {
    $id     = (int)($_POST['id'] ?? 0);
    $fname  = trim($_POST['fname'] ?? '');
    $uname  = trim($_POST['uname'] ?? '');
    $email  = trim($_POST['email'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $dob    = trim($_POST['dob'] ?? '');

    if ($id <= 0 || $fname === '' || $uname === '' || $email === '' || $gender === '' || $dob === '') {
        $err_msg = 'Please fill in all fields.';
    } else {
        if ($stmt = $conn->prepare("UPDATE student SET fname = ?, uname = ?, email = ?, gender = ?, dob = ? WHERE id = ?")) {
            $stmt->bind_param('sssssi', $fname, $uname, $email, $gender, $dob, $id);
            if ($stmt->execute()) {
                $stmt->close();
                header("Location: records.php");
                exit;
            } else {
                $err_msg = 'Failed to update student. Please try again.';
                $stmt->close();
            }
        } else {
            $err_msg = 'Server error. Please try again.';
        }
    }
}

// ---------- Load student to edit ----------
if ($student === null) {
    if (isset($_POST['edit_id'])) {
        $id = (int)$_POST['edit_id'];
    } elseif (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
    }

    if ($id > 0) {
        if ($stmt = $conn->prepare("SELECT id, fname, uname, email, gender, dob FROM student WHERE id = ? LIMIT 1")) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $res = $stmt->get_result();
            $student = $res->fetch_assoc();
            $stmt->close();
        }
    }
}

if (!$student) {
    $err_msg = $err_msg ?: 'Student not found.';
}

// Include header
include_once __DIR__ . '/../header.php';
?>

<div class="row g-4">
  <!-- LEFT: Teacher profile + nav -->
  <aside class="col-lg-3">
    <?php
    $session_img = $_SESSION['img'] ?? '';
    $avatar_url  = '/AssessmentPage/img/mp.png';
    $docRoot     = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');

    if (!empty($session_img)) {
        $basename  = basename($session_img);
        $candidate = $docRoot . '/AssessmentPage/img/' . $basename;
        if (file_exists($candidate)) {
            $avatar_url = '/AssessmentPage/img/' . $basename;
        } else {
            if (strpos($session_img, '/AssessmentPage/') === 0) {
                $check = $docRoot . $session_img;
                if (file_exists($check)) $avatar_url = $session_img;
            }
        }
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
          <a class="nav-link py-1 active" href="records.php">Records</a>
          <a class="nav-link py-1" href="messages.php">Messages</a>
          <a class="nav-link py-1" href="settings.php">Settings</a>
          <a class="nav-link py-1" href="help.php">Help</a>
          <a class="nav-link py-1 text-danger" href="../logout.php">Log out</a>
        </nav>
      </div>
    </div>
  </aside>

  <!-- RIGHT: Edit student form -->
  <section class="col-lg-9">
    <h3 class="mb-3">Edit Student</h3>

    <div class="card">
      <div class="card-body">
        <?php if ($err_msg): ?>
          <div class="alert alert-danger py-2"><?php echo htmlspecialchars($err_msg, ENT_QUOTES, 'UTF-8'); ?></div>
        <?php endif; ?>

        <?php if ($student): ?>
        <form action="updateuserform.php" method="post">
          <input type="hidden" name="id" value="<?php echo (int)$student['id']; ?>">

          <div class="mb-2">
            <label class="form-label mb-1">Student ID</label>
            <input type="text" class="form-control" value="<?php echo (int)$student['id']; ?>" disabled>
          </div>

          <div class="mb-2">
            <label for="fname" class="form-label mb-1">Full Name</label>
            <input
              class="form-control"
              type="text"
              id="fname"
              name="fname"
              value="<?php echo htmlspecialchars($student['fname'], ENT_QUOTES, 'UTF-8'); ?>"
              minlength="4"
              maxlength="30"
              required
            >
          </div>

          <div class="mb-2">
            <label for="uname" class="form-label mb-1">Username</label>
            <input
              class="form-control"
              type="text"
              id="uname"
              name="uname"
              value="<?php echo htmlspecialchars($student['uname'], ENT_QUOTES, 'UTF-8'); ?>"
              minlength="5"
              maxlength="15"
              required
            >
          </div>

          <div class="mb-2">
            <label for="email" class="form-label mb-1">Email</label>
            <input
              class="form-control"
              type="email"
              id="email"
              name="email"
              value="<?php echo htmlspecialchars($student['email'], ENT_QUOTES, 'UTF-8'); ?>"
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
              value="<?php echo htmlspecialchars($student['dob'], ENT_QUOTES, 'UTF-8'); ?>"
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
              value="<?php echo htmlspecialchars($student['gender'], ENT_QUOTES, 'UTF-8'); ?>"
              minlength="1"
              maxlength="10"
              required
            >
          </div>

          <button type="submit" name="save_user" class="btn btn-primary">
            Save Changes
          </button>
          <a href="records.php" class="btn btn-secondary ms-1">
            Cancel
          </a>
        </form>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

<?php
include_once __DIR__ . '/../footer.php';
?>
