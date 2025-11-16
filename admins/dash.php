<?php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login_admin.php");
    exit;
}

require_once __DIR__ . '/../config.php';
error_reporting(0);
date_default_timezone_set('Asia/Kolkata');

// ---------- Quick stats for admin ----------
$totalStudents = 0;
if ($stmt = $conn->prepare("SELECT COUNT(1) FROM student")) {
    $stmt->execute();
    $stmt->bind_result($totalStudents);
    $stmt->fetch();
    $stmt->close();
}

$totalExams = 0;
if ($stmt = $conn->prepare("SELECT COUNT(1) FROM exm_list")) {
    $stmt->execute();
    $stmt->bind_result($totalExams);
    $stmt->fetch();
    $stmt->close();
}

$totalAttempts = 0;
if ($stmt = $conn->prepare("SELECT COUNT(1) FROM atmpt_list")) {
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

// ------------- Upcoming exams -------------
$upcomingExams = [];
if ($stmt = $conn->prepare("SELECT exid, exname, subject, extime, subt, nq FROM exm_list ORDER BY extime ASC LIMIT 6")) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $upcomingExams[] = $r;
    $stmt->close();
}

// ------------- Timer (next exam) -------------
$countdownTargetJs = '';
$now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));

if ($stmt = $conn->prepare("SELECT extime FROM exm_list WHERE extime > ? ORDER BY extime ASC LIMIT 1")) {
    $nowStr = $now->format('Y-m-d H:i:s');
    $stmt->bind_param('s', $nowStr);
    $stmt->execute();
    $stmt->bind_result($nextExtime);
    if ($stmt->fetch() && !empty($nextExtime)) {
        try {
            $dt = new DateTime($nextExtime, new DateTimeZone('Asia/Kolkata'));
            $countdownTargetJs = $dt->format('Y-m-d\TH:i:sP'); // ISO format
        } catch (Exception $e) {
            $countdownTargetJs = '';
        }
    }
    $stmt->close();
}

// include global header (same as student dashboard)
include_once __DIR__ . '/../header.php';
?>

