/**
 * Nova floating assistant — FAB bottom-right, slide-in dock.
 */
(function () {
  const dock = document.getElementById('novaDock');
  const openBtn = document.getElementById('novaOpen');
  const closeBtn = document.getElementById('novaClose');
  const form = document.getElementById('novaForm');
  const log = document.getElementById('novaLog');
  const input = document.getElementById('novaInput');
  if (!dock || !form || !log || !input) return;

  const mode = dock.getAttribute('data-mode') || 'assist';

  function setOpen(open) {
    dock.classList.toggle('is-open', open);
    if (openBtn) {
      openBtn.setAttribute('aria-expanded', open ? 'true' : 'false');
      openBtn.classList.toggle('is-hidden', open);
    }
    if (open && !log.childElementCount) {
      addBubble('Hi. Ask me about registration, matching, Deal Rooms, or the forum schedule.', 'bot');
    }
    if (open && input) {
      window.setTimeout(() => input.focus(), 180);
    }
  }

  function addBubble(text, who) {
    const div = document.createElement('div');
    div.className = 'bubble bubble-' + who;
    div.textContent = text;
    log.appendChild(div);
    log.scrollTop = log.scrollHeight;
  }

  async function ask(question) {
    addBubble(question, 'user');
    try {
      if (mode === 'registration') {
        // reserved
      }
      const body = new URLSearchParams({ question });
      const res = await fetch('/api/v1/ai/ask', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', Accept: 'application/json' },
        body,
      });
      const data = await res.json();
      addBubble((data && data.answer) || 'I could not answer that. Try the FAQ or Contact page.', 'bot');
    } catch (e) {
      addBubble('Assist is temporarily unavailable. Try the FAQ or Contact page.', 'bot');
    }
  }

  if (openBtn) {
    openBtn.addEventListener('click', () => setOpen(!dock.classList.contains('is-open')));
  }
  if (closeBtn) {
    closeBtn.addEventListener('click', () => setOpen(false));
  }

  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && dock.classList.contains('is-open')) {
      setOpen(false);
    }
  });

  document.querySelectorAll('#regAskNovaSide').forEach((btn) => {
    btn.addEventListener('click', () => setOpen(true));
  });

  document.querySelectorAll('#novaChips [data-prompt]').forEach((btn) => {
    btn.addEventListener('click', () => {
      setOpen(true);
      ask(btn.getAttribute('data-prompt') || '');
    });
  });

  form.addEventListener('submit', (e) => {
    e.preventDefault();
    const message = input.value.trim();
    if (!message) return;
    input.value = '';
    ask(message);
  });

  if (dock.classList.contains('is-open')) {
    setOpen(true);
  }
})();
