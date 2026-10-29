/* أثر لتصميم الويب — public site: motion, navigation, tracking, forms */
(function () {
  'use strict';
  const html = document.documentElement;
  const BASE = html.getAttribute('data-base') || '';
  const LANG = html.getAttribute('data-lang') || 'ar';
  const RTL = html.dir === 'rtl';
  const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  const hasGsap = typeof window.gsap !== 'undefined';
  const motion = hasGsap && !reduced;
  if (motion) html.classList.add('js-motion');

  /* ---------------- tracking ---------------- */
  const eventFor = (a) => {
    const explicit = a.getAttribute('data-track');
    if (explicit) return explicit;
    const h = (a.getAttribute('href') || '').toLowerCase();
    if (!h) return null;
    if (h.includes('wa.me') || h.includes('whatsapp.com') || h.startsWith('whatsapp:')) return 'whatsapp';
    if (h.startsWith('tel:')) return 'phone';
    if (h.startsWith('mailto:')) return 'email';
    const map = { 'instagram.com': 'instagram', 'facebook.com': 'facebook', 'fb.com': 'facebook', 'tiktok.com': 'tiktok', 'snapchat.com': 'snapchat', 'x.com': 'x', 'twitter.com': 'x', 'linkedin.com': 'linkedin', 'youtube.com': 'youtube', 'youtu.be': 'youtube', 'threads.net': 'threads', 'pinterest.com': 'pinterest', 'behance.net': 'behance', 't.me': 'telegram', 'maps.google': 'map', 'goo.gl/maps': 'map', 'maps.app.goo.gl': 'map' };
    for (const k in map) if (h.includes(k)) return map[k];
    return null;
  };
  const track = (event, target, label) => {
    try {
      const payload = JSON.stringify({ event, target: target || '', label: label || '', path: location.pathname, lang: LANG });
      const url = BASE + '/track';
      if (navigator.sendBeacon) {
        navigator.sendBeacon(url, new Blob([payload], { type: 'application/json' }));
      } else {
        fetch(url, { method: 'POST', body: payload, headers: { 'Content-Type': 'application/json' }, keepalive: true, credentials: 'same-origin' }).catch(() => {});
      }
    } catch (e) { /* ignore */ }
  };
  document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a) return;
    const ev = eventFor(a);
    if (!ev) return;
    track(ev, a.getAttribute('href'), a.getAttribute('data-label') || (a.textContent || '').trim().slice(0, 80));
  }, { capture: true, passive: true });

  /* ---------------- header ---------------- */
  const header = document.querySelector('.site-header');
  let lastY = window.scrollY;
  const onScroll = () => {
    const y = window.scrollY;
    header.classList.toggle('is-scrolled', y > 40);
    if (!document.body.classList.contains('menu-open')) {
      header.classList.toggle('is-hidden', y > lastY && y > 280);
    }
    lastY = y;
  };
  window.addEventListener('scroll', onScroll, { passive: true });

  const burger = document.querySelector('[data-menu-toggle]');
  const menu = document.getElementById('menu');
  const setMenu = (open) => {
    if (!menu) return;
    menu.classList.toggle('is-open', open);
    menu.setAttribute('aria-hidden', open ? 'false' : 'true');
    burger.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.classList.toggle('menu-open', open);
    header.classList.remove('is-hidden');
    if (open) { const first = menu.querySelector('a'); first && setTimeout(() => first.focus(), 300); }
  };
  burger && burger.addEventListener('click', () => setMenu(!menu.classList.contains('is-open')));
  menu && menu.addEventListener('click', (e) => { if (e.target.closest('a')) setMenu(false); });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && menu && menu.classList.contains('is-open')) { setMenu(false); burger.focus(); } });

  /* ---------------- contact form ---------------- */
  document.querySelectorAll('[data-contact-form]').forEach((form) => {
    const msg = form.querySelector('[data-form-msg]');
    const btn = form.querySelector('button[type="submit"]');
    const label = btn && btn.querySelector('[data-label-idle]');
    form.addEventListener('submit', async (e) => {
      e.preventDefault();
      form.querySelectorAll('.field').forEach((f) => f.classList.remove('is-invalid'));
      let valid = true;
      form.querySelectorAll('[required]').forEach((inp) => { if (!inp.value.trim()) { valid = false; inp.closest('.field').classList.add('is-invalid'); } });
      if (!valid) { msg.dataset.state = 'error'; msg.textContent = form.querySelector('[required]').closest('.field').querySelector('span').textContent; return; }
      btn.classList.add('is-busy'); label.textContent = label.dataset.labelBusy; msg.textContent = ''; msg.removeAttribute('data-state');
      try {
        const res = await fetch(form.action, { method: 'POST', body: new FormData(form), headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' });
        const data = await res.json().catch(() => ({}));
        if (res.ok && data.ok) {
          msg.dataset.state = 'ok'; msg.textContent = data.message || '';
          form.reset();
        } else {
          msg.dataset.state = 'error'; msg.textContent = data.error || 'Error';
          if (data.errors) Object.keys(data.errors).forEach((k) => { const inp = form.querySelector('[name="' + k + '"]'); inp && inp.closest('.field').classList.add('is-invalid'); });
        }
      } catch (err) {
        msg.dataset.state = 'error'; msg.textContent = 'Network error';
      }
      btn.classList.remove('is-busy'); label.textContent = label.dataset.labelIdle;
    });
  });

  /* ---------------- portfolio filters ---------------- */
  document.querySelectorAll('[data-filters]').forEach((wrap) => {
    const grid = wrap.parentElement.querySelector('[data-work-grid]');
    wrap.addEventListener('click', (e) => {
      const b = e.target.closest('button[data-filter]');
      if (!b || !grid) return;
      wrap.querySelectorAll('button').forEach((x) => x.classList.toggle('is-active', x === b));
      const f = b.dataset.filter;
      grid.querySelectorAll('.work-card').forEach((card) => card.classList.toggle('is-filtered', f !== '' && card.dataset.category !== f));
      if (motion) window.ScrollTrigger && ScrollTrigger.refresh();
    });
  });


  /* ---------------- package tabs (Silver / Elite / Premium per service line) ---------------- */
  document.querySelectorAll('[data-pk-tabs]').forEach((tabs) => {
    const section = tabs.closest('section');
    const glider = tabs.querySelector('.pk-tabs__glider');
    const btns = Array.from(tabs.querySelectorAll('[data-pk-tab]'));
    const panels = Array.from(section.querySelectorAll('[data-pk-panel]'));
    const place = (b) => { if (!glider || !b) return; glider.style.left = b.offsetLeft + 'px'; glider.style.width = b.offsetWidth + 'px'; };
    const activate = (b, animate) => {
      btns.forEach((x) => { const on = x === b; x.classList.toggle('is-active', on); x.setAttribute('aria-selected', on ? 'true' : 'false'); });
      panels.forEach((p) => {
        const on = p.dataset.pkPanel === b.dataset.pkTab;
        p.hidden = !on; p.classList.toggle('is-active', on);
        if (on && animate && !reduced) { p.classList.remove('is-entering'); void p.offsetWidth; p.classList.add('is-entering'); }
      });
      place(b);
      if (motion && window.ScrollTrigger) ScrollTrigger.refresh();
    };
    tabs.addEventListener('click', (e) => { const b = e.target.closest('[data-pk-tab]'); if (b) activate(b, true); });
    tabs.addEventListener('keydown', (e) => {
      if (e.key !== 'ArrowLeft' && e.key !== 'ArrowRight') return;
      const i = btns.findIndex((x) => x.classList.contains('is-active'));
      const dir = (e.key === 'ArrowRight') !== RTL ? 1 : -1;
      const next = btns[(i + dir + btns.length) % btns.length]; next.focus(); activate(next, true); e.preventDefault();
    });
    // deep link: #packages-social or ?line=social
    const want = (location.hash.match(/^#packages-([a-z]+)$/) || [])[1] || new URLSearchParams(location.search).get('line');
    const initial = btns.find((b) => b.dataset.pkTab === want) || btns.find((b) => b.classList.contains('is-active')) || btns[0];
    if (initial) activate(initial, false);
    window.addEventListener('resize', () => place(btns.find((b) => b.classList.contains('is-active'))));
    if (document.fonts && document.fonts.ready) document.fonts.ready.then(() => place(btns.find((b) => b.classList.contains('is-active'))));
  });

  /* ---------------- testimonials scroller ---------------- */
  document.querySelectorAll('[data-scroller]').forEach((sc) => {
    const section = sc.closest('section');
    section.querySelectorAll('[data-scroll]').forEach((b) => {
      b.addEventListener('click', () => {
        const dir = b.dataset.scroll === 'next' ? 1 : -1;
        const amount = Math.min(sc.clientWidth * .7, 500) * dir * (RTL ? -1 : 1);
        sc.scrollBy({ left: amount, behavior: 'smooth' });
      });
    });
  });

  /* ---------------- services hover preview ---------------- */
  if (finePointer) {
    document.querySelectorAll('[data-service-list]').forEach((list) => {
      const prev = list.parentElement.querySelector('[data-service-preview]');
      if (!prev) return;
      const img = prev.querySelector('img');
      let x = 0, y = 0, tx = 0, ty = 0, raf = null;
      const loop = () => { x += (tx - x) * .12; y += (ty - y) * .12; prev.style.transform = ''; prev.style.left = x + 'px'; prev.style.top = y + 'px'; raf = requestAnimationFrame(loop); };
      list.addEventListener('mousemove', (e) => { tx = e.clientX + 24; ty = e.clientY - 180; if (!raf) loop(); });
      list.querySelectorAll('a[data-preview]').forEach((a) => {
        a.addEventListener('mouseenter', () => { const s = a.dataset.preview; if (!s) { prev.classList.remove('is-on'); return; } img.src = s; prev.classList.add('is-on'); });
        a.addEventListener('mouseleave', () => prev.classList.remove('is-on'));
      });
    });
  }

  /* ---------------- counters ---------------- */
  const animateCount = (el) => {
    const raw = el.textContent.trim();
    const m = raw.match(/^([^\d]*)(\d[\d,.]*)(.*)$/);
    if (!m) return;
    const target = parseFloat(m[2].replace(/,/g, ''));
    if (!isFinite(target)) return;
    const decimals = (m[2].split('.')[1] || '').length;
    const start = performance.now(); const dur = 1400;
    const step = (now) => {
      const p = Math.min(1, (now - start) / dur); const ease = 1 - Math.pow(1 - p, 3);
      el.textContent = m[1] + (target * ease).toFixed(decimals).replace(/\B(?=(\d{3})+(?!\d))/g, ',') + m[3];
      if (p < 1) requestAnimationFrame(step); else el.textContent = raw;
    };
    requestAnimationFrame(step);
  };

  /* ---------------- motion ---------------- */
  // Entrance reveals: IntersectionObserver toggles .is-in, CSS transitions do the motion.
  // Works even when animation frames are throttled (background tabs, slow devices).
  if (motion) {
    // hero word split (by word: keeps Arabic ligatures intact)
    document.querySelectorAll('[data-split]').forEach((el) => {
      const nodes = Array.from(el.childNodes);
      el.innerHTML = '';
      let idx = 0;
      nodes.forEach((n) => {
        if (n.nodeType === 3) {
          n.textContent.split(/(\s+)/).forEach((part) => {
            if (!part) return;
            if (/^\s+$/.test(part)) { el.appendChild(document.createTextNode(' ')); return; }
            const w = document.createElement('span'); w.className = 'w';
            const inner = document.createElement('span'); inner.className = 'w__in'; inner.textContent = part; inner.style.setProperty('--i', String(idx++));
            w.appendChild(inner); el.appendChild(w);
          });
        } else if (n.nodeName === 'BR') {
          el.appendChild(document.createElement('br'));
        } else {
          el.appendChild(n);
        }
      });
    });
    document.querySelectorAll('.hero [data-count]').forEach((el) => setTimeout(() => animateCount(el), 1000));

    const revealEls = Array.from(document.querySelectorAll('[data-reveal]'));
    // stagger siblings that share a parent
    const seen = new Map();
    revealEls.forEach((el) => {
      const p = el.parentElement; const n = seen.get(p) || 0; seen.set(p, n + 1);
      el.style.setProperty('--d', Math.min(n, 8) * 90 + 'ms');
    });
    const show = (el) => {
      if (el.classList.contains('is-in')) return;
      el.classList.add('is-in');
      el.querySelectorAll('[data-count]').forEach(animateCount);
      if (el.hasAttribute('data-count')) animateCount(el);
    };
    if ('IntersectionObserver' in window) {
      const io = new IntersectionObserver((entries) => entries.forEach((en) => { if (en.isIntersecting) { show(en.target); io.unobserve(en.target); } }), { rootMargin: '0px 0px -8% 0px', threshold: 0.05 });
      revealEls.forEach((el) => io.observe(el));
    } else {
      revealEls.forEach(show);
    }
    // safety nets: anything on screen is shown after 2.5s; everything is shown after 6s
    const sweep = (all) => revealEls.forEach((el) => { const r = el.getBoundingClientRect(); if (all || (r.top < window.innerHeight && r.bottom > 0)) show(el); });
    setTimeout(() => sweep(false), 2500);
    setTimeout(() => sweep(true), 6000);
    document.addEventListener('visibilitychange', () => { if (!document.hidden) setTimeout(() => sweep(false), 200); });
  } else if ('IntersectionObserver' in window) {
    const io = new IntersectionObserver((es) => es.forEach((en) => { if (en.isIntersecting) { animateCount(en.target); io.unobserve(en.target); } }), { threshold: .5 });
    document.querySelectorAll('[data-count]').forEach((el) => io.observe(el));
  }

  /* ---------------- hero particles (gold dust) ---------------- */
  const canvas = document.querySelector('[data-particles]');
  if (canvas && motion && window.innerWidth > 480) {
    const ctx = canvas.getContext('2d');
    const heroEl = canvas.parentElement;
    const accent = getComputedStyle(html).getPropertyValue('--brass').trim() || '#C9A868';
    const ivory = getComputedStyle(html).getPropertyValue('--ivory').trim() || '#F1EADC';
    let W = 0, H = 0, dpr = Math.min(2, window.devicePixelRatio || 1), parts = [], running = false, raf = 0, mouse = { x: -1e4, y: -1e4 };
    const hexRgb = (h) => { const m = h.replace('#', ''); const n = parseInt(m.length === 3 ? m.split('').map((c) => c + c).join('') : m, 16); return [(n >> 16) & 255, (n >> 8) & 255, n & 255]; };
    const gold = hexRgb(accent), pale = hexRgb(ivory);
    const resize = () => {
      const r = heroEl.getBoundingClientRect(); W = r.width; H = r.height;
      canvas.width = Math.round(W * dpr); canvas.height = Math.round(H * dpr); ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      const n = Math.round(Math.min(160, (W * H) / 9000));
      parts = Array.from({ length: n }, () => spawn(true));
    };
    const spawn = (anywhere) => ({
      x: Math.random() * W, y: anywhere ? Math.random() * H : H + 10,
      r: Math.random() < .08 ? 2.2 + Math.random() * 2.4 : .5 + Math.random() * 1.3,
      vy: .12 + Math.random() * .35, drift: (Math.random() - .5) * .25, phase: Math.random() * Math.PI * 2,
      tw: .4 + Math.random() * 1.2, a: .25 + Math.random() * .55, pale: Math.random() < .25,
    });
    let t = 0;
    const frame = () => {
      if (!running) return;
      t += .016; ctx.clearRect(0, 0, W, H);
      for (const p of parts) {
        p.y -= p.vy; p.x += p.drift + Math.sin(t * p.tw + p.phase) * .18;
        const dx = p.x - mouse.x, dy = p.y - mouse.y, d2 = dx * dx + dy * dy;
        if (d2 < 22000) { const f = (1 - d2 / 22000) * .9; p.x += dx / Math.sqrt(d2 + 1) * f; p.y += dy / Math.sqrt(d2 + 1) * f; }
        if (p.y < -10 || p.x < -20 || p.x > W + 20) Object.assign(p, spawn(false));
        const alpha = p.a * (.6 + .4 * Math.sin(t * p.tw * 1.7 + p.phase));
        const c = p.pale ? pale : gold;
        if (p.r > 2) { const g = ctx.createRadialGradient(p.x, p.y, 0, p.x, p.y, p.r * 3); g.addColorStop(0, `rgba(${c[0]},${c[1]},${c[2]},${alpha * .55})`); g.addColorStop(1, `rgba(${c[0]},${c[1]},${c[2]},0)`); ctx.fillStyle = g; ctx.beginPath(); ctx.arc(p.x, p.y, p.r * 3, 0, Math.PI * 2); ctx.fill(); }
        ctx.fillStyle = `rgba(${c[0]},${c[1]},${c[2]},${alpha})`; ctx.beginPath(); ctx.arc(p.x, p.y, p.r, 0, Math.PI * 2); ctx.fill();
      }
      raf = requestAnimationFrame(frame);
    };
    const start = () => { if (running) return; running = true; raf = requestAnimationFrame(frame); };
    const stop = () => { running = false; cancelAnimationFrame(raf); };
    resize(); start();
    let rt; window.addEventListener('resize', () => { clearTimeout(rt); rt = setTimeout(resize, 150); }, { passive: true });
    heroEl.addEventListener('mousemove', (e) => { const r = canvas.getBoundingClientRect(); mouse.x = e.clientX - r.left; mouse.y = e.clientY - r.top; }, { passive: true });
    heroEl.addEventListener('mouseleave', () => { mouse.x = -1e4; mouse.y = -1e4; });
    if ('IntersectionObserver' in window) new IntersectionObserver((en) => en[0].isIntersecting ? start() : stop(), { threshold: 0 }).observe(heroEl);
    document.addEventListener('visibilitychange', () => (document.hidden ? stop() : start()));
  }

  /* ---------------- GSAP extras (parallax, ghost, magnetic, cursor) ---------------- */
  if (!motion) return;
  gsap.registerPlugin(ScrollTrigger);
  const hero = document.querySelector('.hero');
  if (hero) {
    gsap.from('[data-ghost]', { xPercent: RTL ? -10 : 10, opacity: 0, duration: 2, ease: 'power2.out' });
    gsap.to('[data-ghost]', { yPercent: 30, ease: 'none', scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: true } });
  }
  window.addEventListener('load', () => { ScrollTrigger.refresh(); });

  // hero pointer tilt: the card stack and headline answer the cursor (fine pointers only)
  const tilt = document.querySelector('[data-tilt]');
  if (hero && tilt && finePointer) {
    const layer = tilt.querySelector('[data-tilt-layer]');
    const title = hero.querySelector('.display--hero');
    const cards = Array.from(tilt.querySelectorAll('.hero__card'));
    const rotX = gsap.quickTo(layer, 'rotationX', { duration: 1.1, ease: 'power3.out' });
    const rotY = gsap.quickTo(layer, 'rotationY', { duration: 1.1, ease: 'power3.out' });
    const moves = cards.map((c, i) => ({ x: gsap.quickTo(c, 'x', { duration: 1.2, ease: 'power3.out' }), y: gsap.quickTo(c, 'y', { duration: 1.2, ease: 'power3.out' }), depth: parseFloat(c.dataset.depth) || [10, 22, 16][i] || 12 }));
    const tx = title ? gsap.quickTo(title, 'x', { duration: 1.4, ease: 'power3.out' }) : null;
    hero.addEventListener('mousemove', (e) => {
      const r = hero.getBoundingClientRect();
      const px = (e.clientX - r.left) / r.width - .5, py = (e.clientY - r.top) / r.height - .5;
      rotX(-py * 4); rotY(px * 6);
      moves.forEach((m) => { m.x(px * m.depth * 2); m.y(py * m.depth * 1.4); });
      tx && tx(px * 6);
    }, { passive: true });
    hero.addEventListener('mouseleave', () => { rotX(0); rotY(0); moves.forEach((m) => { m.x(0); m.y(0); }); tx && tx(0); });
  }

  // parallax images
  document.querySelectorAll('[data-parallax]').forEach((img) => {
    const speed = parseFloat(img.dataset.parallax) || .1;
    gsap.fromTo(img, { yPercent: -speed * 40 }, { yPercent: speed * 40, ease: 'none', scrollTrigger: { trigger: img.parentElement, start: 'top bottom', end: 'bottom top', scrub: true } });
    img.style.scale = (1 + speed * .8).toString();
  });

  // magnetic buttons + cursor (fine pointers only)
  if (finePointer) {
    document.querySelectorAll('[data-magnetic]').forEach((btn) => {
      const strength = 18;
      btn.addEventListener('mousemove', (e) => {
        const r = btn.getBoundingClientRect();
        const dx = (e.clientX - (r.left + r.width / 2)) / r.width;
        const dy = (e.clientY - (r.top + r.height / 2)) / r.height;
        gsap.to(btn, { x: dx * strength, y: dy * strength, duration: .5, ease: 'power3.out' });
      });
      btn.addEventListener('mouseleave', () => gsap.to(btn, { x: 0, y: 0, duration: .8, ease: 'elastic.out(1, .5)' }));
    });

    const cursor = document.querySelector('.cursor');
    if (cursor) {
      const dot = cursor.querySelector('.cursor__dot'), ring = cursor.querySelector('.cursor__ring');
      let mx = -100, my = -100, rx = -100, ry = -100;
      document.addEventListener('mousemove', (e) => { mx = e.clientX; my = e.clientY; cursor.classList.remove('is-hidden'); }, { passive: true });
      document.addEventListener('mouseleave', () => cursor.classList.add('is-hidden'));
      gsap.ticker.add(() => {
        rx += (mx - rx) * .18; ry += (my - ry) * .18;
        dot.style.transform = 'translate(' + mx + 'px,' + my + 'px)';
        ring.style.transform = 'translate(' + rx + 'px,' + ry + 'px)';
      });
      document.addEventListener('mouseover', (e) => cursor.classList.toggle('is-hover', !!e.target.closest('a, button, summary, [data-magnetic]')));
    }
  }
})();
