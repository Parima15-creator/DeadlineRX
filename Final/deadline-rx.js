let deadlineRxTasks = [];

document.addEventListener("DOMContentLoaded", function () {
    injectDeadlineRxPanel();
    loadDeadlineRxTasks();
});

function injectDeadlineRxPanel() {
    const assignmentBox = document.getElementById("studentAssignmentList");
    const testBox = document.getElementById("studentTestList");

    if (!assignmentBox || !testBox) return;

    let parent = assignmentBox.closest(".content-grid") || assignmentBox.parentElement.parentElement;

    const aiPanel = document.createElement("section");
    aiPanel.className = "tile";
    aiPanel.innerHTML = `
        <h3>DeadlineRX AI Rescue Planner</h3>
        <p style="color: var(--aslb-muted); margin: 10px 0 18px;">
            Enter how much time you have today and generate a realistic action plan.
        </p>

        <div style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center; margin-bottom: 15px;">
            <label style="font-weight: 600;">Available hours today:</label>
            <input type="number" id="availableHoursToday" min="0.5" step="0.5" value="3"
                   style="padding: 10px; border: 1px solid var(--aslb-border); border-radius: 10px; width: 140px;">
            <button onclick="generateAiPlan()" 
                    style="padding: 10px 16px; background: var(--aslb-blue); color: white; border: none; border-radius: 10px; cursor: pointer; font-weight: 600;">
                Generate My Rescue Plan
            </button>
        </div>

        <div id="aiPlanOutput" style="white-space: pre-wrap; background: #f8fafc; border: 1px solid var(--aslb-border); border-radius: 14px; padding: 18px; color: #334155;">
            Your AI plan will appear here.
        </div>
    `;

    parent.appendChild(aiPanel);
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

        renderTaskLists();

    } catch (error) {
        console.error("Error loading DeadlineRX tasks:", error);
    }
}

function renderTaskLists() {
    const assignments = deadlineRxTasks.filter(t => t.task_type === "assignment");
    const tests = deadlineRxTasks.filter(t => t.task_type === "test");

    renderTaskCards("studentAssignmentList", assignments, "No pending assignments.");
    renderTaskCards("studentTestList", tests, "No tests scheduled.");
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
    const disabledStyle = task.is_completed == 1 ? "opacity: 0.65;" : "";

    return `
        <div class="deadline-card" style="border: 1px solid var(--aslb-border); border-radius: 16px; padding: 18px; margin-bottom: 14px; background: white; ${disabledStyle}">
            <div style="display: flex; justify-content: space-between; gap: 12px; align-items: flex-start;">
                <div>
                    <h4 style="margin: 0 0 6px; color: var(--aslb-text);">${escapeHtml(task.title)}</h4>
                    <p style="margin: 0; color: var(--aslb-muted);">
                        ${escapeHtml(task.subject)} • Due: ${escapeHtml(task.deadline)}
                    </p>
                </div>
                <span style="background: ${badgeColor.bg}; color: ${badgeColor.text}; padding: 6px 10px; border-radius: 999px; font-size: 0.8rem; font-weight: 700;">
                    ${escapeHtml(risk.level || "Unknown")} ${risk.score ?? 0}/100
                </span>
            </div>

            <p style="margin: 12px 0; color: #475569;">
                ${escapeHtml(risk.reason || "Risk calculated from deadline and progress.")}
            </p>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 12px; margin-top: 12px;">
                <div>
                    <label style="font-size: 0.8rem; font-weight: 700;">Progress</label>
                    <select id="progress-${task.task_id}" style="width: 100%; padding: 9px; border-radius: 9px; border: 1px solid var(--aslb-border);">
                        ${progressOptions(task.completion_percentage)}
                    </select>
                </div>

                <div>
                    <label style="font-size: 0.8rem; font-weight: 700;">Hours left</label>
                    <input id="hours-${task.task_id}" type="number" min="0" step="0.5" value="${task.estimated_hours_left || ""}"
                           placeholder="${risk.estimated_hours_left || 1}"
                           style="width: 100%; padding: 9px; border-radius: 9px; border: 1px solid var(--aslb-border);">
                </div>

                <div>
                    <label style="font-size: 0.8rem; font-weight: 700;">Status</label>
                    <select id="status-${task.task_id}" style="width: 100%; padding: 9px; border-radius: 9px; border: 1px solid var(--aslb-border);">
                        <option value="not_started" ${task.status === "not_started" ? "selected" : ""}>Not started</option>
                        <option value="in_progress" ${task.status === "in_progress" ? "selected" : ""}>In progress</option>
                        <option value="completed" ${task.status === "completed" ? "selected" : ""}>Completed</option>
                    </select>
                </div>
            </div>

            <div style="display: flex; gap: 10px; margin-top: 14px; flex-wrap: wrap;">
                <button onclick='saveTaskProgress(${JSON.stringify(task.task_id)}, ${JSON.stringify(task.task_type)})'
                        style="padding: 9px 14px; border: 1px solid var(--aslb-border); background: white; border-radius: 9px; cursor: pointer; font-weight: 600;">
                    Save Progress
                </button>

                <button onclick='markCompleted(${JSON.stringify(task.task_id)}, ${JSON.stringify(task.task_type)})'
                        style="padding: 9px 14px; border: none; background: #10b981; color: white; border-radius: 9px; cursor: pointer; font-weight: 600;">
                    Mark Completed
                </button>
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
                status: status
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

async function generateAiPlan() {
    const output = document.getElementById("aiPlanOutput");
    const availableHours = Number(document.getElementById("availableHoursToday")?.value || 3);

    output.textContent = "Generating your DeadlineRX rescue plan...";

    try {
        const res = await fetch("generate-ai-plan.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({
                tasks: deadlineRxTasks,
                available_hours_today: availableHours
            })
        });

        const data = await res.json();

        if (data.success) {
            output.textContent = data.plan;
        } else {
            output.textContent = data.message || "Could not generate plan.";
        }

    } catch (error) {
        console.error("AI plan error:", error);
        output.textContent = "Error generating AI plan.";
    }
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