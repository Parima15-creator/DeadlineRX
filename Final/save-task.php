<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: Login/teacher-login.html");
    exit();
}

function table_columns($conn, $tableName) {
    $columns = [];
    $result = $conn->query("SHOW COLUMNS FROM `$tableName`");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $columns[] = $row['Field'];
        }
    }
    return $columns;
}

function insert_dynamic($conn, $tableName, $data) {
    $existingColumns = table_columns($conn, $tableName);

    $insertData = [];
    foreach ($data as $column => $value) {
        if (in_array($column, $existingColumns)) {
            $insertData[$column] = $value;
        }
    }

    if (empty($insertData)) {
        throw new Exception("No matching columns found for table $tableName.");
    }

    $columns = array_keys($insertData);
    $placeholders = array_fill(0, count($columns), '?');

    $sql = "INSERT INTO `$tableName` (`" . implode("`, `", $columns) . "`) VALUES (" . implode(", ", $placeholders) . ")";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $types = "";
    $values = [];

    foreach ($insertData as $value) {
        if (is_int($value)) {
            $types .= "i";
        } elseif (is_float($value) || is_double($value)) {
            $types .= "d";
        } else {
            $types .= "s";
        }
        $values[] = $value;
    }

    $stmt->bind_param($types, ...$values);

    if (!$stmt->execute()) {
        throw new Exception("Insert failed: " . $stmt->error);
    }

    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $teacher = $_SESSION['teacher_id'];

        $taskType = $_POST['task_type'] ?? '';
        $subject = trim($_POST['subject_name'] ?? '');
        $title = trim($_POST['task_title'] ?? '');
        $classId = (int)($_POST['class_id'] ?? 0);
        $givenDate = $_POST['date_given'] ?? date('Y-m-d');
        $deadline = $_POST['proposed_deadline'] ?? '';

        if ($taskType === '' || $subject === '' || $title === '' || $classId <= 0 || $deadline === '') {
            throw new Exception("Please fill all required fields.");
        }

        if ($taskType === 'assignment') {
            $category = $_POST['assignment_category'] ?? 'theory';
            $pages = (int)($_POST['num_pages'] ?? 0);
            $difficulty = (int)($_POST['difficulty_level'] ?? 5);
            $weightage = (float)($_POST['weightage'] ?? 0);
            $description = trim($_POST['description'] ?? '');

            $assignmentData = [
                'Subject' => $subject,
                'Title' => $title,
                'Given_Date' => $givenDate,
                'Type' => ucfirst($category),
                'Weightage' => $weightage,
                'No_of_Pages' => $pages,
                'Difficulty_Index' => $difficulty,
                'Due_Date' => $deadline,
                'Class_ID' => $classId,
                'Teacher_Username' => $teacher,
                'Description' => $description
            ];

            insert_dynamic($conn, 'assignment', $assignmentData);

            echo "<script>
                alert('Assignment added successfully!');
                window.location.href='teacher-dashboard.php';
            </script>";
            exit();
        }

        if ($taskType === 'test') {
            $difficulty = (int)($_POST['difficulty_level_test'] ?? 5);
            $weightage = (float)($_POST['weightage_test'] ?? 0);
            $description = trim($_POST['description'] ?? '');

            $testData = [
                'Subject' => $subject,
                'Title' => $title,
                'Test_Title' => $title,
                'Test_Date' => $deadline,
                'Date_Given' => $givenDate,
                'Given_Date' => $givenDate,
                'Weightage' => $weightage,
                'Difficulty_Index' => $difficulty,
                'Class_ID' => $classId,
                'Teacher_Username' => $teacher,
                'Description' => $description
            ];

            insert_dynamic($conn, 'test', $testData);

            echo "<script>
                alert('Test added successfully!');
                window.location.href='teacher-dashboard.php';
            </script>";
            exit();
        }

        throw new Exception("Invalid task type selected.");

    } catch (Exception $e) {
        echo "<script>
            alert(" . json_encode($e->getMessage()) . ");
            window.history.back();
        </script>";
        exit();
    }
}
?>