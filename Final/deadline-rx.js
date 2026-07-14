let deadlineRxTasks = [];

document.addEventListener("DOMContentLoaded", function () {
    injectPersonalTaskPanel();
    injectDeadlineRxPanel();
    loadDeadlineRxTasks();
});

function injectPersonalTaskPanel() {
    const assignmentBox = document.getElementById("studentAssignmentList");
    if (!assignmentBox) return;

    const parent = assignmentBox.closest(".content-grid") || assignmentBox.parentElement.parentElement;

    if (document.getElementById("rxPersonalTaskPanel")) return;

    const personalPanel = document.createElement("section");
    personalPanel.className = "tile rx-personal-panel";
    personalPanel.id = "rxPersonalTaskPanel";

    personalPanel.innerHTML = `
        <div class="rx-personal-header">
            <div>
                <h3>My Personal Tasks</h3>
                <p>Add private tasks that only you can see. DeadlineRX will include them in your AI plan.</p>
            </div>
        </div>

        <div class="rx-personal-form">

            <div class="rx-form-group">
                <label>Task Title</label>
                <input type="text" id="personalTitle" placeholder="e.g. Revise Java Unit 3">
            </div>

            <div class="rx-form-group">
                <label>Subject / Category</label>
                <input type="text" id="personalSubject" placeholder="e.g. Java, SEPM, Personal" value="Personal Task">
            </div>

            <div class="rx-form-group">
                <label>Due Date</label>
                <input type="date" id="personalDueDate">
            </div>

            <div class="rx-form-group">
                <label>Difficulty</label>
                <input type="number" id="personalDifficulty" min="1" max="10" value="5" placeholder="1-10">
            </div>

            <div class="rx-form-group">
                <label>Est. Hours</label>
                <input type="number" id="personalHours" min="0.5" step="0.5" value="1" placeholder="Hours needed">
            </div>

            <div class="rx-form-group rx-description-group">
                <label>Description / Notes</label>
                <textarea id="personalDescription" placeholder="e.g. Only diagrams left, need to print, teacher is strict..."></textarea>
            </div>

            <button onclick="addPersonalTask()" class="rx-add-task-btn">Add Personal Task</button>
        </div>

        <div id="studentPersonalTaskList" class="empty-state">No personal tasks added.</div>
    `;

    parent.appendChild(personalPanel);
}

function injectDeadlineRxPanel() {
    const assignmentBox = document.getElementById("studentAssignmentList");
    const testBox = document.getElementById("studentTestList");

    if (!assignmentBox || !testBox) return;

    const parent = assignmentBox.closest(".content-grid") || assignmentBox.parentElement.parentElement;

    if (document.getElementById("rxPlannerPanel")) return;

    const aiPanel = document.createElement("section");
    aiPanel.className = "tile rx-ai-panel";
    aiPanel.id = "rxPlannerPanel";

    aiPanel.innerHTML = `
        <div class="rx-panel-header">
            <div>
                <span class="rx-chip">AI Rescue Mode</span>
                <h3>DeadlineRX Planner</h3>
                <p>Turn deadline pressure into a clear action plan.</p>
            </div>

            <div class="rx-mini-stat">
                <span id="rxTaskCount">0</span>
                <small>active tasks</small>
            </div>
        </div>

        <div class="rx-control-box">
            <div>
                <label>Available hours today</label>
                <input type="number" id="availableHoursToday" min="0.5" step="0.5" value="3">
            </div>

            <div class="rx-context-field">
                <label>Extra details for AI</label>
                <textarea id="extraAiContext" placeholder="Example: Teacher is strict, late submission allowed, I am tired today, only coding is left, I have college from 9 to 5."></textarea>
            </div>

            <button onclick="generateAiPlan()" class="rx-generate-btn">
                Generate Rescue Plan
            </button>
        </div>

        <div id="aiPlanOutput" class="rx-plan-empty">
            <div class="rx-empty-icon">⚡</div>
            <h4>Your AI rescue plan will appear here</h4>
            <p>Add/update task progress and generate a realistic plan for today.</p>
        </div>
    `;

    parent.appendChild(aiPanel);
    setupPlannerLocalSave();
    loadLatestAiPlan();
}

