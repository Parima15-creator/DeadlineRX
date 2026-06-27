<?php
session_start();
require_once 'gemini_config.php';
require_once 'db_config.php';

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode([
        'success' => false,
        'message' => 'No input received'
    ]);
    exit();
}

$tasks = $input['tasks'] ?? [];
$availableHoursToday = (float)($input['available_hours_today'] ?? 3);
$extraContext = trim($input['extra_ai_context'] ?? '');
$studentName = $_SESSION['student_name'] ?? 'Student';
$studentEmail = $_SESSION['student_email'] ?? '';

$availableHoursToday = max(0.5, min(12, $availableHoursToday));

$pendingTasks = array_values(array_filter($tasks, function ($task) {
    return (int)($task['is_completed'] ?? 0) !== 1;
}));

if (empty($pendingTasks)) {
    echo json_encode([
        'success' => true,
        'plan' => "TODAY'S FOCUS:\nAll tasks are completed.\n\nTIME-BLOCK PLAN:\nUse today for revision, checking submissions, and preparing for upcoming tests.\n\nDAMAGE CONTROL:\nNo damage control needed."
    ]);
    exit();
}

$taskSummary = [];

foreach ($pendingTasks as $task) {
    $taskSummary[] = [
        'type' => $task['task_type'] ?? '',
        'source' => $task['source'] ?? '',
        'title' => $task['title'] ?? '',
        'subject' => $task['subject'] ?? '',
        'deadline' => $task['deadline'] ?? '',
        'difficulty' => $task['difficulty'] ?? '',
        'weightage' => $task['weightage'] ?? '',
        'description' => $task['description'] ?? '',
        'completion_percentage' => $task['completion_percentage'] ?? 0,
        'estimated_work_left_hours' => $task['estimated_hours_left'] ?? 0,
        'risk_level' => $task['risk']['level'] ?? '',
        'risk_score' => $task['risk']['score'] ?? ''
    ];
}

$prompt = "
You are DeadlineRX, an academic rescue planner for students.

Student: {$studentName}
Available time today: {$availableHoursToday} hours

Extra student context:
{$extraContext}

Tasks:
" . json_encode($taskSummary, JSON_PRETTY_PRINT) . "

Create a smart rescue plan.

Important rules:
1. Do NOT spend all available time on only one task unless it is the only urgent task.
2. Personal tasks can be more urgent than academic tasks if they have real-world deadlines like exhibition, display, competition, presentation, tomorrow, or today.
3. Teacher-given tasks matter because they affect marks, but personal event deadlines should not be ignored.
4. Consider deadline, progress, estimated work left, difficulty, weightage, risk score, and student context.
5. Allocate time realistically across the top tasks.
6. Give minimum-version strategy, not generic motivation.
7. If everything cannot be completed, clearly say what should be done first and what can wait.
8. Keep the tone supportive but direct.

Output exactly in this format:

TODAY'S FOCUS:
[1-2 lines]

WHY THIS ORDER:
[short reasoning]

PRIORITY ORDER:
1. [task] - [reason]
2. [task] - [reason]

TIME-BLOCK PLAN:
- [time duration] - [task] - [specific action]
- [time duration] - [task] - [specific action]

MINIMUM VERSION:
- [task] - [what minimum version should be finished first]

CAN WAIT:
- [what can be delayed/skipped]

DAMAGE CONTROL:
[honest advice if time is not enough]
";

$aiPlan = callGemini($prompt);

if ($aiPlan) {
    saveAiPlan($conn, $studentEmail, $aiPlan);

    echo json_encode([
        'success' => true,
        'plan' => $aiPlan
    ]);
    exit();
}

$fallbackPlan = smartFallbackPlan($pendingTasks, $availableHoursToday, $extraContext);

saveAiPlan($conn, $studentEmail, $fallbackPlan);

echo json_encode([
    'success' => true,
    'plan' => $fallbackPlan
]);
exit();


function callGemini($prompt) {
    if (!defined('GEMINI_API_KEY') || GEMINI_API_KEY === '' || GEMINI_API_KEY === 'PASTE_YOUR_GEMINI_API_KEY_HERE') {
        return null;
    }

    $model = defined('GEMINI_MODEL') ? GEMINI_MODEL : 'gemini-2.0-flash';

    $url = "https://generativelanguage.googleapis.com/v1beta/models/" . $model . ":generateContent?key=" . GEMINI_API_KEY;

    $payload = [
        'contents' => [
            [
                'parts' => [
                    ['text' => $prompt]
                ]
            ]
        ],
        'generationConfig' => [
            'temperature' => 0.45,
            'topP' => 0.9,
            'maxOutputTokens' => 1200
        ]
    ];

    $ch = curl_init($url);

    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_POSTFIELDS => json_encode($payload),
        CURLOPT_TIMEOUT => 25
    ]);

    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error || !$response) {
        return null;
    }

    $data = json_decode($response, true);

    if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
        return trim($data['candidates'][0]['content']['parts'][0]['text']);
    }

    return null;
}


