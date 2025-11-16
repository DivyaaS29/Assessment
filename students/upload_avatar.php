<?php
// students/upload_avatar.php - handle profile photo upload

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

if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    redirect_with('error=avatar_upload');
}

// Validate size (max ~2MB)
$maxSize = 2 * 1024 * 1024;
if ($_FILES['avatar']['size'] > $maxSize) {
    redirect_with('error=avatar_size');
}

// Validate extension
$allowedExts = ['jpg', 'jpeg', 'png'];
$ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExts, true)) {
    redirect_with('error=avatar_type');
}

// Validate MIME type
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime  = $finfo->file($_FILES['avatar']['tmp_name']);
$allowedMime = ['image/jpeg', 'image/png'];
if (!in_array($mime, $allowedMime, true)) {
    redirect_with('error=avatar_type');
}

$uname     = $_SESSION['uname'];
$safeUname = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $uname);

// Paths based on XAMPP: C:/xampp/htdocs/AssessmentPage/...
$docRoot   = rtrim($_SERVER['DOCUMENT_ROOT'], '/\\');    // e.g. C:\xampp\htdocs
$baseDir   = $docRoot . '/AssessmentPage';
$uploadDir = $baseDir . '/uploads/avatars/';

if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        redirect_with('error=avatar_dir');
    }
}

$filename = 'avatar_' . $safeUname . '_' . time() . '.' . $ext;
$destPath = $uploadDir . $filename;

if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $destPath)) {
    redirect_with('error=avatar_move');
}

// URL that browser will use (and we store in DB)
$publicPath = '/AssessmentPage/uploads/avatars/' . $filename;

// Update DB
if ($stmt = $conn->prepare("UPDATE student SET avatar = ? WHERE uname = ?")) {
    $stmt->bind_param('ss', $publicPath, $uname);
    if (!$stmt->execute()) {
        // optional: unlink($destPath);
        $stmt->close();
        redirect_with('error=avatar_db');
    }
    $stmt->close();
} else {
    // optional: unlink($destPath);
    redirect_with('error=avatar_dbprep');
}

// Update session (for pages still using $_SESSION['img'] or $_SESSION['avatar'])
$_SESSION['avatar'] = $publicPath;
$_SESSION['img']    = $publicPath;

redirect_with('updated=avatar');
