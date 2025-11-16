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

// -------- Fetch exams for which results can be viewed --------
$exams = [];
if ($stmt = $conn->prepare("SELECT exid, exname, desp, nq, datetime FROM exm_list ORDER BY datetime DESC")) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $exams[] = $r;
    }
    $stmt->close();
}

// Include global header (Bootstrap, layout, etc.)
include_once __DIR__ . '/../header.php';
?>

<div class="row g-4">
  <!-- LEFT: Teacher profile + nav -->
  <aside class="col-lg-3">
    <?php
    // Basic avatar resolution (simpler than admin’s but works)
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
          <a class="nav-link py-1 active" href="results.php">Results</a>
          <a class="nav-link py-1" href="records.php">Records</a>
          <a class="nav-link py-1" href="messages.php">Messages</a>
          <a class="nav-link py-1" href="settings.php">Settings</a>
          <a class="nav-link py-1" href="help.php">Help</a>
          <a class="nav-link py-1 text-danger" href="../logout.php">Log out</a>
        </nav>
      </div>
    </div>

    <!-- You can add extra teacher-side stats here if you want later -->
  </aside>

  <!-- RIGHT: Results exam selection -->
  <section class="col-lg-9">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h3 class="mb-0">Exam Results</h3>
      <span class="text-muted small">
        Select an exam to view student results.
      </span>
    </div>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Select Exam</h5>

        <?php if (count($exams) === 0): ?>
          <div class="text-muted">No exams found. Please create an exam first.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>#</th>
                  <th>Exam name</th>
                  <th>Description</th>
                  <th>Questions</th>
                  <th>Added on</th>
                  <th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $i = 1;
                foreach ($exams as $row):
                  $datetime = $row['datetime'] ?? '';
                ?>
                <tr>
                  <td><?php echo $i; ?></td>
                  <td><?php echo htmlspecialchars($row['exname'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($row['desp'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo (int)$row['nq']; ?></td>
                  <td><?php echo htmlspecialchars($datetime, ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="text-center">
                    <form action="viewresults.php" method="post" class="d-inline">
                      <input type="hidden" name="exid" value="<?php echo htmlspecialchars($row['exid'], ENT_QUOTES, 'UTF-8'); ?>">
                      <button class="btn btn-outline-primary btn-sm" type="submit" name="vw_rslts">
                        View Result
                      </button>
                    </form>
                  </td>
                </tr>
                <?php
                  $i++;
                endforeach;
                ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

<?php
include_once __DIR__ . '/../footer.php';
?>
