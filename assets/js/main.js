(function () {
  const nav = document.querySelector('.nav');
  const burger = document.querySelector('.burger');
  if (nav) {
    const onScroll = () => nav.classList.toggle('is-solid', window.scrollY > 20);
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }
  if (burger && nav) {
    burger.addEventListener('click', () => nav.classList.toggle('open'));
  }

  const adminBody = document.querySelector('.admin-body');
  const adminNav = document.querySelector('[data-admin-nav]');
  const adminScrim = document.querySelector('[data-admin-scrim]');
  const closeAdminNav = () => adminBody && adminBody.classList.remove('is-nav');
  if (adminNav && adminBody) {
    adminNav.addEventListener('click', () => adminBody.classList.toggle('is-nav'));
  }
  if (adminScrim) adminScrim.addEventListener('click', closeAdminNav);
  document.querySelectorAll('.aside a').forEach((a) => a.addEventListener('click', closeAdminNav));

  const askLayer = document.getElementById('askLayer');
  const askMsg = document.getElementById('askMsg');
  const askYes = document.getElementById('askYes');
  const askNo = document.getElementById('askNo');
  let askWait = null;
  const closeAsk = (ok) => {
    if (askLayer) askLayer.hidden = true;
    const fn = askWait;
    askWait = null;
    if (fn) fn(!!ok);
  };
  const ask = (msg) => new Promise((resolve) => {
    if (!askLayer || !askMsg) {
      resolve(false);
      return;
    }
    askMsg.textContent = msg;
    askLayer.hidden = false;
    askWait = resolve;
  });
  if (askYes) askYes.addEventListener('click', () => closeAsk(true));
  if (askNo) askNo.addEventListener('click', () => closeAsk(false));
  if (askLayer) askLayer.addEventListener('click', (e) => { if (e.target === askLayer) closeAsk(false); });

  document.querySelectorAll('[data-confirm]').forEach((el) => {
    el.addEventListener('click', (e) => {
      if (el.dataset.ok === '1') return;
      e.preventDefault();
      e.stopPropagation();
      ask(el.getAttribute('data-confirm') || 'مطمئنی؟').then((ok) => {
        if (!ok) return;
        el.dataset.ok = '1';
        if (el.form) {
          if (typeof el.form.requestSubmit === 'function') el.form.requestSubmit(el);
          else el.form.submit();
        } else {
          el.click();
        }
      });
    });
  });

  document.querySelectorAll('[data-toast]').forEach((t) => {
    const close = () => {
      t.classList.add('is-out');
      setTimeout(() => t.remove(), 280);
    };
    const x = t.querySelector('[data-toast-close]');
    if (x) x.addEventListener('click', close);
    setTimeout(close, 5400);
  });

  document.querySelectorAll('.admin-table-wrap table').forEach((table) => {
    const labels = [...table.querySelectorAll('thead th')].map((th) => th.textContent.trim());
    table.querySelectorAll('tbody tr').forEach((tr) => {
      [...tr.children].forEach((td, i) => {
        if (!td.getAttribute('data-label')) td.setAttribute('data-label', labels[i] || '');
      });
    });
  });

  document.querySelectorAll('.aside-g-h').forEach((btn) => {
    btn.addEventListener('click', () => {
      const box = btn.parentElement;
      const on = box.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', on ? 'true' : 'false');
    });
  });

  document.querySelectorAll('[data-flip]').forEach((card) => {
    const toggle = () => {
      const on = card.classList.toggle('is-flipped');
      card.setAttribute('aria-pressed', on ? 'true' : 'false');
    };
    card.addEventListener('click', (e) => {
      e.preventDefault();
      toggle();
    });
    card.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        toggle();
      }
    });
  });

  document.querySelectorAll('[data-picker]').forEach((box) => {
    const file = box.querySelector('input[type="file"]');
    const preset = box.querySelector('[data-preset]');
    const preview = box.querySelector('[data-preview]');
    const items = box.querySelectorAll('[data-preset-src]');
    const setPreview = (url) => {
      if (!preview) return;
      preview.src = url;
      preview.classList.remove('is-empty');
    };
    if (file) {
      file.addEventListener('change', () => {
        if (!file.files || !file.files[0]) return;
        if (preset) preset.value = '';
        items.forEach((b) => b.classList.remove('is-on'));
        setPreview(URL.createObjectURL(file.files[0]));
        const lab = box.querySelector('.file-btn span');
        if (lab) lab.textContent = file.files[0].name;
      });
    }
    items.forEach((btn) => {
      btn.addEventListener('click', (e) => {
        e.preventDefault();
        items.forEach((b) => b.classList.remove('is-on'));
        btn.classList.add('is-on');
        if (preset) preset.value = btn.getAttribute('data-preset-src') || '';
        if (file) file.value = '';
        setPreview(btn.getAttribute('data-preset-url') || '');
        const lab = box.querySelector('.file-btn span');
        if (lab) lab.textContent = 'انتخاب از دستگاه';
      });
    });
  });

  document.querySelectorAll('[data-fill-amount]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const form = btn.closest('form');
      const input = form && form.querySelector('input[name="amount"]');
      if (input) input.value = btn.getAttribute('data-fill-amount') || '';
    });
  });

  const standalone = window.matchMedia('(display-mode: standalone)').matches || !!window.navigator.standalone;
  if (standalone) document.documentElement.classList.add('is-standalone');

  if ('serviceWorker' in navigator) {
    const base = document.querySelector('base');
    const swUrl = (base ? new URL('sw.js', base.href).href : '/sw.js') + '?v=15';
    navigator.serviceWorker.getRegistrations().then(function (regs) {
      return Promise.all(regs.map(function (r) {
        const url = (r.active && r.active.scriptURL) || (r.installing && r.installing.scriptURL) || '';
        if (url.indexOf('sw.js?v=15') === -1) return r.unregister();
      }));
    }).finally(function () {
      navigator.serviceWorker.register(swUrl).catch(function () {});
    });
  }

  const bar = document.getElementById('installBar');
  const btn = document.getElementById('installBtn');
  const dismiss = document.getElementById('installDismiss');
  let deferredPrompt = null;
  const dismissed = localStorage.getItem('rokad-install-hide') === '1';

  if (bar && !standalone && !dismissed) {
    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      deferredPrompt = e;
      bar.hidden = false;
    });
  }
  if (btn) {
    btn.addEventListener('click', async () => {
      if (!deferredPrompt) return;
      deferredPrompt.prompt();
      await deferredPrompt.userChoice;
      deferredPrompt = null;
      if (bar) bar.hidden = true;
    });
  }
  if (dismiss) {
    dismiss.addEventListener('click', () => {
      if (bar) bar.hidden = true;
      try { localStorage.setItem('rokad-install-hide', '1'); } catch (e) {}
    });
  }

  document.querySelectorAll('[data-pass-eye]').forEach((btn) => {
    btn.addEventListener('click', () => {
      const wrap = btn.closest('.pass-wrap');
      const inp = wrap && wrap.querySelector('input');
      if (!inp) return;
      const show = inp.type === 'password';
      inp.type = show ? 'text' : 'password';
      btn.classList.toggle('is-open', show);
      btn.setAttribute('aria-label', show ? 'پنهان کردن رمز' : 'نمایش رمز');
    });
  });
  const meterInp = document.querySelector('[data-pass-meter]');
  const meter = document.querySelector('[data-pass-bar]');
  const meterLab = document.querySelector('[data-pass-lab]');
  const scorePass = (v) => {
    let n = 0;
    if (!v) return 0;
    n += Math.min(40, v.length * 6);
    if (/[a-z]/.test(v) && /[A-Z]/.test(v)) n += 15;
    else if (/[a-zA-Z]/.test(v)) n += 8;
    if (/\d/.test(v)) n += 15;
    if (/[^A-Za-z0-9]/.test(v)) n += 15;
    if (v.length >= 10) n += 15;
    return Math.min(100, n);
  };
  if (meterInp && meter) {
    const paint = () => {
      const v = meterInp.value || '';
      const n = scorePass(v);
      meter.hidden = v.length === 0;
      const bar = meter.querySelector('i');
      if (bar) bar.style.width = n + '%';
      meter.classList.toggle('is-ok', n >= 70);
      meter.classList.toggle('is-mid', n >= 40 && n < 70);
      if (meterLab) {
        meterLab.textContent = n < 40 ? ('ضعیف · ' + n + '٪') : (n < 70 ? ('متوسط · ' + n + '٪') : ('قوی · ' + n + '٪'));
      }
    };
    meterInp.addEventListener('input', paint);
    paint();
  }
  const matchInp = document.querySelector('[data-pass-match]');
  const mismatch = document.querySelector('[data-pass-mismatch]');
  if (matchInp && mismatch) {
    const src = document.querySelector(matchInp.getAttribute('data-pass-match') || '#regPass');
    const check = () => {
      const a = (src && src.value) || '';
      const b = matchInp.value || '';
      mismatch.hidden = !(b && a !== b);
    };
    matchInp.addEventListener('input', check);
    if (src) src.addEventListener('input', check);
  }
  const paySel = document.querySelector('[data-pay-type]');
  if (paySel) {
    const syncPay = () => {
      const v = paySel.value;
      document.querySelectorAll('[data-pay-extra]').forEach((el) => {
        const keys = (el.getAttribute('data-pay-extra') || '').split(/[,\s]+/).filter(Boolean);
        el.hidden = keys.indexOf(v) === -1;
      });
    };
    paySel.addEventListener('change', syncPay);
    syncPay();
  }

  const addRecent = document.querySelector('[data-recent-add]');
  const recentBox = document.querySelector('[data-recent-rows]');
  const recentTpl = document.getElementById('recentRowTpl');
  if (addRecent && recentBox && recentTpl) {
    addRecent.addEventListener('click', () => {
      recentBox.appendChild(recentTpl.content.cloneNode(true));
    });
  }

  const badgeFind = document.querySelector('[data-badge-find]');
  const badgeList = document.querySelector('[data-badge-list]');
  const badgeVal = document.querySelector('[data-badge-val]');
  const badgeHint = document.querySelector('[data-badge-hint]');
  const paintBadgeHint = () => {
    if (!badgeHint || !badgeList) return;
    const on = badgeList.querySelector('.badge-find-row.is-on');
    badgeHint.textContent = on ? (on.getAttribute('data-hint') || on.getAttribute('data-name') || '') : 'جستجو کن و یک بج را از لیست بزن.';
  };
  if (badgeFind && badgeList && badgeVal) {
    const rows = [...badgeList.querySelectorAll('.badge-find-row')];
    badgeFind.addEventListener('input', () => {
      const q = badgeFind.value.trim().toLowerCase();
      rows.forEach((row) => {
        const hay = ((row.getAttribute('data-name') || '') + ' ' + (row.getAttribute('data-hint') || '')).toLowerCase();
        row.hidden = q !== '' && !hay.includes(q);
      });
    });
    badgeList.addEventListener('click', (e) => {
      const row = e.target.closest('.badge-find-row');
      if (!row) return;
      rows.forEach((r) => r.classList.toggle('is-on', r === row));
      badgeVal.value = row.getAttribute('data-id') || '';
      paintBadgeHint();
    });
    paintBadgeHint();
  }

  const medalPop = document.getElementById('medalPop');
  document.querySelectorAll('[data-medal]').forEach((btn) => {
    btn.addEventListener('click', () => {
      let data = {};
      try { data = JSON.parse(btn.getAttribute('data-medal') || '{}'); } catch (err) { data = {}; }
      if (!medalPop) return;
      const coin = medalPop.querySelector('[data-pop-coin]');
      const name = medalPop.querySelector('[data-pop-name]');
      const hint = medalPop.querySelector('[data-pop-hint]');
      const meta = medalPop.querySelector('[data-pop-meta]');
      medalPop.className = 'medal-pop ' + (data.cls || 'bronze');
      if (coin) {
        coin.className = 'medal-coin ' + (data.cls || '');
        coin.innerHTML = data.image
          ? '<img src="' + data.image + '" alt="">'
          : '<span class="medal-ico">' + (data.icon || '★') + '</span>';
      }
      if (name) name.textContent = data.name || '';
      if (hint) hint.textContent = data.hint || 'توضیحی برای این مدال ثبت نشده.';
      if (meta) meta.textContent = data.meta || '';
      if (typeof medalPop.showModal === 'function') medalPop.showModal();
      else medalPop.setAttribute('open', '');
    });
  });

  const radar = document.getElementById('radarChart');
  if (radar && window.Chart) {
    const labels = JSON.parse(radar.dataset.labels || '[]');
    const values = JSON.parse(radar.dataset.values || '[]');
    new Chart(radar, {
      type: 'radar',
      data: {
        labels,
        datasets: [{
          label: radar.dataset.label || 'نرخ برد (%)',
          data: values,
          borderColor: '#58bdaf',
          backgroundColor: 'rgba(88,189,175,0.22)',
          pointBackgroundColor: '#e0195b',
          pointBorderColor: '#21295a',
          borderWidth: 2
        }]
      },
      options: {
        plugins: { legend: { labels: { color: '#21295a', font: { family: 'IRANSans' } } } },
        scales: {
          r: {
            suggestedMin: 0,
            suggestedMax: 100,
            angleLines: { color: 'rgba(33,41,90,0.15)' },
            grid: { color: 'rgba(33,41,90,0.12)' },
            pointLabels: { color: '#21295a', font: { family: 'IRANSans', size: 12 } },
            ticks: { display: false }
          }
        }
      }
    });
  }
})();
