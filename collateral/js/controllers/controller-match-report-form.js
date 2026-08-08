function initForm() {
  const form = document.getElementById('form_ref_match_report');
  const loadingMessage = document.getElementById('loadingMessage');

  if (!form) {
    console.warn('Form with ID "form_ref_match_report" not found.');
    return;
  }

  if (typeof registerFormMatchElements === 'function') {
    registerFormMatchElements(form);
  } else {
    console.warn('registerFormMatchElements() is not defined.');
  }

  if (typeof registerFormSanctionElements === 'function') {
    registerFormSanctionElements(form);
  } else {
    console.warn('registerFormSanctionElements() is not defined.');
  }

  form.addEventListener('submit', function () {
    if (loadingMessage) loadingMessage.hidden = false;
    form.style.display = 'none';
  });
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', initForm);
} else {
  initForm();
}