function smartFallbackPlan($tasks, $availableHoursToday, $extraContext = '') {
    $now = new DateTime();

    foreach ($tasks as &$task) {
        $deadline = $task['deadline'] ?? '';
        $riskScore = (int)($task['risk']['score'] ?? 0);
        $weightage = (float)($task['weightage'] ?? 0);
        $difficulty = (int)($task['difficulty'] ?? 5);
        $completion = (int)($task['completion_percentage'] ?? 0);
        $hoursLeft = (float)($task['estimated_hours_left'] ?? 0);

        if ($hoursLeft <= 0) {
            $hoursLeft = max(1, 8 - ($completion / 15));
        }

        $text = strtolower(
            ($task['title'] ?? '') . ' ' .
            ($task['subject'] ?? '') . ' ' .
            ($task['description'] ?? '') . ' ' .
            $extraContext
        );

        $daysLeft = 999;

        if ($deadline) {
            $deadlineObj = new DateTime($deadline . ' 23:59:59');
            $secondsLeft = $deadlineObj->getTimestamp() - $now->getTimestamp();
            $daysLeft = floor($secondsLeft / 86400);
        }

        $deadlineBoost = 0;

        if ($daysLeft < 0) {
            $deadlineBoost = 90;
        } elseif ($daysLeft == 0) {
            $deadlineBoost = 80;
        } elseif ($daysLeft == 1) {
            $deadlineBoost = 70;
        } elseif ($daysLeft <= 2) {
            $deadlineBoost = 55;
        } elseif ($daysLeft <= 7) {
            $deadlineBoost = 30;
        }

        $contextBoost = 0;
        $urgentWords = ['today', 'tomorrow', 'urgent', 'exhibition', 'display', 'competition', 'presentation', 'viva', 'strict', 'marks', 'submit'];

        foreach ($urgentWords as $word) {
            if (strpos($text, $word) !== false) {
                $contextBoost += 12;
            }
        }

        if (($task['task_type'] ?? '') === 'personal' && $contextBoost > 0) {
            $contextBoost += 25;
        }

        $pendingBoost = 100 - $completion;

        $task['computed_hours_left'] = $hoursLeft;
        $task['days_left'] = $daysLeft;
        $task['priority_score'] =
            $riskScore +
            $deadlineBoost +
            $contextBoost +
            ($difficulty * 3) +
            ($weightage * 1.5) +
            ($pendingBoost * 0.35);
    }

    unset($task);

    usort($tasks, function ($a, $b) {
        return ($b['priority_score'] ?? 0) <=> ($a['priority_score'] ?? 0);
    });

    $topTask = $tasks[0];
    $topTitle = $topTask['title'] ?? 'Top task';

    $plan = "TODAY'S FOCUS:\n";
    $plan .= "{$topTitle} should be handled first because it has the strongest combination of deadline pressure, context urgency, and remaining work.\n\n";

    $plan .= "WHY THIS ORDER:\n";
    $plan .= "DeadlineRX considered deadline closeness, progress, work hours left, difficulty, marks/weightage, and your extra context. Personal tasks with real event deadlines are treated seriously, not ignored.\n\n";

    $plan .= "PRIORITY ORDER:\n";

    $i = 1;
    foreach ($tasks as $task) {
        $source = (($task['task_type'] ?? '') === 'personal') ? 'Personal Task' : 'Teacher Task';
        $reason = makeReason($task);
        $plan .= "{$i}. {$task['title']} ({$source}) - {$reason}\n";
        $i++;
    }

    $plan .= "\nTIME-BLOCK PLAN:\n";

    $remaining = $availableHoursToday;
    $blocks = allocateTimeBlocks($tasks, $availableHoursToday);

    foreach ($blocks as $block) {
        $plan .= "- {$block['hours']} hour(s) - {$block['title']} - {$block['action']}\n";
        $remaining -= $block['hours'];
    }

    if ($remaining > 0.25) {
        $plan .= "- {$remaining} hour(s) - Buffer - Review, upload/check files, and fix mistakes.\n";
    }

    $plan .= "\nMINIMUM VERSION:\n";

    foreach (array_slice($tasks, 0, min(3, count($tasks))) as $task) {
        $plan .= "- {$task['title']} - " . minimumVersion($task) . "\n";
    }

    $plan .= "\nCAN WAIT:\n";
    if (count($tasks) > 2) {
        $lastTask = end($tasks);
        $plan .= "- Extra polishing or beautification of {$lastTask['title']} can wait until the core work is done.\n";
    } else {
        $plan .= "- Decoration, formatting, and perfection can wait until the core submission/display work is ready.\n";
    }

    $plan .= "\nDAMAGE CONTROL:\n";
    $plan .= "If time runs short, finish the task with the nearest real consequence first, then complete the minimum submit-worthy version of academic work. Do not spend too much time making things perfect before the core task is ready.";

    return $plan;
}


