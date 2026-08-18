function initForm() {
  const form = document.getElementById('form_ref_match_report');
  const loadingMessage = document.getElementById('loadingMessage');
  const submitButton = document.getElementById('submit');

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

  // Keep the submit button looking disabled (light blue/grey, via the
  // #submit.btn:disabled CSS rule) until every required field on the form
  // has been filled in.
  if (submitButton) {
    const updateSubmitState = function () {
      submitButton.disabled = !form.checkValidity();
    };

    updateSubmitState();
    form.addEventListener('input', updateSubmitState);
    form.addEventListener('change', updateSubmitState);
    // Buttons (e.g. opening a sanction report or the staffing-issue note)
    // toggle fields' `required` on click, which doesn't fire input/change
    // itself - recheck right after any click so the button doesn't lag.
    form.addEventListener('click', updateSubmitState);
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
