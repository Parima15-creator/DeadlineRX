<?php
// 1. Database config
require_once '../db_config.php';

// 2. Set cookie params BEFORE starting the session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user    = isset($_POST['username']) ? trim($_POST['username']) : '';
    $email   = isset($_POST['email']) ? trim($_POST['email']) : '';
    $dept_id = isset($_POST['department']) ? trim($_POST['department']) : '';
    $pass    = isset($_POST['password']) ? $_POST['password'] : '';

    if (empty($user) || empty($email) || empty($dept_id) || empty($pass)) {
        // Fallback for standard form if JS fails
        die("Please fill all fields."); 
    }

    // Prepare statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM teacher WHERE Username = ? AND College_Email_ID = ? AND Department_ID = ?");
    $stmt->bind_param("ssi", $user, $email, $dept_id); 
    $stmt->execute();
    $result = $stmt->get_result();

    if ($teacher = $result->fetch_assoc()) {
        // Checking plain text password
        if ($pass === $teacher['Password']) {
            $_SESSION['teacher_id'] = $teacher['Username'];
            $_SESSION['dept_id']    = $dept_id;
            echo json_encode(['success' => true]);
            exit();
        } else {
            echo "<script>alert('Incorrect password.'); window.location.href='teacher-login.html';</script>";
        }
    } else {
        echo "<script>alert('Identity mismatch. Please check your credentials.'); window.location.href='teacher-login.html';</script>";
    }
    
    $stmt->close();
}
?>