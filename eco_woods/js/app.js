// Mostrar u ocultar el botón "Arriba" según el scroll
window.addEventListener('scroll', function() {
    const btn = document.getElementById('btnTop');
    if (!btn) return;

    if (window.scrollY > 200) {  // a partir de 200px de scroll aparece
        btn.style.display = 'block';
    } else {
        btn.style.display = 'none';
    }
});

// Función para subir arriba suavemente
function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}

