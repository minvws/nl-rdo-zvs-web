const forms = document.querySelectorAll('[data-auto-submit="form"]');

forms.forEach((form) => {
  const submit = form.querySelector('[data-auto-submit="submit"]');
  submit?.classList.add('visually-hidden');

  const inputs = form.querySelectorAll('[data-auto-submit="input"]');
  inputs.forEach((input) => {
    input.addEventListener('change', () => {
      form.submit();
    });
  });
});
