import htmx from 'htmx.org';

htmx.config.scrollIntoViewOnBoost = false;
htmx.config.inlineScriptNonce = '@cspNonce';
htmx.config.inlineStyleNonce = '@cspNonce';

window.htmx = htmx;

const toggleElement = document.querySelector('#toggle-element');
if (toggleElement) {
  const target = toggleElement.nextElementSibling;
  toggleElement.addEventListener('click', () => {
    toggleElement.classList.toggle('active');
    toggleElement.setAttribute('aria-expanded', toggleElement.classList.contains('active'));
    target.toggleAttribute('hidden');
  });
}

window.addEventListener('htmx:beforeSwap', (event) => {
  if (event.detail.xhr.responseURL.endsWith('/login')) {
    event.detail.shouldSwap = false;
    window.location.href = '/login';
  }
});

window.addEventListener('htmx:afterRequest', (event) => {
  if (event.detail.xhr.status !== 200) return;

  const deleteElements = document.querySelectorAll('.icon-only--delete');
  deleteElements.forEach((element) => {
    element.removeAttribute('hidden');
  });
  addDeleteListener(deleteElements);

  const addElement = document.querySelector('.add-button');
  const duplicateElement = document.querySelector('.duplicate-group');
  const duplicateGroup = document.querySelector('#dynamic-group');
  addElement?.removeAttribute('hidden');
  addAddListener(addElement, duplicateElement, duplicateGroup);
});

window.addEventListener('htmx:afterSwap', (event) => {
  const eventNames = event.target.dataset.throwPetitionRefresh;
  const groupedCheckboxes = event.target.dataset.groupedCheckboxes;
  if (!eventNames && !groupedCheckboxes) return;

  if (eventNames) {
    // Split the event names by comma and trim any whitespace
    const eventNameList = eventNames.split(',').map((name) => name.trim());

    // Dispatch each event name
    eventNameList.forEach((eventName) => {
      document.body.dispatchEvent(new Event(`eventPetitionUpdated-${eventName}`));
    });
  }

  if (groupedCheckboxes) {
    const checkboxes = event.target.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(e => {
      if (!e.matches('input[type="checkbox"][data-group]')) return;

      const group = e.dataset.group;
      if (!group) return;

      e.addEventListener('change', (event) => {
        checkboxes.forEach(b => {
          if (b !== e && b.dataset.group == group) {
            b.checked = false;
          }
        });
      })
    });
  }
});

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

    clonedLabelReference?.setAttribute('for', newId);
    clonedInputReference?.setAttribute('id', newId);
    clonedLabelDate?.setAttribute('for', newId);
    clonedInputDate?.setAttribute('id', newId);
    clonedInputReference?.setAttribute('name', `decision_references[${id}][reference]`);
    duplicateGroup.append(clone);
    clonedInputReference.value = '';
    clonedInputDate.value = '';
    clonedInputReference.focus();
  };

  if (!addElement.dataset.listenerAdded) {
    addElement.addEventListener('click', addNewElement);
    addElement.dataset.listenerAdded = 'true';
  }
}

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
    element.addEventListener('click', () => deleteOnClick(element));
  });
}

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


// Add missing dates to the table

function addMissingDates() {
  const tableRows = document.querySelectorAll('[data-events-calendar-date]');
  if (dateRangeYearsBiggerThan(tableRows, 2)) return;
  for (let i = 0; i < tableRows.length - 1; i++) {
    addMissingDateEntry(tableRows, i);
  }
}

function dateRangeYearsBiggerThan(tableRows, years = 2) {
  // Return true if date range is more than 2 years
  if (tableRows.length === 0) return false;
  const firstDate = new Date(tableRows[0].dataset.eventsCalendarDate);
  const lastDate = new Date(tableRows[tableRows.length - 1].dataset.eventsCalendarDate);
  const diffInYears = (lastDate - firstDate) / (1000 * 60 * 60 * 24 * 365);
  const shouldSkip = diffInYears > years;
  return shouldSkip;
}

