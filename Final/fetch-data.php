<?php
header('Content-Type: application/json');
$conn = new mysqli("localhost", "root", "", "aslb");

if ($conn->connect_error) { die(json_encode(["error" => "Conn failed"])); }

// 1. Fetch Events (Holidays/Institutional Exams)
$eventsQuery = $conn->query("SELECT Dates, Events, Holidays FROM academic_calendar");
$events = [];
while($row = $eventsQuery->fetch_assoc()) {
    $title = $row['Events'];
    $type = ($row['Holidays'] === 'Yes') ? 'holiday' : (stripos($title, 'IT/ISA') !== false ? 'it-exam' : 'other');
    $events[] = ['date' => trim($row['Dates']), 'title' => $title, 'type' => $type];
}

// 2. Fetch Assignments
$assignQuery = $conn->query("
    SELECT a.*, c.Class_Name, DATE(a.Due_Date) as Clean_Date 
    FROM assignment a 
    JOIN class c ON a.Class_ID = c.Class_ID
");
$assignments = [];
while($row = $assignQuery->fetch_assoc()) {
    $assignments[] = [
        'title' => $row['Title'],
        'subject' => $row['Subject'],
        'deadline' => $row['Clean_Date'],
        'className' => $row['Class_Name']
    ];
}

// 3. NEW: Fetch Tests
$testQuery = $conn->query("
    SELECT t.*, c.Class_Name, DATE(t.Test_Date) as Clean_Test_Date 
    FROM test t 
    JOIN class c ON t.Class_ID = c.Class_ID
");
$tests = [];
while($row = $testQuery->fetch_assoc()) {
    $tests[] = [
        'title' => 'Test: ' . $row['Subject'],
        'subject' => $row['Subject'],
        'date' => $row['Clean_Test_Date'],
        'className' => $row['Class_Name'],
        'type' => 'test' // Used for the red dot in calendar
    ];
}

// Return everything combined
echo json_encode([
    'events' => $events, 
    'assignments' => $assignments, 
    'tests' => $tests
]);
?>