<?php
session_start();
?>

<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">

  <!-- Font Awesome for Eye Icon -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- JS -->
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
  <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.16.0/umd/popper.min.js"></script>
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

  <style>
    .content {
      background: url('quiztime.jpg') center / cover no-repeat;
    }
    .password-toggle {
      position: absolute;
      top: 10px;
      right: 15px;
      cursor: pointer;
      color: #ccc;
    }
    .form-group.position-relative {
      position: relative;
    }
  </style>
</head>

<body class="content" style="color:white">
<div class="container m-auto d-block" align="center">
  <div class="col-sm-6 data" style="margin-top: 200px">
    <h2>Login Form</h2>
    <form method="post">
      <div class="form-group">
        <input type="text" class="form-control" name="fname" id="name" placeholder="Enter name" required>
      </div>

      <!-- Password Field with Eye Icon -->
      <div class="form-group position-relative">
        <input type="password" class="form-control" id="pwd" placeholder="Enter password" name="pass" required>
        <span class="password-toggle" onclick="togglePassword()">
          <i class="fa-solid fa-eye" id="toggleIcon"></i>
        </span>
      </div>

      <button type="submit" name="sub" class="btn btn-primary">Submit</button>
    </form>
  </div>
</div>

<!-- PHP Login Logic -->
<div align="center" style="background:grey; margin-top: 10px;">
<?php
include("connection.php");
if(isset($_POST['sub'])){
  $user = $_POST['fname'];
  $pass = $_POST['pass'];

  $sql = "SELECT * FROM `user` WHERE `name` ='$user' and `password` = '$pass'";
  $data = mysqli_query($connection, $sql);
  $result = mysqli_num_rows($data);

  if($result == 1){
    $_SESSION['user'] = $user;
    $_SESSION['pass'] = $pass;

    while($row = mysqli_fetch_assoc($data)){
      echo '<a href="question.php" style="color: white; text-decoration: none;">Welcome ' . htmlspecialchars($user) . ', click here</a>';
    }
  } else {
    echo "Invalid user.";
  }
}
?>
</div>

<!-- Show/Hide Password Script -->
<script>
function togglePassword() {
  var passwordInput = document.getElementById("pwd");
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