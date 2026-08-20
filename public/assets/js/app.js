(function () {
  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // Sticky condensed header
  const header = document.getElementById('siteHeader');
  if (header) {
    const onScroll = () => {
      header.classList.toggle('is-condensed', window.scrollY > 48);
    };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  // Mega-menu
  const navItems = document.querySelectorAll('.nav-item');
  function closeAllMenus(except) {
    navItems.forEach((item) => {
      if (item === except) return;
      item.classList.remove('is-open');
      const trigger = item.querySelector('.nav-trigger');
      if (trigger) trigger.setAttribute('aria-expanded', 'false');
    });
  }
  navItems.forEach((item) => {
    const trigger = item.querySelector('.nav-trigger');
    if (!trigger) return;
    trigger.addEventListener('click', (e) => {
      e.stopPropagation();
      const open = !item.classList.contains('is-open');
      closeAllMenus(item);
      item.classList.toggle('is-open', open);
      trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  });
  document.addEventListener('click', () => closeAllMenus());
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') closeAllMenus();
  });

  // Mobile drawer
  const drawer = document.getElementById('mobileDrawer');
  const navToggle = document.getElementById('navToggle');
  const navClose = document.getElementById('navClose');
  function setDrawer(open) {
    if (!drawer) return;
    drawer.hidden = !open;
    drawer.classList.toggle('open', open);
    if (navToggle) navToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    document.body.style.overflow = open ? 'hidden' : '';
  }
  if (navToggle) navToggle.addEventListener('click', () => setDrawer(true));
  if (navClose) navClose.addEventListener('click', () => setDrawer(false));
  if (drawer) {
    drawer.addEventListener('click', (e) => {
      if (e.target === drawer) setDrawer(false);
    });
  }

  // Command palette
  const cmdk = document.getElementById('cmdk');
  const cmdkInput = document.getElementById('cmdkInput');
  const cmdkList = document.getElementById('cmdkList');
  const searchPages = [
    { group: 'Attend', label: 'Register', href: '/register' },
    { group: 'Attend', label: 'Venue', href: '/venue' },
    { group: 'Attend', label: 'FAQ', href: '/faq' },
    { group: 'Participate', label: 'Open challenges', href: '/challenges' },
    { group: 'Participate', label: 'Innovators', href: '/innovators' },
    { group: 'Participate', label: 'Exhibitors', href: '/exhibitors' },
    { group: 'Participate', label: 'Sponsors', href: '/sponsors' },
    { group: 'Programme', label: 'Agenda', href: '/programme' },
    { group: 'Programme', label: 'Speakers', href: '/speakers' },
    { group: 'Programme', label: 'Awards', href: '/awards' },
    { group: 'Deals', label: 'Deal Rooms', href: '/deals' },
    { group: 'About', label: 'About ZBIF', href: '/about' },
    { group: 'About', label: 'How it works', href: '/how-it-works' },
    { group: 'About', label: 'Newsroom', href: '/newsroom' },
    { group: 'About', label: 'Contact', href: '/contact' },
    { group: 'About', label: 'Style guide', href: '/style-guide' },
    { group: 'Account', label: 'Log in', href: '/login.php' },
  ];

  function renderCmdk(q) {
    if (!cmdkList) return;
    const query = (q || '').trim().toLowerCase();
    const hits = searchPages.filter((p) => !query || p.label.toLowerCase().includes(query) || p.group.toLowerCase().includes(query));
    if (!hits.length) {
      cmdkList.innerHTML = '<div class="cmdk-group">No matches</div>';
      return;
    }
    let html = '';
    let last = '';
    hits.forEach((p) => {
      if (p.group !== last) {
        html += `<div class="cmdk-group">${p.group}</div>`;
        last = p.group;
      }
      html += `<a href="${p.href}">${p.label}</a>`;
    });
    cmdkList.innerHTML = html;
  }

  function openCmdk() {
    if (!cmdk) return;
    cmdk.classList.add('open');
    renderCmdk('');
    if (cmdkInput) {
      cmdkInput.value = '';
      setTimeout(() => cmdkInput.focus(), 10);
    }
  }
  function closeCmdk() {
    if (!cmdk) return;
    cmdk.classList.remove('open');
  }
  ['searchOpen', 'searchOpenTop'].forEach((id) => {
    const btn = document.getElementById(id);
    if (btn) btn.addEventListener('click', openCmdk);
  });
  if (cmdk) {
    cmdk.addEventListener('click', (e) => {
      if (e.target === cmdk) closeCmdk();
    });
  }
  if (cmdkInput) {
    cmdkInput.addEventListener('input', () => renderCmdk(cmdkInput.value));
  }
  document.addEventListener('keydown', (e) => {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
      e.preventDefault();
      if (cmdk && cmdk.classList.contains('open')) closeCmdk();
      else openCmdk();
    }
    if (e.key === 'Escape') {
      closeCmdk();
      setDrawer(false);
    }
  });

  // Countdown (supports multiple)
  document.querySelectorAll('.countdown[data-target]').forEach((cd) => {
    const target = new Date(cd.dataset.target).getTime();
    if (!target) return;
    const tick = () => {
      const diff = Math.max(0, target - Date.now());
      const d = Math.floor(diff / 86400000);
      const h = Math.floor((diff % 86400000) / 3600000);
      const m = Math.floor((diff % 3600000) / 60000);
      const s = Math.floor((diff % 60000) / 1000);
      cd.innerHTML = [
        ['Days', d],
        ['Hours', h],
        ['Mins', m],
        ['Secs', s],
      ]
        .map(([l, v]) => `<div><strong>${String(v).padStart(2, '0')}</strong><span>${l}</span></div>`)
        .join('');
    };
    tick();
    setInterval(tick, 1000);
  });

  // legacy single id kept as no-op if already handled
  const cdLegacy = null;

  // Scroll reveal
  const reveals = document.querySelectorAll('.reveal');
  if (reveals.length) {
    if (reduceMotion || !('IntersectionObserver' in window)) {
      reveals.forEach((el) => el.classList.add('is-in'));
    } else {
      const io = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              entry.target.classList.add('is-in');
              io.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
      );
      reveals.forEach((el, i) => {
        el.style.transitionDelay = `${Math.min(i % 6, 5) * 50}ms`;
        io.observe(el);
      });
    }
  }

  // Count-up stats
  const counters = document.querySelectorAll('[data-count]');
  if (counters.length) {
    const animate = (el) => {
      const end = Number(el.getAttribute('data-count') || 0);
      if (reduceMotion || end === 0) {
        el.textContent = end >= 1000 ? end.toLocaleString() : String(Math.round(end));
        return;
      }
      const duration = 900;
      const start = performance.now();
      const step = (now) => {
        const t = Math.min(1, (now - start) / duration);
        const eased = 1 - Math.pow(1 - t, 3);
        const val = end * eased;
        el.textContent = end >= 1000 ? Math.round(val).toLocaleString() : String(Math.round(val));
        if (t < 1) requestAnimationFrame(step);
      };
      requestAnimationFrame(step);
    };
    if (!('IntersectionObserver' in window)) {
      counters.forEach(animate);
    } else {
      const cio = new IntersectionObserver(
        (entries) => {
          entries.forEach((entry) => {
            if (entry.isIntersecting) {
              animate(entry.target);
              cio.unobserve(entry.target);
            }
          });
        },
        { threshold: 0.4 }
      );
      counters.forEach((el) => cio.observe(el));
    }
  }

  // Testimonial carousel
  document.querySelectorAll('[data-carousel]').forEach((root) => {
    const track = root.querySelector('.carousel-track');
    if (!track) return;
    let index = 0;
    const slides = () => track.children.length;
    const go = (dir) => {
      const n = slides();
      if (!n) return;
      index = (index + dir + n) % n;
      const slide = track.children[index];
      if (slide) {
        track.style.transform = `translateX(-${slide.offsetLeft}px)`;
      }
    };
    const prev = root.querySelector('[data-carousel-prev]');
    const next = root.querySelector('[data-carousel-next]');
    if (prev) prev.addEventListener('click', () => go(-1));
    if (next) next.addEventListener('click', () => go(1));
  });

  // App sidebar rail — fixed; hamburger toggles open/collapsed
  const shell = document.getElementById('dashShell');
  const railToggle = document.getElementById('navRailToggle');
  const scrim = document.getElementById('dashNavScrim');
  if (shell && railToggle) {
    const key = 'zbif-app-nav-open';
    const mq = window.matchMedia('(max-width: 980px)');
    let preferred = true;
    try {
      const stored = localStorage.getItem(key);
      if (stored === '0') preferred = false;
      if (stored === '1') preferred = true;
    } catch (e) {}
    const setNav = (open, persist = true) => {
      shell.classList.toggle('is-nav-open', open);
      shell.classList.toggle('is-nav-collapsed', !open);
      railToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
      if (scrim) scrim.hidden = !open || !mq.matches;
      if (persist) {
        preferred = open;
        try {
          localStorage.setItem(key, open ? '1' : '0');
        } catch (e) {}
      }
    };
    setNav(mq.matches ? false : preferred, false);
    railToggle.addEventListener('click', () => setNav(!shell.classList.contains('is-nav-open')));
    if (scrim) scrim.addEventListener('click', () => setNav(false));
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && shell.classList.contains('is-nav-open') && mq.matches) setNav(false);
    });
    mq.addEventListener('change', () => {
      setNav(mq.matches ? false : preferred, false);
    });
  }

  // Challenge cards expand
  document.querySelectorAll('[data-chal-toggle]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const card = btn.closest('.chal-card');
      if (!card) return;
      const open = !card.classList.contains('is-open');
      card.classList.toggle('is-open', open);
      btn.textContent = open ? 'Hide details' : 'Read full brief';
    });
  });

  // Agenda day tabs
  document.querySelectorAll('[data-agenda-day]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const day = btn.getAttribute('data-agenda-day');
      document.querySelectorAll('[data-agenda-day]').forEach((b) => b.classList.toggle('is-active', b === btn));
      document.querySelectorAll('[data-agenda-panel]').forEach((panel) => {
        panel.hidden = panel.getAttribute('data-agenda-panel') !== day;
      });
    });
  });

  // Deal room filters
  document.querySelectorAll('[data-tt-filter]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const key = btn.getAttribute('data-tt-filter');
      document.querySelectorAll('[data-tt-filter]').forEach((b) => b.classList.toggle('is-active', b === btn));
      document.querySelectorAll('[data-tt-room]').forEach((room) => {
        room.hidden = key !== 'all' && room.getAttribute('data-tt-room') !== key;
      });
    });
  });

  // Partner "Become a partner" coming soon modal
  const partnerModal = document.getElementById('partnerSoonModal');
  if (partnerModal) {
    const setPartnerModal = (open) => {
      partnerModal.hidden = !open;
      document.body.style.overflow = open ? 'hidden' : '';
    };
    document.querySelectorAll('[data-partner-soon]').forEach((btn) => {
      btn.addEventListener('click', () => setPartnerModal(true));
    });
    partnerModal.querySelectorAll('[data-partner-soon-close]').forEach((el) => {
      el.addEventListener('click', () => setPartnerModal(false));
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape' && !partnerModal.hidden) setPartnerModal(false);
    });
  }

  // Deal room polling
  const dealLog = document.getElementById('dealMessages');
  if (dealLog && dealLog.dataset.roomId) {
    let after = Number(dealLog.dataset.after || 0);
    const roomId = dealLog.dataset.roomId;
    setInterval(async () => {
      try {
        const res = await fetch(`/app/deals/${roomId}/messages?after=${after}`);
        const data = await res.json();
        (data.messages || []).forEach((m) => {
          after = Math.max(after, Number(m.id));
          const el = document.createElement('div');
          el.className = 'card';
          el.style.marginBottom = '0.5rem';
          el.innerHTML = `<strong>${m.first_name} ${m.last_name}</strong><div>${m.body}</div><div class="muted" style="font-size:0.8rem">${m.created_at}</div>`;
          dealLog.appendChild(el);
        });
        dealLog.dataset.after = String(after);
      } catch (e) {}
    }, 3000);
  }
})();
