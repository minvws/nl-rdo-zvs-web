const columnHideButton = document.querySelector('#column-hide-button');
const divForUi = document.querySelector('[data-hide-table-columns-ui]');
const tables = document.querySelectorAll('[data-hide-table-columns]');

const activeDepartment = document.querySelector('body').dataset.activeDepartment;
const activeDepartmentHideColumnDefaults = document
  .querySelector('body')
  .dataset.activeDepartmentHideColumnDefaults.split(',');

const columnsToHide = getColumnsToHideFromCookie();

tables.forEach((table) => {
  initHideTableColumns(table);
  createHideUI();
});

function initHideTableColumns(table) {
  const activeDepartmentColumnsToHide = columnsToHide[activeDepartment] || activeDepartmentHideColumnDefaults;
  activeDepartmentColumnsToHide.forEach(toggleColumnVisibility);
}

function createHideUI() {
  const allColumns = document.querySelectorAll('thead th[data-hide-table-column-content]');
  allColumns.forEach(createToggle);
}

function createToggle(column) {
  const columnContent = column.getAttribute('data-hide-table-column-content');
  const toggle = document.createElement('button');
  toggle.classList.add('column-toggle');

  const textNode = document.createTextNode(columnContent + ' ');
  const hiddenSpan = document.createElement('span');
  hiddenSpan.classList.add('visually-hidden');
  hiddenSpan.textContent = 'verbergen';

  toggle.appendChild(textNode);
  toggle.appendChild(hiddenSpan);

  toggle.addEventListener('click', () => {
    toggle.classList.toggle('active');
    hiddenSpan.textContent = toggle.classList.contains('active') ? 'tonen' : 'verbergen';
    toggleColumnVisibility(columnContent);
  });

  if (columnsToHide[activeDepartment]?.includes(columnContent)) {
    toggle.classList.add('active');
    hiddenSpan.textContent = 'tonen';
  }

  divForUi.appendChild(toggle);
}

function toggleColumnVisibility(columnContent) {
  const cellsToToggle = document.querySelectorAll(`[data-hide-table-column-content="${columnContent}"]`);
  cellsToToggle.forEach((cell) => {
    cell.toggleAttribute('hidden');
    updateColumnsToHide(cell);
  });
}

function updateColumnsToHide(element) {
  const columnContent = element.getAttribute('data-hide-table-column-content');
  if (element.hasAttribute('hidden')) {
    columnsToHide[activeDepartment] = columnsToHide[activeDepartment] || activeDepartmentHideColumnDefaults;
    if (!columnsToHide[activeDepartment].includes(columnContent)) {
      columnsToHide[activeDepartment].push(columnContent);
    }
  } else {
    columnsToHide[activeDepartment] = columnsToHide[activeDepartment].filter((col) => col !== columnContent);
  }
  setColumnsToHideCookie(columnsToHide);
}

function setColumnsToHideCookie(columns) {
  const date = new Date();
  date.setTime(date.getTime() + 365 * 24 * 60 * 60 * 1000); // 365 days
  const expires = 'expires=' + date.toUTCString();
  document.cookie = 'columnsToHide=' + JSON.stringify(columns) + ';' + expires + ';path=/';
}

function getColumnsToHideFromCookie() {
  const name = 'columnsToHide=';
  const decodedCookie = decodeURIComponent(document.cookie);
  const ca = decodedCookie.split(';');
  for (let i = 0; i < ca.length; i++) {
    let c = ca[i];
    while (c.charAt(0) === ' ') {
      c = c.substring(1);
    }
    if (c.indexOf(name) === 0) {
      return JSON.parse(c.substring(name.length, c.length));
    }
  }
  return {};
}

if (columnHideButton) {
  columnHideButton.removeAttribute('hidden');
}
