(function () {
  const stack = document.getElementById('toastStack');
  if (!stack) return;

  const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function dismiss(toast) {
    if (!toast || toast.dataset.leaving === '1') return;
    toast.dataset.leaving = '1';
    toast.classList.add('is-leaving');
    const done = () => toast.remove();
    if (reduceMotion) {
      done();
      return;
    }
    window.setTimeout(done, 220);
  }

  stack.querySelectorAll('[data-toast]').forEach((toast) => {
    const type = toast.getAttribute('data-toast-type') || 'info';
    const ttl = type === 'error' ? 9000 : 5200;
    const closeBtn = toast.querySelector('.toast-close');
    if (closeBtn) {
      closeBtn.addEventListener('click', () => dismiss(toast));
    }
    toast.addEventListener('click', (e) => {
      if (e.target === closeBtn) return;
    });
    window.setTimeout(() => dismiss(toast), ttl);
  });
})();
