<?php
session_start();
error_reporting(E_ALL); 
ini_set('display_errors', 1);

// ==========================================================
// 1. ડેટાબેઝ કનેક્શન સેટઅપ
// ==========================================================
// જો તમારા DB નું યુઝરનેમ કે પાસવર્ડ અલગ હોય તો અહીં બદલો.
$connection = new mysqli('localhost', 'root', '', 'quiz');
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// ==========================================================
// 2. ડેટા ચેક અને પ્રારંભિક સેટઅપ
// ==========================================================
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit();
}

// ક્વિઝ ડેટા ચેક
if (!isset($_SESSION['score']) || !isset($_SESSION['qindex'])) {
    // આ મેસેજ આવવો ન જોઈએ જો question.php માં ob_start() અને unset() યોગ્ય હોય.
    echo "Quiz data missing. Please take the quiz first.";
    exit();
}

$score = $_SESSION['score'];
$total = $_SESSION['qindex'];
$db_error = ""; 

// ==========================================================
// 3. ટાઇમિંગ મેળવવો અને ડેટાબેઝમાં સ્કોર સેવ કરવો
// ==========================================================

$time_taken = 0;
$minutes = 0;
$seconds = 0;
$timing_id = 0; 

// DB માંથી Start અને End time મેળવો 
$stmt_time = $connection->prepare("SELECT id, start_time, end_time FROM timing WHERE user_id = ? AND end_time IS NOT NULL ORDER BY id DESC LIMIT 1");
$stmt_time->bind_param("i", $user_id);
$stmt_time->execute();
$time_res = $stmt_time->get_result();
$time_row = $time_res->fetch_assoc();
$stmt_time->close();

if ($time_row && $time_row['start_time'] && $time_row['end_time']) {
    $timing_id = $time_row['id'];
    
    $start_timestamp = strtotime($time_row['start_time']);
    $end_timestamp = strtotime($time_row['end_time']);
    
    // ક્વિઝમાં લીધેલો કુલ સમય સેકન્ડ્સમાં
    $time_taken = $end_timestamp - $start_timestamp; 
    
    $minutes = floor($time_taken / 60);
    $seconds = $time_taken % 60;
} else {
    // જો DB માં timing data ન મળે
    $db_error = "<div class='alert alert-warning text-center'>Warning: Could not fetch accurate timing data from database.</div>";
    
    // જો સેશન માં start time હોય તો અંદાજિત સમય ગણો
    if (isset($_SESSION['quiz_start_time'])) {
        $start_timestamp = $_SESSION['quiz_start_time'];
        $end_timestamp = time(); 
        $time_taken = $end_timestamp - $start_timestamp;
        $minutes = floor($time_taken / 60);
        $seconds = $time_taken % 60;
    }
}


// --- 💾 સ્કોર સેવ કરવાનો લોજિક ---
// scores ટેબલમાં ડેટા સેવ કરો
if ($user_id > 0 && $timing_id > 0) {
    
    // ડુપ્લિકેટ એન્ટ્રી ટાળવા માટે ચેક
    $stmt_check = $connection->prepare("SELECT id FROM scores WHERE user_id = ? AND timing_id = ?");
    $stmt_check->bind_param("ii", $user_id, $timing_id);
    $stmt_check->execute();
    $existing_score = $stmt_check->get_result()->fetch_assoc();
    $stmt_check->close();
    
    // જો સ્કોર સેવ ન થયો હોય તો જ નવો સ્કોર દાખલ કરો
    if (!$existing_score) {
        // scores ટેબલમાં ડેટા દાખલ કરવા માટે SQL ક્વેરી
        $sql = "INSERT INTO scores (user_id, timing_id, score, total_questions, time_taken) 
                VALUES (?, ?, ?, ?, ?)";

        try {
            $stmt_save = $connection->prepare($sql);
            $stmt_save->bind_param("iiiii", $user_id, $timing_id, $score, $total, $time_taken);
            
            if (!$stmt_save->execute()) {
                $db_error = "<div class='alert alert-danger text-center'>Error saving score: " . $stmt_save->error . "</div>";
            }
            $stmt_save->close();
        } catch (Exception $e) {
            $db_error = "<div class='alert alert-danger text-center'>Database Error: " . $e->getMessage() . "</div>";
        }
    }
}
// --- 💾 સ્કોર સેવ કરવાનો લોજિક સમાપ્ત ---

$connection->close();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Quiz Result</title>
    <link
      rel="stylesheet"
      href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css"
    />
    <style>
      body {
        background: #607d8b;
        color: white;
      }
      .blinking {
        animation: blinkingText 1.2s infinite;
        font-weight: bold;
        text-shadow: 1px 1px 2px #000;
      }
      @keyframes blinkingText {
        0% { color: #ffeb3b; }
        25% { color: #ff5722; }
        50% { color: #4caf50; }
        75% { color: #03a9f4; }
        100% { color: #e91e63; }
      }
      .result-card {
        background-color: #333841;
      }
    </style>
</head>
<body>
  <div class="container mt-5">
    <?= $db_error; ?> 
    
    <h2 class="text-center">Test Your Knowledge📝</h2>
    <div class="card result-card mt-4">
      <h3 class="card-header text-center">Result</h3>
      <div class="card-body">
        <h2 class="text-center">Quiz Completed!</h2>
        <p class="text-center">You've completed the quiz. Here's your result:</p>
        <p>Attempted Questions: <span id="attempted"><?= $total ?></span></p>
        <p>Your Score is: <span id="finalScore"><?= $score ?></span></p>
        <h4 class="text-center font-weight-bold mt-4">
          Time Taken: <?= $minutes ?> minutes and <?= $seconds ?> seconds
        </h4>
        <h5 id="feedbackText" class="text-center blinking mt-4"></h5>
      </div>
      <div class="text-center mt-4">
        <a href="login.php" class="btn btn-danger">Quit Quiz</a>
        <a href="restart.php" class="btn btn-warning">Restart Quiz</a>
      </div>
      <div class="card-footer text-center">
        <a href="register.php" class="btn btn-primary">Log out</a>
      </div>
    </div>
  </div>

  <script>
    function showResults() {
      const score = <?= $score ?>;
      const totalQuestions = <?= $total ?>;
      // 0 વડે ભાગાકાર ન થાય તે માટે ચેક મૂક્યો છે
      const percentage = (totalQuestions > 0) ? Math.round((score / totalQuestions) * 100) : 0;
      const feedbackText = document.getElementById("feedbackText");
      
      if (percentage >= 80) {
        feedbackText.textContent = "Excellent! You're a quiz master!";
      } else if (percentage >= 60) {
        feedbackText.textContent = "Good job! You know your stuff!";
      } else if (percentage >= 40) {
        feedbackText.textContent = "Not bad! Keep learning!";
      } else {
        feedbackText.textContent = "Keep practicing to improve your score!";
      }
    }

    window.onload = showResults;
  </script>
</body>
</html>
<?php
// ==========================================================
// 4. સેશન ક્લિયર કરવું (Result બતાવ્યા પછી જ)
// ==========================================================
unset($_SESSION['score']);
unset($_SESSION['qindex']);
unset($_SESSION['quiz_start_time']);
unset($_SESSION['timing_logged']); 
?>