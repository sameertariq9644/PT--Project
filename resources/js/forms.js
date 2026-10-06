// Generic handler for the public forms (lead, contact, checkout). They post
// with fetch() to stateless JSON endpoints: 201 swaps in the success state (or
// follows `redirect`), 422 shows the errors under their fields.
const loadedAt = Date.now();

function clearErrors(form) {
    form.querySelectorAll('.field-error').forEach((node) => node.remove());
    form.querySelectorAll('.has-error').forEach((node) => node.classList.remove('has-error'));

    const alert = form.querySelector('[data-form-alert]');

    if (alert) alert.hidden = true;
}

function showAlert(form, message) {
    const alert = form.querySelector('[data-form-alert]');

    if (!alert) return;

    alert.textContent = message;
    alert.hidden = false;
}

function showErrors(form, errors) {
    let first = null;

    Object.entries(errors).forEach(([name, messages]) => {
        const holder = form.querySelector(`[data-field="${CSS.escape(name)}"]`);

        if (!holder) {
            showAlert(form, messages[0]);

            return;
        }

        const error = document.createElement('span');
        error.className = 'field-error';
        error.textContent = messages[0];
        holder.classList.add('has-error');
        holder.append(error);
        first ??= holder;
    });

    first?.scrollIntoView({ behavior: 'smooth', block: 'center' });
}

function initAjaxForm(form) {
    const button = form.querySelector('[type="submit"]');

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        clearErrors(form);

        const body = new FormData(form);
        // How long the visitor took to fill in the form; bots submit instantly.
        body.set('_elapsed', String(Date.now() - loadedAt));

        button.disabled = true;

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
                body,
            });
            const data = await response.json().catch(() => ({}));

            if (response.status === 201) {
                if (data.redirect) {
                    window.location.assign(data.redirect);

                    return;
                }

                const success = form.parentElement.querySelector('[data-form-success]');

                form.hidden = true;

                if (success) {
                    success.hidden = false;
                    success.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                return;
            }

            if (response.status === 422 && data.errors) {
                showErrors(form, data.errors);
            } else if (response.status === 429) {
                showAlert(form, 'Too many attempts. Please wait a minute and try again.');
            } else {
                showAlert(form, data.message || 'Something went wrong. Please try again.');
            }
        } catch {
            showAlert(form, 'We could not reach the server. Check your connection and try again.');
        }

        button.disabled = false;
    });
}

document.querySelectorAll('[data-ajax-form]').forEach(initAjaxForm);
