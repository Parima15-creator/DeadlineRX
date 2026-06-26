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
    if ($prefix === 'assignment' && isset($row['Assignment_ID'])) {
        return 'assignment_' . $row['Assignment_ID'];
    }

    if ($prefix === 'test' && isset($row['Test_ID'])) {
        return 'test_' . $row['Test_ID'];
    }

    if ($prefix === 'personal' && isset($row['Personal_Task_ID'])) {
        return 'personal_' . $row['Personal_Task_ID'];
    }

    return $prefix . '_' . md5(json_encode($row));
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

/* 1. Teacher Assignments */
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
        'source' => 'teacher',
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

/* 2. Teacher Tests */
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
        'source' => 'teacher',
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

/* 3. Student Personal Tasks */
$stmtP = $conn->prepare("
    SELECT * FROM student_personal_tasks 
    WHERE student_email = ? 
    ORDER BY Due_Date ASC
");
$stmtP->bind_param("s", $studentEmail);
$stmtP->execute();
$resP = $stmtP->get_result();

while ($row = $resP->fetch_assoc()) {
    $taskId = makeTaskId('personal', $row);
    $mapKey = 'personal_' . $taskId;
    $progress = $progressMap[$mapKey] ?? [];

    $isCompleted = (int)($row['Is_Completed'] ?? 0);

    $task = [
        'task_id' => $taskId,
        'task_type' => 'personal',
        'source' => 'student',
        'title' => $row['Title'] ?? 'Personal Task',
        'subject' => $row['Subject'] ?? 'Personal Task',
        'deadline' => isset($row['Due_Date']) ? date('Y-m-d', strtotime($row['Due_Date'])) : '',
        'difficulty' => (int)($row['Difficulty_Index'] ?? 5),
        'weightage' => (float)($row['Weightage'] ?? 0),
        'pages' => 0,
        'description' => $row['Description'] ?? '',
        'completion_percentage' => (int)($progress['completion_percentage'] ?? ($isCompleted ? 100 : 0)),
        'estimated_hours_left' => (float)($progress['estimated_hours_left'] ?? $row['Estimated_Hours'] ?? 1),
        'available_hours_today' => (float)($progress['available_hours_today'] ?? 0),
        'status' => $progress['status'] ?? ($isCompleted ? 'completed' : 'not_started'),
        'is_completed' => (int)($progress['is_completed'] ?? $isCompleted)
    ];

    $task['risk'] = calculateDeadlineRisk($task);
    $tasks[] = $task;
}

$stmtP->close();

usort($tasks, function($a, $b) {
    $aDone = (int)$a['is_completed'];
    $bDone = (int)$b['is_completed'];

    if ($aDone !== $bDone) {
        return $aDone - $bDone;
    }

    $riskA = $a['risk']['score'] ?? 0;
    $riskB = $b['risk']['score'] ?? 0;

    if ($riskA !== $riskB) {
        return $riskB - $riskA;
    }

    return strcmp($a['deadline'], $b['deadline']);
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