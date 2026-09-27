document.addEventListener('DOMContentLoaded', () => {
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const splash = document.querySelector('[data-site-splash]');
  if (splash) {
    const hideSplash = () => {
      if (splash.classList.contains('is-done')) return;
      splash.classList.add('is-done');
      splash.setAttribute('aria-hidden', 'true');
      document.body.classList.remove('site-body--splash');
      window.setTimeout(() => splash.remove(), 700);
    };
    const minMs = reduceMotion ? 200 : 1200;
    const started = performance.now();
    const finish = () => {
      const wait = Math.max(0, minMs - (performance.now() - started));
      window.setTimeout(hideSplash, wait);
    };
    if (document.readyState === 'complete') finish();
    else window.addEventListener('load', finish, { once: true });
    window.setTimeout(hideSplash, reduceMotion ? 600 : 2800);
  }

  const menuBtn = document.querySelector('[data-site-menu]');
  const nav = document.querySelector('[data-site-nav]');
  if (menuBtn && nav) {
    menuBtn.addEventListener('click', () => {
      const open = nav.classList.toggle('site-nav--open');
      menuBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  const progress = document.querySelector('[data-site-progress]');
  const header = document.querySelector('[data-site-header]');
  const hero = document.querySelector('[data-site-hero]');
  let heroH = hero ? hero.offsetHeight : 0;
  let ticking = false;
  const onScroll = () => {
    if (ticking) return;
    ticking = true;
    requestAnimationFrame(() => {
      const y = window.scrollY;
      if (progress) {
        const max = document.documentElement.scrollHeight - window.innerHeight;
        progress.style.transform = `scaleX(${max > 0 ? Math.min(y / max, 1) : 0})`;
      }
      if (header) {
        header.classList.toggle('is-scrolled', y > 24);
        if (hero) header.classList.toggle('is-over-hero', y < heroH - 120);
      }
      ticking = false;
    });
  };
  onScroll();
  window.addEventListener('scroll', onScroll, { passive: true });
  window.addEventListener('resize', () => { heroH = hero ? hero.offsetHeight : 0; }, { passive: true });

  // Fantasy 3D tilt removed when unused

  // Keep Arabic connected — never split glyphs for brand/display text
  document.querySelectorAll('[data-split-text]').forEach((el) => {
    if (reduceMotion) return;
    const text = (el.textContent || '').trim();
    const hasArabic = /[\u0600-\u06FF]/.test(text);
    if (hasArabic) {
      el.classList.add('is-arabic-intact');
      return;
    }
    el.classList.add('is-split');
    el.setAttribute('aria-label', text);
    el.textContent = '';
    [...text].forEach((ch, i) => {
      const span = document.createElement('span');
      span.className = 'site-split-char';
      span.textContent = ch === ' ' ? '\u00A0' : ch;
      span.style.setProperty('--i', String(i));
      el.appendChild(span);
    });
  });

  const reveals = document.querySelectorAll('.site-reveal');
  if (reveals.length) {
    if (reduceMotion || !('IntersectionObserver' in window)) {
      reveals.forEach((el) => el.classList.add('is-visible'));
    } else {
      const io = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            entry.target.classList.add('is-visible');
            io.unobserve(entry.target);
          });
        },
        { threshold: 0.12, rootMargin: '0px 0px -6% 0px' }
      );

      reveals.forEach((el) => {
        if (!el.style.getPropertyValue('--reveal-delay')) {
          const group = el.closest('.site-projects, .site-stats, .site-trust__points');
          if (group) {
            const siblings = [...group.querySelectorAll('.site-reveal')];
            const idx = siblings.indexOf(el);
            el.style.setProperty('--reveal-delay', `${Math.max(idx, 0) * 55}ms`);
          }
        }
        io.observe(el);
      });
    }
  }

  document.querySelectorAll('.site-project').forEach((card) => {
    card.addEventListener('pointermove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = ((e.clientX - rect.left) / rect.width) * 100;
      const y = ((e.clientY - rect.top) / rect.height) * 100;
      card.style.setProperty('--mx', `${x}%`);
      card.style.setProperty('--my', `${y}%`);

      if (card.hasAttribute('data-tilt') && !reduceMotion) {
        const rx = ((y - 50) / 50) * -6;
        const ry = ((x - 50) / 50) * 6;
        card.classList.add('is-tilting');
        card.style.transform = `perspective(900px) rotateX(${rx}deg) rotateY(${ry}deg) translateY(-6px)`;
      }
    });

    card.addEventListener('pointerleave', () => {
      card.classList.remove('is-tilting');
      card.style.transform = '';
    });
  });

  const counters = document.querySelectorAll('[data-count]');
  if (counters.length && 'IntersectionObserver' in window) {
    const animateCount = (el) => {
      const target = Number(el.getAttribute('data-count') || 0);
      const suffix = el.getAttribute('data-suffix') || '';
      if (reduceMotion || target <= 0) {
        el.textContent = `${target}${suffix}`;
        return;
      }
      const duration = 900;
      const start = performance.now();
      const step = (now) => {
        const t = Math.min((now - start) / duration, 1);
        const eased = 1 - Math.pow(1 - t, 3);
        el.textContent = `${Math.round(target * eased)}${suffix}`;
        if (t < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
    };

    const cio = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (!entry.isIntersecting) return;
          animateCount(entry.target);
          cio.unobserve(entry.target);
        });
      },
      { threshold: 0.35 }
    );
    counters.forEach((el) => cio.observe(el));
  }

  document.querySelectorAll('.site-btn').forEach((btn) => {
    if (!btn.querySelector('.site-btn__shine')) {
      const shine = document.createElement('span');
      shine.className = 'site-btn__shine';
      shine.setAttribute('aria-hidden', 'true');
      btn.appendChild(shine);
    }
  });

  if (document.querySelector('[data-building-3d]')) {
    import('./site-3d.js')
      .then(({ initSite3d }) => initSite3d())
      .catch((err) => console.warn('[site-3d] load failed', err));
  }
});
