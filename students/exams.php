<?php
// students/exams.php
session_start();
if (!isset($_SESSION["uname"])) {
    header("Location: ../login_student.php");
    exit;
}

require_once __DIR__ . '/../config.php';
error_reporting(0);
date_default_timezone_set('Asia/Kolkata');

// ---------------------------------------------------
// Quick stats (same as dash.php)
// ---------------------------------------------------
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

// ---------------------------------------------------
// Pagination + filters
// ---------------------------------------------------
$per_page = isset($_GET['per_page']) ? max(5, (int)$_GET['per_page']) : 10;
$page     = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset   = ($page - 1) * $per_page;

$filter_subject = isset($_GET['subject']) ? trim($_GET['subject']) : '';
$search_q       = isset($_GET['q']) ? trim($_GET['q']) : '';

// Build WHERE parts
$where  = [];
$params = [];
$types  = '';

if ($filter_subject !== '') {
    $where[]  = "subject = ?";
    $params[] = $filter_subject;
    $types   .= 's';
}
if ($search_q !== '') {
    $where[]  = "exname LIKE ?";
    $params[] = '%' . $search_q . '%';
    $types   .= 's';
}
$where_sql = count($where) ? 'WHERE ' . implode(' AND ', $where) : '';

// Count total rows
$count_sql = "SELECT COUNT(1) FROM exm_list $where_sql";
$count_stmt = $conn->prepare($count_sql);
if ($count_stmt === false) { die('DB error (count)'); }

if ($types !== '') {
    $bind = array_merge([$types], $params);
    $refs = [];
    foreach ($bind as $k => $v) $refs[$k] = &$bind[$k];
    call_user_func_array([$count_stmt, 'bind_param'], $refs);
}
$count_stmt->execute();
$count_stmt->bind_result($total_rows);
$count_stmt->fetch();
$count_stmt->close();

$total_rows  = (int)$total_rows;
$total_pages = max(1, (int)ceil($total_rows / $per_page));

// Fetch paged exams (include desp)
$data_sql = "SELECT exid, exname, subject, desp, nq, extime, subt 
             FROM exm_list 
             $where_sql 
             ORDER BY extime ASC 
             LIMIT ? OFFSET ?";
$data_stmt = $conn->prepare($data_sql);
if ($data_stmt === false) { die('DB error (data)'); }

$dataParams   = $params;
$dataTypes    = $types . 'ii';
$dataParams[] = $per_page;
$dataParams[] = $offset;

$bind = array_merge([$dataTypes], $dataParams);
$refs = [];
foreach ($bind as $k => $v) $refs[$k] = &$bind[$k];
call_user_func_array([$data_stmt, 'bind_param'], $refs);

$data_stmt->execute();
$res   = $data_stmt->get_result();
$exams = $res->fetch_all(MYSQLI_ASSOC);
$data_stmt->close();

// Distinct subjects
$subjects = [];
$sres = $conn->query("SELECT DISTINCT subject FROM exm_list ORDER BY subject ASC");
if ($sres) {
    while ($r = $sres->fetch_assoc()) {
        $subjects[] = $r['subject'];
    }
    $sres->close();
}

// include global header (same as dash.php)
include_once __DIR__ . '/../header.php';
?>

