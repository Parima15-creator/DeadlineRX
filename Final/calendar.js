// ===== Academic Calendar Component =====

//Helper Function: Checks if a date should be restricted
// In calendar.js
function isDateLocked(date, eventsList) {
  // Use the provided list or fallback to global
  const list = eventsList || academicEvents;
  if (!list) return { locked: false };

  const d = new Date(date);
  const dateStr = d.toISOString().split("T")[0];

  for (const event of list) {
    if (event.type === "holiday" && event.date === dateStr) {
      return { locked: true, reason: "Holiday: " + event.title };
    }

    if (event.type === "it-exam") {
      const examDate = new Date(event.date);
      const diff = (examDate.getTime() - d.getTime()) / (1000 * 60 * 60 * 24);

      // Lock: 2 days before, Exam Day, 1 day after
      if (diff >= -1 && diff <= 2) {
        let lockReason = (diff === 0) ? "IT Exam Day" : (diff > 0) ? "Prep for IT Exam" : "Post-Exam Rest";
        return { locked: true, reason: lockReason };
      }
    }
  }
  return { locked: false };
}

function renderCalendar(containerId, options) {
  options = options || {};
  const assignments = options.assignments || [];
  const tests = options.tests || [];
  const events = options.events || academicEvents;
  const selectable = options.selectable || false;
  const onDateSelect = options.onDateSelect || null;

  const container = document.getElementById(containerId);
  if (!container) return;

  const DAYS = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
  const MONTHS = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];

  const now = new Date();
  let currentYear = now.getFullYear();
  let currentMonth = now.getMonth();

  function getDateStr(day) {
    return currentYear + "-" + String(currentMonth + 1).padStart(2, "0") + "-" + String(day).padStart(2, "0");
  }

  function render() {
    const firstDay = new Date(currentYear, currentMonth, 1).getDay();
    const daysInMonth = new Date(currentYear, currentMonth + 1, 0).getDate();
    const todayStr = new Date().toISOString().split("T")[0];

    let html = '<div class="calendar-wrap">';
    // Header
    html += '<div class="calendar-header">';
    html += '<button class="btn btn-ghost btn-icon" id="' + containerId + '-prev">&#9664;</button>';
    html += '<h3>' + MONTHS[currentMonth] + ' ' + currentYear + '</h3>';
    html += '<button class="btn btn-ghost btn-icon" id="' + containerId + '-next">&#9654;</button>';
    html += '</div>';

    // Grid
    html += '<div class="calendar-grid">';
    DAYS.forEach(function (d) {
      html += '<div class="calendar-day-label">' + d + '</div>';
    });

    // Empty cells before first day
    for (let i = 0; i < firstDay; i++) {
      html += '<div></div>';
    }

    for (let day = 1; day <= daysInMonth; day++) {
      const dateStr = getDateStr(day);
      const dayAssignments = assignments.filter(function (a) {
        return a.deadline.trim() === dateStr;
      });
      const dayTests = tests.filter(function (t) { return t.date === dateStr; });
      const dayEvents = events.filter(function (e) { return e.date === dateStr; });

      const isToday = dateStr === todayStr;
      // ... inside the for (let day = 1; day <= daysInMonth; day++) loop
      const locked = isDateLocked(dateStr, events);

      let classes = 'calendar-cell';
      if (isToday) classes += ' today';

      if (locked.locked) {
        classes += ' locked';
        // CRITICAL: We DO NOT add 'selectable' if the date is locked
      } else if (selectable) {
        classes += ' selectable';
      }


      // Tooltip
      let tooltipLines = [];
      dayEvents.forEach(function (e) { tooltipLines.push('📅 ' + e.title); });
      dayAssignments.forEach(function (a) { tooltipLines.push('📝 ' + a.title + ' (' + a.subject + ')'); });
      dayTests.forEach(function (t) { tooltipLines.push('📋 Test: ' + t.subject); });
      if (locked.locked) tooltipLines.push('🔒 ' + locked.reason);

      html += '<div class="' + classes + '" data-date="' + dateStr + '">';
      html += '<span class="calendar-day-num">' + day + '</span>';

      // Indicators
      if (dayAssignments.length > 0 || dayTests.length > 0 || dayEvents.length > 0 || locked.locked) {
        html += '<div class="calendar-indicators">';
        if (dayAssignments.length > 0) html += '<span class="calendar-dot dot-assignment"></span>';
        if (dayTests.length > 0) html += '<span class="calendar-dot dot-test"></span>';
        if (dayEvents.length > 0) html += '<span class="calendar-dot dot-event"></span>';
        if (locked.locked) html += '<span class="calendar-dot dot-locked"></span>';
        html += '</div>';
      }

      if (tooltipLines.length > 0) {
        html += '<div class="tooltip">' + tooltipLines.join('<br>') + '</div>';
      }

      html += '</div>';
    }
    html += '</div>';

    // Legend
    html += '<div class="calendar-legend">';
    html += '  <div class="legend-item"><span class="calendar-dot dot-assignment"></span> Assignment</div>';
    html += '  <div class="legend-item"><span class="calendar-dot dot-test"></span> Teacher Test</div>';
    html += '  <div class="legend-item"><span class="calendar-dot dot-event"></span> Holiday</div>';
    html += '  <div class="legend-item"><span class="calendar-dot dot-locked"></span> Institutional Exam (Locked)</div>';
    html += '</div>';

    html += '</div>';
    container.innerHTML = html;

    // Event listeners
    document.getElementById(containerId + '-prev').addEventListener('click', function () {
      currentMonth--;
      if (currentMonth < 0) { currentMonth = 11; currentYear--; }
      render();
    });
    document.getElementById(containerId + '-next').addEventListener('click', function () {
      currentMonth++;
      if (currentMonth > 11) { currentMonth = 0; currentYear++; }
      render();
    });

    if (selectable && onDateSelect) {
      container.querySelectorAll('.calendar-cell.selectable').forEach(function (cell) {
        cell.addEventListener('click', function () {
          const dateStr = this.getAttribute('data-date');
          onDateSelect(dateStr);
        });
      });
    }
  }

  render();

  return {
    refresh: function (newAssignments, newTests) {
      if (newAssignments) options.assignments = newAssignments;
      if (newTests) options.tests = newTests;
      // Re-reference
      assignments.length = 0;
      (newAssignments || []).forEach(function (a) { assignments.push(a); });
      tests.length = 0;
      (newTests || []).forEach(function (t) { tests.push(t); });
      render();
    }
  };
}
