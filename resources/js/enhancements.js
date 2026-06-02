// =============================================================================
// Enhancements visuales (acta GORE junio 2026 — rediseño Stripe institucional)
//
// Tres mejoras progresivas, todas opt-in via clase y respetando reduced motion:
//
//  1. Scroll reveal: elementos con .gore-reveal se hacen visibles cuando entran
//     en viewport. Stagger automatico si el padre tiene .gore-stagger.
//  2. Navbar glass-on-scroll: agrega .gore-navbar-scrolled cuando el scroll
//     supera ~50px (densifica el backdrop-blur para legibilidad).
//  3. Card hover spotlight: actualiza variables CSS --mx --my de la card
//     hovered para que el glow radial siga el cursor (Stripe / Linear style).
//
// Si el browser no soporta IntersectionObserver, los .gore-reveal se muestran
// inmediatamente (fallback en SCSS: .is-visible aplicado por defecto).
// =============================================================================

const prefersReducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

function setupScrollReveal() {
    const revealEls = document.querySelectorAll('.gore-reveal');
    if (!revealEls.length) return;

    // Fallback: sin IntersectionObserver mostramos todo de una.
    if (!('IntersectionObserver' in window) || prefersReducedMotion) {
        revealEls.forEach(el => el.classList.add('is-visible'));
        return;
    }

    const io = new IntersectionObserver(
        (entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    io.unobserve(entry.target);
                }
            });
        },
        { rootMargin: '0px 0px -10% 0px', threshold: 0.1 }
    );

    revealEls.forEach(el => io.observe(el));
}

function setupNavbarScroll() {
    const navbar = document.querySelector('.gore-navbar');
    if (!navbar) return;

    const threshold = 32;
    const update = () => {
        navbar.classList.toggle('gore-navbar-scrolled', window.scrollY > threshold);
    };

    update();
    // passive: true mejora performance de scroll en mobile.
    window.addEventListener('scroll', update, { passive: true });
}

function setupCardSpotlight() {
    if (prefersReducedMotion) return;

    const cards = document.querySelectorAll('.gore-feature-card, .gore-consultation-card');
    cards.forEach(card => {
        card.addEventListener('mousemove', (e) => {
            const rect = card.getBoundingClientRect();
            const x = ((e.clientX - rect.left) / rect.width) * 100;
            const y = ((e.clientY - rect.top) / rect.height) * 100;
            card.style.setProperty('--mx', `${x}%`);
            card.style.setProperty('--my', `${y}%`);
        });
        card.addEventListener('mouseleave', () => {
            card.style.removeProperty('--mx');
            card.style.removeProperty('--my');
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    setupScrollReveal();
    setupNavbarScroll();
    setupCardSpotlight();
});
