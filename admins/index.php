<?php
// teachers/index.php

session_start();
require_once __DIR__ . '/../config.php';
include_once __DIR__ . '/../header.php';

// Check if teacher is logged in
$teacherId = $_SESSION['teacher_id'] ?? null;
$teacherName = null;

if ($teacherId) {
    if ($stmt = $conn->prepare("SELECT fname, uname, email, subject FROM teacher WHERE id = ? LIMIT 1")) {
        $stmt->bind_param("i", $teacherId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($row = $res->fetch_assoc()) {
            $teacherName = trim($row['fname']) !== '' ? $row['fname'] : $row['uname'];
            $teacherEmail = $row['email'];
            $teacherSubject = $row['subject'];
        }
        $stmt->close();
    }
}
?>

<main class="container my-4">

    <?php if ($teacherName): ?>
        <!-- If teacher is logged in -->
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div>
                <h2 class="fw-bold">Welcome, <?php echo htmlspecialchars($teacherName); ?>!</h2>
                <p class="text-muted mb-0">Subject: <?php echo htmlspecialchars($teacherSubject); ?></p>
                <p class="text-muted mb-0">Email: <?php echo htmlspecialchars($teacherEmail); ?></p>
            </div>
            <div>
                <a href="../logout.php" class="btn btn-outline-secondary">Logout</a>
            </div>
        </div>

        <div class="row g-4">

            <div class="col-md-4">
                <div class="card p-3 h-100">
                    <h5>Create New Exam</h5>
                    <p class="small text-muted">Add questions and build exams for students.</p>
                    <a href="addexam.php" class="btn btn-primary btn-sm">Create Exam</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card p-3 h-100">
                    <h5>Manage Questions</h5>
                    <p class="small text-muted">Create, edit or delete questions for exams.</p>
                    <a href="addqp.php" class="btn btn-primary btn-sm">Manage Questions</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card p-3 h-100">
                    <h5>Manage Students</h5>
                    <p class="small text-muted">Add new students or update existing accounts.</p>
                    <a href="adduser.php" class="btn btn-primary btn-sm">Manage Students</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card p-3 h-100">
                    <h5>Exams Overview</h5>
                    <p class="small text-muted">View all exams created and their analytics.</p>
                    <a href="exams.php" class="btn btn-primary btn-sm">View Exams</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card p-3 h-100">
                    <h5>Results</h5>
                    <p class="small text-muted">Review student submissions and scores.</p>
                    <a href="results.php" class="btn btn-primary btn-sm">View Results</a>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card p-3 h-100">
                    <h5>Messages</h5>
                    <p class="small text-muted">Communicate with students and admins.</p>
                    <a href="messages.php" class="btn btn-secondary btn-sm">Open Messages</a>
                </div>
            </div>

        </div>

    <?php else: ?>
        <!-- Teacher NOT logged in -->
        <div class="text-center py-5">
            <h2 class="fw-bold">Teacher Portal</h2>
            <p class="text-muted mb-4">Log in to create exams, manage questions, view results and communicate with students.</p>

            <a href="../login_teacher.php" class="btn btn-primary btn-lg">Teacher Login</a>

            <div class="row mt-5 justify-content-center">
                <div class="col-md-8">
                    <div class="card p-3">
                        <h5>How to use the Teacher Portal</h5>
                        <ol>
                            <li>Log in with the credentials provided by the administrator.</li>
                            <li>Create exams and questions from the Exam Management section.</li>
                            <li>Assign exams and monitor submissions.</li>
                            <li>Review student scores under Results.</li>
                        </ol>
                        <p class="small text-muted mb-0">Need help? Contact your system administrator.</p>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>

</main>

<?php include_once __DIR__ . '/../footer.php'; ?>
