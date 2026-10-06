document.querySelectorAll('[data-faq]').forEach((root) => {
    const items = [...root.querySelectorAll('[data-faq-item]')];
    const cats = [...root.querySelectorAll('[data-faq-cat]')];
    const visible = () => items.filter((item) => !item.hidden);

    // Only one answer is open at a time; clicking the open one closes it.
    const open = (target) => {
        items.forEach((item) => {
            item.querySelector('button').setAttribute('aria-expanded', String(item === target));
        });
    };

    items.forEach((item) => {
        const button = item.querySelector('button');

        button.addEventListener('click', () => {
            open(button.getAttribute('aria-expanded') === 'true' ? null : item);
        });
    });

    cats.forEach((cat) => {
        cat.addEventListener('click', () => {
            const name = cat.dataset.faqCat;

            cats.forEach((other) => other.setAttribute('aria-pressed', String(other === cat)));
            items.forEach((item) => {
                item.hidden = name !== 'All' && item.dataset.cat !== name;
            });
            open(visible()[0] ?? null);
        });
    });
});