async function loadDeadlineRxTasks() {
    try {
        const res = await fetch("student-tasks-api.php");
        const data = await res.json();

        if (!data.success) {
            console.error(data.message);
            return;
        }

        deadlineRxTasks = data.tasks || [];

        // Make tasks available everywhere
        window.deadlineRxTasks = deadlineRxTasks;

        renderTaskLists();

        if (typeof renderMyTasksView === "function") {
            renderMyTasksView();
        }

    } catch (error) {
        console.error("Error loading DeadlineRX tasks:", error);
    }
}

function renderTaskLists() {
    const pendingTasks = deadlineRxTasks.filter(t => Number(t.is_completed) !== 1);

    const assignments = pendingTasks.filter(t => t.task_type === "assignment");
    const tests = pendingTasks.filter(t => t.task_type === "test");
    const personal = pendingTasks.filter(t => t.task_type === "personal");

    const activeCount = pendingTasks.length;
    const countBox = document.getElementById("rxTaskCount");

    if (countBox) countBox.textContent = activeCount;

    renderTaskCards("studentAssignmentList", assignments, "No pending assignments.");
    renderTaskCards("studentTestList", tests, "No tests scheduled.");
    renderTaskCards("studentPersonalTaskList", personal, "No personal tasks added.");
}

function renderTaskCards(containerId, tasks, emptyText) {
    const container = document.getElementById(containerId);
    if (!container) return;

    if (!tasks.length) {
        container.className = "empty-state";
        container.innerHTML = emptyText;
        return;
    }

    container.className = "";
    container.innerHTML = tasks.map(task => taskCardHtml(task)).join("");
}

function taskCardHtml(task) {
    const risk = task.risk || {};
    const badgeColor = getRiskColor(risk.level);
    const disabledStyle = Number(task.is_completed) === 1 ? "opacity: 0.65;" : "";
    const sourceLabel = task.source === "student" ? "Personal" : "Teacher-given";

    return `
        <div class="deadline-card" style="${disabledStyle}">
            <div class="deadline-card-top">
                <div>
                    <span class="task-source ${task.source === "student" ? "personal" : "teacher"}">${sourceLabel}</span>
                    <h4>${escapeHtml(task.title)}</h4>
                    <p>${escapeHtml(task.subject)} • Due: ${escapeHtml(task.deadline)}</p>
                </div>

                <span class="risk-badge" style="background:${badgeColor.bg}; color:${badgeColor.text};">
                    ${escapeHtml(risk.level || "Unknown")} ${risk.score ?? 0}/100
                </span>
            </div>

            <p class="risk-reason">${escapeHtml(risk.reason || "Risk calculated from deadline and progress.")}</p>

            <div class="task-progress-grid">
                <div>
                    <label>Progress</label>
                    <select id="progress-${task.task_id}" onchange='saveTaskProgress(${JSON.stringify(task.task_id)}, ${JSON.stringify(task.task_type)})'>
                        ${progressOptions(task.completion_percentage)}
                    </select>
                </div>

                <div>
                    <label>Hours left</label>
                    <input id="hours-${task.task_id}" type="number" min="0" step="0.5" value="${task.estimated_hours_left || ""}" placeholder="Hours needed to finish" onblur='saveTaskProgress(${JSON.stringify(task.task_id)}, ${JSON.stringify(task.task_type)})'>
                </div>

                <div>
                    <label>Status</label>
                    <select id="status-${task.task_id}" onchange='saveTaskProgress(${JSON.stringify(task.task_id)}, ${JSON.stringify(task.task_type)})'>
                        <option value="not_started" ${task.status === "not_started" ? "selected" : ""}>Not started</option>
                        <option value="in_progress" ${task.status === "in_progress" ? "selected" : ""}>In progress</option>
                        <option value="completed" ${task.status === "completed" ? "selected" : ""}>Completed</option>
                    </select>
                </div>
            </div>

            <div class="task-actions">
                <button onclick='saveTaskProgress(${JSON.stringify(task.task_id)}, ${JSON.stringify(task.task_type)})' class="task-save-btn">
                    Save Progress
                </button>

                ${
                    Number(task.is_completed) === 1
                    ? `<button onclick='unmarkCompleted(${JSON.stringify(task.task_id)}, ${JSON.stringify(task.task_type)})' class="task-uncomplete-btn">
                        ↺ Unmark Completed
                    </button>`
                    : `<button onclick='markCompleted(${JSON.stringify(task.task_id)}, ${JSON.stringify(task.task_type)})' class="task-complete-btn">
                        ✓ Mark Completed
                    </button>`
                }

                ${task.task_type === "personal"
            ? `<button onclick='deletePersonalTask(${JSON.stringify(task.task_id)})' class="task-delete-btn">Delete</button>`
            : ""
        }
            </div>
        </div>
    `;
}

