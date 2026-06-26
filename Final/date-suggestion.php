<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'db_config.php';

// 1. Get current task data from URL parameters
$subject  = $_GET['subject'] ?? 'Subject';
$title    = $_GET['title'] ?? 'New Task';
$pages    = (int)($_GET['pages'] ?? 1);
$user_diff = (int)($_GET['diff'] ?? 5);
$weight   = (float)($_GET['weight'] ?? 10);
$class_id = (int)($_GET['class_id'] ?? 3);
$given    = $_GET['given'] ?? date('Y-m-d'); 
$original_deadline = $_GET['deadline'] ?? date('Y-m-d');

// 2. Constants for the Stress Engine [cite: 116, 492]
$p_max = 30;             // Normalization factor for pages
$alpha = 0.50;           // Holiday relief weight
$standard_window = 7;    // Base preparation time in days
$delta = 1.0;            // Multiplier (1.0 for Theory, 0.3 for Practical) [cite: 113, 236]
$d_val = $user_diff / 10; // Normalized difficulty [cite: 106, 235]

$suggestions = [];
$test_deadline = $original_deadline;
$found = 0;
$safety_break = 0; // Prevents infinite loops [cite: 583]

// 3. THE SEARCH LOOP [cite: 31, 165]
// This runs until 3 safe dates are found or 60 days have passed
while ($found < 3 && $safety_break < 90) { // Increased safety break to 90 days [cite: 583]
    $safety_break++;
    $test_deadline = date('Y-m-d', strtotime("$test_deadline +1 day"));
    
    // --- A. CALCULATE PREPARATION WINDOW ---
    $dateStart = new DateTime($given);
    $dateEnd   = new DateTime($test_deadline);
    $days_total = $dateStart->diff($dateEnd)->days ?: 1;

    // --- B. HOLIDAY RELIEF (H) [cite: 30, 519, 520] ---
    $H = 0;
    $tempLoop = clone $dateStart;
    
    // Fetch official holidays for this specific sliding window [cite: 149]
    $stmtH = $conn->prepare("SELECT Dates FROM academic_calendar WHERE Holidays = 'Yes' AND Dates BETWEEN ? AND ?");
    $stmtH->bind_param("ss", $given, $test_deadline);
    $stmtH->execute();
    $resH = $stmtH->get_result();
    $officialHolidays = [];
    while($hRow = $resH->fetch_assoc()) { 
        $officialHolidays[] = date('Y-m-d', strtotime($hRow['Dates'])); 
    }

    while ($tempLoop <= $dateEnd) {
        $curr = $tempLoop->format('Y-m-d');
        // Count if it is a weekend OR an official academic holiday [cite: 517, 519]
        if ($tempLoop->format('N') >= 6 || in_array($curr, $officialHolidays)) {
            $H++;
        }
        $tempLoop->modify('+1 day');
    }

    // --- C. CHECK FOR INSTITUTIONAL EXAM BLOCKERS [cite: 30, 473, 480] ---
    // The system locks IT/Exam phases to prevent scheduling 
    $stmtInst = $conn->prepare("SELECT Events FROM academic_calendar 
                                WHERE (Events LIKE '%IT%' OR Events LIKE '%ISA%' OR Events LIKE '%Exam%') 
                                AND Dates BETWEEN ? AND ?");
    $stmtInst->bind_param("ss", $given, $test_deadline);
    $stmtInst->execute();
    $is_it_phase = ($stmtInst->get_result()->num_rows > 0);

    // --- D. DYNAMIC WORKLOAD INTENSITY [cite: 114, 255] ---
    // Scan database for other tasks assigned to this class by other teachers [cite: 266]
    $total_intensity = 0;
    $stmtA = $conn->prepare("SELECT Weightage, Difficulty_Index, No_of_Pages FROM assignment WHERE Class_ID = ? AND Due_Date BETWEEN ? AND ?");
    $stmtA->bind_param("iss", $class_id, $given, $test_deadline);
    $stmtA->execute();
    $resA = $stmtA->get_result();
    while ($row = $resA->fetch_assoc()) {
        $total_intensity += ($row['Weightage'] * ($row['Difficulty_Index']/10) * ($row['No_of_Pages']/30));
    }

    // --- E. FINAL STRESS FORMULA [cite: 37, 492, 499] ---
    $current_intensity = $weight * $d_val * ($pages / $p_max) * $delta;
    $time_pressure = $standard_window / $days_total;
    $workload_multiplier = 1 + (0.05 * $total_intensity);
    $holiday_relief = 1 + ($alpha * $H);

    // Final Stress calculation [cite: 504]
    $S = ($current_intensity * $time_pressure * $workload_multiplier) / $holiday_relief;
    $final_score = round(($S / 2.0) * 100);
    $final_score = max(5, min($final_score, 100)); // Clamp between 5 and 100

    // --- F. SELECTION CRITERIA [cite: 493, 494] ---
    // If stress score < 50 and not an exam day, save the suggestion [cite: 102]
    if ($final_score < 70) {
        $suggestions[] = [
            'date' => $test_deadline,
            'score' => $final_score
        ];
        $found++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ASLB - Safe Deadline Suggestions</title>
    <style>
        body { font-family: 'Inter', sans-serif; background: #f8fafc; display: flex; justify-content: center; padding: 50px; }
        .suggestions-card { width: 100%; max-width: 650px; background: white; padding: 35px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
        h2 { color: #1e293b; margin-bottom: 25px; letter-spacing: -0.02em; }
        table { width: 100%; border-collapse: collapse; }
        th { text-align: left; padding: 15px; border-bottom: 2px solid #e2e8f0; color: #64748b; font-size: 0.8rem; text-transform: uppercase; }
        td { padding: 20px 15px; border-bottom: 1px solid #f1f5f9; }
        .score-badge { color: #15803d; font-weight: 700; background: #dcfce7; padding: 6px 10px; border-radius: 8px; font-size: 0.9rem; }
        .select-btn { background: #6366f1; color: white; border: none; padding: 10px 20px; border-radius: 10px; font-weight: 600; cursor: pointer; transition: all 0.2s; }
        .select-btn:hover { background: #4f46e5; transform: translateY(-2px); box-shadow: 0 5px 15px rgba(99, 102, 241, 0.3); }
        .empty-state { text-align: center; color: #64748b; padding: 50px 0; }
    </style>
</head>
<body>
    <div class="suggestions-card">
        <h2>Safe Alternatives for <?php echo htmlspecialchars($subject); ?></h2>
        
        <?php if (empty($suggestions)): ?>
            <div class="empty-state">
                <p>No safe dates found in the next 60 days. The current academic workload is too high.</p>
                <button onclick="window.history.back();" class="select-btn" style="margin-top: 20px;">Return to Dashboard</button>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>Recommended Date</th>
                        <th>Stress Score</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($suggestions as $s): ?>
                    <tr>
                        <td><strong><?php echo date('D, M d, Y', strtotime($s['date'])); ?></strong></td>
                        <td><span class="score-badge"><?php echo $s['score']; ?>/100</span></td>
                        <td>
                            <form action="confirm_assignment.php" method="POST">
                                <input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>">
                                <input type="hidden" name="task_title" value="<?php echo htmlspecialchars($title); ?>">
                                <input type="hidden" name="deadline" value="<?php echo $s['date']; ?>">
                                <input type="hidden" name="given_date" value="<?php echo $given; ?>">
                                <input type="hidden" name="class_id" value="<?php echo $class_id; ?>">
                                <input type="hidden" name="pages" value="<?php echo $pages; ?>">
                                <input type="hidden" name="diff" value="<?php echo $user_diff; ?>">
                                <input type="hidden" name="weight" value="<?php echo $weight; ?>">
                                <button type="submit" class="select-btn">Select This Date</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>