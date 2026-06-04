import htmx from 'htmx.org';

// Add 'js' class to html element when JS is loaded
document.documentElement.classList.add('js');

// --- HTMX Configuration ---

// Disables automatic scrolling to the top of the page on HTMX boosted links
htmx.config.scrollIntoViewOnBoost = false;

// Sets the nonce for inline scripts and styles to comply with Content Security Policy (CSP)
htmx.config.inlineScriptNonce = '@cspNonce';
htmx.config.inlineStyleNonce = '@cspNonce';

// Expose htmx to the global window object for easy debugging and integration
window.htmx = htmx;

// --- General UI Logic ---

// Adds click functionality to a generic toggle element to show/hide its next sibling element
const toggleElement = document.querySelector('#toggle-element');
if (toggleElement) {
  const target = toggleElement.nextElementSibling;
  toggleElement.addEventListener('click', () => {
    toggleElement.classList.toggle('active');
    toggleElement.setAttribute('aria-expanded', toggleElement.classList.contains('active'));
    target.toggleAttribute('hidden');
  });
}

// --- HTMX Event Listeners ---

/**
 * Intercepts HTMX beforeSwap event to check if the response URL ends with '/login'.
 * If a request was redirected to the login page (e.g. due to session expiration),
 * it cancels the swap and perform a full page redirect to the login page.
 */
window.addEventListener('htmx:beforeSwap', (event) => {
  if (event.detail.xhr.responseURL.endsWith('/login')) {
    event.detail.shouldSwap = false;
    window.location.href = '/login';
  }
});

/**
 * After any HTMX request, this listener finds and enables "add" and "delete"
 * buttons that might have been added to the DOM, and attaches the necessary
 * event listeners to them.
 */
window.addEventListener('htmx:afterRequest', (event) => {
  // Only proceed if the request was successful
  if (event.detail.xhr.status !== 200) return;

  // Set up listeners fo delete buttons
  const deleteElements = document.querySelectorAll('.icon-only--delete');
  deleteElements.forEach((element) => {
    element.removeAttribute('hidden');
  });
  addDeleteListener(deleteElements);

  // Set up listener for "add" button to handle dynamic form groups
  const addElement = document.querySelector('.add-button');
  const duplicateElement = document.querySelector('.duplicate-group');
  const duplicateGroup = document.querySelector('#dynamic-group');
  addElement?.removeAttribute('hidden');
  addAddListener(addElement, duplicateElement, duplicateGroup);
});

/**
 * After an HTMX swap, this listener performs two main actions based on the
 * data attributes on the element that triggered the swap:
 *
 * 1. Throws custom events for other parts of th UI to react to (`data-throw-petition-refresh`).
 * 2. Sets up grouped checkboxes to behave like radio buttons (`data-grouped-checkboxes`).
 */
window.addEventListener('htmx:afterSwap', (event) => {
  const eventNames = event.target.dataset.throwPetitionRefresh;
  const groupedCheckboxes = event.target.dataset.groupedCheckboxes;
  if (!eventNames && !groupedCheckboxes) return;

  // Dispatch custom events if specified.
  if (eventNames) {
    // Split the event names by comma and trim any whitespace.
    const eventNameList = eventNames.split(',').map((name) => name.trim());

    // Dispatch each event name on the body.
    eventNameList.forEach((eventName) => {
      document.body.dispatchEvent(new Event(`eventPetitionUpdated-${eventName}`));
    });
  }

  // Handle grouped checkboxes to allow only one selection per group.
  if (groupedCheckboxes) {
    const checkboxes = event.target.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach((e) => {
      if (!e.matches('input[type="checkbox"][data-group]')) return;

      const group = e.dataset.group;
      if (!group) return;

      e.addEventListener('change', (event) => {
        // When a checkbox is checked, uncheck all other checkboxes in the same group.
        checkboxes.forEach((b) => {
          if (b !== e && b.dataset.group == group) {
            b.checked = false;
          }
        });
      });
    });
  }
});

/**
 * Attaches a click listener to an "add" button to duplicate a form group.
 * It clones a template element, updates its IDs and names to be unique,
 * and appends it to the form.
 *
 * @param {HTMLElement} addElement - The button element that triggers adding a new form group.
 * @param {HTMLElement} duplicateElement - The template element to duplicate.
 * @param {HTMLElement} duplicateGroup - The container element where the new form group will be appended.
 */
