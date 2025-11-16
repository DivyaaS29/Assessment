<?php 
// students/messages.php
session_start();
if (!isset($_SESSION["uname"])) {
    header("Location: ../login_student.php");
    exit;
}

require_once __DIR__ . '/../config.php';
error_reporting(0);

// Quick stats (same as dash.php / exams.php)
$totalExams = 0;
if ($stmt = $conn->prepare("SELECT COUNT(1) FROM exm_list")) {
    $stmt->execute();
    $stmt->bind_result($totalExams);
    $stmt->fetch();
    $stmt->close();
}

$uname = $_SESSION['uname'];
$totalAttempts = 0;
if ($stmt = $conn->prepare("SELECT COUNT(1) FROM atmpt_list WHERE uname = ?")) {
    $stmt->bind_param("s", $uname);
    $stmt->execute();
    $stmt->bind_result($totalAttempts);
    $stmt->fetch();
    $stmt->close();
}

$totalMessages = 0;
if ($stmt = $conn->prepare("SELECT COUNT(1) FROM message")) {
    $stmt->execute();
    $stmt->bind_result($totalMessages);
    $stmt->fetch();
    $stmt->close();
}

// Fetch all messages (announcements)
$messages = [];
$sql = "SELECT feedback, fname, date FROM message ORDER BY date DESC";
if ($res = $conn->query($sql)) {
    while ($row = $res->fetch_assoc()) {
        $messages[] = $row;
    }
    $res->close();
}

// include global header (Bootstrap, etc.)
include_once __DIR__ . '/../header.php';
?>

<main class="container my-4">
  <div class="row g-4">
    <!-- LEFT: Sidebar (profile + nav) -->
    <aside class="col-lg-3">
      <?php
      // avatar resolution (same logic as dash.php)
      $session_img = $_SESSION['img'] ?? '';
      $avatar_url  = '/AssessmentPage/img/mp.png'; // default
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

      if (!empty($_SESSION['gender'])) {
          $g = strtoupper(trim($_SESSION['gender']));
          if ($g === 'F' || $g === 'FEMALE') {
              $femalePath = $docRoot . '/AssessmentPage/img/fp.png';
              if (file_exists($femalePath)) $avatar_url = '/AssessmentPage/img/fp.png';
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
            <?php echo htmlspecialchars($_SESSION['fname'] ?? 'Student', ENT_QUOTES, 'UTF-8'); ?>
          </div>
          <div class="user-uname">
            <?php echo htmlspecialchars($_SESSION['uname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
          </div>
          <hr />
          <nav class="nav flex-column text-start">
            <a class="nav-link py-1" href="dash.php">Dashboard</a>
            <a class="nav-link py-1" href="exams.php">Exams</a>
            <a class="nav-link py-1" href="results.php">Results</a>
            <a class="nav-link py-1" href="messages.php">Messages</a>
            <a class="nav-link py-1" href="settings.php">Settings</a>
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

    <!-- RIGHT: Main content -->
    <section class="col-lg-9">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Messages / Announcements</h3>
        <a href="dash.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
      </div>

      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">All Announcements</h5>

          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th style="width:60%;">Announcement</th>
                  <th style="width:20%;">From</th>
                  <th style="width:20%;">Date and time</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($messages)): ?>
                  <?php foreach ($messages as $row): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($row['feedback'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($row['fname'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($row['date'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="3" class="text-muted text-center">No announcements available.</td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

        </div>
      </div>
    </section>
  </div>
</main>

<?php
// global footer
include_once __DIR__ . '/../footer.php';
?>
