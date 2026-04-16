const DATE_FIELD_SELECTOR = '.c-field--icon .c-field__inner--date';
const DATE_ICON_SELECTOR = '.c-field__icon';

const openDatePicker = (input) => {
  if (
    !(input instanceof HTMLInputElement) ||
    input.type !== 'date' ||
    input.disabled ||
    input.readOnly
  ) {
    return;
  }

  input.focus({ preventScroll: true });

  if (typeof input.showPicker === 'function') {
    try {
      input.showPicker();
      return;
    } catch (error) {
      // Fall back to the browser default interaction when showPicker is blocked.
    }
  }

  input.click();
};

const initDateFieldPicker = () => {
  document.querySelectorAll(DATE_FIELD_SELECTOR).forEach((field) => {
    const input = field.querySelector('input[type="date"]');
    const icon = field.querySelector(DATE_ICON_SELECTOR);

    if (!(input instanceof HTMLInputElement) || !(icon instanceof HTMLElement)) {
      return;
    }

    if (input.disabled || input.readOnly) {
      icon.setAttribute('aria-hidden', 'true');
      return;
    }

    icon.setAttribute('role', 'button');
    icon.setAttribute('tabindex', '0');
    icon.setAttribute(
      'aria-label',
      input.labels?.[0]?.textContent?.trim()
        ? `Oppna datumvaljare for ${input.labels[0].textContent.trim()}`
        : 'Oppna datumvaljare'
    );

    icon.addEventListener('click', (event) => {
      event.preventDefault();
      openDatePicker(input);
    });

    icon.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' && event.key !== ' ') {
        return;
      }

      event.preventDefault();
      openDatePicker(input);
    });
  });
};

export default initDateFieldPicker;
