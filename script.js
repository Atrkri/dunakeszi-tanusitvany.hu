document.addEventListener("DOMContentLoaded", function () {
  const toggle = document.querySelector(".menu-toggle");
  const nav = document.querySelector(".main-nav");
  if (toggle && nav) {
    toggle.addEventListener("click", function () {
      const isOpen = nav.classList.toggle("open");
      toggle.setAttribute("aria-expanded", String(isOpen));
    });
    nav.querySelectorAll("a").forEach(function (link) {
      link.addEventListener("click", function () {
        nav.classList.remove("open");
        toggle.setAttribute("aria-expanded", "false");
      });
    });
  }

  document.querySelectorAll("form[data-form-type]").forEach(function (form) {
    form.addEventListener("submit", function (event) {
      if (!form.checkValidity()) {
        event.preventDefault();
        form.reportValidity();
      }
    });
  });

  const backToTop = document.querySelector(".back-to-top");
  if (backToTop) {
    backToTop.addEventListener("click", function () {
      window.scrollTo({ top: 0, left: 0, behavior: "smooth" });
      document.documentElement.scrollTop = 0;
      document.body.scrollTop = 0;
    });
  }

  const header = document.querySelector(".site-header");
  if (header) {
    const updateHeader = function () {
      header.classList.toggle("header-scrolled", window.scrollY > 40);
    };
    window.addEventListener("scroll", updateHeader, { passive: true });
    updateHeader();
  }
});
// Highlight one service card at a time, using the original featured colors.
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.service-grid').forEach(function (grid) {
    const cards = Array.from(grid.querySelectorAll('.service-card'));
    if (!cards.length) return;
    const defaultCard = cards.find(function (card) { return card.classList.contains('featured'); }) || cards[0];
    let hoveredCard = null;
    let focusedCard = null;
    function updateHighlight() {
      const active = hoveredCard || focusedCard || defaultCard;
      cards.forEach(function (card) { card.classList.toggle('featured', card === active); });
    }
    cards.forEach(function (card) {
      card.addEventListener('pointerenter', function (event) {
        if (event.pointerType === 'touch') return;
        hoveredCard = card;
        updateHighlight();
      });
      card.addEventListener('pointerleave', function () {
        if (hoveredCard === card) hoveredCard = null;
        updateHighlight();
      });
    });
    grid.addEventListener('focusin', function (event) {
      focusedCard = cards.find(function (card) { return card.contains(event.target); }) || null;
      updateHighlight();
    });
    grid.addEventListener('focusout', function (event) {
      focusedCard = cards.find(function (card) { return card.contains(event.relatedTarget); }) || null;
      updateHighlight();
    });
  });
});

// Reveal each price button once, two seconds after its card enters view.
document.addEventListener('DOMContentLoaded', function () {
  if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
  const cards = document.querySelectorAll('.price-card');
  const observer = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      observer.unobserve(entry.target);
      const button = entry.target.querySelector('.price-order');
      if (button) window.setTimeout(function () { button.classList.remove('price-order-pending'); }, 2000);
    });
  }, { threshold: 0.15 });
  cards.forEach(function (card) {
    const button = card.querySelector('.price-order');
    if (!button) return;
    button.classList.add('price-order-reveal', 'price-order-pending');
    button.addEventListener('focus', function () { button.classList.remove('price-order-pending'); });
    observer.observe(card);
  });
});

