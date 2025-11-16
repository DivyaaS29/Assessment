<?php
// students/upload_cv.php - handle CV/resume upload

session_start();
if (!isset($_SESSION['uname'])) {
    header("Location: ../login_student.php");
    exit;
}

require_once __DIR__ . '/../config.php';

function redirect_with($query)
{
    header("Location: settings.php?$query");
    exit;
}

if (!isset($_FILES['cv_file']) || $_FILES['cv_file']['error'] !== UPLOAD_ERR_OK) {
    redirect_with('error=cv_upload');
}

// Validate size (max ~5MB)
$maxSize = 5 * 1024 * 1024;
if ($_FILES['cv_file']['size'] > $maxSize) {
    redirect_with('error=cv_size');
}

// Validate extension
$allowedExts = ['pdf', 'doc', 'docx'];
$ext = strtolower(pathinfo($_FILES['cv_file']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExts, true)) {
    redirect_with('error=cv_type');
}

$uname     = $_SESSION['uname'];
$safeUname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $uname);

// Paths based on XAMPP: C:/xampp/htdocs/AssessmentPage/...
$docRoot   = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');    // e.g. C:\xampp\htdocs
$baseDir   = $docRoot . '/AssessmentPage';
$uploadDir = $baseDir . '/uploads/cv/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        redirect_with('error=cv_dir');
    }
}

$filename = 'cv_' . $safeUname . '_' . time() . '.' . $ext;
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['cv_file']['tmp_name'], $destPath)) {
    redirect_with('error=cv_move');
}

// URL that browser will use (and we store in DB)
$publicPath = '/AssessmentPage/uploads/cv/' . $filename;

// Update DB
if ($stmt = $conn->prepare("UPDATE student SET cv_path = ? WHERE uname = ?")) {
    $stmt->bind_param('ss', $publicPath, $uname);
    if (!$stmt->execute()) {
        // optional: unlink($destPath);
        $stmt->close();
        redirect_with('error=cv_db');
    }
    $stmt->close();
} else {
    // optional: unlink($destPath);
    redirect_with('error=cv_dbprep');
}

redirect_with('updated=cv');
