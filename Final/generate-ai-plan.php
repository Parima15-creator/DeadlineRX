<?php
session_start();
require_once 'db_config.php';
require_once 'gemini_config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['student_name'])) {
    echo json_encode(['success' => false, 'message' => 'Student not logged in']);
    exit();
}

$studentEmail = $_SESSION['student_email'] ?? '';
$studentName = $_SESSION['student_name'] ?? 'Student';

$input = json_decode(file_get_contents('php://input'), true);
$tasks = $input['tasks'] ?? [];
$availableHoursToday = (float)($input['available_hours_today'] ?? 3);
$extraContext = trim($input['extra_ai_context'] ?? '');

if (empty($tasks)) {
    echo json_encode(['success' => false, 'message' => 'No tasks found for planning']);
    exit();
}

function localFallbackPlan($tasks, $availableHoursToday, $extraContext = '') {
    $now = new DateTime();

    foreach ($tasks as &$task) {
        $deadline = $task['deadline'] ?? '';
        $riskScore = (int)($task['risk']['score'] ?? 0);
        $weightage = (float)($task['weightage'] ?? 0);
        $difficulty = (int)($task['difficulty'] ?? 5);
        $description = strtolower(($task['description'] ?? '') . ' ' . ($task['title'] ?? '') . ' ' . $extraContext);

        $deadlineBoost = 0;
        if ($deadline) {
            $deadlineObj = new DateTime($deadline . ' 23:59:59');
            $daysLeft = floor(($deadlineObj->getTimestamp() - $now->getTimestamp()) / 86400);

            if ($daysLeft < 0) {
                $deadlineBoost = 80;
            } elseif ($daysLeft == 0) {
                $deadlineBoost = 70;
            } elseif ($daysLeft == 1) {
                $deadlineBoost = 60;
            } elseif ($daysLeft <= 2) {
                $deadlineBoost = 45;
            } elseif ($daysLeft <= 7) {
                $deadlineBoost = 25;
            }
        }

        $contextBoost = 0;

        $urgentWords = [
            'tomorrow', 'today', 'urgent', 'exhibition', 'display',
            'presentation', 'viva', 'submit', 'strict', 'marks',
            'deadline', 'important', 'competition'
        ];

        foreach ($urgentWords as $word) {
            if (str_contains($description, $word)) {
                $contextBoost += 12;
            }
        }

        if (($task['task_type'] ?? '') === 'personal' && $contextBoost > 0) {
            $contextBoost += 20;
        }

        $task['priority_score'] =
            $riskScore +
            $deadlineBoost +
            $contextBoost +
            ($weightage * 1.5) +
            ($difficulty * 2);
    }
    unset($task);

    usort($tasks, function($a, $b) {
        $aDone = (int)($a['is_completed'] ?? 0);
        $bDone = (int)($b['is_completed'] ?? 0);

        if ($aDone !== $bDone) return $aDone - $bDone;

        return ($b['priority_score'] ?? 0) <=> ($a['priority_score'] ?? 0);
    });

    $pending = array_filter($tasks, function($task) {
        return (int)($task['is_completed'] ?? 0) !== 1;
    });

    if (empty($pending)) {
        return "All tasks are marked completed. Use today for revision, checking submissions, and preparing for upcoming tests.";
    }

    $plan = "DeadlineRX Rescue Plan\n\n";
    $plan .= "Available time today: {$availableHoursToday} hours\n\n";

    if ($extraContext !== '') {
        $plan .= "Student context considered: {$extraContext}\n\n";
    }

    $plan .= "Priority Order:\n";

    $count = 1;
    foreach ($pending as $task) {
        $risk = $task['risk']['level'] ?? 'Unknown';
        $source = ($task['task_type'] ?? '') === 'personal' ? 'Personal Task' : 'Teacher Task';
        $plan .= "{$count}. {$task['title']} ({$source}) - {$risk}, due {$task['deadline']}\n";
        $count++;
    }

    $plan .= "\nSuggested Plan:\n";

    $remaining = $availableHoursToday;
    foreach ($pending as $task) {
        if ($remaining <= 0) break;

        $hours = (float)($task['estimated_hours_left'] ?? 0);
        if ($hours <= 0) {
            $hours = (float)($task['risk']['estimated_hours_left'] ?? 1);
        }

        $block = min($remaining, max(0.5, $hours));

        $title = $task['title'];
        $description = strtolower($task['description'] ?? '');

        if (str_contains($description, 'exhibition') || str_contains($description, 'display')) {
            $plan .= "- Spend about {$block} hour(s) on {$title} first because it is needed for exhibition/display and has a real-world deadline.\n";
        } else {
            $plan .= "- Spend about {$block} hour(s) on {$title}. Focus on the minimum submit-worthy version first.\n";
        }

        $remaining -= $block;
    }

    $plan .= "\nDamage-control advice:\n";
    $plan .= "If everything cannot be completed, do the task with the nearest real consequence first. Personal event deadlines like exhibition/display should not be ignored just because they are not teacher-given. Then complete the minimum required version of academic submissions.";

    return $plan;
}

