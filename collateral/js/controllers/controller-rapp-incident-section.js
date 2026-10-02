// Wires up match-report.php's optional "also report abuse" section:
// the toggle button (same aria-controls/aria-expanded idiom as the
// match-issue/ref-staffing-issue sections in display-match-details.js), and
// the inline email-code exchange against match-report-rapp-otp.php. No page
// reload anywhere in this flow -- gamecard photos are file inputs, and a
// reload would lose whatever the referee had already attached.
function registerRappIncidentSection(form) {
  const toggleButton = document.getElementById('button_rapp-incident');
  if (!toggleButton) {
    return;
  }

  const detailSection = document.getElementById('section_rapp-incident-detail');
  const emailInput = document.getElementById('rapp_email');
  const sendCodeButton = document.getElementById('button_rapp-send-code');
  const noticeEl = document.getElementById('rapp-otp-notice');
  const errorEl = document.getElementById('rapp-otp-error');
  const codeRow = document.getElementById('rapp-otp-code-row');
  const codeInput = document.getElementById('rapp_code');
  const verifyCodeButton = document.getElementById('button_rapp-verify-code');
  const otpStep = document.getElementById('rapp-otp-step');
  const reportFields = document.getElementById('rapp-report-fields');
  const recordingGuidance = document.getElementById('rapp-recording-guidance');
  const csrfInput = document.getElementById('rapp_csrf_token');

  // A code that's been emailed but never verified must not be left behind --
  // without this, the referee could request a code, get distracted, and
  // submit the match report with the RAPP section silently dropped (the
  // server only attaches a RAPP report when $_SESSION carries a verified
  // identity). codeRequested flips back off if the referee collapses this
  // section themselves -- that's treated as abandoning the RAPP report for
  // this submission, not as a problem to block on.
  let codeRequested = false;
  let verified = false;

  function notifyStateChange() {
    // controller-match-report-form.js listens for 'input' on the form to
    // re-run its own submit-button-disabled check -- reuse that instead of
    // inventing a second event type it would also need to listen for.
    form.dispatchEvent(new Event('input', { bubbles: true }));
  }

  function showNotice(message) {
    errorEl.hidden = true;
    noticeEl.textContent = message;
    noticeEl.hidden = false;
  }

  function showError(message) {
    noticeEl.hidden = true;
    errorEl.textContent = message;
    errorEl.hidden = false;
  }

  function postAction(action, extraFields) {
    const body = new URLSearchParams(Object.assign(
      { action: action, csrf_token: csrfInput.value },
      extraFields
    ));
    return fetch('match-report-rapp-otp.php', { method: 'POST', body: body })
      .then(function (response) { return response.json(); })
      .then(function (data) {
        // The endpoint hands back a fresh, still-valid token with every
        // response (its CSRF tokens are one-time-use) -- swap it in so the
        // next AJAX step, or the eventual form submit, has one to spend.
        if (data.csrf_token) {
          csrfInput.value = data.csrf_token;
        }
        return data;
      });
  }

  toggleButton.addEventListener('click', function () {
    const isExpanded = toggleButton.getAttribute('aria-expanded') === 'true';
    if (!isExpanded) {
      toggleButton.setAttribute('aria-expanded', 'true');
      detailSection.hidden = false;
    } else {
      toggleButton.setAttribute('aria-expanded', 'false');
      detailSection.hidden = true;
      // Collapsing counts as abandoning this RAPP report for now -- an
      // outstanding code no longer needs to block submission.
      if (codeRequested && !verified) {
        codeRequested = false;
        notifyStateChange();
      }
    }
  });

  sendCodeButton.addEventListener('click', function () {
    const email = emailInput.value.trim();
    if (!email) {
      showError('Please enter a valid email address.');
      return;
    }
    sendCodeButton.disabled = true;
    postAction('request', { email: email })
      .then(function (data) {
        sendCodeButton.disabled = false;
        if (data.error) {
          showError(data.error);
          return;
        }
        showNotice(data.notice);
        codeRow.hidden = false;
        codeInput.focus();
        codeRequested = true;
        notifyStateChange();
      })
      .catch(function () {
        sendCodeButton.disabled = false;
        showError('Something went wrong sending the code. Please try again.');
      });
  });

  verifyCodeButton.addEventListener('click', function () {
    const email = emailInput.value.trim();
    const code = codeInput.value.trim();
    if (!code) {
      showError('Please enter the code we emailed you.');
      return;
    }
    verifyCodeButton.disabled = true;
    postAction('verify', { email: email, code: code })
      .then(function (data) {
        verifyCodeButton.disabled = false;
        if (data.error) {
          showError(data.error);
          return;
        }
        errorEl.hidden = true;
        noticeEl.hidden = true;
        otpStep.hidden = true;
        recordingGuidance.hidden = false;
        reportFields.hidden = false;
        verified = true;
        notifyStateChange();
      })
      .catch(function () {
        verifyCodeButton.disabled = false;
        showError('Something went wrong verifying that code. Please try again.');
      });
  });

  // Consumed by controller-match-report-form.js to keep the match report
  // from submitting while a RAPP code is outstanding.
  return {
    hasUnverifiedCode: function () {
      return codeRequested && !verified;
    },
    explainUnverifiedCode: function () {
      showError('Please enter and verify the code we emailed you, or collapse this section, before submitting.');
      codeInput.focus();
    }
  };
}
