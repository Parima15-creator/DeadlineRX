<?php
header('Content-Type: application/json');
require_once 'db_config.php';

// 1. Fetch Events
$eventsQuery = $conn->query("SELECT Dates, Events, Holidays FROM academic_calendar");
$events = [];

if ($eventsQuery) {
    while ($row = $eventsQuery->fetch_assoc()) {
        $title = $row['Events'];
        $type = ($row['Holidays'] === 'Yes') ? 'holiday' : 
                ((stripos($title, 'IT') !== false || stripos($title, 'ISA') !== false || stripos($title, 'Exam') !== false) ? 'it-exam' : 'other');

        $events[] = [
            'date' => trim($row['Dates']),
            'title' => $title,
            'type' => $type
        ];
    }
}

// 2. Fetch Assignments
$assignQuery = $conn->query("
    SELECT a.*, c.Class_Name, DATE(a.Due_Date) AS Clean_Date
    FROM assignment a
    JOIN class c ON a.Class_ID = c.Class_ID
");

$assignments = [];

if ($assignQuery) {
    while ($row = $assignQuery->fetch_assoc()) {
        $assignments[] = [
            'title' => $row['Title'],
            'subject' => $row['Subject'],
            'deadline' => $row['Clean_Date'],
            'className' => $row['Class_Name'],
            'classId' => $row['Class_ID']
        ];
    }
}

// 3. Fetch Tests
$testQuery = $conn->query("
    SELECT t.*, c.Class_Name, DATE(t.Test_Date) AS Clean_Test_Date
    FROM test t
    JOIN class c ON t.Class_ID = c.Class_ID
");

$tests = [];

if ($testQuery) {
    while ($row = $testQuery->fetch_assoc()) {
        $tests[] = [
            'title' => $row['Title'] ?: ('Test: ' . $row['Subject']),
            'subject' => $row['Subject'],
            'date' => $row['Clean_Test_Date'],
            'className' => $row['Class_Name'],
            'classId' => $row['Class_ID'],
            'type' => 'test'
        ];
    }
}

echo json_encode([
    'events' => $events,
    'assignments' => $assignments,
    'tests' => $tests
]);
?>