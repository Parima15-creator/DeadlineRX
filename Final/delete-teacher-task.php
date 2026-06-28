<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: Login/teacher-login.html");
    exit();
}

$teacher = $_SESSION['teacher_id'];
$taskType = $_POST['task_type'] ?? '';
$taskId = $_POST['task_id'] ?? '';

if ($taskId === '' || !in_array($taskType, ['assignment', 'test'])) {
    echo "<script>
            alert('Invalid task details.');
            window.location.href = 'teacher-dashboard.php';
          </script>";
    exit();
}

$conn->begin_transaction();

try {
    if ($taskType === 'assignment') {
        // Delete student progress connected to this assignment
        $stmtProgress = $conn->prepare("
            DELETE FROM student_task_progress 
            WHERE task_type = 'assignment' AND task_id = ?
        ");
        $stmtProgress->bind_param("s", $taskId);
        $stmtProgress->execute();
        $stmtProgress->close();

        // Delete assignment only if created by this teacher
        $stmtDelete = $conn->prepare("
            DELETE FROM assignment 
            WHERE Assignment_ID = ? AND Teacher_Username = ?
        ");
        $stmtDelete->bind_param("is", $taskId, $teacher);
        $stmtDelete->execute();

    } else {
        // Delete student progress connected to this test
        $stmtProgress = $conn->prepare("
            DELETE FROM student_task_progress 
            WHERE task_type = 'test' AND task_id = ?
        ");
        $stmtProgress->bind_param("s", $taskId);
        $stmtProgress->execute();
        $stmtProgress->close();

        // Delete test only if created by this teacher
        $stmtDelete = $conn->prepare("
            DELETE FROM test 
            WHERE Test_ID = ? AND Teacher_Username = ?
        ");
        $stmtDelete->bind_param("is", $taskId, $teacher);
        $stmtDelete->execute();
    }

    if ($stmtDelete->affected_rows > 0) {
        $conn->commit();

        echo "<script>
                alert('Task deleted successfully.');
                window.location.href = 'teacher-dashboard.php';
              </script>";
    } else {
        $conn->rollback();

        echo "<script>
                alert('Task not found or you are not allowed to delete it.');
                window.location.href = 'teacher-dashboard.php';
              </script>";
    }

    $stmtDelete->close();

} catch (Exception $e) {
    $conn->rollback();

    echo "<script>
            alert('Error deleting task.');
            window.location.href = 'teacher-dashboard.php';
          </script>";
}

$conn->close();
?>