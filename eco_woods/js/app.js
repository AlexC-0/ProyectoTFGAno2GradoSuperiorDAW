function syncBackToTopButton() {
    const btn = document.getElementById('btnTop');
    if (!btn) {
        return;
    }

    btn.setAttribute('type', 'button');
    btn.setAttribute('aria-label', 'Volver arriba');
    btn.innerHTML = '<span class="btn-top-icon" aria-hidden="true">&uarr;</span>';
}

window.addEventListener('scroll', function () {
    const btn = document.getElementById('btnTop');
    if (!btn) {
        return;
    }

    btn.style.display = window.scrollY > 200 ? 'inline-flex' : 'none';
});

window.addEventListener('DOMContentLoaded', syncBackToTopButton);

function ewSetCartBadge(count) {
    const badge = document.querySelector('[data-cart-count]');
    if (!badge) {
        return;
    }

    const total = Math.max(0, parseInt(count, 10) || 0);
    badge.textContent = total > 99 ? '99+' : String(total);
    badge.hidden = total <= 0;
}

async function ewRefreshCartBadge() {
    const badge = document.querySelector('[data-cart-count]');
    if (!badge) {
        return;
    }

    try {
        const resp = await fetch('carrito_contador.php', {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        const data = await resp.json().catch(() => null);
        if (resp.ok && data && typeof data.cart_count !== 'undefined') {
            ewSetCartBadge(data.cart_count);
        }
    } catch (e) {
        ewSetCartBadge(0);
    }
}

window.ewSetCartBadge = ewSetCartBadge;
window.ewRefreshCartBadge = ewRefreshCartBadge;
window.addEventListener('DOMContentLoaded', ewRefreshCartBadge);

function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}
