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

$taskId = $input['task_id'] ?? '';
$taskType = $input['task_type'] ?? '';

if ($taskId === '' || !in_array($taskType, ['assignment', 'test', 'personal'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid task details']);
    exit();
}

/*
    This does NOT delete teacher assignment/test.
    It only hides the completed task from this student's list.
*/

$stmt = $conn->prepare("
    UPDATE student_task_progress
    SET is_hidden = 1
    WHERE student_email = ?
      AND task_type = ?
      AND task_id = ?
      AND is_completed = 1
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    exit();
}

$stmt->bind_param("sss", $studentEmail, $taskType, $taskId);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Completed task removed from your list']);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>