function addAddListener(addElement, duplicateElement, duplicateGroup) {
  if (!(addElement && duplicateElement && duplicateGroup)) return;

  const addNewElement = () => {
    let id = Date.now().toString();

    const newId = `new-decision-reference-${id}`;
    const clone = duplicateElement.cloneNode(true);
    const clonedLabelReference = clone.querySelector('.new-decision-reference-label');
    const clonedInputReference = clone.querySelector('#new-decision-reference');
    const clonedLabelDate = clone.querySelector('.new-decision-date-label');
    const clonedInputDate = clone.querySelector('#new-decision-reference-date');

    // Update attributes to ensure uniqueness and proper naming
    clonedLabelReference?.setAttribute('for', newId);
    clonedInputReference?.setAttribute('id', newId);
    clonedLabelDate?.setAttribute('for', newId);
    clonedInputDate?.setAttribute('id', newId);
    clonedInputReference?.setAttribute('name', `decision_references[${id}][reference]`);

    // Add the new element to the DOM and clear its values
    duplicateGroup.append(clone);
    clonedInputReference.value = '';
    clonedInputDate.value = '';
    clonedInputReference.focus();
  };

  // Add listener only once to prevent duplicates
  if (!addElement.dataset.listenerAdded) {
    addElement.addEventListener('click', addNewElement);
    addElement.dataset.listenerAdded = 'true';
  }
}

/**
 * Atttaches click listeners to a collection of "delete" buttons. When clicked,
 * the button's parent form group is removed from the DOM.
 *
 * @param {NodeList} deleteElements - A list of elements to attach delete listeners to.
 */
function addDeleteListener(deleteElements) {
  const deleteOnClick = (element) => {
    const inputElement = element.previousElementSibling;
    const parent = element.closest('.form-input-group');
    if (!inputElement?.classList.contains('form-control')) return;
    parent.remove();
    if (!parent.querySelector('input')) {
      parent.remove();
    }
  };

  deleteElements?.forEach((element) => {
    // Use a new listener for each to avoid issues with re-binding
    element.addEventListener('click', () => deleteOnClick(element));
  });
}

// --- Alert Dismissal Logic ---

// Adds a click listener to the dismiss button of a dismissable alert to remove
// the alert from the DOM when clicked.
const alert = document.querySelector('.alert');
const isDismissable = alert?.classList.contains('alert-dismissible');
if (isDismissable) {
  alert?.querySelector('[data-bs-dismiss]').addEventListener('click', () => {
    alert.remove();
  });
}

// --- Objection Events Calendar Table Logic ---

const DATE_LOCALE = 'nl-NL';
const DATE_OPTIONS = { weekday: 'short', day: '2-digit', month: 'short', year: 'numeric' };
const eventCalendarToggleButton = document.querySelector('#toggle-event-calendar');

/**
 * Main function to fill in missing dates in the entire calendar table. Skips
 * the process if the date range is too large (e.g., more than 2 years) to avoid
 * performance issues.
 */
function addMissingDates() {
  const tableRows = document.querySelectorAll('[data-events-calendar-date]');
  if (dateRangeYearsBiggerThan(tableRows, 2)) return;
  for (let i = 0; i < tableRows.length - 1; i++) {
    addMissingDateEntry(tableRows, i);
  }
}

/**
 * Checks if the date range between the first and last row of the calendar table
 * is greater than a specified number of years.
 *
 * @param {NodeList<Element>} tableRows - Rows of the calendar table, each with `data-events-calendar-date` attribute.
 * @param {number} years - The number of years to check against. Default is 2 years.
 * @returns {boolean} - Returns true if the date range exceeds the specified number of years, false otherwise.
 */
function dateRangeYearsBiggerThan(tableRows, years = 2) {
  if (tableRows.length === 0) return false;

  const firstDate = new Date(tableRows[0].dataset.eventsCalendarDate);
  const lastDate = new Date(tableRows[tableRows.length - 1].dataset.eventsCalendarDate);
  const diffInYears = (lastDate - firstDate) / (1000 * 60 * 60 * 24 * 365);

  const shouldSkip = diffInYears > years;
  return shouldSkip;
}

/**
 * Iterates between to existing table rows and inserts new rows for any dates.
 *
 * @param {NodeList<Element>} tableRows - All rows of the calendar table.
 * @param {number} i - The index of the current row to compare with the next row.
 */
