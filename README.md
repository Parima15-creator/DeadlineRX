# DeadlineRX ⚡

## AI-Powered Academic Rescue Planner

**DeadlineRX** is an AI-powered academic planning system designed to help students manage deadline pressure more effectively.  
It combines teacher-given assignments, scheduled tests, private personal tasks, progress tracking, risk scoring, and AI-generated rescue planning into one simple platform.

The goal of DeadlineRX is not just to show deadlines, but to help students decide **what to do first, how much time to spend, and how to handle last-minute academic overload**.

---

## Hackathon Track

**Track:** The Last-Minute Life Saver

DeadlineRX fits this track because it helps students during deadline pressure by creating a practical rescue plan based on real academic workload, task progress, and available time.

---

## Problem Statement

Students often struggle to manage multiple assignments, tests, and personal responsibilities at the same time.  
Most students know *what* tasks are pending, but they do not always know:

- Which task should be done first
- How much time should be spent on each task
- Whether a personal task is more urgent than an academic task
- How to handle incomplete work close to the deadline
- How to make a realistic plan when time is limited

Traditional academic dashboards only display deadlines.  
They do not understand student progress, urgency, workload, or real-life constraints.

---

## Solution

DeadlineRX solves this problem by acting as an **academic rescue planner**.

Faculty can add assignments and tests for a class.  
Students can view those tasks, add their own private personal tasks, update task progress, and generate an AI-based rescue plan.

The AI planner considers:

- Deadline closeness
- Task type
- Completion percentage
- Estimated work left
- Difficulty level
- Weightage / importance
- Student's available time
- Extra student context
- Personal real-world deadlines

Based on this, DeadlineRX generates a clear action plan for the student.

---

## What Does RX Mean?

In DeadlineRX, **RX** represents a “prescription” for deadline stress.

Just like a medical prescription gives a treatment plan, DeadlineRX gives students a practical plan to handle academic pressure and deadline overload.

---

## Key Features

### Faculty Portal

- Faculty login
- Add assignments for a class
- Add tests for a class
- Set due dates, difficulty, weightage, and description
- Teacher-given tasks become visible to students of that class

### Student Portal

- Student login
- View teacher-given assignments
- View scheduled tests
- Add private personal tasks
- Update progress percentage
- Enter estimated work hours left
- Mark tasks as completed
- Unmark completed tasks if marked by mistake
- Delete completed tasks from personal task history
- Generate AI rescue plans
- View latest generated AI plan even after refreshing
- Calendar view for assignments, tests, holidays, and personal tasks

### Deadline Risk Score

Each task gets a risk score based on:

- How close the deadline is
- How much work is pending
- Difficulty level
- Weightage
- Estimated hours left
- Completion progress

Risk levels help students quickly understand which tasks need urgent attention.

### AI Rescue Planner

The AI planner creates a structured plan with:

- Today's focus
- Reason behind the priority order
- Priority order of tasks
- Time-block plan
- Minimum version strategy
- What can wait
- Damage-control advice

This helps students focus on completing the most important work first instead of panicking.

---

## Example Use Case

A student has:

- A Software Engineering assignment due soon
- A DFA test tomorrow
- A personal painting task needed for an exhibition tomorrow

DeadlineRX does not blindly prioritize only academic work.  
It understands that a personal task with a real-world exhibition deadline can be more urgent.

The generated plan may suggest:

1. Finish the painting first because it is needed for display.
2. Revise DFA because the test is tomorrow.
3. Complete the minimum submit-worthy version of the assignment.
4. Avoid unnecessary decoration or perfection until core work is completed.

---

## Tech Stack

| Category | Technology |
|---|---|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP |
| Database | MySQL |
| AI Integration | Gemini API |
| Server | XAMPP / Apache |
| Version Control | Git and GitHub |

---

## Database Tables

The project uses the following main tables:

- `department`
- `class`
- `teacher`
- `student`
- `assignment`
- `test`
- `academic_calendar`
- `student_personal_tasks`
- `student_task_progress`
- `ai_plans`

---

## Folder Structure

```text
DeadlineRX/
│
├── Final/
│   │
│   ├── Login/
│   │   ├── student-login.html
│   │   ├── student-login-process.php
│   │   ├── teacher-login.html
│   │   └── teacher-login-process.php
│   │
│   ├── database/
│   │   └── deadlinerx_updates.sql
│   │
│   ├── index.html
│   ├── index.css
│   ├── student-dashboard.php
│   ├── student-dashboard.css
│   ├── teacher-dashboard.php
│   ├── teacher-dashboard.css
│   ├── deadline-rx.js
│   ├── calendar.js
│   ├── calendar-data.js
│   ├── fetch-data.php
│   ├── student-tasks-api.php
│   ├── save-task.php
│   ├── update-task-progress.php
│   ├── delete-personal-task.php
│   ├── delete-completed-task.php
│   ├── generate-ai-plan.php
│   ├── get-latest-ai-plan.php
│   ├── calculate-risk.php
│   ├── db_config.php
│   └── gemini_config.php
│
├── README.md
└── LICENSE
