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

// -------- Fetch students --------
$students = [];
if ($stmt = $conn->prepare("SELECT id, fname, uname, email, gender, dob FROM student ORDER BY id ASC")) {
    $stmt->execute();
    $res = $stmt->get_result();
    while ($r = $res->fetch_assoc()) {
        $students[] = $r;
    }
    $stmt->close();
}

// Include global header (Bootstrap/layout, same as other new pages)
include_once __DIR__ . '/../header.php';
?>

<div class="row g-4">
  <!-- LEFT: Teacher profile + nav -->
  <aside class="col-lg-3">
    <?php
    $session_img = $_SESSION['img'] ?? '';
    // simple fallback avatar
    $avatar_url  = '../img/anon.png';
    if (!empty($session_img)) {
        $avatar_url = $session_img;
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
          <a class="nav-link py-1" href="results.php">Results</a>
          <a class="nav-link py-1 active" href="records.php">Records</a>
          <a class="nav-link py-1" href="messages.php">Messages</a>
          <a class="nav-link py-1" href="settings.php">Settings</a>
          <a class="nav-link py-1" href="help.php">Help</a>
          <a class="nav-link py-1 text-danger" href="../logout.php">Log out</a>
        </nav>
      </div>
    </div>
  </aside>

  <!-- RIGHT: Student records + Add new student -->
  <section class="col-lg-9">
    <!-- Simple heading -->
    <h3 class="mb-3">Student Records</h3>

    <!-- All students card -->
    <div class="card mb-4">
      <div class="card-body">
        <h5 class="card-title mb-3">All Students</h5>

        <?php if (count($students) === 0): ?>
          <div class="text-muted">No students found.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Full name</th>
                  <th>Username</th>
                  <th>Email</th>
                  <th>Gender</th>
                  <th>DOB</th>
                  <th class="text-center">Edit</th>
                  <th class="text-center">Delete</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($students as $row): ?>
                <tr>
                  <td><?php echo (int)$row['id']; ?></td>
                  <td><?php echo htmlspecialchars($row['fname'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($row['uname'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($row['email'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($row['gender'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td><?php echo htmlspecialchars($row['dob'], ENT_QUOTES, 'UTF-8'); ?></td>
                  <td class="text-center">
                    <form action="updateuserform.php" method="post" class="d-inline">
                      <input type="hidden" name="edit_id" value="<?php echo (int)$row['id']; ?>">
                      <button type="submit" name="edit_btn" class="btn btn-outline-primary btn-sm">
                        Edit
                      </button>
                    </form>
                  </td>
                  <td class="text-center">
                    <form action="del.php" method="post" class="d-inline" onsubmit="return confirm('Delete this student?');">
                      <input type="hidden" name="delete_id" value="<?php echo (int)$row['id']; ?>">
                      <button type="submit" name="delete_btn" class="btn btn-outline-danger btn-sm">
                        Delete
                      </button>
                    </form>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Add new student card (full width, no image) -->
    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Add New Student</h5>

        <form action="adduser.php" method="post">
          <div class="mb-2">
            <label for="fname" class="form-label mb-1">Full Name</label>
            <input
              class="form-control"
              type="text"
              id="fname"
              name="fname"
              placeholder="Enter full name"
              minlength="4"
              maxlength="30"
              required
            >
          </div>

          <div class="mb-2">
            <label for="uname" class="form-label mb-1">Username</label>
            <input
              class="form-control"
              type="text"
              id="uname"
              name="uname"
              placeholder="Enter username"
              minlength="5"
              maxlength="15"
              required
            >
          </div>

          <div class="mb-2">
            <label for="pword" class="form-label mb-1">Password</label>
            <input
              class="form-control"
              type="password"
              id="pword"
              name="pword"
              placeholder="pass****"
              minlength="8"
              maxlength="16"
              required
            >
          </div>

          <div class="mb-2">
            <label for="cpword" class="form-label mb-1">Confirm password</label>
            <input
              class="form-control"
              type="password"
              id="cpword"
              name="cpword"
              placeholder="pass****"
              minlength="8"
              maxlength="16"
              required
            >
          </div>

          <div class="mb-2">
            <label for="email" class="form-label mb-1">Email</label>
            <input
              class="form-control"
              type="email"
              id="email"
              name="email"
              placeholder="Enter email"
              minlength="5"
              maxlength="50"
              required
            >
          </div>

          <div class="mb-2">
            <label for="dob" class="form-label mb-1">Date of Birth</label>
            <input
              class="form-control"
              type="date"
              id="dob"
              name="dob"
              required
            >
          </div>

          <div class="mb-3">
            <label for="gender" class="form-label mb-1">Gender</label>
            <input
              class="form-control"
              type="text"
              id="gender"
              name="gender"
              placeholder="Enter gender (M or F)"
              minlength="1"
              maxlength="1"
              required
            >
          </div>

          <button type="submit" name="adduser" class="btn btn-primary">
            Add Student
          </button>
        </form>
      </div>
    </div>
  </section>
</div>

<?php
include_once __DIR__ . '/../footer.php';
?>