function progressOptions(selected) {
    const values = [0, 25, 50, 75, 100];

    return values.map(v => {
        return `<option value="${v}" ${Number(selected) === v ? "selected" : ""}>${v}%</option>`;
    }).join("");
}

async function addPersonalTask() {
    const title = document.getElementById("personalTitle").value.trim();
    const subject = document.getElementById("personalSubject").value.trim() || "Personal Task";
    const dueDate = document.getElementById("personalDueDate").value;
    const difficulty = Number(document.getElementById("personalDifficulty").value || 5);
    const estimatedHours = Number(document.getElementById("personalHours").value || 1);
    const description = document.getElementById("personalDescription").value.trim();

    if (!title || !dueDate) {
        alert("Please enter task title and due date.");
        return;
    }

    try {
        const res = await fetch("add-personal-task.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                title,
                subject,
                due_date: dueDate,
                difficulty,
                estimated_hours: estimatedHours,
                description
            })
        });

        const data = await res.json();

        if (data.success) {
            document.getElementById("personalTitle").value = "";
            document.getElementById("personalSubject").value = "Personal Task";
            document.getElementById("personalDueDate").value = "";
            document.getElementById("personalDifficulty").value = "5";
            document.getElementById("personalHours").value = "1";
            document.getElementById("personalDescription").value = "";

            await loadDeadlineRxTasks();
        } else {
            alert(data.message || "Could not add personal task.");
        }

    } catch (error) {
        console.error("Add personal task error:", error);
        alert("Error adding personal task.");
    }
}

async function deletePersonalTask(taskId) {
    if (!confirm("Delete this personal task?")) return;

    try {
        const res = await fetch("delete-personal-task.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ task_id: taskId })
        });

        const data = await res.json();

        if (data.success) {
            await loadDeadlineRxTasks();
        } else {
            alert(data.message || "Could not delete task.");
        }

    } catch (error) {
        console.error("Delete personal task error:", error);
        alert("Error deleting task.");
    }
}

async function saveTaskProgress(taskId, taskType) {
    const progress = Number(document.getElementById(`progress-${taskId}`).value);
    const hours = Number(document.getElementById(`hours-${taskId}`).value || 0);
    const status = document.getElementById(`status-${taskId}`).value;
    const availableHours = Number(document.getElementById("availableHoursToday")?.value || 3);

    try {
        const res = await fetch("update-task-progress.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                task_id: taskId,
                task_type: taskType,
                completion_percentage: progress,
                estimated_hours_left: hours,
                available_hours_today: availableHours,
                status
            })
        });

        const data = await res.json();

        if (data.success) {
            await loadDeadlineRxTasks();
        } else {
            alert(data.message || "Could not save progress");
        }

    } catch (error) {
        console.error("Save progress error:", error);
        alert("Error saving progress");
    }
}

async function markCompleted(taskId, taskType) {
    try {
        const res = await fetch("update-task-progress.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                task_id: taskId,
                task_type: taskType,
                completion_percentage: 100,
                estimated_hours_left: 0,
                available_hours_today: Number(document.getElementById("availableHoursToday")?.value || 3),
                status: "completed"
            })
        });

        const data = await res.json();

        if (data.success) {
            await loadDeadlineRxTasks();
        } else {
            alert(data.message || "Could not mark completed");
        }

    } catch (error) {
        console.error("Mark completed error:", error);
        alert("Error marking completed");
    }
}

async function unmarkCompleted(taskId, taskType) {
    try {
        const res = await fetch("update-task-progress.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                task_id: taskId,
                task_type: taskType,
                completion_percentage: 75,
                estimated_hours_left: 1,
                available_hours_today: Number(document.getElementById("availableHoursToday")?.value || 3),
                status: "in_progress"
            })
        });

        const data = await res.json();

        if (data.success) {
            await loadDeadlineRxTasks();
        } else {
            alert(data.message || "Could not unmark completed");
        }

    } catch (error) {
        console.error("Unmark completed error:", error);
        alert("Error unmarking task.");
    }
}

