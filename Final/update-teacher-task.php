<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: Login/teacher-login.html");
    exit();
}

$teacher = $_SESSION['teacher_id'];

$taskType = $_POST['task_type'] ?? '';
$taskId = $_POST['task_id'] ?? '';

$classId = $_POST['class_id'] ?? '';
$subject = trim($_POST['subject'] ?? '');
$title = trim($_POST['title'] ?? '');
$taskDate = $_POST['task_date'] ?? '';
$difficulty = (int)($_POST['difficulty'] ?? 5);
$weightage = (float)($_POST['weightage'] ?? 0);
$description = trim($_POST['description'] ?? '');

if ($taskId === '' || !in_array($taskType, ['assignment', 'test'])) {
    die("Invalid task details.");
}

if ($classId === '' || $subject === '' || $title === '' || $taskDate === '') {
    die("Please fill all required fields.");
}

if ($taskType === 'assignment') {
    $category = $_POST['category'] ?? 'Theory';
    $pages = (int)($_POST['pages'] ?? 0);

    $stmt = $conn->prepare("
        UPDATE assignment
        SET 
            Subject = ?,
            Title = ?,
            Type = ?,
            Weightage = ?,
            No_of_Pages = ?,
            Difficulty_Index = ?,
            Due_Date = ?,
            Class_ID = ?,
            Description = ?
        WHERE Assignment_ID = ? AND Teacher_Username = ?
    ");

    $stmt->bind_param(
        "sssdiisisis",
        $subject,
        $title,
        $category,
        $weightage,
        $pages,
        $difficulty,
        $taskDate,
        $classId,
        $description,
        $taskId,
        $teacher
    );

} else {
    $stmt = $conn->prepare("
        UPDATE test
        SET 
            Subject = ?,
            Title = ?,
            Test_Title = ?,
            Weightage = ?,
            Difficulty_Index = ?,
            Test_Date = ?,
            Class_ID = ?,
            Description = ?
        WHERE Test_ID = ? AND Teacher_Username = ?
    ");

    $stmt->bind_param(
        "sssdisssis",
        $subject,
        $title,
        $title,
        $weightage,
        $difficulty,
        $taskDate,
        $classId,
        $description,
        $taskId,
        $teacher
    );
}

if ($stmt->execute()) {
    echo "<script>
            alert('Task updated successfully.');
            window.location.href = 'teacher-dashboard.php';
          </script>";
} else {
    echo "Error updating task: " . $stmt->error;
}

$stmt->close();
$conn->close();
?>