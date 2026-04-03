/* =========================================
   SOMFAI KLÍMATECHNIKA – JavaScript
   ========================================= */

'use strict';

// ---- Navbar: átlátszó → sötét görgetéskor ----
const navbar  = document.getElementById('navbar');
const backTop = document.getElementById('backToTop');

function handleScroll() {
  const scrolled = window.scrollY > 50;
  navbar.classList.toggle('scrolled', scrolled);
  backTop.classList.toggle('visible', window.scrollY > 400);
}
window.addEventListener('scroll', handleScroll, { passive: true });
handleScroll(); // inicializálás oldalletöltéskor

// ---- Mobil menü ----
const navToggle = document.getElementById('navToggle');
const navLinks  = document.getElementById('navLinks');

navToggle.addEventListener('click', () => {
  const isOpen = navLinks.classList.toggle('open');
  navToggle.classList.toggle('open', isOpen);
  navToggle.setAttribute('aria-expanded', String(isOpen));
  document.body.style.overflow = isOpen ? 'hidden' : '';
});

// Menü bezárása linkre kattintáskor
navLinks.querySelectorAll('.nav-link').forEach(link => {
  link.addEventListener('click', () => {
    navLinks.classList.remove('open');
    navToggle.classList.remove('open');
    navToggle.setAttribute('aria-expanded', 'false');
    document.body.style.overflow = '';
  });
});

// ---- Vissza a tetejére gomb ----
backTop.addEventListener('click', () => {
  window.scrollTo({ top: 0, behavior: 'smooth' });
});

// ---- Scroll reveal animáció ----
const revealObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach((entry, i) => {
      if (entry.isIntersecting) {
        // Lépcsőzetes megjelenés egymás utáni elemeknél
        const siblings = entry.target.parentElement.querySelectorAll('.reveal');
        let delay = 0;
        siblings.forEach((sibling, idx) => {
          if (sibling === entry.target) delay = idx * 80;
        });
        setTimeout(() => {
          entry.target.classList.add('visible');
        }, delay);
        revealObserver.unobserve(entry.target);
      }
    });
  },
  { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
);

document.querySelectorAll('.reveal').forEach(el => revealObserver.observe(el));

// ---- Aktív navigációs link kiemelése görgetéskor ----
const sections = document.querySelectorAll('section[id]');
const navLinkEls = document.querySelectorAll('.nav-link:not(.nav-cta)');

const sectionObserver = new IntersectionObserver(
  (entries) => {
    entries.forEach(entry => {
      if (entry.isIntersecting) {
        navLinkEls.forEach(link => {
          const href = link.getAttribute('href');
          link.classList.toggle('active', href === `#${entry.target.id}`);
        });
      }
    });
  },
  { rootMargin: '-30% 0px -60% 0px' }
);
sections.forEach(s => sectionObserver.observe(s));

// ---- Kapcsolati űrlap ----
const form          = document.getElementById('contactForm');
const submitBtn     = document.getElementById('submitBtn');
const formSuccess   = document.getElementById('formSuccess');
const formErrorMsg  = document.getElementById('formErrorMsg');

// Validáció
function validateField(input) {
  const errorEl = document.getElementById(input.id + 'Error');
  let message = '';

  if (input.required && !input.value.trim()) {
    message = 'Ez a mező kötelező.';
  } else if (input.type === 'email' && input.value && !/.+@.+\..+/.test(input.value)) {
    message = 'Adjon meg érvényes e-mail címet.';
  } else if (input.id === 'phone' && input.value && !/^[\d\s\+\-\/\(\)]{6,20}$/.test(input.value)) {
    message = 'Adjon meg érvényes telefonszámot.';
  }

  if (errorEl) {
    errorEl.textContent = message;
    errorEl.classList.toggle('show', !!message);
  }
  input.classList.toggle('invalid', !!message);
  return !message;
}

// Valósidejű validáció (csak ha már érintett)
form.querySelectorAll('input, select, textarea').forEach(field => {
  field.addEventListener('blur', () => validateField(field));
  field.addEventListener('input', () => {
    if (field.classList.contains('invalid')) validateField(field);
  });
});

// Küldés
form.addEventListener('submit', async (e) => {
  e.preventDefault();
  formSuccess.classList.remove('show');
  formErrorMsg.classList.remove('show');

  // Validálás
  const fields = [...form.querySelectorAll('input, select, textarea')];
  const valid  = fields.map(f => validateField(f)).every(Boolean);
  if (!valid) return;

  // Küldés előkészítése
  submitBtn.classList.add('loading');
  submitBtn.disabled = true;

  const data = new FormData(form);

  try {
    /*
     * FORMSPREE INTEGRÁCIÓ:
     * 1. Regisztráljon a https://formspree.io oldalon.
     * 2. Hozzon létre egy új Form-ot a somfai.tamas02@gmail.com e-mail címhez.
     * 3. Cserélje le az alábbi 'YOUR_FORM_ID' értéket a kapott egyedi azonosítóra.
     *    Pl.: https://formspree.io/f/xpwzjkab
     */
    const FORMSPREE_ID = 'YOUR_FORM_ID'; // <-- IDE ÍRJA A SAJÁT FORM ID-JÁT

    if (FORMSPREE_ID === 'YOUR_FORM_ID') {
      // Fejlesztési módban: szimulált siker (5 másodperc után)
      await new Promise(res => setTimeout(res, 1200));
      showSuccess();
      return;
    }

    const response = await fetch(`https://formspree.io/f/${FORMSPREE_ID}`, {
      method: 'POST',
      body: data,
      headers: { 'Accept': 'application/json' }
    });

    if (response.ok) {
      showSuccess();
    } else {
      showError();
    }
  } catch {
    showError();
  }
});

function showSuccess() {
  form.reset();
  submitBtn.classList.remove('loading');
  submitBtn.disabled = false;
  formSuccess.classList.add('show');
  formSuccess.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function showError() {
  submitBtn.classList.remove('loading');
  submitBtn.disabled = false;
  formErrorMsg.classList.add('show');
}

// ---- Aktuális év a footerben ----
document.getElementById('year').textContent = new Date().getFullYear();
