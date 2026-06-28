# DeadlineRX ⚡  
## AI-Powered Academic Rescue Planner

DeadlineRX is an AI-powered academic planning and deadline rescue system designed to help students manage academic pressure more effectively. It brings teacher-given assignments, scheduled tests, private personal tasks, progress tracking, deadline risk scoring, and AI-generated rescue planning into one platform.

The goal of DeadlineRX is not just to show deadlines, but to help students decide what to do first, how much time to spend, and how to handle last-minute academic overload.

---

## Live Project Links

**Deployed Application:**  
https://deadlinerx-908698198038.asia-south1.run.app

**GitHub Repository:**  
https://github.com/Parima15-creator/DeadlineRX

---

## Hackathon Track

**Track:** The Last-Minute Life Saver

DeadlineRX fits this track because it helps students during deadline pressure by creating a practical rescue plan based on real academic workload, task progress, available time, and personal commitments.

---

## Problem Statement

Students often struggle to manage multiple assignments, tests, and personal responsibilities at the same time. Most students know what tasks are pending, but they do not always know:

- Which task should be done first
- How much time should be spent on each task
- Whether a personal task is more urgent than an academic task
- How to handle incomplete work close to the deadline
- How to make a realistic plan when time is limited

Traditional academic dashboards and reminder apps only display deadlines. They do not understand student progress, urgency, workload, or real-life constraints.

---

## Solution

DeadlineRX solves this problem by acting as an academic rescue planner.

Faculty can add assignments and tests for a class. Students can view those tasks, add private personal tasks, update progress, track risk levels, and generate an AI-based rescue plan.

The AI planner considers:

- Deadline closeness
- Task type
- Completion percentage
- Estimated work left
- Difficulty level
- Weightage / importance
- Student’s available time
- Extra student context
- Personal real-world deadlines

Based on this, DeadlineRX generates a clear and practical action plan for the student.

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
- View class schedule using calendar view
- Edit created assignments and tests
- Delete created assignments and tests
- Teacher-given tasks become visible to students of that class
  
<img width="1901" height="1079" alt="Screenshot 2026-06-28 110216" src="https://github.com/user-attachments/assets/e7d5ef5a-7532-44e6-a387-d3d04f1ad6bc" />

<img width="1919" height="1079" alt="Screenshot 2026-06-28 110238" src="https://github.com/user-attachments/assets/283b1dd5-5f5b-45ae-ade8-44343937fd27" />

### Student Portal

- Student login
- View teacher-given assignments
- View scheduled tests
- Add private personal tasks
- Update progress percentage
- Enter estimated work hours left
- Save task progress
- Mark tasks as completed
- Unmark completed tasks if marked by mistake
- Delete completed tasks from personal task history
- View combined task list in My Tasks
- Generate AI rescue plans
- View latest generated AI plan even after refreshing
- Calendar view for assignments, tests, holidays, and personal tasks
  
<img width="1899" height="1079" alt="Screenshot 2026-06-28 114136" src="https://github.com/user-attachments/assets/80463d09-7ee6-441b-9589-dbda58cd7f7f" />

<img width="1896" height="1079" alt="Screenshot 2026-06-28 114434" src="https://github.com/user-attachments/assets/636d5987-4b19-494f-bdb4-9564ec2724c6" />

<img width="1919" height="1079" alt="Screenshot 2026-06-28 134216" src="https://github.com/user-attachments/assets/6f348d86-e2ff-4d5e-b47f-9dfe97a9f7b7" />

---

## Deadline Risk Score

Each task gets a deadline risk score based on:

- How close the deadline is
- How much work is pending
- Difficulty level
- Weightage
- Estimated hours left
- Completion progress
- Task status

Risk levels help students quickly understand which tasks need urgent attention.

Example risk levels:

- Safe
- Warning
- Critical

---

## AI Rescue Planner

The AI planner creates a structured rescue plan using the Gemini API.

The generated plan may include:

- Today’s focus
- Reason behind the priority order
- Priority order of tasks
- Time-block plan
- Minimum submit-worthy version strategy
- What can wait
- Damage-control advice

This helps students focus on completing the most important work first instead of panicking.

---

## Example Use Case

A student has:

- A Software Engineering assignment due soon
- A DFA test tomorrow
- A personal painting task needed for an exhibition tomorrow

DeadlineRX does not blindly prioritize only academic work. It understands that a personal task with a real-world exhibition deadline can also be urgent.

The generated plan may suggest:

1. Finish the painting first because it is needed for display.
2. Revise DFA because the test is tomorrow.
3. Complete the minimum submit-worthy version of the assignment.
4. Avoid unnecessary decoration or perfection until the core work is completed.

<img width="1896" height="1079" alt="Screenshot 2026-06-28 114455" src="https://github.com/user-attachments/assets/b7d745ad-c2b6-4d85-8b47-af1b0e9c4bda" />
<img width="1898" height="1079" alt="Screenshot 2026-06-28 114506" src="https://github.com/user-attachments/assets/eea19f2b-41e9-45fe-853c-82039a06d144" />