<div class="row g-4">
  <!-- LEFT: Sidebar profile + nav + stats + timer -->
  <aside class="col-lg-3">
    <?php
    // Avatar resolution (reuse student logic)
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
          <?php echo htmlspecialchars($_SESSION['fname'] ?? 'Admin', ENT_QUOTES, 'UTF-8'); ?>
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
          <a class="nav-link py-1" href="settings.php">Settings</a>
          <a class="nav-link py-1" href="help.php">Help</a>
          <a class="nav-link py-1 text-danger" href="../logout.php">Log out</a>
        </nav>
      </div>
    </div>

    <div class="card mb-3">
      <div class="card-body">
        <h6 class="card-title">Quick Stats</h6>
        <ul class="list-unstyled mb-0">
          <li class="mb-2"><strong><?php echo (int)$totalStudents; ?></strong> <span class="text-muted">Students</span></li>
          <li class="mb-2"><strong><?php echo (int)$totalExams; ?></strong> <span class="text-muted">Exams</span></li>
          <li class="mb-2"><strong><?php echo (int)$totalAttempts; ?></strong> <span class="text-muted">Attempts</span></li>
          <li class="mb-2"><strong><?php echo (int)$totalMessages; ?></strong> <span class="text-muted">Announcements</span></li>
        </ul>
      </div>
    </div>

    <!-- Exam Timer -->
    <div class="card">
      <div class="card-body">
        <h6 class="card-title mb-2">Exam Timer</h6>
        <div id="time" class="fw-semibold text-danger">
          Timer: --h --m --s
        </div>
        <div class="text-muted small mt-1">
          <?php if ($countdownTargetJs): ?>
            Counting down to the next scheduled exam.
          <?php else: ?>
            No upcoming exams scheduled.
          <?php endif; ?>
        </div>
      </div>
    </div>
  </aside>

  <!-- RIGHT: Main admin content -->
  <section class="col-lg-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">Admin Dashboard</h3>
      <div>
        <a href="exams.php" class="btn btn-outline-primary btn-sm">Manage Exams</a>
        <a href="records.php" class="btn btn-outline-secondary btn-sm">View Students</a>
      </div>
    </div>

    <!-- Top stats cards (similar style to student dash) -->
    <div class="row g-3 mb-4">
      <div class="col-md-3">
        <div class="card h-100">
          <div class="card-body">
            <h6 class="card-subtitle mb-1">Students</h6>
            <div class="h4 mb-0"><?php echo (int)$totalStudents; ?></div>
            <small class="text-muted">Total registered</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card h-100">
          <div class="card-body">
            <h6 class="card-subtitle mb-1">Exams</h6>
            <div class="h4 mb-0"><?php echo (int)$totalExams; ?></div>
            <small class="text-muted">Total created</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card h-100">
          <div class="card-body">
            <h6 class="card-subtitle mb-1">Attempts</h6>
            <div class="h4 mb-0"><?php echo (int)$totalAttempts; ?></div>
            <small class="text-muted">All exam attempts</small>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card h-100">
          <div class="card-body">
            <h6 class="card-subtitle mb-1">Announcements</h6>
            <div class="h4 mb-0"><?php echo (int)$totalMessages; ?></div>
            <small class="text-muted">Messages sent</small>
          </div>
        </div>
      </div>
    </div>

    <!-- Upcoming exams list -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title mb-3">Upcoming / Active Exams</h5>
        <?php if (count($upcomingExams) === 0): ?>
          <div class="text-muted">No exams scheduled yet.</div>
        <?php else: ?>
          <div class="list-group">
            <?php foreach ($upcomingExams as $exam):
              $extime = htmlspecialchars($exam['extime'] ?? '-', ENT_QUOTES, 'UTF-8');
              $subt   = htmlspecialchars($exam['subt'] ?? '-', ENT_QUOTES, 'UTF-8');
            ?>
              <div class="list-group-item d-flex justify-content-between align-items-center">
                <div>
                  <div class="fw-semibold">
                    <?php echo htmlspecialchars($exam['exname'], ENT_QUOTES, 'UTF-8'); ?>
                  </div>
                  <div class="text-muted small">
                    <?php echo htmlspecialchars($exam['subject'] ?? '', ENT_QUOTES, 'UTF-8'); ?>
                  </div>
                  <div class="text-muted small">
                    Start: <?php echo $extime; ?> • Submit by: <?php echo $subt; ?>
                  </div>
                </div>
                <a href="exams.php" class="btn btn-sm btn-primary">Edit</a>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Admin instructions -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title">Admin Instructions</h5>
        <ul>
          <li>Create and manage exams from the <strong>Exams</strong> section.</li>
          <li>Monitor students and their performance from <strong>Records</strong> and <strong>Results</strong>.</li>
          <li>Use <strong>Messages</strong> to send announcements to students.</li>
          <li>Update account details in <strong>Settings</strong>.</li>
        </ul>
      </div>
    </div>

  </section>
</div>

<?php if ($countdownTargetJs): ?>
<script>
(function(){
  var countDownDate = new Date("<?php echo $countdownTargetJs; ?>").getTime();

  function pad(n){ return n < 10 ? '0' + n : n; }

  var x = setInterval(function(){
    var now = new Date().getTime();
    var distance = countDownDate - now;

    if (distance <= 0) {
      clearInterval(x);
      var timerElem = document.getElementById("time");
      if (timerElem) timerElem.textContent = "Timer: 00h 00m 00s";
      return;
    }

    var hours   = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
    var seconds = Math.floor((distance % (1000 * 60)) / 1000);

    var display = "Timer: " + pad(hours) + "h " + pad(minutes) + "m " + pad(seconds) + "s";
    var timerElem = document.getElementById("time");
    if (timerElem) timerElem.textContent = display;
  }, 1000);
})();
</script>
<?php endif; ?>

<?php
include_once __DIR__ . '/../footer.php';
?>