function allocateTimeBlocks($tasks, $availableHoursToday) {
    $blocks = [];
    $remaining = $availableHoursToday;
    $taskCount = count($tasks);

    foreach ($tasks as $index => $task) {
        if ($remaining <= 0.25) {
            break;
        }

        $hoursLeft = (float)($task['computed_hours_left'] ?? $task['estimated_hours_left'] ?? 1);
        $title = $task['title'] ?? 'Task';

        if ($taskCount === 1) {
            $hours = min($remaining, $hoursLeft);
        } else {
            if ($index === 0) {
                $maxShare = $availableHoursToday * 0.50;
                $hours = min($hoursLeft, max(1, $maxShare));
            } elseif ($index === 1) {
                $maxShare = $availableHoursToday * 0.32;
                $hours = min($hoursLeft, max(0.75, $maxShare));
            } else {
                $hours = min($hoursLeft, max(0.5, $remaining));
            }

            $hours = min($hours, $remaining);
        }

        $hours = round($hours * 2) / 2;

        if ($hours <= 0) {
            continue;
        }

        $blocks[] = [
            'title' => $title,
            'hours' => $hours,
            'action' => actionForTask($task)
        ];

        $remaining -= $hours;
    }

    return $blocks;
}


function makeReason($task) {
    $daysLeft = (int)($task['days_left'] ?? 999);
    $completion = (int)($task['completion_percentage'] ?? 0);
    $text = strtolower(($task['description'] ?? '') . ' ' . ($task['title'] ?? ''));

    if (strpos($text, 'exhibition') !== false || strpos($text, 'display') !== false) {
        return "real-world exhibition/display deadline, due " . ($task['deadline'] ?? '');
    }

    if ($daysLeft <= 1) {
        return "very close deadline, due " . ($task['deadline'] ?? '');
    }

    if ($completion < 50) {
        return "more than half is still pending, due " . ($task['deadline'] ?? '');
    }

    return "needs planned work, due " . ($task['deadline'] ?? '');
}


function actionForTask($task) {
    $text = strtolower(($task['description'] ?? '') . ' ' . ($task['title'] ?? ''));

    if (strpos($text, 'exhibition') !== false || strpos($text, 'display') !== false || strpos($text, 'painting') !== false) {
        return "finish the display-ready version first, then only do touch-ups if time remains.";
    }

    if (($task['task_type'] ?? '') === 'test') {
        return "revise high-weight concepts first, then solve quick practice questions.";
    }

    return "complete the minimum submit-worthy version first before formatting or extra decoration.";
}


function minimumVersion($task) {
    $text = strtolower(($task['description'] ?? '') . ' ' . ($task['title'] ?? ''));

    if (strpos($text, 'painting') !== false || strpos($text, 'exhibition') !== false || strpos($text, 'display') !== false) {
        return "make it clean enough for display, even if final decoration is not perfect.";
    }

    if (($task['task_type'] ?? '') === 'test') {
        return "revise important topics and formulas first instead of trying to cover everything equally.";
    }

    return "finish the required content/code/answers first, then format only if time remains.";
}

function saveAiPlan($conn, $studentEmail, $planText) {
    if (!$studentEmail || trim($planText) === '') {
        return;
    }

    $stmt = $conn->prepare("
        INSERT INTO ai_plans (student_email, plan_text)
        VALUES (?, ?)
    ");

    if (!$stmt) {
        return;
    }

    $stmt->bind_param("ss", $studentEmail, $planText);
    $stmt->execute();
    $stmt->close();
}
?>