// Animate FAQ height while keeping one selected answer open.
document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.accordion').forEach(function (accordion) {
    const items = Array.from(accordion.querySelectorAll('details'));
    if (!items.length) return;
    const motion = window.matchMedia('(prefers-reduced-motion: reduce)');
    const running = new Map();
    let selected = null;
    items.forEach(function (item) { item.open = item === selected; });
    function resizeItem(item, expand) {
      const start = item.getBoundingClientRect().height;
      const previous = running.get(item);
      if (previous) {
        previous.onfinish = null;
        previous.cancel();
        running.delete(item);
      }
      item.style.height = '';
      item.style.overflow = '';
      if (motion.matches || typeof item.animate !== 'function') {
        item.open = expand;
        return;
      }
      item.open = expand;
      const end = item.getBoundingClientRect().height;
      if (Math.abs(start - end) < 1) return;
      // Keep the answer rendered until the closing animation finishes.
      item.open = true;
      item.style.height = start + 'px';
      item.style.overflow = 'hidden';
      const animation = item.animate(
        [{ height: start + 'px' }, { height: end + 'px' }],
        { duration: 450, easing: 'cubic-bezier(.4,0,.2,1)' }
      );
      running.set(item, animation);
      animation.onfinish = function () {
        if (running.get(item) !== animation) return;
        item.open = item === selected;
        item.style.height = '';
        item.style.overflow = '';
        running.delete(item);
      };
    }
    items.forEach(function (item) {
      const summary = item.querySelector('summary');
      if (!summary) return;
      summary.addEventListener('click', function (event) {
        event.preventDefault();
        if (selected === item) {
          selected = null;
          resizeItem(item, false);
          return;
        }
        selected = item;
        items.forEach(function (other) {
          if (other === item || other.open || running.has(other)) resizeItem(other, other === item);
        });
      });
    });
    function finishAnimations() {
      running.forEach(function (animation, item) {
        animation.onfinish = null;
        animation.cancel();
        item.open = item === selected;
        item.style.height = '';
        item.style.overflow = '';
      });
      running.clear();
    }
    window.addEventListener('resize', finishAnimations);
    motion.addEventListener('change', finishAnimations);
  });
});

// Touch highlighting never intercepts navigation or scrolling.
document.addEventListener('DOMContentLoaded', function () {
  const cards = Array.from(document.querySelectorAll('.service-card, .price-card'));
  if (!cards.length) return;
  let start = null;
  let selected = null;
  function select(card) {
    selected = card;
    cards.forEach(function (item) { item.classList.toggle('touch-selected', item === card); });
  }
  document.addEventListener('pointerdown', function (event) {
    if (event.pointerType === 'mouse') {
      document.documentElement.classList.remove('card-touch-mode');
      select(null);
      start = null;
      return;
    }
    if (!event.isPrimary) return;
    document.documentElement.classList.add('card-touch-mode');
    start = { id: event.pointerId, x: event.clientX, y: event.clientY, target: event.target };
  }, { passive: true });
  document.addEventListener('pointerup', function (event) {
    const initial = start;
    start = null;
    if (!initial || initial.id !== event.pointerId || Math.hypot(event.clientX - initial.x, event.clientY - initial.y) > 12) return;
    const card = event.target.closest('.service-card, .price-card');
    if (initial.target.closest('.service-card, .price-card') !== card) return;
    select(card === selected ? null : card);
  }, { passive: true });
  document.addEventListener('pointercancel', function () { start = null; }, { passive: true });
  document.addEventListener('keydown', function (event) {
    if (event.key === 'Tab' || event.key === 'Escape') {
      document.documentElement.classList.remove('card-touch-mode');
      select(null);
    }
  });
});


// FAQ image popups.
document.addEventListener('DOMContentLoaded', function () {
  const triggers = document.querySelectorAll('.image-popup-trigger[data-popup-image]');
  let backdrop = null;
  function closePopup() { if (!backdrop) return; backdrop.remove(); backdrop = null; document.body.classList.remove('image-popup-open'); }
  function openPopup(trigger) {
    closePopup();
    backdrop = document.createElement('div');
    backdrop.className = 'image-popup-backdrop';
    backdrop.setAttribute('role', 'dialog');
    backdrop.setAttribute('aria-modal', 'true');
    const figure = document.createElement('figure'); figure.className = 'image-popup-dialog';
    const close = document.createElement('button'); close.type = 'button'; close.className = 'image-popup-close'; close.setAttribute('aria-label', 'Kép bezárása'); close.textContent = '×';
    const image = document.createElement('img'); image.src = trigger.dataset.popupImage; image.alt = trigger.dataset.popupTitle || 'Illusztráció';
    const caption = document.createElement('figcaption'); caption.textContent = trigger.dataset.popupTitle || '';
    close.addEventListener('click', closePopup); figure.append(close, image, caption); backdrop.append(figure); document.body.append(backdrop); document.body.classList.add('image-popup-open'); close.focus();
    backdrop.addEventListener('click', function (event) { if (event.target === backdrop) closePopup(); });
  }
  triggers.forEach(function (trigger) { trigger.addEventListener('click', function (event) { event.preventDefault(); openPopup(trigger); }); });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape') closePopup(); });
});
