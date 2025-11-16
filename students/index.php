<?php
// students/index.php
// Put this file in: AssessmentPage/students/index.php

session_start();

// include DB config (adjust path if your config.php is elsewhere)
require_once __DIR__ . '/../config.php';

// Optional: if header needs DB later, include header after config
include_once __DIR__ . '/../header.php';

// Try to get logged-in student info if available
$studentName = null;
$studentId = $_SESSION['student_id'] ?? null;

if ($studentId) {
    // Use prepared statement to fetch student's name (safe)
    if ($stmt = $conn->prepare("SELECT fname, uname, email FROM student WHERE id = ? LIMIT 1")) {
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            // Prefer full name if present, otherwise username
            $studentName = trim($row['fname']) !== '' ? $row['fname'] : $row['uname'];
            $studentEmail = $row['email'] ?? '';
        }
        $stmt->close();
    }
}
?>

<main class="container my-4">
  <?php if ($studentName): ?>
    <!-- Logged-in student view -->
    <div class="d-flex align-items-center justify-content-between mb-4">
      <div>
        <h2 class="fw-bold">Welcome back, <?php echo htmlspecialchars($studentName); ?>!</h2>
        <p class="text-muted mb-0">Access your upcoming tests, results and messages below.</p>
      </div>
      <div>
        <a href="../logout.php" class="btn btn-outline-secondary">Logout</a>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-md-4">
        <div class="card p-3 h-100">
          <h5 class="mb-2">My Exams</h5>
          <p class="small text-muted">View the exams assigned to you and start when ready.</p>
          <a href="exams.php" class="btn btn-primary btn-sm">View Exams</a>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3 h-100">
          <h5 class="mb-2">My Results</h5>
          <p class="small text-muted">Check scores and detailed reports for completed tests.</p>
          <a href="results.php" class="btn btn-primary btn-sm">View Results</a>
        </div>
      </div>

      <div class="col-md-4">
        <div class="card p-3 h-100">
          <h5 class="mb-2">Profile</h5>
          <p class="small text-muted">Update your profile or change password.</p>
          <a href="settings.php" class="btn btn-secondary btn-sm">Profile Settings</a>
        </div>
      </div>
    </div>

    <!-- Optionally show recent messages or quick actions -->
    <div class="mt-4">
      <h6>Quick actions</h6>
      <div class="d-flex gap-2">
        <a href="examportal.php" class="btn btn-outline-primary">Enter Exam Portal</a>
        <a href="messages.php" class="btn btn-outline-secondary">Messages</a>
      </div>
    </div>

  <?php else: ?>
    <!-- Guest / not logged in view -->
    <div class="text-center py-5">
      <h2 class="fw-bold">Student Portal</h2>
      <p class="text-muted mb-4">Log in to access assigned exams, view results and communicate with instructors.</p>

      <div class="d-flex justify-content-center gap-3">
        <a href="../login_student.php" class="btn btn-primary btn-lg">Student Login</a>
        <a href="../help.php" class="btn btn-outline-secondary btn-lg">Help</a>
      </div>

      <div class="row mt-5 justify-content-center">
        <div class="col-md-8">
          <div class="card p-3">
            <h5>How to take an exam</h5>
            <ol>
              <li>Log in with the credentials provided by your college or the administrator.</li>
              <li>Select the scheduled exam from <strong>My Exams</strong>.</li>
              <li>Follow rules and submit before the timer ends. Results will be available in <strong>My Results</strong>.</li>
            </ol>
            <p class="small text-muted mb-0">If you don't have an account, contact your coordinator or click Help.</p>
          </div>
        </div>
      </div>
    </div>
  <?php endif; ?>
</main>

<?php include_once __DIR__ . '/../footer.php'; ?>
