function initScrollableTable() {
  const scrollContainer = document.querySelector('.x-scrollable-wrapper');
  const scrollContent = document.querySelector('.x-scrollable');

  if (!scrollContainer) {
    return;
  }

  // Set initial states
  scrollContainer.classList.add('at-start');
  scrollContainer.classList.toggle('at-end', scrollContainer.clientWidth >= scrollContent.scrollWidth);

  scrollContent.addEventListener('scroll', () => {
    const { scrollLeft, scrollWidth, clientWidth } = scrollContent;

    scrollContainer.classList.toggle('at-start', scrollLeft === 0);
    scrollContainer.classList.toggle('at-end', scrollLeft + clientWidth >= scrollWidth - 1);
  });
}

initScrollableTable();

window.addEventListener('resize', () => {
  requestAnimationFrame(initScrollableTable);
});
