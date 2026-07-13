<?php
session_start();

if (!isset($_SESSION['student_name'])) {
    header("Location: Login/student-login.html");
    exit();
}

$name = ucwords(strtolower($_SESSION['student_name']));
$dept = $_SESSION['dept_name'] ?? '';
$class = $_SESSION['class_name'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard — DeadlineRX System</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="global-font.css">
  <link rel="stylesheet" href="student-dashboard.css">
</head>

<body>
  <aside class="sidebar">
    <div class="logo-container">
      <div class="logo-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/>
        </svg>
      </div>

      <span style="font-weight: 700; font-size: 1.1rem;">DeadlineRX System</span>
    </div>

    <nav class="nav-group">
      <a href="javascript:void(0)" id="btn-show-calendar" class="nav-button">Calendar View</a>
      <a href="javascript:void(0)" id="btn-show-assignments" class="nav-button active">Add Tasks</a>
      <a href="javascript:void(0)" id="btn-show-my-tasks" class="nav-button">My Tasks</a>
    </nav>

    <div class="logout-container">
      <a href="index.html" class="nav-button logout-button">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none"
             stroke="currentColor" stroke-width="2">
          <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
          <polyline points="16 17 21 12 16 7"/>
          <line x1="21" x2="9" y1="12" y2="12"/>
        </svg>
        Logout
      </a>
    </div>
  </aside>

  <main class="main-viewport">

    <!-- ASSIGNMENTS & TESTS VIEW -->
    <div id="view-assignments" class="dashboard-view">
      <header class="header">
        <div>
          <h1 style="font-size: 1.8rem; font-weight: 700; text-transform: capitalize;">
            Hello, <?php echo htmlspecialchars($name); ?> 👋🏻
          </h1>

          <p style="color: var(--aslb-muted); margin-top: 14px;">
            <?php echo htmlspecialchars($class); ?> | <?php echo htmlspecialchars($dept); ?>
          </p>
        </div>
      </header>

      <div class="content-grid">
        <section class="tile">
          <h3>Upcoming Assignments</h3><br>
          <div id="studentAssignmentList" class="empty-state">No pending assignments.</div>
        </section>

        <section class="tile">
          <h3>Scheduled Tests</h3><br>
          <div id="studentTestList" class="empty-state">No tests scheduled.</div>
        </section>
      </div>
    </div>

    <!-- CALENDAR VIEW -->
    <div id="view-calendar" class="dashboard-view" style="display: none;">
      <header class="header">
        <h1 style="font-size: 1.8rem; font-weight: 700;">Calendar</h1>
      </header>

      <section class="tile">
        <div id="student-calendar"></div>
      </section>
    </div>

    <!-- MY TASKS VIEW -->
    <div id="view-my-tasks" class="dashboard-view" style="display: none;">
      <header class="header">
        <div>
          <h1 style="font-size: 1.8rem; font-weight: 700;">My Tasks</h1>

          <p style="color: var(--aslb-muted); margin-top: 10px;">
            Your teacher-given tasks and private personal tasks in one place.
          </p>
        </div>
      </header>

      <section class="tile">
        <div class="my-task-header">
          <div>
            <h3>All Pending Tasks</h3>
            <p>Track what is pending, completed, and what needs attention first.</p>
          </div>

          <div class="my-task-count" id="myTaskCount">0 tasks</div>
        </div>

        <div id="myTaskList" class="empty-state">
          No tasks found.
        </div>
      </section>
    </div>

  </main>

  <script src="calendar-data.js"></script>
  <script src="calendar.js"></script>
  <script>
    const studentEmail = <?= json_encode($_SESSION['student_email'] ?? "") ?>;
    const studentName = <?= json_encode($_SESSION['student_name'] ?? "Student") ?>;
  </script>
  <script src="deadline-rx.js"></script>

  <script>
    (function() {
      const studentClass = <?php echo json_encode($class); ?>;

      const btnCal = document.getElementById('btn-show-calendar');
      const btnAsgn = document.getElementById('btn-show-assignments');
      const btnMyTasks = document.getElementById('btn-show-my-tasks');

      const viewCal = document.getElementById('view-calendar');
      const viewAsgn = document.getElementById('view-assignments');
      const viewMyTasks = document.getElementById('view-my-tasks');

      function hideAllViews() {
        viewCal.style.display = 'none';
        viewAsgn.style.display = 'none';
        viewMyTasks.style.display = 'none';

        btnCal.classList.remove('active');
        btnAsgn.classList.remove('active');
        btnMyTasks.classList.remove('active');
      }

      async function showCalendar() {
        hideAllViews();

        viewCal.style.display = 'block';
        btnCal.classList.add('active');

        if (typeof loadDeadlineRxTasks === 'function') {
          await loadDeadlineRxTasks();
        }

        if (typeof renderCalendar === 'function') {
          const assignments = typeof academicAssignments !== 'undefined' ? academicAssignments : [];
          const tests = typeof academicTests !== 'undefined' ? academicTests : [];
          const events = typeof academicEvents !== 'undefined' ? academicEvents : [];

          const classAssignments = assignments.filter(a => a.className === studentClass);
          const classTests = tests.filter(t => t.className === studentClass);

          const personalTasksAsAssignments = (typeof deadlineRxTasks !== 'undefined' ? deadlineRxTasks : [])
            .filter(t => t.task_type === "personal" && Number(t.is_completed) !== 1)
            .map(t => ({
              title: t.title,
              subject: t.subject || "Personal Task",
              deadline: t.deadline,
              className: studentClass,
              type: "personal"
            }));

          renderCalendar('student-calendar', {
            assignments: [...classAssignments, ...personalTasksAsAssignments],
            tests: classTests,
            events: events
          });
        }
      }

      async function showAssignments() {
        hideAllViews();

        viewAsgn.style.display = 'block';
        btnAsgn.classList.add('active');

        if (typeof loadDeadlineRxTasks === 'function') {
          await loadDeadlineRxTasks();
        }
      }

      async function showMyTasks() {
        hideAllViews();

        viewMyTasks.style.display = 'block';
        btnMyTasks.classList.add('active');

        if (typeof loadDeadlineRxTasks === 'function') {
          await loadDeadlineRxTasks();
        }

        if (typeof renderMyTasksView === 'function') {
          renderMyTasksView();
        }
      }

      btnCal.addEventListener('click', showCalendar);
      btnAsgn.addEventListener('click', showAssignments);
      btnMyTasks.addEventListener('click', showMyTasks);

      showAssignments();
    })();
  </script>
</body>
</html>