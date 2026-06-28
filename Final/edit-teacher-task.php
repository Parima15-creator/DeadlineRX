<?php
session_start();
require_once 'db_config.php';

if (!isset($_SESSION['teacher_id'])) {
    header("Location: Login/teacher-login.html");
    exit();
}

$teacher = $_SESSION['teacher_id'];
$taskType = $_GET['type'] ?? '';
$taskId = $_GET['id'] ?? '';

if ($taskId === '' || !in_array($taskType, ['assignment', 'test'])) {
    die("Invalid task details.");
}

$class_map = [
    "1" => "FE COMP 1",
    "2" => "FE COMP 2",
    "3" => "SE COMP 1",
    "4" => "SE COMP 2",
    "5" => "TE COMP 1",
    "6" => "BE COMP 1"
];

if ($taskType === 'assignment') {
    $stmt = $conn->prepare("
        SELECT *
        FROM assignment
        WHERE Assignment_ID = ? AND Teacher_Username = ?
    ");
} else {
    $stmt = $conn->prepare("
        SELECT *
        FROM test
        WHERE Test_ID = ? AND Teacher_Username = ?
    ");
}

$stmt->bind_param("is", $taskId, $teacher);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows !== 1) {
    die("Task not found or you are not allowed to edit it.");
}

$task = $result->fetch_assoc();

if ($taskType === 'assignment') {
    $title = $task['Title'];
    $subject = $task['Subject'];
    $date = $task['Due_Date'];
    $classId = $task['Class_ID'];
    $difficulty = $task['Difficulty_Index'];
    $weightage = $task['Weightage'];
    $description = $task['Description'];
    $category = $task['Type'];
    $pages = $task['No_of_Pages'];
} else {
    $title = $task['Test_Title'] ?: ($task['Title'] ?: $task['Subject']);
    $subject = $task['Subject'];
    $date = $task['Test_Date'];
    $classId = $task['Class_ID'];
    $difficulty = $task['Difficulty_Index'];
    $weightage = $task['Weightage'];
    $description = $task['Description'];
    $category = '';
    $pages = '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Task — DeadlineRX</title>
    <link rel="stylesheet" href="teacher-dashboard.css">
</head>

<body>
    <main class="main-viewport" style="max-width: 900px;">
        <div class="header">
            <div>
                <h1 class="page-title">Edit <?php echo ucfirst($taskType); ?></h1>
                <p style="color: var(--aslb-muted); margin-top: 8px;">
                    Update the task details. Changes will be visible to students.
                </p>
            </div>
        </div>

        <div class="form-card">
            <form action="update-teacher-task.php" method="POST">
                <input type="hidden" name="task_type" value="<?php echo htmlspecialchars($taskType); ?>">
                <input type="hidden" name="task_id" value="<?php echo htmlspecialchars($taskId); ?>">

                <div class="form-grid">
                    <div class="form-group">
                        <label>Class</label>
                        <select name="class_id" required>
                            <?php foreach ($class_map as $id => $className): ?>
                                <option value="<?php echo $id; ?>" <?php echo ((int)$classId === (int)$id) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($className); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Subject</label>
                        <input type="text" name="subject" value="<?php echo htmlspecialchars($subject); ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label>Title</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                    </div>

                    <?php if ($taskType === 'assignment'): ?>
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category">
                                <option value="Theory" <?php echo strtolower($category) === 'theory' ? 'selected' : ''; ?>>Theory</option>
                                <option value="Practical" <?php echo strtolower($category) === 'practical' ? 'selected' : ''; ?>>Practical</option>
                                <option value="Both" <?php echo strtolower($category) === 'both' ? 'selected' : ''; ?>>Both</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Pages</label>
                            <input type="number" name="pages" min="0" value="<?php echo htmlspecialchars($pages); ?>">
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label>Difficulty</label>
                        <input type="number" name="difficulty" min="1" max="10" value="<?php echo htmlspecialchars($difficulty); ?>">
                    </div>

                    <div class="form-group">
                        <label>Weightage</label>
                        <input type="number" name="weightage" min="0" max="100" value="<?php echo htmlspecialchars($weightage); ?>">
                    </div>

                    <div class="form-group">
                        <label><?php echo $taskType === 'assignment' ? 'Deadline' : 'Test Date'; ?></label>
                        <input type="date" name="task_date" value="<?php echo htmlspecialchars($date); ?>" required>
                    </div>

                    <div class="form-group full-width">
                        <label>Description</label>
                        <textarea name="description" rows="4"><?php echo htmlspecialchars($description); ?></textarea>
                    </div>
                </div>

                <div class="teacher-edit-actions">
                    <button type="submit" class="submit-btn">Save Changes</button>
                    <a href="teacher-dashboard.php" class="cancel-edit-btn">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>