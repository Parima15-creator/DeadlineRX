<?php
session_start();
require_once 'db_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_name'])) {
    echo json_encode([
        'success' => false,
        'message' => 'Student not logged in'
    ]);
    exit();
}

$studentEmail = $_SESSION['student_email'] ?? '';

if (!$studentEmail) {
    echo json_encode([
        'success' => false,
        'message' => 'Student email missing in session'
    ]);
    exit();
}

$stmt = $conn->prepare("
    SELECT plan_text, created_at
    FROM ai_plans
    WHERE student_email = ?
    ORDER BY created_at DESC
    LIMIT 1
");

$stmt->bind_param("s", $studentEmail);
$stmt->execute();

$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    echo json_encode([
        'success' => true,
        'has_plan' => true,
        'plan' => $row['plan_text'],
        'created_at' => $row['created_at']
    ]);
} else {
    echo json_encode([
        'success' => true,
        'has_plan' => false
    ]);
}

$stmt->close();
$conn->close();
?>