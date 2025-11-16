<?php
// login_admin.php - Admin login with same UI as student login

session_start();
error_reporting(0);
require_once __DIR__ . '/config.php';



$err_msg = '';

if (isset($_POST["signin"])) {
    $uname = trim($_POST["uname"] ?? '');
    $pword = $_POST["pword"] ?? '';

    if ($uname === '' || $pword === '') {
        $err_msg = 'Please enter username and password.';
    } else {
        // fetch admin (admin) row securely
        $stmt = $conn->prepare("SELECT id, uname, fname, email, subject, pword, pword_new FROM admins WHERE uname = ? LIMIT 1");
        if ($stmt === false) {
            $err_msg = 'Server error. Please try again later.';
        } else {
            $stmt->bind_param("s", $uname);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($row = $res->fetch_assoc()) {
                $id     = (int)$row['id'];
                $db_md5 = $row['pword'];     // old MD5
                $db_new = $row['pword_new']; // new bcrypt hash (optional)

                $login_ok = false;

                // 1) If bcrypt exists, verify against it
                if (!empty($db_new) && password_verify($pword, $db_new)) {
                    $login_ok = true;
                }
                // 2) Else fallback to MD5; if OK, migrate to bcrypt
                elseif (!empty($db_md5) && md5($pword) === $db_md5) {
                    $newhash = password_hash($pword, PASSWORD_DEFAULT);
                    $upd = $conn->prepare("UPDATE admins SET pword_new = ? WHERE id = ?");
                    if ($upd) {
                        $upd->bind_param("si", $newhash, $id);
                        $upd->execute();
                        $upd->close();
                    }
                    $login_ok = true;
                }

                if ($login_ok) {
                  // prevent session fixation
                  session_regenerate_id(true);

                  // set session variables (keep name teacher_id so existing pages still work)
                  $_SESSION['user_id'] = $id;
                  $_SESSION['fname']      = $row['fname'];
                  $_SESSION['email']      = $row['email'];
                  $_SESSION['uname']      = $row['uname'];
                  $_SESSION['subject']    = $row['subject'];

                  // default admin avatar (optional)
                  $_SESSION['img'] = "img/mp.png";
                  
                  header("Location: admins/dash.php");
                  exit();
                } else {
                    $err_msg = 'Invalid username or password. Please try again.';
                }
            } else {
                $err_msg = 'Invalid username or password. Please try again.';
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login | Safear Defence India Limited</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      font-family: "Poppins", sans-serif;
    }

    body {
      background: linear-gradient(to right, #000000, #ffffff);
      display: flex;
      flex-direction: column;
      align-items: center;
      min-height: 100vh;
    }

    /* Header */
    .header {
      width: 100%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 15px 60px;
      background-color: rgba(0, 0, 0, 0.95);
      color: white;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
      position: fixed;
      top: 0;
      left: 0;
      z-index: 100;
    }

    .logo-container {
      display: flex;
      align-items: center;
      gap: 12px;
    }

    .logo {
      width: 50px;
      height: 50px;
      border-radius: 10px;
      object-fit: cover;
    }

    .company-name {
      font-size: 18px;
      font-weight: 600;
      letter-spacing: 0.5px;
    }

    .navbar a {
      margin-left: 25px;
      color: white;
      text-decoration: none;
      font-weight: 500;
      transition: color 0.3s ease;
    }

    .navbar a:hover {
      color: #dcdcdc;
    }

    /* Login Section */
    .login-container {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-top: 140px;
      background: white;
      border-radius: 15px;
      overflow: hidden;
      width: 75%;
      max-width: 1000px;
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.3);
    }

    .login-left {
      background-color: black;
      color: white;
      flex: 1;
      padding: 60px 50px;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      text-align: center;
    }

    .login-left h2 {
      font-size: 28px;
      margin-bottom: 15px;
    }

    .login-left p {
      font-size: 15px;
      margin-bottom: 25px;
      line-height: 1.5;
      max-width: 80%;
    }

    .switch-btn {
      padding: 10px 25px;
      border: 2px solid white;
      background: none;
      color: white;
      border-radius: 25px;
      cursor: pointer;
      font-weight: 500;
      transition: 0.3s;
    }

    .switch-btn:hover {
      background-color: white;
      color: black;
    }

    .login-right {
      flex: 1;
      padding: 60px 50px;
      background-color: #f9f9f9;
    }

    .login-right h2 {
      font-size: 28px;
      font-weight: 700;
      margin-bottom: 10px;
    }

    .login-right p {
      font-size: 15px;
      color: #555;
      margin-bottom: 15px;
    }

    .login-right input {
      width: 100%;
      padding: 12px;
      margin-bottom: 15px;
      border: 1px solid #ddd;
      border-radius: 8px;
      outline: none;
      font-size: 14px;
    }

    .login-right input:focus {
      border-color: #000;
    }

    .login-right button {
      width: 100%;
      padding: 12px;
      background-color: black;
      color: white;
      border: none;
      border-radius: 8px;
      cursor: pointer;
      font-weight: 600;
      font-size: 15px;
      transition: background 0.3s ease;
    }

    .login-right button:hover {
      background-color: #333;
    }

    .extra-links {
      margin-top: 15px;
      text-align: center;
    }

    .extra-links a {
      color: black;
      font-weight: 500;
      text-decoration: none;
    }

    .extra-links a:hover {
      text-decoration: underline;
    }

    .error-msg {
      color: #b00020;
      margin-bottom: 10px;
      font-size: 14px;
    }

    @media (max-width: 900px) {
      .login-container {
        flex-direction: column;
        width: 90%;
      }

      .login-left,
      .login-right {
        padding: 40px 30px;
      }

      .header {
        flex-direction: column;
        padding: 15px 20px;
        text-align: center;
      }

      .navbar {
        margin-top: 10px;
      }

      .navbar a {
        margin: 0 10px;
      }
    }
  </style>
</head>
<body>
  <!-- Header -->
  <header class="header">
    <div class="logo-container">
      <img src="img/logo.png" alt="Company Logo" class="logo" />
      <h2 class="company-name">Safear Defence India Limited</h2>
    </div>
    <nav class="navbar">
      <a href="#">Home</a>
      <a href="#">About</a>
      <a href="#">Help</a>
    </nav>
  </header>

  <!-- Login Section -->
  <div class="login-container">
    <div class="login-left">
      <h2>Welcome Admin</h2>
      
      <button class="switch-btn" onclick="window.location.href='login_student.php'">
        Login as Student
      </button>
    </div>

    <div class="login-right">
      <h2 id="greet">Hello</h2>
      <p>Please enter your admin credentials to continue.</p>

      <?php if ($err_msg): ?>
        <div class="error-msg"><?php echo htmlspecialchars($err_msg, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="text" name="uname" placeholder="Admin Username"
               value="<?php echo isset($_POST['uname']) ? htmlspecialchars($_POST['uname'], ENT_QUOTES, 'UTF-8') : ''; ?>" required />
        <input type="password" name="pword" placeholder="Password" required />
        <button type="submit" name="signin">Sign In</button>
        <div class="extra-links">
          <a href="#">Forgot your password?</a>
        </div>
      </form>
    </div>
  </div>

  <script>
  document.addEventListener("DOMContentLoaded", ()=>{
    const hour = new Date().getHours();
    const greet = document.getElementById("greet");
    if(hour < 12) greet.textContent = "Good Morning";
    else if(hour < 18) greet.textContent = "Good Afternoon";
    else greet.textContent = "Good Evening";
  });
  </script>
</body>
</html>
