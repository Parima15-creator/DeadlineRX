<?php
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. COLLECT DATA FROM FORM
    $taskType = $_POST['task_type'];
    $deadline = $_POST['proposed_deadline'];
    $given    = $_POST['date_given'];     
    $weight     = (float)(($taskType === 'test') ? $_POST['weightage_test'] : $_POST['weightage']);
    $user_diff  = (int)(($taskType === 'test') ? $_POST['difficulty_level_test'] : $_POST['difficulty_level']);
    $pages      = (int)($_POST['num_pages'] ?? 1);
    $category   = strtolower($_POST['assignment_category'] ?? 'theory');

    // 2. MATHEMATICAL NORMALIZATION
    $delta = ($taskType === 'test' || $category === 'theory') ? 1.0 : ($category === 'practical' ? 0.0 : 0.5);
    $d_val = $user_diff / 10;
    $p_norm = $pages / 10;
    $alpha = 0.5;

    // 3. DYNAMIC HOLIDAY CALCULATION (H)
    // First, count public holidays from your database
    $stmtH = $conn->prepare("SELECT Dates FROM academic_calendar WHERE Holidays = 'Yes' AND Dates BETWEEN ? AND ?");
    $stmtH->bind_param("ss", $given, $deadline);
    $stmtH->execute();
    $result = $stmtH->get_result();
    
    $holidayDates = [];
    while($row = $result->fetch_assoc()) {
        $holidayDates[] = $row['Dates'];
    }
    
    // Second, calculate weekends (Sat/Sun) between Given Date and Deadline
    $startDate = new DateTime($given);
    $endDate = new DateTime($deadline);
    $weekendsCount = 0;

    while ($startDate <= $endDate) {
        $currentDate = $startDate->format('Y-m-d');
        $dayOfWeek = $startDate->format('N'); // 6 is Saturday, 7 is Sunday

        // If it's a weekend AND NOT already in our holiday database list
        if (($dayOfWeek == 6 || $dayOfWeek == 7) && !in_array($currentDate, $holidayDates)) {
            $weekendsCount++;
        }
        $startDate->modify('+1 day');
    }

    // Total H = Public Holidays + Weekends
    $H = count($holidayDates) + $weekendsCount;

    // 3b. NEW: CALCULATE EXISTING WORKLOAD (W)
    $class_id = $_POST['class_id']; // Ensure you pass class_id from your form

    $stmtW = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM assignment WHERE Class_ID = ? AND Due_Date BETWEEN ? AND ?) +
            (SELECT COUNT(*) FROM test WHERE Class_ID = ? AND Test_Date BETWEEN ? AND ?) 
        AS total_tasks
    ");
    $stmtW->bind_param("isssis", $class_id, $given, $deadline, $class_id, $given, $deadline);
    $stmtW->execute();
    $resW = $stmtW->get_result();
    $rowW = $resW->fetch_assoc();

    $W = (int)$rowW['total_tasks']; // This is your Concurrency Factor

    // 4. CALCULATE S_max (Constant baseline)
    $p_max = 30; 
    $S_max = $weight * (($delta * $p_max) + ((1 - $delta) * $p_max));

    // 5. CALCULATE FINAL STRESS SCORE (S%)
    $stress_percentage = 0;
    if ($S_max > 0) {
        // Math Logic:
        // Numerator: Weight * Difficulty * Pages
        // Beta: Impact of existing tasks (e.g., 0.15 means +15% stress per existing task)
        $beta = 0.15; 
        
        $numerator = $weight * (($delta * $d_val * $p_norm) + ((1 - $delta) * $d_val * $p_norm));
        
        // The "Workload Multiplier" increases stress
        $workload_multiplier = 1 + ($beta * $W);
        
        // The "Holiday Denominator" decreases stress
        $holiday_denominator = 1 + ($alpha * $H); 
        
        $S = ($numerator * $workload_multiplier) / $holiday_denominator;
        $stress_percentage = round(($S / $S_max) * 100);
    }

    // 6. REDIRECT
    $query = http_build_query([
        'score'    => min($stress_percentage, 100),
        'subject'  => $_POST['subject_name'],
        'deadline' => $deadline,
        'diff'     => $user_diff,
        'pages'    => $pages,
        'holidays' => $H 
    ]);

    header("Location: AnalysisResult.php?" . $query);
    exit();
}
?>