function callGemini($prompt) {
    if (GEMINI_API_KEY === 'PASTE_YOUR_GEMINI_API_KEY_HERE' || trim(GEMINI_API_KEY) === '') {
        return null;
    }

    $url = "https://generativelanguage.googleapis.com/v1beta/models/" . GEMINI_MODEL . ":generateContent?key=" . urlencode(GEMINI_API_KEY);

    $payload = [
        "contents" => [
            [
                "parts" => [
                    ["text" => $prompt]
                ]
            ]
        ],
        "generationConfig" => [
            "temperature" => 0.4,
            "maxOutputTokens" => 1200
        ]
    ];

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 30
    ]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        curl_close($ch);
        return null;
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($status < 200 || $status >= 300) {
        return null;
    }

    $data = json_decode($response, true);

    return $data['candidates'][0]['content']['parts'][0]['text'] ?? null;
}

$taskSummary = [];
foreach ($tasks as $task) {
    $taskSummary[] = [
    'type' => $task['task_type'] ?? '',
    'source' => $task['source'] ?? '',
    'title' => $task['title'] ?? '',
    'subject' => $task['subject'] ?? '',
    'deadline' => $task['deadline'] ?? '',
    'difficulty' => $task['difficulty'] ?? '',
    'weightage' => $task['weightage'] ?? '',
    'pages' => $task['pages'] ?? '',
    'description' => $task['description'] ?? '',
    'completion_percentage' => $task['completion_percentage'] ?? 0,
    'estimated_hours_left' => $task['estimated_hours_left'] ?? 0,
    'risk_level' => $task['risk']['level'] ?? '',
    'risk_score' => $task['risk']['score'] ?? ''
];
}

$prompt = "
You are DeadlineRX, an academic deadline rescue assistant for students.

Student name: {$studentName}
Available study/work time today: {$availableHoursToday} hours.

Extra student context:
{$extraContext}

Tasks:
" . json_encode($taskSummary, JSON_PRETTY_PRINT) . "

Create a practical last-minute rescue plan.

Rules:
1. Prioritize tasks by deadline closeness, risk score, weightage, and pending work.
2. Consider whether the task is teacher-given or student personal.
3. Consider the student's extra context seriously.
4. If the teacher is strict, prioritize avoiding late submission.
5. If late submission is acceptable, balance marks, test preparation, and workload realistically.
6. Give a clear priority order.
7. Give a time-block plan for today.
8. Mention what minimum version should be completed first.
9. Mention what can wait.
10. If everything cannot be completed, be honest and suggest damage-control.
11. Keep tone supportive but realistic.
12. Do not give generic motivation. Give actionable steps.
13. Personal tasks can be more urgent than teacher tasks if they have a real-world event deadline.
14. If a personal task mentions exhibition, display, competition, presentation, or tomorrow, treat it as high priority.
15. Do not blindly prioritize only academic assignments. Explain why a personal task may come first.
";

$planText = callGemini($prompt);

if (!$planText) {
    $planText = localFallbackPlan($tasks, $availableHoursToday, $extraContext);
}

if ($studentEmail) {
    $stmt = $conn->prepare("INSERT INTO ai_plans (student_email, plan_text) VALUES (?, ?)");
    if ($stmt) {
        $stmt->bind_param("ss", $studentEmail, $planText);
        $stmt->execute();
        $stmt->close();
    }
}

echo json_encode([
    'success' => true,
    'plan' => $planText
]);
?>