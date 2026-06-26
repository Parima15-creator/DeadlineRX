<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_name'])) {
    echo json_encode(['success' => false, 'message' => 'Student not logged in']);
    exit();
}

$studentEmail = $_SESSION['student_email'] ?? '';

if (!$studentEmail) {
    echo json_encode(['success' => false, 'message' => 'Student email missing in session']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'No data received']);
    exit();
}

$taskId = $input['task_id'] ?? '';

if (!str_starts_with($taskId, 'personal_')) {
    echo json_encode(['success' => false, 'message' => 'Invalid personal task ID']);
    exit();
}

$personalId = (int)str_replace('personal_', '', $taskId);

$stmt = $conn->prepare("
    DELETE FROM student_personal_tasks 
    WHERE Personal_Task_ID = ? AND student_email = ?
");

$stmt->bind_param("is", $personalId, $studentEmail);

if ($stmt->execute()) {
    $progressTaskId = 'personal_' . $personalId;

    $stmt2 = $conn->prepare("
        DELETE FROM student_task_progress 
        WHERE student_email = ? AND task_type = 'personal' AND task_id = ?
    ");
    $stmt2->bind_param("ss", $studentEmail, $progressTaskId);
    $stmt2->execute();
    $stmt2->close();

    echo json_encode(['success' => true, 'message' => 'Personal task deleted']);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>