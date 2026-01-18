<?php
session_start();
// Reset quiz session variables
unset($_SESSION['qindex']);
unset($_SESSION['score']);
unset($_SESSION['quiz_start_time']);
unset($_SESSION['quiz_end_time']);
unset($_SESSION['timing_logged']);

// Redirect to first question page
header("Location: question.php");
exit();
?>