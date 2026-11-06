const rsvpForm = document.querySelector(".rsvp-form");

if (rsvpForm) {
  const submitButton = rsvpForm.querySelector(".rsvp-submit");
  const submitStatus = rsvpForm.querySelector(".rsvp-submit-status");
  let isSubmitting = false;

  rsvpForm.addEventListener("submit", (event) => {
    if (isSubmitting) {
      event.preventDefault();
      return;
    }

    isSubmitting = true;
    submitButton.disabled = true;
    submitButton.textContent = "Sending RSVP...";
    submitStatus.hidden = false;
    submitStatus.textContent = "Sending your RSVP. Please wait.";
    rsvpForm.setAttribute("aria-busy", "true");
  });

  window.addEventListener("pageshow", () => {
    isSubmitting = false;
    submitButton.disabled = false;
    submitButton.innerHTML = 'Send RSVP <span aria-hidden="true">↗</span>';
    submitStatus.hidden = true;
    submitStatus.textContent = "";
    rsvpForm.removeAttribute("aria-busy");
  });
}