async function generateAiPlan() {
    const output = document.getElementById("aiPlanOutput");
    const availableHours = Number(document.getElementById("availableHoursToday")?.value || 3);
    const extraContext = document.getElementById("extraAiContext")?.value || "";

    const pendingTasks = deadlineRxTasks.filter(t => Number(t.is_completed) !== 1);

    if (!pendingTasks.length) {
        output.className = "rx-plan-empty";
        output.innerHTML = `
            <div class="rx-empty-icon">✅</div>
            <h4>No pending tasks</h4>
            <p>Everything is marked completed.</p>
        `;
        return;
    }

    output.className = "rx-plan-loading";
    output.innerHTML = `
        <div class="rx-loader"></div>
        <h4>Creating your rescue plan...</h4>
        <p>DeadlineRX is checking task urgency, progress, strictness notes, and available time.</p>
    `;

    try {
        const res = await fetch("/api/generate-plan", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                student_name: studentName,
                student_email: studentEmail,
                tasks: pendingTasks,
                available_hours_today: availableHours,
                extra_ai_context: extraContext
            })
        });

        const data = await res.json();

        if (data.success) {
            output.className = "rx-plan-output";
            output.innerHTML = renderAiPlanText(data.plan);
        } else {
            output.className = "rx-plan-empty";
            output.innerHTML = `
                <div class="rx-empty-icon">⚠️</div>
                <h4>Could not generate plan</h4>
                <p>${escapeHtml(data.message || "Something went wrong.")}</p>
            `;
        }

    } catch (error) {
        console.error("AI plan error:", error);
        output.className = "rx-plan-empty";
        output.innerHTML = `
            <div class="rx-empty-icon">⚠️</div>
            <h4>Error generating AI plan</h4>
            <p>Please check console or try again.</p>
        `;
    }
}

function renderAiPlanText(plan) {
    const safePlan = escapeHtml(plan);

    return `
        <div class="rx-plan-card">
            <div class="rx-plan-top">
                <span class="rx-chip danger">Generated Plan</span>
                <h3>Today’s Rescue Strategy</h3>
                <p>Follow this plan based on your pending teacher and personal tasks.</p>
            </div>

            <div class="rx-section">
                <h4>DeadlineRX Plan</h4>
                <div style="white-space: pre-wrap; line-height: 1.7; color: #334155;">${safePlan}</div>
            </div>
        </div>
    `;
}

function getRiskColor(level) {
    switch (level) {
        case "Critical":
        case "Overdue":
            return { bg: "#fee2e2", text: "#991b1b" };
        case "High Risk":
            return { bg: "#ffedd5", text: "#9a3412" };
        case "Warning":
            return { bg: "#fef3c7", text: "#92400e" };
        case "Safe":
            return { bg: "#dcfce7", text: "#166534" };
        default:
            return { bg: "#e0e7ff", text: "#3730a3" };
    }
}

function escapeHtml(value) {
    return String(value ?? "")
        .replaceAll("&", "&amp;")
        .replaceAll("<", "&lt;")
        .replaceAll(">", "&gt;")
        .replaceAll('"', "&quot;")
        .replaceAll("'", "&#039;");
}