function addMissingDateEntry(tableRows, i) {
  if (tableRows.length === 0) return false;

  const currentRow = tableRows[i];
  const nextRow = tableRows[i + 1];
  const currentDate = new Date(currentRow.dataset.eventsCalendarDate);
  const nextDate = new Date(nextRow.dataset.eventsCalendarDate);

  let dateToAdd = new Date(currentDate);
  dateToAdd.setDate(dateToAdd.getDate() + 1);

  // Add rows for each day between the current and the next row's date
  while (
    dateToAdd.toLocaleDateString(DATE_LOCALE, DATE_OPTIONS) !== nextDate.toLocaleDateString(DATE_LOCALE, DATE_OPTIONS)
  ) {
    createDateRow(dateToAdd, nextRow);
    dateToAdd.setDate(dateToAdd.getDate() + 1);
  }
}

/**
 * Creates and inserts a new table row for a given date.
 *
 * @param {Date} dateToAdd - The date for which to create the new row.
 * @param {HTMLElement} nextRow - The row before which the new row will be inserted.
 */
function createDateRow(dateToAdd, nextRow) {
  if (!nextRow?.parentNode) return;

  const newRow = document.createElement('tr');
  newRow.classList.add('no-change-row');
  newRow.setAttribute('data-events-calendar-date', dateToAdd.toISOString().split('T')[0]);
  newRow.setAttribute('data-penalty-today-in-euros', 0);

  const dateCell = document.createElement('td');
  dateCell.classList.add('date');
  newRow.appendChild(dateCell);

  const span = document.createElement('span');
  span.textContent = dateToAdd.toLocaleDateString(DATE_LOCALE, DATE_OPTIONS);

  // Add empty cells to match table structure
  for (let j = 0; j < 3; j++) {
    newRow.appendChild(document.createElement('td'));
  }
  dateCell.appendChild(span);

  nextRow.parentNode.insertBefore(newRow, nextRow);
}

/**
 * Finds today's date in the calendar table, adds a 'today-row' class to it,
 * and calculates/displays the total penalty amounts as of today.
 *
 * If no row for today exists, it appends a new row at the end with the total penalties.
 */
function addTodayClass() {
  const tableRows = document.querySelectorAll('[data-events-calendar-date]');
  const today = new Date();
  let totalPenaltyNoticeOfDefault = 0;
  let totalPenaltyAppealNotTimely = 0;
  let lastNonPenaltyTermSeen = null;

  // Helper function to get penalty summary string
  function getPenaltySummaryString(totalPenaltyNoticeOfDefault, totalPenaltyAppealNotTimely) {
    const penaltyStrings = [];
    if (totalPenaltyNoticeOfDefault > 0)
      penaltyStrings.push(
        `Dwangsom IGS: € ${totalPenaltyNoticeOfDefault.toLocaleString('nl-NL', { maximumFractionDigits: 0 })}`,
      );
    if (totalPenaltyAppealNotTimely > 0)
      penaltyStrings.push(
        `Dwangsom BNT: € ${totalPenaltyAppealNotTimely.toLocaleString('nl-NL', { maximumFractionDigits: 0 })}`,
      );
    return penaltyStrings.join(' | ');
  }

  // Iterate through table rows to find today's date and calculate penalties
  tableRows.forEach((row) => {
    const rowDate = new Date(row.dataset.eventsCalendarDate);

    // Determine last non-penalty term seen and accumulate penalties
    const term = row.dataset.applicableTerm;
    lastNonPenaltyTermSeen = term && term !== 'penalty_period' ? term : lastNonPenaltyTermSeen;
    if (lastNonPenaltyTermSeen === 'appeal_not_timely')
      totalPenaltyAppealNotTimely += parseFloat(row.dataset.penaltyTodayInEuros || 0);
    if (lastNonPenaltyTermSeen === 'notice_of_default')
      totalPenaltyNoticeOfDefault += parseFloat(row.dataset.penaltyTodayInEuros || 0);

    // If the row is for today, add the 'today-row' class and display the penalty summary.
    if (rowDate.toDateString() === today.toDateString()) {
      row.classList.add('today-row');
      const penaltySpan = row.querySelector('.today-penalty');
      if (!penaltySpan) return;
      penaltySpan.textContent = getPenaltySummaryString(totalPenaltyNoticeOfDefault, totalPenaltyAppealNotTimely);
    }
  });

  // If no 'today-row' was found but there are penalties, add a placeholder summary at the end.
  const todayRowExists = Array.from(tableRows).some((row) => row.classList.contains('today-row'));
  if (!todayRowExists && (totalPenaltyNoticeOfDefault > 0 || totalPenaltyAppealNotTimely > 0)) {
    const table = document.querySelector('.objection-events-calendar-table tbody');
    if (!table) return;

    const newRow = document.createElement('tr');
    newRow.classList.add('no-change-row');
    const dateCell = document.createElement('td');
    dateCell.setAttribute('colspan', '3');
    newRow.appendChild(dateCell);

    const todayPenaltyCell = document.createElement('td');
    todayPenaltyCell.classList.add('today-penalty');
    todayPenaltyCell.textContent = getPenaltySummaryString(totalPenaltyNoticeOfDefault, totalPenaltyAppealNotTimely);
    newRow.appendChild(todayPenaltyCell);
    table.appendChild(newRow);
  }
}

