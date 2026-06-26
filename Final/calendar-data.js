// Ensure these variables are declared globally at the very top of calendar-data.js
let academicEvents = [];
let academicAssignments = [];
let academicTests = []; // Added this global [cite: 1, 2]

async function syncDatabase() {
  try {
    const response = await fetch('fetch-data.php'); // Ensure this matches your sidebar filename
    const data = await response.json();

    // Save the fetched data to the GLOBAL variables [cite: 11, 12]
    academicAssignments = data.assignments || [];
    academicEvents = data.events || [];
    academicTests = data.tests || []; // Save the tests from your PHP query [cite: 14, 271]

    console.log("Database Sync Complete");

    // Trigger the UI to refresh with the new data [cite: 17, 18]
    if (typeof refreshTeacherCalendar === 'function') refreshTeacherCalendar();
    if (typeof initDashboard === 'function') initDashboard();

  } catch (err) {
    console.error("Sync Error:", err);
  }
}

/**
 * 3. STRESS CALCULATION
 * This stays as is, but now consumes data from your 'assignment' table
 */
function calculateStressScore(params) {
  const { pages, difficulty, type, daysAvailable, assignmentsInWeek } = params;

  const difficultyScore = difficulty === "High" ? 3 : 2;
  const typeScore = type === "Practical" ? 1.3 : 1;
  const pageScore = Math.min(pages / 5, 3);
  const timeScore = daysAvailable <= 2 ? 3 : daysAvailable <= 5 ? 2 : 1;
  const weekLoadScore = Math.min(assignmentsInWeek * 0.8, 3);

  const raw = (difficultyScore + pageScore + timeScore + weekLoadScore) * typeScore;
  return Math.max(1, Math.min(10, Math.round(raw * 10) / 10));
}

function getStressLevel(score) {
  if (score <= 4) return "low";
  if (score <= 7) return "medium";
  return "high";
}

// Initialize sync
syncDatabase();