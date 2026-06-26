<?php
// 1. Catch data from process-analysis.php via GET (URL)
$score    = isset($_GET['score']) ? (int)$_GET['score'] : 0;
$subject  = $_GET['subject'] ?? 'Not Specified';
$deadline = $_GET['deadline'] ?? '';
$diff     = $_GET['diff'] ?? 0;
$pages    = $_GET['pages'] ?? 0;
$title = $_GET['title'] ?? 'New Task';
// 2. Catch the extra fields for the database
$given    = $_GET['given'] ?? date('Y-m-d');
$category = $_GET['category'] ?? 'Theory';
$weight   = $_GET['weight'] ?? 0;
$class_id = $_GET['class_id'] ?? 3;
$isHigh = ($score >= 65);
$holidays = $_GET['holidays'] ?? 0;
$workload = $_GET['workload'] ?? 0;
$days     = $_GET['days'] ?? 1;
$intensity = $_GET['intensity'] ?? 0;
$num_assignments = $_GET['num_assignments'] ?? 0;
$num_tests       = $_GET['num_tests'] ?? 0;
$it_exam         = $_GET['it_exam'] ?? 'No';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>ASLB - Analysis Result</title>
    <link rel="stylesheet" href="AnalysisResult.css">
    <style>
        .progress-fill { 
            /* Cap the width at 100% to prevent UI breaking */
            width: <?php echo min($score, 100); ?>%; 
            background: <?php echo $isHigh ? '#ff4d4d' : '#4CAF50'; ?>; 
            height: 100%;
            border-radius: 8px;
            transition: width 0.5s ease-in-out;
        }

        /* Base style for both buttons - Matches the "Go Back" look */
        .confirm-btn, .secondary-btn {
            flex: 1;
            padding: 12px;
            border: 1px solid #e2e8f0; 
            border-radius: 8px;
            cursor: pointer;
            background: #ffffff; 
            color: #475569; 
            font-weight: 600;
            transition: all 0.3s ease;
            text-align: center;
            font-family: inherit;
            text-decoration: none;
            display: inline-block;
            font-size: 1rem;
            width: 100%;
        }

        /* Hover state: turn blue with white text */
        .confirm-btn:hover, .secondary-btn:hover {
            background: #6366f1; 
            color: #ffffff; 
            border-color: #6366f1;
        }

        .button-group {
            display: flex;
            gap: 15px;
            margin-top: 25px;
        }

        form {
            flex: 1;
        }
    </style>
</head>
<body>
    <div class="analysis-card" style="max-width: 600px; margin: 50px auto; padding: 30px; background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
        <h2>Stress Score: <?php echo $score; ?>/100</h2>
        <div class="progress-bar" style="background: #eee; height: 20px; border-radius: 10px; overflow: hidden; margin-bottom: 20px;">
            <div class="progress-fill"></div>
        </div>

        <div class="justification-list">
            <h4>Analysis Details for <?php echo htmlspecialchars($subject); ?>:</h4>
            <ul>
    <li>Preparation Window: <strong><?php echo htmlspecialchars($days); ?> Days</strong></li>
    <li>Holidays/Weekends in Period: <strong><?php echo htmlspecialchars($holidays); ?> Days Off</strong></li>
    
    <hr style="border: 0; border-top: 1px solid #eee; margin: 10px 0;">
    
    <li>Existing Assignments: <strong><?php echo $num_assignments; ?></strong></li>
    <li>Existing Theory Tests: <strong><?php echo $num_tests; ?></strong></li>
    <li>IT Exam Phase: <strong style="color: <?php echo ($it_exam === 'Yes') ? '#b91c1c' : 'inherit'; ?>;">
        <?php echo $it_exam; ?>
    </strong></li>
    
    <li>Overall Workload Multiplier: <strong><?php echo htmlspecialchars($intensity); ?></strong></li>
</ul>
        </div>

        <div class="suggestion-alert <?php echo $isHigh ? 'high-stress' : 'low-stress'; ?>" 
             style="padding: 15px; border-radius: 8px; margin: 20px 0; background: <?php echo $isHigh ? '#fee2e2' : '#dcfce7'; ?>; color: <?php echo $isHigh ? '#b91c1c' : '#15803d'; ?>; font-weight: 500;">
            <?php echo $isHigh ? "⚠️ High risk of burnout! You must select an alternative date." : "✅ Workload looks good. You may proceed."; ?>
        </div>

        <div class="button-group">
            <form action="confirm_assignment.php" method="POST">
                <input type="hidden" name="subject" value="<?php echo htmlspecialchars($subject); ?>">
                <input type="hidden" name="title" value="<?php echo htmlspecialchars($subject); ?>"> <input type="hidden" name="given_date" value="<?php echo htmlspecialchars($given); ?>">
                <input type="hidden" name="task_title" value="<?php echo htmlspecialchars($title); ?>">
                <input type="hidden" name="category" value="<?php echo htmlspecialchars($category); ?>">
                <input type="hidden" name="weight" value="<?php echo htmlspecialchars($weight); ?>">
                <input type="hidden" name="class_id" value="<?php echo htmlspecialchars($class_id); ?>">
                <input type="hidden" name="deadline" value="<?php echo htmlspecialchars($deadline); ?>">
                <input type="hidden" name="diff" value="<?php echo htmlspecialchars($diff); ?>">
                <input type="hidden" name="pages" value="<?php echo htmlspecialchars($pages); ?>">
                
                <?php if (!$isHigh): ?>
                    <button type="submit" class="confirm-btn">Confirm Deadline</button>
                <?php else: ?>
                    <?php 
                        $suggest_params = [
                            'subject'  => $subject,
                            'title'    => $title,
                            'pages'    => $pages,
                            'diff'     => $diff,
                            'weight'   => $weight,
                            'class_id' => $class_id,
                            'given'    => $given,
                            'deadline' => $deadline
                        ];
                    ?>
                    <button type="button" class="confirm-btn" 
                            onclick="window.location.href='date-suggestion.php?<?php echo http_build_query($suggest_params); ?>'">
                        View Suggested Dates
                    </button>
                <?php endif; ?>
            </form>
            
            <button class="secondary-btn" onclick="window.history.back();">
                Go Back
            </button>
        </div>
    </div>
</body>
</html>