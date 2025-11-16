<?php 
session_start();
error_reporting(0);

// include DB config (path is correct because this file is in the root)
require_once __DIR__ . '/config.php';



$err_msg = '';

if (isset($_POST["signin"])) {
    $uname_raw = $_POST["uname"] ?? '';
    $pword_raw = $_POST["pword"] ?? '';

    $uname = trim($uname_raw);

    if ($uname === '' || $pword_raw === '') {
        $err_msg = 'Please enter username and password.';
    } else {
        // original logic: student passwords are stored as MD5 in pword column
        $pword_md5 = md5($pword_raw);

        // Try prepared statement first (safe)
        $stmt = $conn->prepare("SELECT * FROM student WHERE uname = ? AND pword = ? LIMIT 1");

        if ($stmt) {
            $stmt->bind_param("ss", $uname, $pword_md5);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($row = $res->fetch_assoc()) {
                // Set sessions used by the student area
                $_SESSION["user_id"] = $row['id'];
                $_SESSION["fname"]   = $row['fname'];
                $_SESSION["email"]   = $row['email'];
                $_SESSION["dob"]     = $row['dob'];
                $_SESSION["gender"]  = $row['gender'];
                $_SESSION["uname"]   = $row['uname'];

                // Avatar: use DB avatar if available, else gender-based default
                if (!empty($row['avatar'])) {
                    $_SESSION['img'] = $row['avatar']; // e.g. /AssessmentPage/img/...
                } else {
                    $_SESSION['img'] = ($row['gender'] === 'F')
                        ? "img/fp.png"
                        : "img/mp.png";
                }

                header("Location: students/dash.php");
                exit();
            } else {
                $err_msg = 'Invalid username or password. Please try again.';
            }

            $stmt->close();
        } else {
            // Fallback to old mysqli_query if prepare() fails for some reason
            $uname_esc = mysqli_real_escape_string($conn, $uname);
            $pword_esc = mysqli_real_escape_string($conn, $pword_md5);

            $check_user = mysqli_query(
                $conn,
                "SELECT * FROM student WHERE uname='$uname_esc' AND pword='$pword_esc' LIMIT 1"
            );

            if ($check_user && mysqli_num_rows($check_user) > 0) {
                $row = mysqli_fetch_assoc($check_user);

                $_SESSION["user_id"] = $row['id'];
                $_SESSION["fname"]   = $row['fname'];
                $_SESSION["email"]   = $row['email'];
                $_SESSION["dob"]     = $row['dob'];
                $_SESSION["gender"]  = $row['gender'];
                $_SESSION["uname"]   = $row['uname'];

                if (!empty($row['avatar'])) {
                    $_SESSION['img'] = $row['avatar'];
                } else {
                    $_SESSION['img'] = ($row['gender'] === 'F')
                        ? "img/fp.png"
                        : "img/mp.png";
                }

                header("Location: students/dash.php");
                exit();
            } else {
                $err_msg = 'Invalid username or password. Please try again.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Student Login | Safear Defence India Limited</title>
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

    .error-msg {
      color: #b00020;
      font-size: 14px;
      margin-bottom: 10px;
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
      <h2>Welcome Back</h2>
      <p>Empowering Students through Knowledge and Assessments.</p>
      <button class="switch-btn" onclick="window.location.href='login_admin.php'">
        Login as Admin
      </button>
    </div>

    <div class="login-right">
      <h2 id="greet">Hello</h2>
      <p>Please enter your credentials to continue.</p>

      <?php if ($err_msg): ?>
        <div class="error-msg">
          <?php echo htmlspecialchars($err_msg, ENT_QUOTES, 'UTF-8'); ?>
        </div>
      <?php endif; ?>

      <form method="POST">
        <input type="text" name="uname" placeholder="Username"
               value="<?php echo isset($_POST['uname']) ? htmlspecialchars($_POST['uname'], ENT_QUOTES, 'UTF-8') : ''; ?>"
               required />
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
