(function () {
  const form = document.getElementById('regForm');
  if (!form) {
    bindPasswordToggles(document);
    bindOtp(document);
    return;
  }

  const panels = [...form.querySelectorAll('.wizard-panel')];
  const steps = [...document.querySelectorAll('#wizardSteps [data-step]')];
  const personaInput = document.getElementById('persona');
  const draftToken = document.getElementById('draftToken');
  const stepNames = ['organisation', 'account', 'capabilities'];
  let step = Math.max(0, panels.findIndex((p) => p.classList.contains('active')));
  if (step < 0) step = 0;

  function syncPersonaLabels() {
    const p = personaInput.value === 'corporate' ? 'corporate' : 'innovator';
    form.querySelectorAll('[data-label-innovator]').forEach((el) => {
      el.hidden = p !== 'innovator';
    });
    form.querySelectorAll('[data-label-corporate]').forEach((el) => {
      el.hidden = p !== 'corporate';
    });
    const typeSelect = document.getElementById('organization_type');
    if (typeSelect && !typeSelect.dataset.ready) {
      typeSelect.dataset.ready = '1';
      typeSelect._allOptions = [...typeSelect.querySelectorAll('option')].map((o) => ({
        value: o.value,
        label: o.textContent,
        group: o.parentElement.getAttribute('data-persona-group') || 'innovator',
      }));
    }
    if (typeSelect && typeSelect._allOptions) {
      const current = typeSelect.value;
      typeSelect.innerHTML = '';
      typeSelect._allOptions.filter((o) => o.group === p).forEach((o) => {
        const opt = document.createElement('option');
        opt.value = o.value;
        opt.textContent = o.label;
        typeSelect.appendChild(opt);
      });
      const match = typeSelect._allOptions.find((o) => o.group === p && o.value === current);
      typeSelect.value = match ? current : (typeSelect.options[0] ? typeSelect.options[0].value : '');
    }
  }

  function go(n) {
    step = Math.max(0, Math.min(panels.length - 1, n));
    panels.forEach((p, i) => p.classList.toggle('active', i === step));
    steps.forEach((s) => {
      const idx = parseInt(s.getAttribute('data-step') || '0', 10);
      s.classList.toggle('is-active', idx === step);
      s.classList.toggle('is-done', idx < step);
      const mark = s.querySelector('.reg-progress-num, .reg-step-mark');
      if (mark) mark.textContent = idx < step ? '✓' : String(idx + 1);
    });
    syncPersonaLabels();
    const first = panels[step].querySelector('input:not([type=hidden]):not([type=radio]), select, textarea, button');
    if (first) first.focus({ preventScroll: true });
    window.scrollTo({ top: 0, behavior: 'smooth' });
  }

  function validatePanel(panel) {
    const required = [...panel.querySelectorAll('[required]')].filter((el) => el.offsetParent !== null || el.type === 'checkbox' || el.type === 'hidden');
    for (const el of required) {
      if (el.type === 'checkbox') {
        if (!el.checked) {
          el.focus();
          alert('Please accept the required agreements to continue.');
          return false;
        }
        continue;
      }
      if (!String(el.value || '').trim()) {
        el.focus();
        alert('Please complete the required fields.');
        return false;
      }
      if (el.type === 'email' && el.validity && !el.validity.valid) {
        el.focus();
        alert('Enter a valid email address.');
        return false;
      }
    }
    return true;
  }

  function passwordsMatch() {
    const a = document.getElementById('password');
    const b = document.getElementById('password_confirmation');
    const err = document.getElementById('pwdMatchError');
    if (!a || !b) return true;
    const ok = a.value === b.value && a.value.length >= 10;
    if (err) err.hidden = a.value === b.value || !b.value;
    if (a.value !== b.value) {
      b.focus();
      if (err) err.hidden = false;
      return false;
    }
    if (a.value.length < 10) {
      alert('Use at least 10 characters with letters and numbers.');
      a.focus();
      return false;
    }
    if (!/[A-Za-z]/.test(a.value) || !/[0-9]/.test(a.value)) {
      alert('Include both letters and numbers in your password.');
      a.focus();
      return false;
    }
    return ok || a.value === b.value;
  }

  document.querySelectorAll('.persona-card, .reg-role').forEach((card) => {
    card.addEventListener('click', () => {
      document.querySelectorAll('.persona-card, .reg-role').forEach((c) => c.classList.remove('is-selected'));
      card.classList.add('is-selected');
      const radio = card.querySelector('input[type=radio]');
      if (radio) {
        radio.checked = true;
        personaInput.value = radio.value;
      }
      syncPersonaLabels();
    });
  });

  document.querySelectorAll('.reg-chip').forEach((chip) => {
    chip.addEventListener('click', (e) => {
      e.preventDefault();
      const input = chip.querySelector('input');
      if (!input) return;
      input.checked = !input.checked;
      chip.classList.toggle('is-on', input.checked);
    });
  });

  async function autosave(stepName) {
    const body = new URLSearchParams(new FormData(form));
    body.set('current_step', stepName || 'organisation');
    try {
      const res = await fetch(form.getAttribute('data-draft-url') || '/register/draft', {
        method: 'POST',
        headers: { Accept: 'application/json' },
        body,
      });
      const data = await res.json();
      if (data.draft_token && draftToken) draftToken.value = data.draft_token;
    } catch (e) {}
  }

  form.querySelectorAll('[data-next]').forEach((b) =>
    b.addEventListener('click', async () => {
      if (!validatePanel(panels[step])) return;
      if (step === 1 && !passwordsMatch()) return;
      await autosave(stepNames[step]);
      go(step + 1);
    })
  );
  form.querySelectorAll('[data-prev]').forEach((b) => b.addEventListener('click', () => go(step - 1)));

  form.addEventListener('submit', (e) => {
    if (!validatePanel(panels[step])) {
      e.preventDefault();
      return;
    }
    if (!passwordsMatch()) {
      e.preventDefault();
      go(1);
    }
  });

  // Contacts
  const list = document.getElementById('contactsList');
  const tpl = document.getElementById('contactTemplate');
  const addBtn = document.getElementById('addContactBtn');

  function reindexContacts() {
    if (!list) return;
    [...list.querySelectorAll('.reg-contact')].forEach((card, i) => {
      card.setAttribute('data-contact-index', String(i));
      const title = card.querySelector('.reg-contact-top strong');
      if (title) title.textContent = i === 0 ? 'Contact 1 · primary' : `Contact ${i + 1}`;
      card.querySelectorAll('input[name]').forEach((input) => {
        input.name = input.name.replace(/contacts\[\d+]/, `contacts[${i}]`);
        if (input.name.includes('[name]') || input.name.includes('[email]')) {
          input.required = i === 0;
        }
      });
      let remove = card.querySelector('[data-remove-contact]');
      if (i === 0 && remove) remove.remove();
      if (i > 0 && !remove) {
        const top = card.querySelector('.reg-contact-top');
        if (top) {
          remove = document.createElement('button');
          remove.type = 'button';
          remove.className = 'reg-contact-remove';
          remove.setAttribute('data-remove-contact', '');
          remove.textContent = 'Remove';
          top.appendChild(remove);
        }
      }
    });
  }

  if (addBtn && list && tpl) {
    addBtn.addEventListener('click', () => {
      const i = list.querySelectorAll('.reg-contact').length;
      const html = tpl.innerHTML.replaceAll('__i__', String(i)).replaceAll('__n__', String(i + 1));
      list.insertAdjacentHTML('beforeend', html);
      reindexContacts();
    });
    list.addEventListener('click', (e) => {
      const btn = e.target.closest('[data-remove-contact]');
      if (!btn) return;
      const card = btn.closest('.reg-contact');
      if (card) card.remove();
      reindexContacts();
    });
  }

  // Logo preview
  const logoInput = document.getElementById('logoInput');
  const logoBtn = document.getElementById('logoPickBtn');
  const logoPreview = document.getElementById('logoPreview');
  const orgInput = document.getElementById('organization');
  if (logoBtn && logoInput) {
    logoBtn.addEventListener('click', () => logoInput.click());
    logoInput.addEventListener('change', () => {
      const file = logoInput.files && logoInput.files[0];
      if (!file || !logoPreview) return;
      if (file.size > 1.5 * 1024 * 1024) {
        alert('Logo must be 1.5 MB or smaller.');
        logoInput.value = '';
        return;
      }
      const url = URL.createObjectURL(file);
      logoPreview.style.backgroundImage = `url(${url})`;
      logoPreview.textContent = '';
    });
  }
  if (orgInput && logoPreview) {
    orgInput.addEventListener('input', () => {
      if (logoPreview.style.backgroundImage) return;
      const parts = orgInput.value.trim().split(/\s+/).filter(Boolean);
      const a = (parts[0] || 'Z').slice(0, 1);
      const b = (parts[1] || parts[0] || 'B').slice(0, 1);
      logoPreview.textContent = (a + b).toUpperCase();
    });
  }

  const pwd = document.getElementById('password');
  const confirm = document.getElementById('password_confirmation');
  const meter = document.getElementById('strengthMeter');
  const matchErr = document.getElementById('pwdMatchError');
  if (pwd && meter) {
    pwd.addEventListener('input', () => {
      const v = pwd.value;
      let score = 0;
      if (v.length >= 10) score++;
      if (v.length >= 14) score++;
      if (/[a-z]/.test(v) && /[A-Z]/.test(v)) score++;
      if (/[0-9]/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;
      score = Math.min(4, score);
      [...meter.children].forEach((el, i) => {
        el.classList.toggle('on', i < score);
        el.classList.toggle('warn', score === 2);
        el.classList.toggle('good', score >= 3);
      });
    });
  }
  if (confirm && matchErr) {
    const check = () => {
      matchErr.hidden = !confirm.value || confirm.value === (pwd ? pwd.value : '');
    };
    confirm.addEventListener('input', check);
    if (pwd) pwd.addEventListener('input', check);
  }

  syncPersonaLabels();
  go(step);
  bindPasswordToggles(form);
})();

function bindPasswordToggles(root) {
  root.querySelectorAll('[data-toggle-password]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const id = btn.getAttribute('data-toggle-password');
      const input = document.getElementById(id);
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.setAttribute('aria-pressed', show ? 'true' : 'false');
      btn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
      const icon = btn.querySelector('.bi');
      if (icon) {
        icon.classList.toggle('bi-eye', !show);
        icon.classList.toggle('bi-eye-slash', show);
      } else {
        btn.textContent = show ? 'Hide' : 'Show';
      }
    });
  });
}

function bindOtp(root) {
  const wrap = root.querySelector('.otp-inputs');
  if (!wrap) return;
  const inputs = [...wrap.querySelectorAll('input')];
  const hidden = root.querySelector('#otpCode');
  const sync = () => {
    if (hidden) hidden.value = inputs.map((i) => i.value).join('');
  };
  inputs.forEach((input, idx) => {
    input.addEventListener('input', () => {
      input.value = input.value.replace(/\D/g, '').slice(0, 1);
      if (input.value && inputs[idx + 1]) inputs[idx + 1].focus();
      sync();
    });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Backspace' && !input.value && inputs[idx - 1]) inputs[idx - 1].focus();
    });
    input.addEventListener('paste', (e) => {
      const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
      if (!text) return;
      e.preventDefault();
      text.split('').forEach((ch, i) => {
        if (inputs[i]) inputs[i].value = ch;
      });
      sync();
      (inputs[Math.min(text.length, inputs.length - 1)] || input).focus();
    });
  });
}
