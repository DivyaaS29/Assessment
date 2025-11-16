<?php
// header.php - global header for AssessmentPage

$page_css = $page_css ?? null;

// Make sure session is active so we can detect login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Detect which area we are in
$requestUri = $_SERVER['REQUEST_URI'] ?? '';

$isStudentArea = (strpos($requestUri, '/AssessmentPage/students/') !== false);
$isAdminArea   = (strpos($requestUri, '/AssessmentPage/admin/') !== false);

// Detect login status
$isStudentLoggedIn = !empty($_SESSION['student_id']);
$isAdminLoggedIn   = !empty($_SESSION['admin_id']);   // <-- NEW for admin login
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Safear Assessments</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"/>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="/AssessmentPage/assets/css/styles.css" rel="stylesheet">

  <style>
    .navbar-dark .navbar-nav .nav-link {
        color: #ffffff !important;
    }
    .navbar-dark .navbar-nav .nav-link:hover {
        color: #cccccc !important;
    }
    .navbar .container {
      max-width: 1180px;
    }
  </style>

  <?php if (!empty($page_css)): ?>
    <link href="<?php echo htmlspecialchars($page_css, ENT_QUOTES, 'UTF-8'); ?>" rel="stylesheet">
  <?php endif; ?>
</head>

<body class="bg-light" style="font-family:Inter,system-ui,-apple-system,'Segoe UI',Roboto,'Helvetica Neue',Arial;">

<nav class="navbar navbar-expand-lg navbar-dark" style="background-color:#000000;">
  <div class="container">
    <a class="navbar-brand d-flex align-items-center" href="/AssessmentPage/">
      <img src="/AssessmentPage/img/logo.png" alt="Logo" style="height:40px; display:block;">
    </a>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navMenu">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navMenu">
      <ul class="navbar-nav ms-auto">
        
        <li class="nav-item">
          <a class="nav-link" href="/AssessmentPage/">Home</a>
        </li>

        <!-- STUDENT MENU -->
        <?php if (!$isStudentLoggedIn && !$isStudentArea): ?>
          <li class="nav-item">
            <a class="nav-link" href="/AssessmentPage/login_student.php">Students</a>
          </li>
        <?php endif; ?>

        <!-- ADMIN MENU -->
        <?php if (!$isAdminLoggedIn && !$isAdminArea): ?>
          <li class="nav-item">
            <a class="nav-link" href="/AssessmentPage/login_admin.php">Admin</a>
          </li>
        <?php endif; ?>

        <li class="nav-item">
          <a class="nav-link" href="/AssessmentPage/help.php">Help</a>
        </li>

      </ul>
    </div>
  </div>
</nav>

<main class="container my-4">
