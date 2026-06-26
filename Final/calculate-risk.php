<?php
function calculateDeadlineRisk($task) {
    $deadline = $task['deadline'] ?? null;
    $difficulty = (int)($task['difficulty'] ?? 5);
    $weightage = (float)($task['weightage'] ?? 0);
    $pages = (int)($task['pages'] ?? 0);
    $completion = (int)($task['completion_percentage'] ?? 0);
    $estimatedHoursLeft = (float)($task['estimated_hours_left'] ?? 0);
    $taskType = $task['task_type'] ?? 'assignment';

    if (!$deadline) {
        return [
            'score' => 0,
            'level' => 'Unknown',
            'reason' => 'No deadline available.'
        ];
    }

    $now = new DateTime();
    $deadlineObj = new DateTime($deadline . ' 23:59:59');

    if ($deadlineObj < $now) {
        return [
            'score' => 100,
            'level' => 'Overdue',
            'reason' => 'The deadline has already passed.'
        ];
    }

    $hoursLeft = max(0.1, ($deadlineObj->getTimestamp() - $now->getTimestamp()) / 3600);
    $pendingPercent = max(0, 100 - $completion);

    if ($estimatedHoursLeft <= 0) {
        if ($taskType === 'test') {
            $estimatedHoursLeft = max(1, ($difficulty * 0.6) + ($weightage * 0.08));
        } else {
            $estimatedHoursLeft = max(1, ($pages * 0.35) + ($difficulty * 0.35));
        }

        $estimatedHoursLeft = $estimatedHoursLeft * ($pendingPercent / 100);
    }

    $score = 0;
    $reasons = [];

    if ($hoursLeft <= 6) {
        $score += 40;
        $reasons[] = "Very little time left";
    } elseif ($hoursLeft <= 24) {
        $score += 30;
        $reasons[] = "Deadline is within 24 hours";
    } elseif ($hoursLeft <= 48) {
        $score += 20;
        $reasons[] = "Deadline is within 2 days";
    } elseif ($hoursLeft <= 120) {
        $score += 10;
        $reasons[] = "Deadline is this week";
    }

    if ($pendingPercent >= 80) {
        $score += 25;
        $reasons[] = "Most of the task is still pending";
    } elseif ($pendingPercent >= 50) {
        $score += 18;
        $reasons[] = "More than half of the task is pending";
    } elseif ($pendingPercent >= 25) {
        $score += 10;
        $reasons[] = "Some work is still pending";
    }

    if ($difficulty >= 8) {
        $score += 15;
        $reasons[] = "High difficulty task";
    } elseif ($difficulty >= 5) {
        $score += 8;
        $reasons[] = "Medium difficulty task";
    }

    if ($weightage >= 15) {
        $score += 10;
        $reasons[] = "High weightage task";
    } elseif ($weightage >= 8) {
        $score += 5;
        $reasons[] = "Moderate weightage task";
    }

    if ($estimatedHoursLeft > ($hoursLeft * 0.6)) {
        $score += 10;
        $reasons[] = "Work left is high compared to time left";
    }

    if ($completion >= 100) {
        $score = 0;
        $reasons = ["Task is already completed"];
    }

    $score = min(100, max(0, round($score)));

    if ($score >= 85) {
        $level = "Critical";
    } elseif ($score >= 65) {
        $level = "High Risk";
    } elseif ($score >= 35) {
        $level = "Warning";
    } else {
        $level = "Safe";
    }

    return [
        'score' => $score,
        'level' => $level,
        'reason' => implode(", ", $reasons),
        'hours_left' => round($hoursLeft, 1),
        'estimated_hours_left' => round($estimatedHoursLeft, 1)
    ];
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    header('Content-Type: application/json');

    $input = json_decode(file_get_contents('php://input'), true);
    if (!$input) {
        echo json_encode(['success' => false, 'message' => 'No input received']);
        exit();
    }

    echo json_encode([
        'success' => true,
        'risk' => calculateDeadlineRisk($input)
    ]);
}
?>