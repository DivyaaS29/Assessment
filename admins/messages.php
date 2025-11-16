<?php 
date_default_timezone_set('Asia/Kolkata');
session_start();

if (!isset($_SESSION["user_id"])) {
    header("Location: ../login_teacher.php");
    exit;
}

error_reporting(0);

// If you ever need DB data on this page, you can include config:
// require_once __DIR__ . '/../config.php';

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
          <a class="nav-link py-1 active" href="messages.php">Messages</a>
          <a class="nav-link py-1" href="settings.php">Settings</a>
          <a class="nav-link py-1" href="help.php">Help</a>
          <a class="nav-link py-1 text-danger" href="../logout.php">Log out</a>
        </nav>
      </div>
    </div>
  </aside>

  <!-- RIGHT: Announcements form -->
  <section class="col-lg-9">
    <h3 class="mb-3">Announcements</h3>

    <div class="card">
      <div class="card-body">
        <h5 class="card-title mb-3">Send a New Announcement</h5>
        <form action="addmsg.php" method="post">
          <input type="hidden" name="fname"
                 value="<?php echo htmlspecialchars($_SESSION['fname'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

          <div class="mb-2">
            <label for="feedback" class="form-label mb-1"><b>Announcement text</b></label>
            <textarea
              class="form-control"
              id="feedback"
              name="feedback"
              rows="4"
              minlength="4"
              maxlength="100"
              placeholder="Type your announcement here..."
              required
            ></textarea>
          </div>

          <button type="submit" name="addmsg" class="btn btn-primary">
            <i class='bx bx-paper-plane'></i> Send
          </button>
        </form>
      </div>
    </div>
  </section>
</div>

<?php
include_once __DIR__ . '/../footer.php';
?>
