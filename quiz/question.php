<?php
// CRITICAL FIX 1: Output Buffering ચાલુ કરો
ob_start(); 

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Kolkata');

// ડેટાબેઝ કનેક્શન સેટઅપ
$connection = new mysqli('localhost', 'root', '', 'quiz');
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php"); 
    exit();
}

// --- FUNCTION TO HANDLE QUIZ COMPLETION & END TIME UPDATE ---
function update_end_time_and_redirect($connection, $user_id) {
    
    // 1. Find the latest ongoing timing record (where end_time is NULL)
    $stmt = $connection->prepare("SELECT id FROM timing WHERE user_id = ? AND end_time IS NULL ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res->fetch_assoc();
    $stmt->close();

    if ($row) {
        $timing_id = $row['id'];
        $end_time_current = date('Y-m-d H:i:s'); 
        
        // 2. Update the end_time in the database
        $stmt2 = $connection->prepare("UPDATE timing SET end_time = ? WHERE id = ?");
        $stmt2->bind_param("si", $end_time_current, $timing_id);
        
        // ગંભીર DB ભૂલ ચેક
        if (!$stmt2->execute()) {
            echo "<h1>CRITICAL DATABASE ERROR!</h1>";
            echo "Error updating timing table: " . $stmt2->error;
            exit(); 
        }
        $stmt2->close();
    }
    
    // 3. Clear unnecessary Session Data (qindex અને score result.php માં વાપરવા માટે retained રાખેલ છે)
    unset($_SESSION['quiz_start_time']);
    unset($_SESSION['quiz_end_time']);
    unset($_SESSION['timing_logged']);

    // 4. Redirect to results
    header("Location: result.php");
    exit();
}
// --- END OF FUNCTION ---


// Time tracking for start_time
if (!isset($_SESSION['timing_logged'])) {
    $stmt = $connection->prepare("SELECT id, end_time FROM timing WHERE user_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $latest = $result->fetch_assoc();
    $stmt->close();

    if (!$latest || $latest['end_time'] !== null) {
        $start_time = date('Y-m-d H:i:s');
        $stmt = $connection->prepare("INSERT INTO timing (user_id, start_time) VALUES (?, ?)");
        $stmt->bind_param("is", $user_id, $start_time);
        $stmt->execute();
        $stmt->close();
    }
    $_SESSION['timing_logged'] = true;
}

$quiz_duration = 60; // seconds

if (!isset($_SESSION['quiz_start_time'])) {
    $_SESSION['quiz_start_time'] = time();
    $_SESSION['quiz_end_time'] = $_SESSION['quiz_start_time'] + $quiz_duration;
}

// 1. Time'સ up check
if (time() >= $_SESSION['quiz_end_time']) {
    update_end_time_and_redirect($connection, $user_id); 
}

if (!isset($_SESSION['qindex'])) {
    $_SESSION['qindex'] = 0;
    $_SESSION['score'] = 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['next'])) {
    $userAnswer = isset($_POST['answer']) ? (int)$_POST['answer'] : 0;
    $correctAnswer = isset($_POST['correct_answer']) ? (int)$_POST['correct_answer'] : 0;

    if ($userAnswer === $correctAnswer) {
        $_SESSION['score']++;
    }

    $_SESSION['qindex']++;

    $stmt = $connection->prepare("SELECT COUNT(*) as total FROM question");
    $stmt->execute();
    $totalRow = $stmt->get_result()->fetch_assoc();
    $total_questions = $totalRow['total'];
    $stmt->close();

    // 2. All questions answered check
    if ($_SESSION['qindex'] >= $total_questions) {
        update_end_time_and_redirect($connection, $user_id);
    } else {
        header("Location: question.php");
        exit();
    }
}

$qindex = $_SESSION['qindex'];
$stmt = $connection->prepare("SELECT * FROM question LIMIT 1 OFFSET ?");
$stmt->bind_param("i", $qindex);
$stmt->execute();
$question = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fallback if no question is found
if (!$question) {
    update_end_time_and_redirect($connection, $user_id);
}

$stmt2 = $connection->prepare("SELECT * FROM answer WHERE qid = ?");
$stmt2->bind_param("i", $question['id']);
$stmt2->execute();
$options = $stmt2->get_result();
$stmt2->close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Online Quiz</title>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" />
    <style>
    .option-box {
        padding: 15px 20px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 500;
        transition: all 0.3s ease;
        background-color: #f9f9f9;
        border: 2px solid transparent;
        display: block;
        text-align: left;
    }

    .correct {
        background-color: #e0f7fa;
        border-color: #00bcd4;
        box-shadow: 0 0 10px rgba(0, 188, 212, 0.5);
    }

    .wrong {
        background-color: #ffe4ecff;
        border-color: #e91e63;
        box-shadow: 0 0 10px rgba(233, 30, 99, 0.5);
    }

    .default {
        border-color: #ccc;
    }

    .form-check-input {
        display: none;
    }

    #timer {
        font-weight: bold;
        font-size: 18px;
        color: black;
    }

    .options-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 15px;
        margin-top: 15px;
    }
    </style>
