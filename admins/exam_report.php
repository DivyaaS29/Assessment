<?php
session_start();
// TODO: replace this with real teacher auth check
if (!isset($_SESSION['uname']) || !isset($_SESSION['is_teacher'])) {
    header('Location: ../login_teacher.php'); exit;
}
require_once __DIR__ . '/../config.php';
$exid = $_GET['exid'] ?? '';
if (!$exid) { die('Missing exid'); }

// get exam info
$stmt = $conn->prepare("SELECT exname, nq FROM exm_list WHERE exid = ? LIMIT 1");
$stmt->bind_param("s",$exid); $stmt->execute(); $res = $stmt->get_result(); $exam = $res->fetch_assoc(); $stmt->close();

// attempts summary
$sql = "SELECT a.uname, a.nq, a.cnq, a.ptg, a.subtime, a.id as attempt_id
        FROM atmpt_list a WHERE a.exid = ? ORDER BY a.ptg DESC, a.subtime ASC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s",$exid);
$stmt->execute();
$res = $stmt->get_result();
$attempts = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

include_once __DIR__ . '/../header.php';
?>
<div class="container my-4">
  <h3>Exam Report: <?php echo htmlspecialchars($exam['exname'] ?? $exid); ?></h3>
  <p>Questions: <?php echo (int)$exam['nq']; ?></p>

  <div class="card mb-3">
    <div class="card-body">
      <h5>Attempts</h5>
      <div class="table-responsive">
        <table class="table table-striped">
          <thead><tr><th>Student</th><th>Correct</th><th>Percentage</th><th>Submitted</th><th>Details</th></tr></thead>
          <tbody>
            <?php foreach ($attempts as $a): ?>
            <tr>
              <td><?php echo htmlspecialchars($a['uname']); ?></td>
              <td class="text-center"><?php echo (int)$a['cnq']; ?></td>
              <td class="text-center"><?php echo htmlspecialchars($a['ptg']); ?>%</td>
              <td><?php echo htmlspecialchars($a['subtime']); ?></td>
              <td>
                <a href="exam_report_detail.php?attempt_id=<?php echo (int)$a['attempt_id']; ?>" class="btn btn-sm btn-outline-primary">View Qs</a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>
