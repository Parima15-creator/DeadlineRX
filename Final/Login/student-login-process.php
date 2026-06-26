<?php
session_start();
include '../db_config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    $dept_name = $_POST['department']; // From the <select> name="department"
    $class_name = $_POST['class'];       // From the <select> name="class"

$stmt = $conn->prepare("
    SELECT s.Username, d.Department_Name, c.Class_Name 
    FROM student s
    JOIN department d ON s.Department_ID = d.Department_ID
    JOIN class c ON s.Class_ID = c.Class_ID
    WHERE s.College_Email_ID = ? AND s.Password = ?
");

$stmt->bind_param("ss", $email, $password);
$stmt->execute();
$result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $student = $result->fetch_assoc(); 

        $_SESSION['student_name'] = $student['Username'];
        $_SESSION['dept_name'] = $student['Department_Name'];
        $_SESSION['class_name'] = $student['Class_Name'];

        // Redirect to dashboard
        header("Location: ../student-dashboard.php");
        exit();
    } else {
        echo "<script>alert('Invalid credentials or department/class mismatch'); window.location='student-login.html';</script>";
    }

    $stmt->close();
    $conn->close();
}
?>