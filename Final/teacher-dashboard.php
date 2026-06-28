<?php
session_start();
if (!isset($_SESSION['teacher_id'])) {
    header("Location: Login/teacher-login.html");
    exit();
}

// Format the name to Title Case
$name = ucwords(strtolower($_SESSION['teacher_id']));
$dept_id = isset($_SESSION['dept_id']) ? $_SESSION['dept_id'] : 'N/A';

// Create a mapping array to translate ID to Name
$dept_names = [
    "1" => "Computer Department",
    "2" => "Civil Department",
    "3" => "Mechanical Department",
    "4" => "Electronics and Computer Science Department",
    "5" => "Science and Humanities Department"
];

// Determine the display name, defaulting to 'N/A' if ID isn't found
$display_dept = isset($dept_names[$dept_id]) ? $dept_names[$dept_id] : "N/A";

require_once 'db_config.php';

// Fetch Assignments for this teacher
$stmtA = $conn->prepare("
    SELECT 
        Assignment_ID,
        Title,
        Due_Date,
        Subject,
        Class_ID,
        Type,
        Weightage,
        No_of_Pages,
        Difficulty_Index,
        Description
    FROM assignment 
    WHERE Teacher_Username = ? 
    ORDER BY Due_Date DESC
");
$stmtA->bind_param("s", $_SESSION['teacher_id']);
$stmtA->execute();
$assignmentsResult = $stmtA->get_result();

// Fetch Tests for this teacher
$stmtT = $conn->prepare("
    SELECT 
        Test_ID,
        Subject,
        Title,
        Test_Title,
        Test_Date,
        Class_ID,
        Weightage,
        Difficulty_Index,
        Description
    FROM test 
    WHERE Teacher_Username = ? 
    ORDER BY Test_Date DESC
");
$stmtT->bind_param("s", $_SESSION['teacher_id']);
$stmtT->execute();
$testsResult = $stmtT->get_result();

// Mapping for Class IDs to Display Names
$class_map = [
    "1" => "FE COMP 1", "2" => "FE COMP 2", "3" => "SE COMP 1", 
    "4" => "SE COMP 2", "5" => "TE COMP 1", "6" => "BE COMP 1"
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard — DeadlineRX System</title>
    <link rel="stylesheet" href="teacher-dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/themes/airbnb.css">
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
    <a href="javascript:void(0)" id="nav-btn-calendar" class="nav-button active">
        Calendar View
    </a>
    <a href="javascript:void(0)" id="nav-btn-task" class="nav-button">
        Create New Task
    </a>
    <a href="javascript:void(0)" id="nav-btn-my-tasks" class="nav-button">
    My Created Tasks
    </a>
</nav>
    
    <div class="logout-container">
      <a href="index.html" class="nav-button logout-button">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
        Logout
      </a>
    </div>
  </aside>

  <main class="main-viewport">
    <div id="view-calendar" class="dashboard-view" style="display: block;">
    <div class="welcome-section">
    <div class="welcome-card">
        <div class="welcome-content">
            <h2 class="welcome-title">Welcome, <span class="highlight"><?php echo htmlspecialchars($name); ?></span>👋🏻</h2>
            <p style="color: var(--aslb-muted); margin-top: 5px;">
                Faculty | <?php echo htmlspecialchars($display_dept); ?>
            </p>
        </div>
        
    </div>
    </div>

    <div class="calendar-controls-card">
        <div class="card-header">
            <h1 class="page-title-small">Class Schedules</h1>
            <p class="card-subtitle">Select a department and class to view the academic timeline.</p>
        </div>

        <div class="filter-grid">
            <div class="form-group">
                <label>Department</label>
                <select id="filter-dept" onchange="updateClassDropdown('filter-dept', 'filter-class')" required>
                    <option value="">Select Department</option>
                    <option value="Civil">Civil Department</option>
                    <option value="Computer">Computer Department</option>          
                    <option value="Mechanical">Mechanical Department</option>
                    <option value="ECS">Electronics and Computer Science Department</option>
                </select>
            </div>
            <div class="form-group">
                <label>Class</label>
                <select id="filter-class" onchange="refreshTeacherCalendar()">
                    <option value="">Select Class</option>
                </select>
            </div>
        </div>
    </div>

    <section class="tile" id="calendar-tile" style="display: none;">
    <div id="teacher-calendar-container"></div>
</section>
</div>   
    <div id="view-create-task" class="dashboard-view" style="display: none;">
        <div class="header">
            <h1 class="page-title">Create New Task</h1>            
        </div>

        <div class="form-card">
            <form action="save-task.php" method="POST">
                
                <div class="form-grid">
                    <div class="form-group">
                        <label>Department</label>
                        <select name="dept_name" id="task-dept" onchange="updateClassDropdown('task-dept', 'task-class')" required>
                            <option value="">Select Department</option>
                            <option value="Civil">Civil Department</option>
                            <option value="Computer">Computer Department</option>          
                            <option value="Mechanical">Mechanical Department</option>
                            <option value="ECS">Electronics and Computer Science Department</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Class</label>
                        <select name="class_id" id="task-class" required>
                            <option value="">Select Class</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Subject Name</label>
                        <input type="text" name="subject_name" placeholder="e.g. Software Engineering" required>
                    </div>
                    <div class="form-group">
                        <label>Task Type</label>
                        <select name="task_type" id="task_type" onchange="toggleTaskFields()" required>
                            <option value="">Select Type</option>
                            <option value="assignment">Assignment</option>
                            <option value="test">Test</option>
                        </select>
                    </div>
                    <div class="form-group full-width">
                        <label>Title of Task</label>
                        <input type="text" name="task_title" placeholder="Enter Specified Title" required>
                    </div>

                    <div id="assignment_params" class="form-grid-sub full-width" style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
                        <div class="form-group"><label>Category</label><select name="assignment_category" required><option value="">Select Category</option><option value="theory">Theory</option><option value="practical">Practical</option><option value="both">Both Theory and Practical</option></select></div>
                        <div class="form-group"><label>Length (pages)</label><input type="number" name="num_pages" id="num_pages" min="0" placeholder="e.g. 6" required></div>                        
                        <div class="form-group"><label>Complexity (1-10)</label><input type="number" name="difficulty_level" min="1" max="10"></div>
                        <div class="form-group"><label>Weightage</label><input type="number" name="weightage" min="1" max="20"></div>
                    </div>

                    <div id="test_params" class="form-grid-sub full-width" style="display: none; grid-template-columns: 1fr 1fr; gap: 25px;">
                        <div class="form-group"><label>Complexity (1-10)</label><input type="number" name="difficulty_level_test" min="1" max="10"></div>
                        <div class="form-group"><label>Weightage</label><input type="number" name="weightage_test" min="1" max="20" ></div>
                    </div>

                    <div class="form-group">
                        <label>Date Given</label>
                        <input 
                            type="date" 
                            name="date_given" 
                            id="date_given" 
                            value="<?php echo date('Y-m-d'); ?>" 
                            readonly 
                            style="cursor: not-allowed;"
                        >
                    </div>
                    <div class="form-group"><label>Proposed Deadline</label><input type="date" name="proposed_deadline" id="proposed_deadline" required></div>
                    <div class="form-group full-width"><label>Portion / Description</label><textarea name="description" rows="4"></textarea></div>
                </div>
                <button type="submit" class="submit-btn">Add Task</button>
            </form>
        </div>
    </div>

<div id="view-my-tasks" class="dashboard-view" style="display: none;">
    <div class="header">
        <h1 class="page-title">My Created Tasks</h1>            
    </div>

    <div class="content-grid teacher-task-grid">
        <section class="tile">
            <h3 style="color: var(--aslb-text); font-size: 1.1rem;">Assignments Issued</h3><br>

            <div id="teacherAssignmentList">
                <?php if ($assignmentsResult->num_rows > 0): ?>
                    <?php while($row = $assignmentsResult->fetch_assoc()): ?>
                        <div class="teacher-task-card assignment-card">
                            <div class="teacher-task-main">
                                <span class="teacher-task-chip assignment-chip">Assignment</span>

                                <h4><?php echo htmlspecialchars($row['Title']); ?></h4>

                                <p>
                                    <strong>Class:</strong> <?php echo $class_map[$row['Class_ID']] ?? 'Unknown'; ?> |
                                    <strong>Subject:</strong> <?php echo htmlspecialchars($row['Subject']); ?>
                                </p>

                                <small>
                                    Deadline: <?php echo date('M d, Y', strtotime($row['Due_Date'])); ?>
                                </small>
                            </div>

                            <div class="teacher-task-actions">
                                <a 
                                    class="task-edit-btn"
                                    href="edit-teacher-task.php?type=assignment&id=<?php echo $row['Assignment_ID']; ?>"
                                >
                                    Edit
                                </a>

                                <form 
                                    action="delete-teacher-task.php" 
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this assignment? It will be removed for all students in this class.');"
                                >
                                    <input type="hidden" name="task_type" value="assignment">
                                    <input type="hidden" name="task_id" value="<?php echo $row['Assignment_ID']; ?>">
                                    <button type="submit" class="task-delete-btn">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">No assignments found in database.</div>
                <?php endif; ?>
            </div>
        </section>

        <section class="tile">
            <h3 style="color: var(--aslb-text); font-size: 1.1rem;">Tests Scheduled</h3><br>

            <div id="teacherTestList">
                <?php if ($testsResult->num_rows > 0): ?>
                    <?php while($row = $testsResult->fetch_assoc()): ?>
                        <?php 
                            $testTitle = $row['Test_Title'] ?: ($row['Title'] ?: $row['Subject']);
                        ?>

                        <div class="teacher-task-card test-card">
                            <div class="teacher-task-main">
                                <span class="teacher-task-chip test-chip">Test</span>

                                <h4><?php echo htmlspecialchars($testTitle); ?></h4>

                                <p>
                                    <strong>Class:</strong> <?php echo $class_map[$row['Class_ID']] ?? 'Unknown'; ?> |
                                    <strong>Subject:</strong> <?php echo htmlspecialchars($row['Subject']); ?>
                                </p>

                                <small>
                                    Date: <?php echo date('M d, Y', strtotime($row['Test_Date'])); ?>
                                </small>
                            </div>

                            <div class="teacher-task-actions">
                                <a 
                                    class="task-edit-btn"
                                    href="edit-teacher-task.php?type=test&id=<?php echo $row['Test_ID']; ?>"
                                >
                                    Edit
                                </a>

                                <form 
                                    action="delete-teacher-task.php" 
                                    method="POST"
                                    onsubmit="return confirm('Are you sure you want to delete this test? It will be removed for all students in this class.');"
                                >
                                    <input type="hidden" name="task_type" value="test">
                                    <input type="hidden" name="task_id" value="<?php echo $row['Test_ID']; ?>">
                                    <button type="submit" class="task-delete-btn">Delete</button>
                                </form>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="empty-state">No tests found in database.</div>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>

<script src="calendar-data.js"></script>
<script src="calendar.js"></script>

<script>
    (function() {
        // --- 1. SELECTORS ---
        const btnNavCal = document.getElementById('nav-btn-calendar');
        const btnNavTask = document.getElementById('nav-btn-task');
        const btnNavMyTasks = document.getElementById('nav-btn-my-tasks');

        const viewCalendar = document.getElementById('view-calendar');
        const viewCreateTask = document.getElementById('view-create-task');
        const viewMyTasks = document.getElementById('view-my-tasks');
        
        const deptSelect = document.getElementById('filter-dept');
        const classSelect = document.getElementById('filter-class');
        const dateGivenInput = document.getElementById('date_given');
        const deadlineInput = document.getElementById('proposed_deadline');
        const today = new Date().toLocaleDateString('en-CA'); 

// --- 2. VIEW SWITCHING LOGIC (Consolidated) ---
        function hideAllViews() {
            // Hide all containers
            viewCalendar.style.display = 'none';
            viewCreateTask.style.display = 'none';
            viewMyTasks.style.display = 'none';
            
            // Reset button states
            btnNavCal.classList.remove('active');
            btnNavTask.classList.remove('active');
            btnNavMyTasks.classList.remove('active');
        }

        if(btnNavCal) {
            btnNavCal.addEventListener('click', function() {
                hideAllViews();
                viewCalendar.style.display = 'block';
                btnNavCal.classList.add('active');
            });
        }

        if(btnNavTask) {
            btnNavTask.addEventListener('click', function() {
                hideAllViews();
                viewCreateTask.style.display = 'block';
                btnNavTask.classList.add('active');
            });
        }

        if(btnNavMyTasks) {
            btnNavMyTasks.addEventListener('click', function() {
                hideAllViews();
                viewMyTasks.style.display = 'block';
                btnNavMyTasks.classList.add('active');
                // renderTeacherTasks() is removed as PHP now handles data on load
            });
        }

        // --- 4. CASCADING DROPDOWN ---
        function addOptions(selectElement, optionsArray) {
            optionsArray.forEach(opt => {
                const option = document.createElement("option");
                option.value = opt.val;
                option.textContent = opt.text;
                selectElement.appendChild(option);
            });
        }

        window.updateClassDropdown = function(deptId, classId) {
            const selectedDept = document.getElementById(deptId).value;
            const targetClassSelect = document.getElementById(classId);
            targetClassSelect.innerHTML = '<option value="">Select Class</option>';

            if (selectedDept === "Computer") {
                const computerClasses = [
                    { val: "1", text: "FE Computer - 1" },
                    { val: "2", text: "FE Computer - 2" },
                    { val: "3", text: "SE Computer - 1" },
                    { val: "4", text: "SE Computer - 2" },
                    { val: "5", text: "TE Computer" },
                    { val: "6", text: "BE Computer" }
                ];
                addOptions(targetClassSelect, computerClasses);
            } else if (selectedDept !== "") {
                const genericClasses = [
                    { val: "FE-" + selectedDept, text: "FE " + selectedDept },
                    { val: "SE-" + selectedDept, text: "SE " + selectedDept },
                    { val: "TE-" + selectedDept, text: "TE " + selectedDept },
                    { val: "BE-" + selectedDept, text: "BE " + selectedDept }
                ];
                addOptions(targetClassSelect, genericClasses);
            }
        };

        // --- 5. CALENDAR REFRESH ---
        window.refreshTeacherCalendar = function() {
            const selectedClass = document.getElementById('filter-class')?.value;
            const calendarTile = document.getElementById('calendar-tile');
            
            if (!selectedClass) {
                if(calendarTile) calendarTile.style.display = 'none';
                return;
            }

            if(calendarTile) calendarTile.style.display = 'block';

            const filteredAssignments = academicAssignments.filter(a => a.className === selectedClass);
            const filteredTests = academicTests.filter(t => t.className === selectedClass);

            if (typeof renderCalendar === 'function') {
                renderCalendar('teacher-calendar-container', { 
                    assignments: filteredAssignments, 
                    tests: filteredTests, 
                    events: academicEvents 
                });
            }
        };

        // --- 6. TASK FORM FIELD TOGGLING ---
        window.toggleTaskFields = function() {
    const taskType = document.getElementById('task_type').value;
    const assignmentSection = document.getElementById('assignment_params');
    const testSection = document.getElementById('test_params');

    // Select specific inputs
    const assignInputs = assignmentSection.querySelectorAll('input, select');
    const testInputs = testSection.querySelectorAll('input');

    if (taskType === 'assignment') {
        assignmentSection.style.display = 'grid';
        testSection.style.display = 'none';
        
        // Make assignment fields required, clear test requirements
        assignInputs.forEach(input => input.required = true);
        testInputs.forEach(input => input.required = false);
    } else if (taskType === 'test') {
        assignmentSection.style.display = 'none';
        testSection.style.display = 'grid';
        
        // Make test fields required, clear assignment requirements
        assignInputs.forEach(input => input.required = false);
        testInputs.forEach(input => input.required = true);
    } else {
        // If "Select Type" is chosen, make nothing required to avoid blocking
        assignInputs.forEach(input => input.required = false);
        testInputs.forEach(input => input.required = false);
    }
    if (taskType !== "") {
        deadlineInput.required = true;
    } else {
        deadlineInput.required = false;
    }
};

        // --- 7. DATE CONSTRAINTS & CONFLICTS ---
// Ensure this runs inside your (function() { ... })();
const deadlinePicker = flatpickr("#proposed_deadline", {
    theme: "airbnb",
    animate: true,
    minDate: "today", // Correctly freezes everything before today
    dateFormat: "Y-m-d",
    altInput: true,
    altInputClass: "form-control", // Add your CSS class here if needed
    altFormat: "F j, Y",
    
    // Use 'onOpen' to ensure the picker refreshes its logic every time it's clicked
    onOpen: function(selectedDates, dateStr, instance) {
        instance.set("minDate", "today");
    },

    disable: [
        function(date) {
            // 1. Check Weekends
            const isWeekend = (date.getDay() === 0 || date.getDay() === 6);
            
            // 2. Format date correctly for comparison (YYYY-MM-DD)
            const year = date.getFullYear();
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const day = String(date.getDate()).padStart(2, '0');
            const dateStr = `${year}-${month}-${day}`;

            // 3. Check Academic Restrictions
            // We check if academicEvents exists and has data
            const isRestricted = (typeof academicEvents !== 'undefined') && academicEvents.some(e => {
                return e.date === dateStr && (e.type === 'holiday' || e.type === 'it-exam');
            });

            return isWeekend || isRestricted;
        }
    ],
    locale: {
        firstDayOfWeek: 0
    }
});
    })();
</script>
</body>
</html>