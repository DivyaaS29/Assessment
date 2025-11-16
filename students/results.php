<?php
// students/results.php (enhanced, unified UI)

session_start();
if (!isset($_SESSION["uname"])) {
    header("Location: ../login_student.php");
    exit;
}

require_once __DIR__ . '/../config.php';
error_reporting(0);

// ---------------------------
// CSV export for this student
// ---------------------------
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $uname = $_SESSION['uname'];
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=attempts_' . preg_replace('/[^a-z0-9_-]/i','', $uname) . '.csv');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Exam ID', 'Exam Name', 'Total Q', 'Correct', 'Percentage', 'Completed On']);

    $sql = "SELECT a.exid, COALESCE(e.exname, a.exid) AS exname, a.nq, a.cnq, a.ptg, a.subtime
            FROM atmpt_list a
            LEFT JOIN exm_list e ON a.exid = e.exid
            WHERE a.uname = ?
            ORDER BY a.subtime DESC";
    if ($stmt = $conn->prepare($sql)) {
        $stmt->bind_param('s', $uname);
        $stmt->execute();
        $res = $stmt->get_result();
        while ($row = $res->fetch_assoc()) {
            fputcsv($out, [
                $row['exid'],
                $row['exname'],
                $row['nq'],
                $row['cnq'],
                $row['ptg'],
                $row['subtime']
            ]);
        }
        $stmt->close();
    }
    fclose($out);
    exit;
}

// ---------------------------
// Fetch student's attempts
// ---------------------------
$uname = $_SESSION['uname'];
$attempts = [];
$sql = "SELECT a.exid, COALESCE(e.exname, a.exid) AS exname, a.nq, a.cnq, a.ptg, a.subtime
        FROM atmpt_list a
        LEFT JOIN exm_list e ON a.exid = e.exid
        WHERE a.uname = ?
        ORDER BY a.subtime ASC"; // chronological for chart

if ($stmt = $conn->prepare($sql)) {
    $stmt->bind_param('s', $uname);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $attempts[] = $r;
    $stmt->close();
}

// ---------------------------
// Top scorers per exam
// ---------------------------
$topScorers = [];
$topSql = "
  SELECT e.exid, e.exname, t.uname, t.ptg
  FROM exm_list e
  LEFT JOIN (
    SELECT a1.exid, a1.uname, a1.ptg
    FROM atmpt_list a1
    INNER JOIN (
      SELECT exid, MAX(ptg) AS maxptg FROM atmpt_list GROUP BY exid
    ) m ON a1.exid = m.exid AND a1.ptg = m.maxptg
  ) t ON e.exid = t.exid
  ORDER BY e.extime DESC
";
if ($res = $conn->query($topSql)) {
    while ($r = $res->fetch_assoc()) $topScorers[] = $r;
}

// ---------------------------
// Quick stats for sidebar
// ---------------------------
$totalAttempts = count($attempts);

$totalExams = 0;
if ($s = $conn->prepare("SELECT COUNT(1) FROM exm_list")) {
    $s->execute();
    $s->bind_result($totalExams);
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

// include global header (same as other pages)
include_once __DIR__ . '/../header.php';
?>

<main class="container my-4">
  <div class="row g-4">
    <!-- LEFT: Sidebar (profile + nav) -->
    <aside class="col-lg-3">
      <?php
      // Avatar resolution (same logic as dash/exams/messages)
      $session_img = $_SESSION['img'] ?? '';
      $avatar_url  = '/AssessmentPage/img/mp.png'; // default male
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
            <a class="nav-link py-1 active" href="results.php">Results</a>
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
        <h3 class="mb-0">Results</h3>
        <div class="d-flex gap-2">
          <a href="results.php?export=csv" class="btn btn-outline-secondary btn-sm">Export CSV</a>
          <a href="exams.php" class="btn btn-outline-primary btn-sm">View All Exams</a>
        </div>
      </div>

      <!-- Progress chart -->
      <div class="card mb-4">
        <div class="card-body">
          <h5 class="card-title mb-2">Your progress</h5>
          <p class="small text-muted mb-3">Percentage over time (most recent attempts on the right).</p>
          <div style="height: 260px;">
            <canvas id="progressChart"></canvas>
          </div>
        </div>
      </div>

      <!-- Attempts table -->
      <div class="card mb-4">
        <div class="card-body">
          <h5 class="card-title mb-3">Your Attempts</h5>
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>Exam name</th>
                  <th class="text-center">Total questions</th>
                  <th class="text-center">Correct answers</th>
                  <th class="text-center">Percentage</th>
                  <th class="text-center">Completed on</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($attempts)): ?>
                  <tr>
                    <td colspan="5" class="text-center text-muted">
                      You have not completed any exams yet.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($attempts as $r): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($r['exname'] ?: $r['exid'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td class="text-center"><?php echo (int)$r['nq']; ?></td>
                      <td class="text-center"><?php echo (int)$r['cnq']; ?></td>
                      <td class="text-center"><?php echo htmlspecialchars($r['ptg'], ENT_QUOTES, 'UTF-8'); ?>%</td>
                      <td class="text-center"><?php echo htmlspecialchars($r['subtime'], ENT_QUOTES, 'UTF-8'); ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Top scorers per exam -->
      <div class="card mb-4">
        <div class="card-body">
          <h5 class="card-title mb-3">Top scorers (per exam)</h5>
          <div class="table-responsive">
            <table class="table table-sm table-striped align-middle">
              <thead>
                <tr>
                  <th>Exam</th>
                  <th>Top student</th>
                  <th class="text-center">Percentage</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($topScorers)): ?>
                  <tr>
                    <td colspan="3" class="text-center text-muted">
                      No exam results available yet.
                    </td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($topScorers as $t): ?>
                    <tr>
                      <td><?php echo htmlspecialchars($t['exname'] ?: $t['exid'], ENT_QUOTES, 'UTF-8'); ?></td>
                      <td><?php echo htmlspecialchars($t['uname'] ?: '-', ENT_QUOTES, 'UTF-8'); ?></td>
                      <td class="text-center">
                        <?php echo isset($t['ptg']) ? htmlspecialchars($t['ptg'], ENT_QUOTES, 'UTF-8') . '%' : '-'; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </section>
  </div>
</main>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  const attemptLabels = <?php echo json_encode(array_map(function($a){ return $a['subtime']; }, $attempts)); ?>;
  const attemptData   = <?php echo json_encode(array_map(function($a){ return (float)$a['ptg']; }, $attempts)); ?>;

  if (attemptLabels.length > 0) {
    const ctx = document.getElementById('progressChart').getContext('2d');
    new Chart(ctx, {
      type: 'line',
      data: {
        labels: attemptLabels,
        datasets: [{
          label: 'Percentage',
          data: attemptData,
          fill: false,
          tension: 0.2,
          pointRadius: 4,
          borderColor: '#0b3d91',
          backgroundColor: '#0b3d91'
        }]
      },
      options: {
        scales: {
          y: {
            beginAtZero: true,
            suggestedMax: 100
          }
        },
        plugins: {
          legend: { display: false }
        },
        responsive: true,
        maintainAspectRatio: false
      }
    });
  } else {
    document.getElementById('progressChart').style.display = 'none';
  }
</script>

<?php
include_once __DIR__ . '/../footer.php';
?>
