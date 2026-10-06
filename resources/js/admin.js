// Small behaviours for the admin panel and the affiliate portal.

// <select data-autosubmit> submits its form as soon as the value changes.
document.querySelectorAll('[data-autosubmit]').forEach((field) => {
    field.addEventListener('change', () => field.form.requestSubmit());
});

// <button data-confirm="Question?"> asks before submitting.
document.querySelectorAll('[data-confirm]').forEach((button) => {
    button.addEventListener('click', (event) => {
        if (!window.confirm(button.dataset.confirm)) event.preventDefault();
    });
});

document.querySelectorAll('[data-print]').forEach((button) => {
    button.addEventListener('click', () => window.print());
});

// Email template editor: placeholder chips and a live, server-rendered preview.
function initTemplateEditor(root) {
    const subject = root.querySelector('[data-el="subject"]');
    const body = root.querySelector('[data-el="body"]');
    const preview = root.querySelector('[data-el="preview"]');
    const previewSubject = root.querySelector('[data-el="preview-subject"]');
    let lastFocused = body;
    let timer = null;

    const refresh = async () => {
        try {
            const response = await fetch(root.dataset.previewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': root.dataset.token,
                },
                credentials: 'same-origin',
                body: JSON.stringify({ subject: subject.value, body: body.value }),
            });

            if (!response.ok) return;

            const data = await response.json();

            // The preview is rendered inside a sandboxed iframe, isolated from the admin page.
            preview.srcdoc = data.html;
            previewSubject.textContent = data.subject ? `Subject: ${data.subject}` : 'Shown with example values';
        } catch {
            // Keep the last good preview if the request fails.
        }
    };

    const schedule = () => {
        clearTimeout(timer);
        timer = setTimeout(refresh, 400);
    };

    [subject, body].forEach((field) => {
        field.addEventListener('input', schedule);
        field.addEventListener('focus', () => {
            lastFocused = field;
        });
    });

    root.querySelectorAll('[data-placeholder]').forEach((chip) => {
        chip.addEventListener('click', () => {
            const field = lastFocused;
            const start = field.selectionStart ?? field.value.length;
            const end = field.selectionEnd ?? field.value.length;

            field.setRangeText(chip.dataset.placeholder, start, end, 'end');
            field.focus();
            schedule();
        });
    });

    refresh();
}

document.querySelectorAll('[data-template-editor]').forEach(initTemplateEditor);
