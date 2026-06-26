<?php
require_once 'db_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. COLLECT DATA FROM FORM
    $taskType = $_POST['task_type'] ?? 'assignment';
    $deadline = $_POST['proposed_deadline'];
    $given    = $_POST['date_given']; 
    $subject  = $_POST['subject_name'] ?? 'Unknown Subject';
    $task_title = $_POST['task_title'];
    $class_id = (int)($_POST['class_id'] ?? 3); 

    $weight    = (float)(($taskType === 'test') ? ($_POST['weightage_test'] ?? 0) : ($_POST['weightage'] ?? 0));
    $user_diff = (int)(($taskType === 'test') ? ($_POST['difficulty_level_test'] ?? 5) : ($_POST['difficulty_level'] ?? 5));
    $pages     = (int)($_POST['num_pages'] ?? 1);
    $category  = strtolower($_POST['assignment_category'] ?? 'theory');

    // 2. MATHEMATICAL NORMALIZATION
    $delta = ($taskType === 'test' || $category === 'theory') ? 1.0 : ($category === 'practical' ? 0.3 : 0.6);
    $d_val = $user_diff / 10; 
    
    // 3. CALCULATE TIMELINE
    $dateStart = new DateTime($given);
    $dateEnd   = new DateTime($deadline);
    $interval  = $dateStart->diff($dateEnd);
    $days_total = $interval->days > 0 ? $interval->days : 1; 

    // --- 4. ACCURATE RELIEF CALCULATION (H) ---
    // Only counts Sundays, Saturdays, and official 'Yes' Holidays
    $H = 0;
    $tempDate = clone $dateStart;
    
    // Fetch ONLY official holidays where Holidays = 'Yes'
    $stmtH = $conn->prepare("SELECT Dates FROM academic_calendar WHERE Holidays = 'Yes' AND Dates BETWEEN ? AND ?");
    $stmtH->bind_param("ss", $given, $deadline);
    $stmtH->execute();
    $resultH = $stmtH->get_result();
    
    $officialHolidays = [];
    while($row = $resultH->fetch_assoc()) { 
        $officialHolidays[] = date('Y-m-d', strtotime($row['Dates'])); 
    }
    
    while ($tempDate <= $dateEnd) {
        $currentStr = $tempDate->format('Y-m-d');
        $dayOfWeek = (int)$tempDate->format('N'); 

        // Count ONLY if it's a Weekend OR an official Holiday
        if ($dayOfWeek >= 6 || in_array($currentStr, $officialHolidays)) {
            $H++;
        }
        $tempDate->modify('+1 day');
    }

    // --- 5. DYNAMIC WORKLOAD & IT PHASE DETECTION ---
    $total_existing_intensity = 0;
    $assignment_count = 0;
    $test_count = 0;
    $is_it_exam_period = "No"; 

    // A. Check existing Assignments
    $stmtA = $conn->prepare("SELECT Weightage, Difficulty_Index, No_of_Pages, Subject FROM assignment WHERE Class_ID = ? AND Due_Date BETWEEN ? AND ?");
    $stmtA->bind_param("iss", $class_id, $given, $deadline);
    $stmtA->execute();
    $resA = $stmtA->get_result();
    while ($row = $resA->fetch_assoc()) {
        $ex_diff  = $row['Difficulty_Index'] / 10;
        $ex_pages = $row['No_of_Pages'] / 30; 
        $total_existing_intensity += ($row['Weightage'] * $ex_diff * $ex_pages);
        $assignment_count++;
    }

    // B. Check existing Tests & Teacher-created ITs
    $stmtT = $conn->prepare("SELECT Subject, Weightage, Difficulty_Index FROM test WHERE Class_ID = ? AND Test_Date BETWEEN ? AND ?");
    $stmtT->bind_param("iss", $class_id, $given, $deadline);
    $stmtT->execute();
    $resT = $stmtT->get_result();
    while ($row = $resT->fetch_assoc()) {
        $ex_diff = $row['Difficulty_Index'] / 10;
        $total_existing_intensity += ($row['Weightage'] * $ex_diff * 1.0); 
        $test_count++;
        if (stripos($row['Subject'], 'IT') !== false || stripos($row['Subject'], 'Internal') !== false) { 
            $is_it_exam_period = "Yes"; 
        }
    }

    // C. Check Institutional Calendar for IT/ISA Events
    $stmtInst = $conn->prepare("SELECT Events FROM academic_calendar 
                                WHERE (Events LIKE '%IT%' OR Events LIKE '%ISA%' OR Events LIKE '%Exam%') 
                                AND Dates BETWEEN ? AND ?");
    $stmtInst->bind_param("ss", $given, $deadline);
    $stmtInst->execute();
    if ($stmtInst->get_result()->num_rows > 0) {
        $is_it_exam_period = "Yes";
    }

    // --- 6. FINAL STRESS CALCULATION ENGINE ---
    $p_max = 30;     
    $alpha = 0.50;   
    $standard_window = 7; 

    $current_intensity = $weight * $d_val * ($pages / $p_max) * $delta;
    $time_pressure = $standard_window / $days_total;
    
    // Balanced multiplier (0.05)
    $workload_multiplier = 1 + (0.05 * $total_existing_intensity);
    $holiday_relief = 1 + ($alpha * $H);

    $S = ($current_intensity * $time_pressure * $workload_multiplier) / $holiday_relief;
    $stress_percentage = round(($S / 2.0) * 100);
    $final_score = max(5, min($stress_percentage, 100));

    // --- 7. REDIRECT ---
    $params = [
        'score'           => $final_score,
        'subject'         => $subject,
        'title'           => $task_title,
        'deadline'        => $deadline,
        'given'           => $given,
        'category'        => $category,
        'weight'          => $weight,
        'diff'            => $user_diff,
        'pages'           => $pages,
        'holidays'        => $H,
        'intensity'       => round($total_existing_intensity, 2),
        'days'            => $days_total,
        'num_assignments' => $assignment_count,
        'num_tests'       => $test_count,
        'it_exam'         => $is_it_exam_period,
        'class_id'        => $class_id
    ];

    header("Location: AnalysisResult.php?" . http_build_query($params));
    exit();
} else {
    header("Location: teacher-dashboard.php");
    exit();
}
?>