<main class="container my-4">
  <div class="row g-4">
    <!-- LEFT: Sidebar (profile + nav) -->
    <aside class="col-lg-3">
      <?php
      // avatar resolution (same as dash.php)
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
        <h3 class="mb-0">Exams</h3>
        <div>
          <a href="dash.php" class="btn btn-outline-secondary btn-sm">Back to Dashboard</a>
          <a href="results.php" class="btn btn-outline-primary btn-sm">My Results</a>
        </div>
      </div>

      <!-- Filter & list card -->
      <div class="card mb-4">
        <div class="card-body">
          <!-- Filter form -->
          <form class="row g-2 mb-3" method="get" action="exams.php">
            <div class="col-md-4">
              <input type="text"
                     name="q"
                     class="form-control form-control-sm"
                     placeholder="Search exam name..."
                     value="<?php echo htmlspecialchars($search_q, ENT_QUOTES, 'UTF-8'); ?>">
            </div>
            <div class="col-md-3">
              <select name="subject" class="form-select form-select-sm">
                <option value="">All Subjects</option>
                <?php foreach ($subjects as $sub): ?>
                  <option value="<?php echo htmlspecialchars($sub, ENT_QUOTES, 'UTF-8'); ?>"
                          <?php echo ($filter_subject === $sub) ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($sub, ENT_QUOTES, 'UTF-8'); ?>
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <select name="per_page" class="form-select form-select-sm">
                <option value="5"  <?php echo $per_page==5  ? 'selected' : ''; ?>>5 per page</option>
                <option value="10" <?php echo $per_page==10 ? 'selected' : ''; ?>>10 per page</option>
                <option value="20" <?php echo $per_page==20 ? 'selected' : ''; ?>>20 per page</option>
              </select>
            </div>
            <div class="col-md-3 d-flex justify-content-end">
              <button class="btn btn-sm btn-primary me-2">Filter</button>
              <a href="exams.php" class="btn btn-sm btn-outline-secondary">Reset</a>
            </div>
          </form>

          <!-- Exams table -->
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead>
                <tr>
                  <th>Sl.no</th>
                  <th>Exam Name</th>
                  <th>Description</th>
                  <th>Subject</th>
                  <th>No. of questions</th>
                  <th>Exam time</th>
                  <th>Submission time</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                $i = ($page - 1) * $per_page + 1;
                if (!empty($exams)) {
                    foreach ($exams as $row) {
                        $exid = $row['exid'];

                        // check attempt status
                        $stmt2  = $conn->prepare("SELECT status FROM atmpt_list WHERE uname = ? AND exid = ? LIMIT 1");
                        $status = null;
                        if ($stmt2) {
                            $stmt2->bind_param("ss", $_SESSION['uname'], $exid);
                            $stmt2->execute();
                            $r2 = $stmt2->get_result()->fetch_assoc();
                            $status = $r2['status'] ?? null;
                            $stmt2->close();
                        }

                        $now = new DateTime('now', new DateTimeZone('Asia/Kolkata'));
                        $extime_dt = null; $subt_dt = null;
                        try { $extime_dt = $row['extime'] ? new DateTime($row['extime'], new DateTimeZone('Asia/Kolkata')) : null; } catch (Exception $e) {}
                        try { $subt_dt   = $row['subt']   ? new DateTime($row['subt'],   new DateTimeZone('Asia/Kolkata')) : null; } catch (Exception $e) {}

                        $status_flag = 'available';
                        if ($extime_dt && $now < $extime_dt) {
                            $status_flag = 'not_started';
                        } elseif ($subt_dt && $now > $subt_dt) {
                            $status_flag = 'expired';
                        }

                        // skip if already attempted
                        if ($status === '1' || $status === 1) {
                            continue;
                        }

                        echo '<tr>';
                        echo '<td>' . $i . '</td>';
                        echo '<td>' . htmlspecialchars($row['exname'], ENT_QUOTES, 'UTF-8') . '</td>';
                        echo '<td>' . htmlspecialchars($row['desp'] ?? '-', ENT_QUOTES, 'UTF-8') . '</td>';
                        echo '<td>' . htmlspecialchars($row['subject'], ENT_QUOTES, 'UTF-8') . '</td>';
                        echo '<td>' . (int)$row['nq'] . '</td>';
                        echo '<td>' . htmlspecialchars($row['extime'], ENT_QUOTES, 'UTF-8') . '</td>';
                        echo '<td>' . htmlspecialchars($row['subt'], ENT_QUOTES, 'UTF-8') . '</td>';

                        echo '<td>';
                        if ($status_flag === 'available') {
                            echo '<form action="examportal.php" method="post" class="d-inline">';
                            echo '<input type="hidden" name="exid" value="' . htmlspecialchars($exid, ENT_QUOTES, 'UTF-8') . '">';
                            echo '<input type="hidden" name="nq" value="' . (int)$row['nq'] . '">';
                            echo '<button type="submit" name="edit_btn" class="btn btn-sm btn-primary">Start</button>';
                            echo '</form>';
                        } elseif ($status_flag === 'not_started') {
                            echo '<button class="btn btn-sm btn-secondary" disabled>Not started</button>';
                        } else {
                            echo '<button class="btn btn-sm btn-outline-secondary" disabled>Expired</button>';
                        }
                        echo '</td>';

                        echo '</tr>';
                        $i++;
                    }
                } else {
                    echo '<tr><td colspan="8" class="text-muted text-center">No exams found.</td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <nav aria-label="Exams pagination" class="mt-3">
            <ul class="pagination pagination-sm mb-0">
              <?php
              $base_q = [];
              if ($filter_subject !== '') $base_q['subject'] = $filter_subject;
              if ($search_q !== '')       $base_q['q']       = $search_q;
              $base_q['per_page'] = $per_page;

              for ($p = 1; $p <= $total_pages; $p++):
                  $base_q['page'] = $p;
                  $qs = http_build_query($base_q);
              ?>
                <li class="page-item <?php echo $p === $page ? 'active' : ''; ?>">
                  <a class="page-link" href="?<?php echo $qs; ?>"><?php echo $p; ?></a>
                </li>
              <?php endfor; ?>
            </ul>
          </nav>

        </div>
      </div>

    </section>
  </div>
</main>

<?php
// global footer
include_once __DIR__ . '/../footer.php';
?>