---

## Technologies Used

| Category | Technology |
|---|---|
| Frontend | HTML, CSS, JavaScript |
| Backend | PHP |
| Database | MySQL |
| AI Integration | Gemini API |
| AI Tool | Google AI Studio |
| Local Server | XAMPP / Apache |
| Cloud Deployment | Google Cloud Run |
| Cloud Database | Cloud SQL for MySQL |
| Version Control | Git and GitHub |

---

## Google Technologies Used

DeadlineRX uses Google technologies in the following ways:

1. **Google AI Studio**  
   Used to create and test the Gemini API key and prompts.

2. **Gemini API**  
   Used to generate personalized AI rescue plans for students.

3. **Google Cloud Run**  
   Used to deploy the PHP web application online.

4. **Cloud SQL for MySQL**  
   Used to support cloud-based database hosting.

---

## Database Tables

The project uses the following main tables:

| Table Name | Purpose |
|---|---|
| department | Stores department details |
| class | Stores class/division details |
| teacher | Stores faculty login details |
| student | Stores student login and class details |
| assignment | Stores teacher-given assignments |
| test | Stores teacher-given tests |
| academic_calendar | Stores holidays and academic events |
| student_personal_tasks | Stores private personal tasks added by students |
| student_task_progress | Stores student progress, hours left, and task status |
| ai_plans | Stores generated AI rescue plans |

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
│   ├── global-font.css
│   │
│   ├── student-dashboard.php
│   ├── student-dashboard.css
│   ├── teacher-dashboard.php
│   ├── teacher-dashboard.css
│   │
│   ├── deadline-rx.js
│   ├── calendar.js
│   ├── calendar-data.js
│   │
│   ├── fetch-data.php
│   ├── student-tasks-api.php
│   ├── save-task.php
│   ├── update-task-progress.php
│   ├── delete-personal-task.php
│   ├── delete-completed-task.php
│   │
│   ├── delete-teacher-task.php
│   ├── edit-teacher-task.php
│   ├── update-teacher-task.php
│   │
│   ├── generate-ai-plan.php
│   ├── get-latest-ai-plan.php
│   ├── calculate-risk.php
│   │
│   ├── db_config.php
│   ├── gemini_config.php
│   └── Dockerfile
│
├── README.md
└── LICENSE
```

---

## Local Setup Instructions

### 1. Clone the Repository

```bash
git clone https://github.com/Parima15-creator/DeadlineRX.git
```

### 2. Move the Project to XAMPP

Place the project folder inside:

```text
xampp/htdocs/
```

### 3. Start XAMPP

Start:

- Apache
- MySQL

### 4. Import the Database

Open phpMyAdmin and import:

```text
Final/database/deadlinerx_updates.sql
```

### 5. Configure Database Connection

Update `db_config.php` if required.

For local XAMPP, default values are usually:

```text
DB_HOST = localhost
DB_USER = root
DB_PASS = empty
DB_NAME = deadlinerx
```

### 6. Configure Gemini API

Use `gemini_config.php` to connect Gemini API.

For security, the real API key should not be hardcoded in public GitHub repositories.

Recommended format:

```php
define('GEMINI_API_KEY', getenv('GEMINI_API_KEY') ?: 'PASTE_YOUR_GEMINI_API_KEY_HERE');
```

### 7. Run the Project Locally

Open:

```text
http://localhost/DeadlineRX/Final/index.html
```

---

## Cloud Deployment

DeadlineRX is deployed using Google Cloud Run.

The PHP application is containerized using a Dockerfile and deployed online. The database can be connected using Cloud SQL for MySQL. Gemini API is used through backend integration for AI rescue plan generation.

Deployed link:

```text
https://deadlinerx-908698198038.asia-south1.run.app
```

---

## Security Note

- The Gemini API key is not exposed to users from the frontend.
- API calls are handled through the PHP backend.
- Student personal tasks are private and visible only to the logged-in student.
- Students cannot delete teacher-created tasks from the main database.
- Completed teacher-given tasks are hidden only from the student’s own view.
- API keys should be stored using environment variables during deployment.

---

## Impact of DeadlineRX

DeadlineRX helps students:

- Reduce last-minute academic panic
- Prioritize urgent tasks
- Manage academic and personal workload together
- Track real task progress
- Understand deadline risk
- Get an AI-generated action plan instead of only reminders

It also helps faculty understand class workload and avoid deadline clustering.

---

## Future Scope

In the future, DeadlineRX can be improved with:

- Email or WhatsApp deadline alerts
- Push notifications
- Mobile app version
- Google Calendar integration
- Weekly AI study plans
- Faculty workload analytics
- Student productivity insights
- Multi-college support
- More advanced AI personalization

---

## Developer

Developed by: **Parima Tendulkar**

Project: **DeadlineRX — AI-Powered Academic Rescue Planner**

Hackathon Track: **The Last-Minute Life Saver**

---

## License

This project is created for hackathon and academic demonstration purposes.