function addMissingDateEntry(tableRows, i) {
  if (tableRows.length === 0) return false;
  const currentRow = tableRows[i];
  const nextRow = tableRows[i + 1];
  const currentDate = new Date(currentRow.dataset.eventsCalendarDate);
  const nextDate = new Date(nextRow.dataset.eventsCalendarDate);

  let dateToAdd = new Date(currentDate);
  dateToAdd.setDate(dateToAdd.getDate() + 1);
  while (dateToAdd.toLocaleDateString(DATE_LOCALE, DATE_OPTIONS) !== nextDate.toLocaleDateString(DATE_LOCALE, DATE_OPTIONS)) {
    createDateRow(dateToAdd, nextRow);
    dateToAdd.setDate(dateToAdd.getDate() + 1);
  }
}

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
  for (let j = 0; j < 3; j++) {
    newRow.appendChild(document.createElement('td'));
  }
  dateCell.appendChild(span);

  nextRow.parentNode.insertBefore(newRow, nextRow);
}

addMissingDates();


// Add today class and total penalty for today

function addTodayClass() {
  const tableRows = document.querySelectorAll('[data-events-calendar-date]');
  const today = new Date();
  let totalPenaltyNoticeOfDefault = 0;
  let totalPenaltyAppealNotTimely = 0;
  let lastNonPenaltyTermSeen = null;

  // Helper function to get penalty summary string
  function getPenaltySummaryString(totalPenaltyNoticeOfDefault, totalPenaltyAppealNotTimely) {
    const penaltyStrings = [];
    if (totalPenaltyNoticeOfDefault > 0) penaltyStrings.push(`Dwangsom IGS: € ${totalPenaltyNoticeOfDefault.toLocaleString('nl-NL', { maximumFractionDigits: 0 })}`);
    if (totalPenaltyAppealNotTimely > 0) penaltyStrings.push(`Dwangsom BNT: € ${totalPenaltyAppealNotTimely.toLocaleString('nl-NL', { maximumFractionDigits: 0 })}`);
    return penaltyStrings.join(' | ');
  }

  // Iterate through table rows to find today's date and calculate penalties
  tableRows.forEach((row) => {
    const rowDate = new Date(row.dataset.eventsCalendarDate);

    // Determine last non-penalty term seen and accumulate penalties
    const term = row.dataset.applicableTerm;
    lastNonPenaltyTermSeen = term && term !== 'penalty_period' ? term : lastNonPenaltyTermSeen;
    if (lastNonPenaltyTermSeen === 'appeal_not_timely') totalPenaltyAppealNotTimely += parseFloat(row.dataset.penaltyTodayInEuros || 0);
    if (lastNonPenaltyTermSeen === 'notice_of_default') totalPenaltyNoticeOfDefault += parseFloat(row.dataset.penaltyTodayInEuros || 0);

    // Add today class and display total penalties
    if (rowDate.toDateString() === today.toDateString()) {
      row.classList.add('today-row');
      const penaltySpan = row.querySelector('.today-penalty')
      if (!penaltySpan) return;
      penaltySpan.textContent = getPenaltySummaryString(totalPenaltyNoticeOfDefault, totalPenaltyAppealNotTimely);
    }
  });

  // If no today row was found, add a placeholder row at the end
  const todayRowExists = Array.from(tableRows).some(row => row.classList.contains('today-row'));
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
addTodayClass();


// Collapse table rows with same objection event calendar

function collapseTable(table, tableRows, showLastInGroup = false) {
  if (!table || !tableRows) return;

  let dateFirstInGroup = null;
  let dateLastInGroup = null;

  for (let i = 1; i < tableRows.length - 1; i++) {
    const previousRow = tableRows[i - 1];
    const currentRow = tableRows[i];
    const nextRow = tableRows[i + 1];
    const isEqualToNext = currentRow.dataset.objectionEventsCalendar === nextRow.dataset.objectionEventsCalendar;
    const isEqualToPrevious = currentRow.dataset.objectionEventsCalendar === previousRow.dataset.objectionEventsCalendar;
    const shouldCollapse = showLastInGroup ? isEqualToNext && isEqualToPrevious : isEqualToPrevious;
    if (shouldCollapse) currentRow.classList.add('no-change-row')
  }

  table.classList.remove('hidden');
}

function toggleTable(element) {
  if (!element) return;
  const inGroupMiddleElements = document.querySelectorAll('.no-change-row,.no-change-row-show');
  inGroupMiddleElements.forEach((element) => {
    element.classList.toggle('no-change-row');
    element.classList.toggle('no-change-row-show');
  });
  element.textContent = element.textContent.trim() === 'Klap uit' ? 'Klap in' : 'Klap uit';
}

collapseTable(document.querySelector('.objection-events-calendar-table'), document.querySelectorAll('[data-objection-events-calendar]'));
eventCalendarToggleButton?.addEventListener('click', () => toggleTable(eventCalendarToggleButton));
