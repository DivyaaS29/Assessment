<?php
// students/submit_exam.php

session_start();
if (!isset($_SESSION['uname'])) {
    header("Location: ../login_student.php");
    exit;
}

require_once __DIR__ . '/../config.php';
date_default_timezone_set('Asia/Kolkata');

// For debugging while you develop:
error_reporting(E_ALL);
ini_set('display_errors', 1);

$uname = $_SESSION['uname'] ?? '';

// ---------------------------
// 1. Basic POST validation
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Invalid request method.";
    exit;
}

$exid          = $_POST['exid']          ?? '';
$answers_json  = $_POST['answers_json']  ?? '';
$duration_over = isset($_POST['duration_over']) ? (int)$_POST['duration_over'] : 0;

if ($exid === '' || $answers_json === '') {
    echo "Missing exam data (EXID or answers).";
    exit;
}

// ---------------------------
// 2. Decode answers JSON
//    Expecting array of objects:
//    [{ qid: <qno>, index: <1..N>, selected: <0..3|null>, status: "answered|review|..." }, ...]
// ---------------------------
$answers = json_decode($answers_json, true);
if (!is_array($answers)) {
    echo "Invalid answers payload.";
    exit;
}

$totalQuestions = count($answers);

// ---------------------------
// 3. Fetch correct answers from DB
//    qstn_list(exid, qno, qns, op1, op2, op3, op4, correct)
// ---------------------------
$correctMap = []; // qno => correctOption(1..4)

if ($stmt = $conn->prepare("
    SELECT qno, correct
    FROM qstn_list
    WHERE exid = ?
")) {
    $stmt->bind_param("s", $exid);
    $stmt->execute();
    $res = $stmt->get_result();
    while ($row = $res->fetch_assoc()) {
        $qno = (int)$row['qno'];
        $correctMap[$qno] = (int)$row['correct']; // 1..4
    }
    $stmt->close();
} else {
    echo "DB error while preparing question query: " . $conn->error;
    exit;
}

// ---------------------------
// 4. Evaluate answers
// ---------------------------
$answeredCount = 0;
$correctCount  = 0;

foreach ($answers as $item) {
    // Safety: ignore weird rows
    if (!is_array($item)) continue;

    $qno      = isset($item['qid']) ? (int)$item['qid'] : null;        // we used 'qid' for qno in examportal
    $selected = $item['selected'];                                     // 0..3 or null

    if ($qno === null) continue;

    // Count answered
    if ($selected !== null && $selected !== '') {
        $answeredCount++;
    }

    // Check correctness
    if (array_key_exists($qno, $correctMap) && $selected !== null && $selected !== '') {
        $correctOption = $correctMap[$qno];   // 1..4 from DB
        $chosenOption  = ((int)$selected) + 1; // convert 0..3 index -> 1..4
        if ($chosenOption === $correctOption) {
            $correctCount++;
        }
    }
}

// ---------------------------
// 5. Optionally store attempt in DB
//    We'll *try* to mark attempt as done in atmpt_list.
//    If table structure is different, this may fail silently.
// ---------------------------
$attemptSaved = false;

if ($stmt = $conn->prepare("
    INSERT INTO atmpt_list (uname, exid, status)
    VALUES (?, ?, 1)
")) {
    $stmt->bind_param("ss", $uname, $exid);
    if ($stmt->execute()) {
        $attemptSaved = true;
    }
    $stmt->close();
} else {
    // If this prepare fails because columns differ, we just skip saving attempt.
    // echo "Warning: could not save attempt: " . $conn->error;
}

// ---------------------------
// 6. Fetch exam name for display (optional)
// ---------------------------
$exname = $exid;
if ($stmt = $conn->prepare("SELECT exname FROM exm_list WHERE exid = ? LIMIT 1")) {
    $stmt->bind_param("s", $exid);
    $stmt->execute();
    $stmt->bind_result($exnameDb);
    if ($stmt->fetch()) {
        $exname = $exnameDb;
    }
    $stmt->close();
}

// Include header (if you want same layout as other pages)
include_once __DIR__ . '/../header.php';
?>

<main class="container my-4">
  <div class="card">
    <div class="card-body">
      <h3 class="card-title mb-3">Exam Submitted</h3>

      <p><strong>Exam:</strong> <?php echo htmlspecialchars($exname, ENT_QUOTES, 'UTF-8'); ?></p>
      <p><strong>Exam ID:</strong> <?php echo htmlspecialchars($exid, ENT_QUOTES, 'UTF-8'); ?></p>
      <p><strong>User:</strong> <?php echo htmlspecialchars($uname, ENT_QUOTES, 'UTF-8'); ?></p>

      <hr>

      <p><strong>Total Questions:</strong> <?php echo (int)$totalQuestions; ?></p>
      <p><strong>Answered:</strong> <?php echo (int)$answeredCount; ?></p>
      <p><strong>Correct:</strong> <?php echo (int)$correctCount; ?></p>

      <p>
        <strong>Time over (auto submit):</strong>
        <?php echo $duration_over ? 'Yes' : 'No'; ?>
      </p>

      <?php if ($attemptSaved): ?>
        <p class="text-success small mb-2">
          Attempt status saved.
        </p>
      <?php else: ?>
        <p class="text-muted small mb-2">
          (Attempt status not stored, or table structure different.)
        </p>
      <?php endif; ?>

      <hr>

      <p class="mb-0">
        <a href="results.php" class="btn btn-sm btn-outline-primary">View Results</a>
        <a href="exams.php" class="btn btn-sm btn-outline-secondary ms-2">Back to Exams</a>
      </p>
    </div>
  </div>
</main>

<?php
include_once __DIR__ . '/../footer.php';
?>
