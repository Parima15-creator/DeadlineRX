<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. COLLECT ALL DATA (Ensure these are passed as hidden inputs from AnalysisResult.php)
    $subject  = $_POST['subject'];
    $title    = $_POST['task_title'] ?? 'New Task';    // Matches 'Title' column
    $given    = $_POST['given_date'] ?? date('Y-m-d'); // Matches 'Given_Date'
    $type     = $_POST['category'] ?? 'Theory';   // Matches 'Type' column
    $weight   = $_POST['weight'] ?? 0;            // Matches 'Weightage' column
    $pages    = (int)($_POST['pages'] ?? 0);      // Matches 'No_of_Pages'
    $diff     = (int)($_POST['diff'] ?? 0);       // Matches 'Difficulty_Index'
    $deadline = $_POST['deadline'];               // Matches 'Due_Date'
    
    // Use session or default
    $classId  = $_POST['class_id'] ?? 1; 
    $teacher  = $_SESSION['teacher_id'] ?? null;

    if (!$teacher) {
        die("Error: No teacher session found. Please log in again.");
    }

    if (empty($subject) || empty($deadline)) {
        die("Error: Missing assignment details.");
    }

    // 2. UPDATED SQL STATEMENT TO MATCH YOUR TABLE COLUMNS
    // columns: Subject, Title, Given_Date, Type, Weightage, No_of_Pages, Difficulty_Index, Due_Date, Class_ID, Teacher_Username
    $sql = "INSERT INTO assignment (Subject, Title, Given_Date, Type, Weightage, No_of_Pages, Difficulty_Index, Due_Date, Class_ID, Teacher_Username) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    // 3. BIND PARAMETERS (s=string, i=int, d=double/float)
    // types: s, s, s, s, d, i, i, s, i, s
    $stmt->bind_param("ssssdiisis", 
        $subject, 
        $title, 
        $given, 
        $type, 
        $weight, 
        $pages, 
        $diff, 
        $deadline, 
        $classId, 
        $teacher
    );

    if ($stmt->execute()) {
        echo "<script>
                alert('Assignment successfully recorded in the database!');
                window.location.href = 'teacher-dashboard.php';
              </script>";
    } else {
        echo "Error recording assignment: " . $conn->error;
    }

    $stmt->close();
    $conn->close();
}
?>