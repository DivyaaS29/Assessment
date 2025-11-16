<?php 
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login_teacher.php");
    exit;
}

date_default_timezone_set('Asia/Kolkata');
error_reporting(0);

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
          <a class="nav-link py-1" href="records.php">Records</a>
          <a class="nav-link py-1" href="messages.php">Messages</a>
          <a class="nav-link py-1" href="settings.php">Settings</a>
          <a class="nav-link py-1 active" href="help.php">Help</a>
          <a class="nav-link py-1 text-danger" href="../logout.php">Log out</a>
        </nav>
      </div>
    </div>
  </aside>

  <!-- RIGHT: Help / About content -->
  <section class="col-lg-9">
    <h3 class="mb-3">Help &amp; About</h3>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">How to use the Teacher Panel</h5>

        <div class="mb-3">
          <h6>Q1. How to log out?</h6>
          <p class="mb-2">
            Click on the <strong>Log out</strong> button at the bottom of the left navigation bar.
          </p>
        </div>

        <div class="mb-3">
          <h6>Q2. How to edit my profile details?</h6>
          <p class="mb-2">
            Click on the <strong>Settings</strong> option from the left navigation bar.
            After updating the required fields, click on <strong>Update Profile</strong>.
          </p>
        </div>

        <div class="mb-3">
          <h6>Q3. How to edit, delete or add new student records?</h6>
          <p class="mb-2">
            Go to the <strong>Records</strong> section from the left navigation bar.
            You will see a list of all students:
          </p>
          <ul>
            <li>Use the <strong>Edit</strong> button in each row to edit a student.</li>
            <li>Use the <strong>Delete</strong> button in each row to remove a student.</li>
            <li>Use the <strong>Add New Student</strong> form at the bottom of the page to create a new student record.</li>
          </ul>
        </div>

        <div class="mb-3">
          <h6>Q4. How to view the results?</h6>
          <p class="mb-2">
            Go to the <strong>Results</strong> section from the left navigation bar
            and select the exam for which you want to view results.
          </p>
        </div>

        <div class="mb-3">
          <h6>Q5. How to conduct exams?</h6>
          <p class="mb-2">
            Navigate to the <strong>Exams</strong> tab using the left navigation bar. From there:
          </p>
          <ul>
            <li>Create new exams and manage existing ones.</li>
            <li>After creating an exam, click on the <strong>Edit</strong> icon to add questions.</li>
            <li>Old exams can also be deleted as needed.</li>
          </ul>
        </div>

        <div class="mb-3">
          <h6>Important notice</h6>
          <p class="mb-0">
            Once the test questions have been added, please avoid updating the questions directly,
            as it may cause errors in the functioning of the website.
            An improved question-editing feature will be introduced in future updates.
          </p>
        </div>

        <hr class="my-4">

        

<?php
include_once __DIR__ . '/../footer.php';
?>
