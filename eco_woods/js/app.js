function syncBackToTopButton() {
    const btn = document.getElementById('btnTop');
    if (!btn) {
        return;
    }

    btn.setAttribute('type', 'button');
    btn.setAttribute('aria-label', 'Volver arriba');
    btn.innerHTML = '<span class="btn-top-icon" aria-hidden="true">↑</span>';
}

window.addEventListener('scroll', function () {
    const btn = document.getElementById('btnTop');
    if (!btn) {
        return;
    }

    btn.style.display = window.scrollY > 200 ? 'inline-flex' : 'none';
});

window.addEventListener('DOMContentLoaded', syncBackToTopButton);

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}
