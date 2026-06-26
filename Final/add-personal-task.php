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

$title = trim($input['title'] ?? '');
$subject = trim($input['subject'] ?? 'Personal Task');
$dueDate = $input['due_date'] ?? '';
$difficulty = (int)($input['difficulty'] ?? 5);
$weightage = (float)($input['weightage'] ?? 0);
$estimatedHours = (float)($input['estimated_hours'] ?? 1);
$description = trim($input['description'] ?? '');

if ($title === '' || $dueDate === '') {
    echo json_encode(['success' => false, 'message' => 'Title and due date are required']);
    exit();
}

$difficulty = max(1, min(10, $difficulty));
$estimatedHours = max(0.5, $estimatedHours);

$stmt = $conn->prepare("
    INSERT INTO student_personal_tasks
    (student_email, Title, Subject, Due_Date, Difficulty_Index, Weightage, Estimated_Hours, Description)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    echo json_encode(['success' => false, 'message' => $conn->error]);
    exit();
}

$stmt->bind_param(
    "ssssidds",
    $studentEmail,
    $title,
    $subject,
    $dueDate,
    $difficulty,
    $weightage,
    $estimatedHours,
    $description
);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Personal task added successfully']);
} else {
    echo json_encode(['success' => false, 'message' => $stmt->error]);
}

$stmt->close();
$conn->close();
?>