/**
 * Collapses table rows by hiding rows that have the same `data-objection-events-calendar` value
 * as the previous row, creating a more compact view.
 *
 * @param {HTMLElement} table - The table element to collapse rows in.
 * @param {NodeList<Element>} tableRows - All rows of the table with `data-objection-events-calendar` attribute.
 * @param {boolean} showLastInGroup - If true, keeps the last row in a group visible; otherwise, hides all but the first.
 */
function collapseTable(table, tableRows, showLastInGroup = false) {
  if (!table || !tableRows) return;

  let dateFirstInGroup = null; // FIXME: Currently unused variable
  let dateLastInGroup = null; // FIXME: Currently unused variable

  for (let i = 1; i < tableRows.length - 1; i++) {
    const previousRow = tableRows[i - 1];
    const currentRow = tableRows[i];
    const nextRow = tableRows[i + 1];
    const isEqualToNext = currentRow.dataset.objectionEventsCalendar === nextRow.dataset.objectionEventsCalendar;
    const isEqualToPrevious =
      currentRow.dataset.objectionEventsCalendar === previousRow.dataset.objectionEventsCalendar;
    const shouldCollapse = showLastInGroup ? isEqualToNext && isEqualToPrevious : isEqualToPrevious;
    if (shouldCollapse) currentRow.classList.add('no-change-row');
  }

  table.classList.remove('hidden');
}

/**
 * Toggles the visibility of collapsed rows in the calendar table and updates
 * the button text accordingly.
 *
 * @param {HTMLElement} element - The button element that triggers the toggle.
 */
function toggleTable(element) {
  if (!element) return;
  const inGroupMiddleElements = document.querySelectorAll('.no-change-row,.no-change-row-show');
  inGroupMiddleElements.forEach((element) => {
    element.classList.toggle('no-change-row');
    element.classList.toggle('no-change-row-show');
  });
  element.textContent = element.textContent.trim() === 'Klap uit' ? 'Klap in' : 'Klap uit';
}

// --- Initializations ---

// Initialize the objection events calendar table by adding missing dates,
addMissingDates();

// Highlight today's date and penalties
addTodayClass();

// Collapse the table rows for a compact view
collapseTable(
  document.querySelector('.objection-events-calendar-table'),
  document.querySelectorAll('[data-objection-events-calendar]'),
);

// Set up the toggle button to expand/collapse the table rows
eventCalendarToggleButton?.addEventListener('click', () => toggleTable(eventCalendarToggleButton));

// --- Petition Event Duration <-> Deadline Sync ---

function initTermDeadlineSync() {
  const durationInput = document.getElementById('duration');
  const termDeadlineInput = document.getElementById('term_deadline');
  const eventDateInput = document.getElementById('date');

  if (!durationInput || !termDeadlineInput || !eventDateInput) return;

  function calculateDeadline() {
    const eventDate = new Date(eventDateInput.value);
    const duration = parseInt(durationInput.value, 10);
    if (isNaN(eventDate.getTime()) || isNaN(duration)) return;

    const deadline = new Date(eventDate);
    deadline.setDate(deadline.getDate() + duration);
    termDeadlineInput.value = deadline.toISOString().split('T')[0];
  }

  function calculateDuration() {
    const eventDate = new Date(eventDateInput.value);
    const deadlineDate = new Date(termDeadlineInput.value);
    if (isNaN(eventDate.getTime()) || isNaN(deadlineDate.getTime())) return;

    const diffTime = deadlineDate - eventDate;
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    if (diffDays >= 0) {
      durationInput.value = diffDays;
    }
  }

  // Calculate initial deadline if duration is already filled
  calculateDeadline();

  // Listen for input on duration field
  durationInput.addEventListener('input', calculateDeadline);

  // Listen for both input and change on deadline field (handles keyboard & picker)
  termDeadlineInput.addEventListener('input', calculateDuration);
  termDeadlineInput.addEventListener('change', calculateDuration);

  // Also recalculate deadline when event date changes
  eventDateInput.addEventListener('change', calculateDeadline);
}

initTermDeadlineSync();