</head>
<body style="background:#607d8b">
<div class="container mt-4">
    <h1 class="text-center text-white">Test Your Knowledge📝</h1>
    <h4 class="text-center text-light">Welcome! Your test has started</h4>
    <div id="timer" class="text-center text-black my-2"></div>

    <div class="card p-4">
        <h4>Q<?= ($qindex + 1) ?>: <?= htmlspecialchars($question['question']) ?></h4>
        <form method="POST" id="quizForm">
            <div class="options-grid" id="optionsGrid">
            <?php
            $letters = ['A', 'B', 'C', 'D'];
            $i = 0;
            while ($opt = $options->fetch_assoc()):
            ?>
                <label class="option-box default" data-option-id="<?= $opt['id'] ?>">
                    <input type="radio" class="form-check-input" name="answer" value="<?= $opt['id'] ?>" required>
                    <strong><?= $letters[$i] ?>.</strong> <?= htmlspecialchars($opt['answer']) ?>
                </label>
            <?php
            $i++;
            endwhile;
            ?>
            </div>
            <input type="hidden" name="correct_answer" value="<?= $question['ans_id'] ?>">
            <div class="mt-4">
                <button type="submit" class="btn btn-primary btn-block py-3 fs-5" name="next" id="nextButton">Next Question</button>
            </div>
        </form>
        <div class="card-footer text-center mt-4">
            <a href="Logout.php" class="btn btn-danger">Logout</a>
        </div>
    </div>
</div>

<script>
    const endTime = <?= $_SESSION['quiz_end_time'] ?>;
    const timerDiv = document.getElementById("timer");
    const correctId = <?= $question['ans_id'] ?>;
    const optionsGrid = document.getElementById('optionsGrid');
    const nextButton = document.getElementById('nextButton');
    let answerLocked = false;

    function updateTimer() {
        const now = Math.floor(Date.now() / 1000);
        const remaining = endTime - now;
        if (remaining <= 0) {
            window.location.href = "question.php"; 
        }
        const minutes = Math.floor(remaining / 60).toString().padStart(2, '0');
        const seconds = (remaining % 60).toString().padStart(2, '0');
        timerDiv.innerText = `⏰ Time: ${minutes}:${seconds}`;
    }

    setInterval(updateTimer, 1000);
    updateTimer();

    optionsGrid.addEventListener('click', (event) => {
        const clickedBox = event.target.closest('.option-box');
        if (!clickedBox || answerLocked) return;

        answerLocked = true;
        nextButton.disabled = true;

        document.querySelectorAll('.option-box').forEach(b => {
            b.classList.remove('selected', 'default');
        });

        const input = clickedBox.querySelector('input');
        input.checked = true;

        const selectedId = parseInt(input.value);

        if (selectedId === correctId) {
            clickedBox.classList.add('correct'); 
        } else {
            clickedBox.classList.add('wrong'); 
            document.querySelector(`[data-option-id="${correctId}"]`).classList.add('correct'); 
        }

        setTimeout(() => {
            nextButton.disabled = false;
        }, 1500);
    });

    nextButton.addEventListener('click', (event) => {
        if (!document.querySelector('input[name="answer"]:checked')) {
            alert("Please select an answer first.");
            event.preventDefault();
        }
    });
</script>
</body>
</html>
<?php
// CRITICAL FIX 2: Output Buffering સમાપ્ત કરો
ob_end_flush();
?>