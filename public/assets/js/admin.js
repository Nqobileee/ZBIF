(() => {
  const shell = document.getElementById('admShell');
  const toggle = document.getElementById('admNavToggle');
  const scrim = document.getElementById('admNavScrim');
  if (!shell || !toggle) return;

  const key = 'zbif-admin-nav-open';
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
    toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    if (scrim) scrim.hidden = !open || !mq.matches;
    if (persist) {
      preferred = open;
      try {
        localStorage.setItem(key, open ? '1' : '0');
      } catch (e) {}
    }
  };

  setNav(mq.matches ? false : preferred, false);

  toggle.addEventListener('click', () => setNav(!shell.classList.contains('is-nav-open')));
  if (scrim) scrim.addEventListener('click', () => setNav(false));
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && shell.classList.contains('is-nav-open') && mq.matches) setNav(false);
  });
  mq.addEventListener('change', () => {
    setNav(mq.matches ? false : preferred, false);
  });
})();