function setupPlannerLocalSave() {
    const hoursInput = document.getElementById("availableHoursToday");
    const contextBox = document.getElementById("extraAiContext");

    if (hoursInput) {
        const savedHours = localStorage.getItem("deadlineRxAvailableHours");
        if (savedHours) {
            hoursInput.value = savedHours;
        }

        hoursInput.addEventListener("input", function () {
            localStorage.setItem("deadlineRxAvailableHours", hoursInput.value);
        });
    }

    if (contextBox) {
        const savedContext = localStorage.getItem("deadlineRxExtraContext");
        if (savedContext) {
            contextBox.value = savedContext;
        }

        contextBox.addEventListener("input", function () {
            localStorage.setItem("deadlineRxExtraContext", contextBox.value);
        });
    }
}
function renderMyTasksView() {
    const container = document.getElementById("myTaskList");
    const countBox = document.getElementById("myTaskCount");

    if (!container) return;

    const tasks = [...deadlineRxTasks].sort((a, b) => {
        const aDone = Number(a.is_completed || 0);
        const bDone = Number(b.is_completed || 0);

        if (aDone !== bDone) return aDone - bDone;

        const aRisk = a.risk?.score || 0;
        const bRisk = b.risk?.score || 0;

        if (aRisk !== bRisk) return bRisk - aRisk;

        return String(a.deadline || "").localeCompare(String(b.deadline || ""));
    });

    const activeTasks = tasks.filter(t => Number(t.is_completed || 0) !== 1);

    if (countBox) {
        countBox.textContent = `${activeTasks.length} active task${activeTasks.length === 1 ? "" : "s"}`;
    }

    if (!tasks.length) {
        container.className = "empty-state";
        container.innerHTML = "No tasks found.";
        return;
    }

    container.className = "my-task-list";

    container.innerHTML = tasks.map(task => {
        const risk = task.risk || {};
        const riskColor = getRiskColor(risk.level);
        const isDone = Number(task.is_completed || 0) === 1;

        let typeLabel = "Teacher Assignment";

        if (task.task_type === "personal") {
            typeLabel = "Personal";
        } else if (task.task_type === "test") {
            typeLabel = "Teacher Test";
        }

        return `
            <div class="my-task-row ${isDone ? "done" : ""}">
                <div class="my-task-main">
                    <div class="my-task-title-line">
                        <span class="task-source ${task.task_type === "personal" ? "personal" : "teacher"}">
                            ${typeLabel}
                        </span>

                        <span class="risk-badge" style="background:${riskColor.bg}; color:${riskColor.text};">
                            ${escapeHtml(risk.level || "Unknown")} ${risk.score ?? 0}/100
                        </span>
                    </div>

                    <h4>${escapeHtml(task.title)}</h4>

                    <p>
                        ${escapeHtml(task.subject || "")}
                        • Due: ${escapeHtml(task.deadline || "No deadline")}
                    </p>
                </div>

                <div class="my-task-progress">
                    <div>
                        <strong>${Number(task.completion_percentage || 0)}%</strong>
                        <span>Progress</span>
                    </div>

                    <div>
                        <strong>${Number(task.estimated_hours_left || 0)}h</strong>
                        <span>Work left</span>
                    </div>

                    <div>
                        <strong>${formatStatus(task.status)}</strong>
                        <span>Status</span>
                    </div>
                </div>

                <div class="my-task-actions">
                    ${
                        isDone
                        ? `
                        <button onclick='unmarkCompleted(${JSON.stringify(task.task_id)}, ${JSON.stringify(task.task_type)})' class="task-uncomplete-btn">
                            ↺ Unmark
                        </button>

                        <button onclick='deleteCompletedTask(${JSON.stringify(task.task_id)}, ${JSON.stringify(task.task_type)})' class="task-delete-final-btn">
                            Delete
                        </button>
                        `
                        : `<button onclick='markCompleted(${JSON.stringify(task.task_id)}, ${JSON.stringify(task.task_type)})' class="task-complete-btn">
                            ✓ Done
                        </button>`
                    }
                </div>
            </div>
        `;
    }).join("");
}

function formatStatus(status) {
    if (!status) return "Not started";

    return String(status)
        .replaceAll("_", " ")
        .replace(/\b\w/g, char => char.toUpperCase());
}
async function deleteCompletedTask(taskId, taskType) {
    if (!confirm("Remove this completed task from your list?")) {
        return;
    }

    try {
        const res = await fetch("delete-completed-task.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                task_id: taskId,
                task_type: taskType
            })
        });

        const data = await res.json();

        if (data.success) {
            await loadDeadlineRxTasks();
        } else {
            alert(data.message || "Could not delete completed task.");
        }

    } catch (error) {
        console.error("Delete completed task error:", error);
        alert("Error deleting completed task.");
    }
}
async function loadLatestAiPlan() {
    const output = document.getElementById("aiPlanOutput");

    if (!output) return;

    try {
        const res = await fetch(
            `/api/latest-plan?student_email=${encodeURIComponent(studentEmail)}`
        );
        const data = await res.json();

        if (data.success && data.has_plan && data.plan) {
            output.className = "rx-plan-output";
            output.innerHTML = renderAiPlanText(data.plan);
        }

    } catch (error) {
        console.error("Could not load latest AI plan:", error);
    }
}