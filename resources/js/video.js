// Intro video modal. The player iframe is only created when the visitor
// clicks play, so the video costs nothing on page load.
function openVideo(url) {
    const modal = document.createElement('div');
    modal.className = 'video-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-label', 'Intro video');

    const frame = document.createElement('iframe');
    frame.src = url;
    frame.title = 'Intro video';
    frame.allow = 'autoplay; encrypted-media; picture-in-picture; fullscreen';
    frame.allowFullscreen = true;

    const close = document.createElement('button');
    close.type = 'button';
    close.className = 'video-modal__close';
    close.setAttribute('aria-label', 'Close video');
    close.textContent = '×';

    const box = document.createElement('div');
    box.className = 'video-modal__box';
    box.append(frame);
    modal.append(close, box);

    const dismiss = () => {
        modal.remove();
        document.removeEventListener('keydown', onKey);
    };
    const onKey = (event) => {
        if (event.key === 'Escape') dismiss();
    };

    modal.addEventListener('click', (event) => {
        if (event.target === modal || event.target === close) dismiss();
    });
    document.addEventListener('keydown', onKey);
    document.body.append(modal);
    close.focus();
}

document.querySelectorAll('[data-video]').forEach((button) => {
    button.addEventListener('click', () => {
        if (button.dataset.video) openVideo(button.dataset.video);
    });
});
