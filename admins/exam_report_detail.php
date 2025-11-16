<?php
session_start();
if (!isset($_SESSION['uname']) || !isset($_SESSION['is_teacher'])) { header('Location: ../login_teacher.php'); exit; }
require_once __DIR__ . '/../config.php';
$attempt_id = (int)($_GET['attempt_id'] ?? 0);
if (!$attempt_id) die('Missing attempt id');
// get attempt and answers
$stmt = $conn->prepare("SELECT a.uname, a.exid, a.subtime FROM atmpt_list a WHERE a.id = ? LIMIT 1");
$stmt->bind_param("i",$attempt_id); $stmt->execute(); $res = $stmt->get_result(); $att = $res->fetch_assoc(); $stmt->close();
$exid = $att['exid'];
$answers = [];
if ($stmt = $conn->prepare("SELECT qid, selected, correct, is_correct FROM atmpt_answers WHERE attempt_id = ? ORDER BY qid ASC")) {
    $stmt->bind_param("i",$attempt_id); $stmt->execute(); $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) $answers[] = $r;
    $stmt->close();
}
include_once __DIR__ . '/../header.php';
?>
<div class="container my-4">
  <h4>Details for <?php echo htmlspecialchars($att['uname']); ?> — <?php echo htmlspecialchars($exid); ?></h4>
  <div class="card">
    <div class="card-body">
      <ul class="list-group">
        <?php foreach ($answers as $ans): ?>
          <li class="list-group-item">
            <strong>QID <?php echo (int)$ans['qid']; ?></strong> —
            Selected: <?php echo htmlspecialchars($ans['selected']); ?> |
            Correct: <?php echo htmlspecialchars($ans['correct']); ?> |
            <?php if ($ans['is_correct']): ?>
              <span class="text-success">Correct</span>
            <?php else: ?>
              <span class="text-danger">Wrong</span>
            <?php endif; ?>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
<?php include_once __DIR__ . '/../footer.php'; ?>
