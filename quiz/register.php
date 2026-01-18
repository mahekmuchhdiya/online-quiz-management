<!DOCTYPE html>
<html>
<head>
  <title>Codes</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  
  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
  
  <!-- Font Awesome for Eye Icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- jQuery and Bootstrap JS -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

  <style>
    .content {
      background: url('quiz.jpg') center / cover;
    }
    .password-toggle {
      position: absolute;
      top: 10px;
      right: 15px;
      cursor: pointer;
      color: #aaa;
    }
    .form-group.position-relative {
      position: relative;
    }
  </style>
</head>

<body class="content" style="color:black">

<div class="container m-auto d-block" align="center">
  <div class="col-sm-6 data" style="margin-top: 200px">
    <h2>Registration Form</h2>

    <form method="post">
      <div class="form-group">
        <input type="text" class="form-control" name="fname" placeholder="Enter name" required>
      </div>

      <!-- Password Field with Eye Icon -->
      <div class="form-group position-relative">
        <input type="password" class="form-control" id="password" name="pass" placeholder="Enter password" required>
        <span class="password-toggle" onclick="togglePassword()">
          <i class="fa-solid fa-eye" id="toggleIcon"></i>
        </span>
      </div>

      <button type="submit" name="sub" class="btn btn-primary">Submit</button>
    </form>
  </div>

<?php 
include("connection.php");
session_start();

if(isset($_POST['sub'])){
    $name = $_POST['fname'];
    $pass = $_POST['pass'];

    // Optional: Hash the password (secure)
    // $pass = password_hash($pass, PASSWORD_DEFAULT);

    $sql = "INSERT INTO `user` (name, password) VALUES ('$name', '$pass')";
    if(mysqli_query($connection, $sql)) {
        $user_id = mysqli_insert_id($connection);
        $_SESSION['user_id'] = $user_id;

        echo '<p style="background:white; margin-top:10px">' . htmlspecialchars($name) . ', your record is successfully registered.</p>';
        header('Refresh:1; URL=login.php');
        exit();
    } else {
        echo "<p style='color: red;'>Error: Registration failed.</p>";
    }
}
?>
</div>

<!-- JavaScript to Toggle Password Visibility -->
<script>
function togglePassword() {
  var passwordInput = document.getElementById("password");
  var toggleIcon = document.getElementById("toggleIcon");

  if (passwordInput.type === "password") {
    passwordInput.type = "text";
    toggleIcon.classList.remove("fa-eye");
    toggleIcon.classList.add("fa-eye-slash");
  } else {
    passwordInput.type = "password";
    toggleIcon.classList.remove("fa-eye-slash");
    toggleIcon.classList.add("fa-eye");
  }
}
</script>

</body>
</html>