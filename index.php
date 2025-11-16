<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Safear Assessments</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <style>
    body {
      margin: 0;
      font-family: "Poppins", sans-serif;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background: linear-gradient(to right, #000, #fff);
    }
    .box {
      background: #fff;
      padding: 40px 30px;
      border-radius: 14px;
      box-shadow: 0 6px 20px rgba(0,0,0,0.2);
      text-align: center;
      width: 90%;
      max-width: 420px;
    }
    h1 {
      margin-bottom: 10px;
      font-size: 24px;
    }
    button {
      width: 100%;
      padding: 12px;
      margin: 8px 0;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-size: 16px;
      font-weight: 600;
    }
    .student-btn {
      background: #000;
      color: #fff;
    }
    .admin-btn {
      background: #e0e0e0;
    }
  </style>
</head>
<body>

  <div class="box">
    <h1>Safear Assessments</h1>
    <p>Select a login option:</p>

    <button class="student-btn" onclick="window.location.href='login_student.php'">
      Login as Student
    </button>

    <button class="admin-btn" onclick="window.location.href='login_admin.php'">
      Login as Admin
    </button>
  </div>

</body>
</html>
