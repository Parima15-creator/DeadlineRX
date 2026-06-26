<?php
session_start();
require_once 'db_config.php';
require_once 'calculate-risk.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_name'])) {
    echo json_encode(['success' => false, 'message' => 'Student not logged in']);
    exit();
}

$studentEmail = $_SESSION['student_email'] ?? '';
$classId = $_SESSION['student_class_id'] ?? null;
$className = $_SESSION['class_name'] ?? '';

if (!$studentEmail) {
    echo json_encode(['success' => false, 'message' => 'Student email missing in session. Update student-login-process.php.']);
    exit();
}

if (!$classId && $className) {
    $stmtClass = $conn->prepare("SELECT Class_ID FROM class WHERE Class_Name = ? LIMIT 1");
    $stmtClass->bind_param("s", $className);
    $stmtClass->execute();
    $resClass = $stmtClass->get_result();
    if ($rowClass = $resClass->fetch_assoc()) {
        $classId = (int)$rowClass['Class_ID'];
    }
    $stmtClass->close();
}

if (!$classId) {
    echo json_encode(['success' => false, 'message' => 'Class ID not found']);
    exit();
}

function makeTaskId($prefix, $row) {
    $idCandidates = [
        'Assignment_ID', 'assignment_id', 'Assign_ID', 'ID',
        'Test_ID', 'test_id'
    ];

    foreach ($idCandidates as $key) {
        if (isset($row[$key]) && $row[$key] !== '') {
            return $prefix . '_' . $row[$key];
        }
    }

    $raw = $prefix . '|' .
        ($row['Subject'] ?? '') . '|' .
        ($row['Title'] ?? $row['Test_Title'] ?? '') . '|' .
        ($row['Due_Date'] ?? $row['Test_Date'] ?? '') . '|' .
        ($row['Class_ID'] ?? '');

    return $prefix . '_' . md5($raw);
}

function fetchProgressMap($conn, $studentEmail) {
    $map = [];

    $stmt = $conn->prepare("SELECT * FROM student_task_progress WHERE student_email = ?");
    $stmt->bind_param("s", $studentEmail);
    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $key = $row['task_type'] . '_' . $row['task_id'];
        $map[$key] = $row;
    }

    $stmt->close();
    return $map;
}

$progressMap = fetchProgressMap($conn, $studentEmail);
$tasks = [];

/* Assignments */
$stmtA = $conn->prepare("SELECT * FROM assignment WHERE Class_ID = ? ORDER BY Due_Date ASC");
$stmtA->bind_param("i", $classId);
$stmtA->execute();
$resA = $stmtA->get_result();

while ($row = $resA->fetch_assoc()) {
    $taskId = makeTaskId('assignment', $row);
    $mapKey = 'assignment_' . $taskId;
    $progress = $progressMap[$mapKey] ?? [];

    $task = [
        'task_id' => $taskId,
        'task_type' => 'assignment',
        'title' => $row['Title'] ?? 'Assignment',
        'subject' => $row['Subject'] ?? '',
        'deadline' => isset($row['Due_Date']) ? date('Y-m-d', strtotime($row['Due_Date'])) : '',
        'difficulty' => (int)($row['Difficulty_Index'] ?? 5),
        'weightage' => (float)($row['Weightage'] ?? 0),
        'pages' => (int)($row['No_of_Pages'] ?? 0),
        'description' => $row['Description'] ?? '',
        'completion_percentage' => (int)($progress['completion_percentage'] ?? 0),
        'estimated_hours_left' => (float)($progress['estimated_hours_left'] ?? 0),
        'available_hours_today' => (float)($progress['available_hours_today'] ?? 0),
        'status' => $progress['status'] ?? 'not_started',
        'is_completed' => (int)($progress['is_completed'] ?? 0)
    ];

    $task['risk'] = calculateDeadlineRisk($task);
    $tasks[] = $task;
}
$stmtA->close();

/* Tests */
$stmtT = $conn->prepare("SELECT * FROM test WHERE Class_ID = ? ORDER BY Test_Date ASC");
$stmtT->bind_param("i", $classId);
$stmtT->execute();
$resT = $stmtT->get_result();

while ($row = $resT->fetch_assoc()) {
    $taskId = makeTaskId('test', $row);
    $mapKey = 'test_' . $taskId;
    $progress = $progressMap[$mapKey] ?? [];

    $title = $row['Title'] ?? $row['Test_Title'] ?? ('Test: ' . ($row['Subject'] ?? ''));

    $task = [
        'task_id' => $taskId,
        'task_type' => 'test',
        'title' => $title,
        'subject' => $row['Subject'] ?? '',
        'deadline' => isset($row['Test_Date']) ? date('Y-m-d', strtotime($row['Test_Date'])) : '',
        'difficulty' => (int)($row['Difficulty_Index'] ?? 5),
        'weightage' => (float)($row['Weightage'] ?? 0),
        'pages' => 0,
        'description' => $row['Description'] ?? '',
        'completion_percentage' => (int)($progress['completion_percentage'] ?? 0),
        'estimated_hours_left' => (float)($progress['estimated_hours_left'] ?? 0),
        'available_hours_today' => (float)($progress['available_hours_today'] ?? 0),
        'status' => $progress['status'] ?? 'not_started',
        'is_completed' => (int)($progress['is_completed'] ?? 0)
    ];

    $task['risk'] = calculateDeadlineRisk($task);
    $tasks[] = $task;
}
$stmtT->close();

usort($tasks, function($a, $b) {
    $aDone = (int)$a['is_completed'];
    $bDone = (int)$b['is_completed'];

    if ($aDone !== $bDone) return $aDone - $bDone;

    $dateCompare = strcmp($a['deadline'], $b['deadline']);
    if ($dateCompare !== 0) return $dateCompare;

    return ($b['risk']['score'] ?? 0) <=> ($a['risk']['score'] ?? 0);
});

echo json_encode([
    'success' => true,
    'student' => [
        'name' => $_SESSION['student_name'],
        'email' => $studentEmail,
        'class_id' => $classId,
        'class_name' => $className
    ],
    'tasks' => $tasks
]);
?>