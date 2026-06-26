<?php
session_start();
if (!isset($_SESSION['student_name'])) {
    header("Location: student-login.html");
    exit();
}

// Format the name to Title Case
$name = ucwords(strtolower($_SESSION['student_name']));
$dept = $_SESSION['dept_name'];
$class = $_SESSION['class_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Student Dashboard — DeadlineRX System</title>
  <link rel="stylesheet" href="student-dashboard.css">
</head>
<body>
  <aside class="sidebar">
    <div class="logo-container">
      <div class="logo-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1 0-5H20"/></svg>
      </div>
      <span style="font-weight: 700; font-size: 1.1rem;">DeadlineRX System</span>
    </div>

    <nav class="nav-group">
      <a href="javascript:void(0)" id="btn-show-calendar" class="nav-button">Calendar View</a>
      <a href="javascript:void(0)" id="btn-show-assignments" class="nav-button active">Assignments & Tests</a>
    </nav>
    
    <div class="logout-container">
      <a href="index.html" class="nav-button logout-button">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
        Logout
      </a>
    </div>
  </aside>

  <main class="main-viewport">
  
    <div id="view-assignments" class="dashboard-view">
      <header class="header">
        <div>
          <h1 style="font-size: 1.8rem; font-weight: 700; text-transform: capitalize;">
          Hello, <?php echo htmlspecialchars(strtolower($name)); ?>👋🏻 </h1>
          <p style="color: var(--aslb-muted);"><br>
          <?php echo htmlspecialchars($class); ?> | <?php echo htmlspecialchars($dept); ?></p>
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

    <div id="view-calendar" class="dashboard-view" style="display: none;">
      <header class="header">
        <h1 style="font-size: 1.8rem; font-weight: 700;">Academic Calendar</h1>
      </header>
      <section class="tile">
        <div id="student-calendar"></div> 
      </section>
    </div>
  </main>

  <script src="calendar-data.js"></script>
  <script src="calendar.js"></script>
  <script src="deadline-rx.js"></script>

  <script>
    (function() {
      const studentClass = "<?php echo $class; ?>"; 
      
      function isWithinUpcomingWeek(dateStr) {
          if (!dateStr) return false;
          const today = new Date();
          today.setHours(0, 0, 0, 0); 
          const targetDate = new Date(dateStr);
          targetDate.setHours(0, 0, 0, 0);
          const oneWeekFromNow = new Date(today);
          oneWeekFromNow.setDate(today.getDate() + 7);
          return targetDate >= today && targetDate <= oneWeekFromNow;
      }

      //Switching Logic
      const btnCal = document.getElementById('btn-show-calendar');
      const btnAsgn = document.getElementById('btn-show-assignments');
      const viewCal = document.getElementById('view-calendar');
      const viewAsgn = document.getElementById('view-assignments');

      function showCalendar() {
          viewCal.style.display = 'block';
          viewAsgn.style.display = 'none';
          btnCal.classList.add('active');
          btnAsgn.classList.remove('active');
          if (typeof renderCalendar === 'function') {
              renderCalendar('student-calendar', {
                assignments: academicAssignments,
                tests: academicTests // Use the direct tests array instead of filtering events
              });
          }
      }

      function showAssignments() {
          viewCal.style.display = 'none';
          viewAsgn.style.display = 'block';
          btnAsgn.classList.add('active');
          btnCal.classList.remove('active');
      }

      btnCal.addEventListener('click', showCalendar);
      btnAsgn.addEventListener('click', showAssignments);
      
      // Dashboard Initialization
// Dashboard Initialization
function initDashboard() {
    const studentClass = "<?php echo $_SESSION['class_name']; ?>";
    const today = new Date();
    today.setHours(0, 0, 0, 0); // Reset time to compare only dates

    // 1. Filter for assignments that match the class AND are due today or later
    const filteredAssignments = academicAssignments.filter(a => {
        const dueDate = new Date(a.deadline);
        return a.className === studentClass && dueDate >= today;
    });

    // 2. Filter for tests that match the class AND are scheduled for today or later
    const filteredTests = academicTests.filter(t => {
        const testDate = new Date(t.date);
        return t.className === studentClass && testDate >= today;
    });

    // Update Assignments UI
    const assignEl = document.getElementById('studentAssignmentList');
    if (filteredAssignments.length > 0) {
        assignEl.classList.remove('empty-state');
        assignEl.innerHTML = filteredAssignments.map(a => `
            <div class="assignment-card" style="margin-bottom: 15px; padding: 10px; border-bottom: 1px solid #eee;">
                <h4 style="margin: 0; color: var(--aslb-blue);">${a.title}</h4>
                <p style="margin: 4px 0;"><strong>Subject:</strong> ${a.subject}</p>
                <small style="color: var(--aslb-mute);"><strong>Due:</strong> ${a.deadline}</small>
            </div>
        `).join('');
    } else {
        assignEl.innerHTML = "No upcoming assignments for May.";
        assignEl.classList.add('empty-state');
    }

    // Update Tests UI (Same logic)
    const testEl = document.getElementById('studentTestList');
    if (filteredTests.length > 0) {
        testEl.classList.remove('empty-state');
        // ... (rest of your mapping logic)
    } else {
        testEl.innerHTML = "No tests scheduled for May.";
        testEl.classList.add('empty-state');
    }
}

      const checkDataLoad = setInterval(() => {
          if (typeof academicAssignments !== 'undefined' && academicAssignments.length > 0) {
              initDashboard();
              clearInterval(checkDataLoad);
          }
      }, 200);
    })();
  </script>
</body>
</html>