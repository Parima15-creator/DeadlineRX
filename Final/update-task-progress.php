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
$taskType = $input['task_type'] ?? '';
$completion = (int)($input['completion_percentage'] ?? 0);
$hoursLeft = (float)($input['estimated_hours_left'] ?? 0);
$availableHours = (float)($input['available_hours_today'] ?? 0);
$status = $input['status'] ?? 'not_started';

if ($taskId === '' || !in_array($taskType, ['assignment', 'test'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid task details']);
    exit();
}

$completion = max(0, min(100, $completion));
$isCompleted = ($completion >= 100 || $status === 'completed') ? 1 : 0;

if ($isCompleted) {
    $status = 'completed';
    $completion = 100;
}

if (!in_array($status, ['not_started', 'in_progress', 'completed'])) {
    $status = 'not_started';
}

$sql = "
    INSERT INTO student_task_progress
    (student_email, task_type, task_id, completion_percentage, estimated_hours_left, available_hours_today, status, is_completed)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE
        completion_percentage = VALUES(completion_percentage),
        estimated_hours_left = VALUES(estimated_hours_left),
        available_hours_today = VALUES(available_hours_today),
        status = VALUES(status),
        is_completed = VALUES(is_completed),
        updated_at = CURRENT_TIMESTAMP
";

$stmt = $conn->prepare($sql);

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    exit();
}

$stmt->bind_param(
    "sssiddsi",
    $studentEmail,
    $taskType,
    $taskId,
    $completion,
    $hoursLeft,
    $availableHours,
    $status,
    $isCompleted
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Progress updated']);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>