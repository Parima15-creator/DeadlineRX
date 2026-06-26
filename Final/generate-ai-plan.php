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

function localFallbackPlan($tasks, $availableHoursToday) {
    usort($tasks, function($a, $b) {
        $aDone = (int)($a['is_completed'] ?? 0);
        $bDone = (int)($b['is_completed'] ?? 0);

        if ($aDone !== $bDone) return $aDone - $bDone;

        $riskA = $a['risk']['score'] ?? 0;
        $riskB = $b['risk']['score'] ?? 0;

        if ($riskA !== $riskB) return $riskB - $riskA;

        return strcmp($a['deadline'] ?? '', $b['deadline'] ?? '');
    });

    $pending = array_filter($tasks, function($task) {
        return (int)($task['is_completed'] ?? 0) !== 1;
    });

    if (empty($pending)) {
        return "All tasks are marked completed. Use today for revision, checking submissions, and preparing for upcoming tests.";
    }

    $plan = "DeadlineRX Rescue Plan\n\n";
    $plan .= "Available time today: {$availableHoursToday} hours\n\n";
    $plan .= "Priority Order:\n";

    $count = 1;
    foreach ($pending as $task) {
        $risk = $task['risk']['level'] ?? 'Unknown';
        $plan .= "{$count}. {$task['title']} ({$task['subject']}) - {$risk}, due {$task['deadline']}\n";
        $count++;
    }

    $plan .= "\nSuggested Plan:\n";

    $remaining = $availableHoursToday;
    foreach ($pending as $task) {
        if ($remaining <= 0) break;

        $hours = (float)($task['risk']['estimated_hours_left'] ?? $task['estimated_hours_left'] ?? 1);
        $block = min($remaining, max(0.5, $hours));

        $plan .= "- Spend about {$block} hour(s) on {$task['title']}. Focus on the minimum submit-worthy version first.\n";
        $remaining -= $block;
    }

    $plan .= "\nImportant: If any task cannot be fully completed, finish the highest-mark and closest-deadline parts first. Avoid spending time on decoration or formatting until the core answers/code/content are done.";

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
        'title' => $task['title'] ?? '',
        'subject' => $task['subject'] ?? '',
        'deadline' => $task['deadline'] ?? '',
        'difficulty' => $task['difficulty'] ?? '',
        'weightage' => $task['weightage'] ?? '',
        'pages' => $task['pages'] ?? '',
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
";

$planText = callGemini($prompt);

if (!$planText) {
    $planText = localFallbackPlan($tasks, $availableHoursToday);
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