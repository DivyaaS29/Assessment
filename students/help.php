<?php
session_start();
if (!isset($_SESSION["uname"])) {
    header("Location: ../login_student.php");
    exit;
}
require_once __DIR__ . '/../config.php';
error_reporting(0);

// Quick stats
$totalExams = 0;
if ($s = $conn->prepare("SELECT COUNT(1) FROM exm_list")) {
    $s->execute();
    $s->bind_result($totalExams);
    $s->fetch();
    $s->close();
}

$totalAttempts = 0;
$uname = $_SESSION['uname'];
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

include_once __DIR__ . '/../header.php';
?>

<main class="container my-4">
  <div class="row g-4">
    <!-- Sidebar same as others -->
    <aside class="col-lg-3">
      <?php
      // avatar snippet (same fixed one)
      $session_img = $_SESSION['img'] ?? '';
      $avatar_url  = '/AssessmentPage/img/mp.png';
      $docRoot     = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');
      if (!empty($session_img)) {
          $candidatePath = $session_img;
          if ($session_img[0] === '/') {
              $fsPath = $docRoot . $session_img;
          } else {
              $fsPath = $docRoot . '/AssessmentPage/img/' . basename($session_img);
              $candidatePath = '/AssessmentPage/img/' . basename($session_img);
          }
          if (file_exists($fsPath)) $avatar_url = $candidatePath;
      }
      if ($avatar_url === '/AssessmentPage/img/mp.png' && !empty($_SESSION['gender'])) {
          $g = strtoupper(trim($_SESSION['gender']));
          if ($g === 'F' || $g === 'FEMALE') {
              $femalePath = $docRoot . '/AssessmentPage/img/fp.png';
              if (file_exists($femalePath)) $avatar_url = '/AssessmentPage/img/fp.png';
          }
      }
      ?>
      <style>
        .sa-profile-card .avatar {
          width: 96px; height: 96px; border-radius:50%;
          object-fit:cover; border:3px solid rgba(0,0,0,0.05);
          box-shadow:0 4px 10px rgba(0,0,0,0.06); background:#fff;
        }
        .sa-profile-card .card-body { text-align:center; padding-top:18px; }
        .sa-profile-card .nav-link { padding-left:0; padding-right:0; }
        .sa-profile-card .username { margin-top:8px;margin-bottom:2px;font-size:20px;font-weight:600; }
        .sa-profile-card .user-uname { color:#6c757d;font-size:13px;margin-bottom:10px;display:block; }
      </style>

      <div class="card mb-3 sa-profile-card">
        <div class="card-body">
          <img src="<?php echo htmlspecialchars($avatar_url, ENT_QUOTES, 'UTF-8'); ?>" alt="avatar" class="avatar mb-2">
          <div class="username"><?php echo htmlspecialchars($_SESSION['fname'] ?? 'Student', ENT_QUOTES, 'UTF-8'); ?></div>
          <div class="user-uname"><?php echo htmlspecialchars($_SESSION['uname'] ?? '', ENT_QUOTES, 'UTF-8'); ?></div>
          <hr />
          <nav class="nav flex-column text-start">
            <a class="nav-link py-1" href="dash.php">Dashboard</a>
            <a class="nav-link py-1" href="exams.php">Exams</a>
            <a class="nav-link py-1" href="results.php">Results</a>
            <a class="nav-link py-1" href="messages.php">Messages</a>
            <a class="nav-link py-1" href="settings.php">Settings</a>
            <a class="nav-link py-1 active" href="help.php">Help</a>
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

    <!-- Main help content -->
    <section class="col-lg-9">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Help &amp; About</h3>
      </div>

      <div class="card mb-4">
        <div class="card-body">
          <h5 class="card-title"><b>How to use</b></h5>
          <hr>
          <h6>Q1. How to logout?</h6>
          <p>Click on the <b>Log out</b> link in the left sidebar.</p>

          <h6>Q2. How to edit my profile details?</h6>
          <p>Go to <b>Settings</b> in the left sidebar, update your details, then click <b>Update Profile</b>.</p>

          <h6>Q3. How to view my results?</h6>
          <p>Click on <b>Results</b> in the left sidebar to see your exam attempts and scores.</p>

          <h6>Q4. How to attempt exams?</h6>
          <p>Open the <b>Exams</b> page, then click <b>Start</b> on an available exam.</p>

          <h6>Q5. How to view announcements?</h6>
          <p>Go to the <b>Messages</b> page to see all announcements.</p>
        </div>
      </div>

      <div class="card">
        <div class="card-body text-center text-muted">
          © 2025 by Safear Defense Private Limited - Bengaluru
        </div>
      </div>
    </section>
  </div>
</main>

<?php
include_once __DIR__ . '/../footer.php';
?>
