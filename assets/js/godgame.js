(function () {
  const app = document.getElementById('godApp');
  if (!app) return;
  if (!document.getElementById('godSkin')) {
    const skin = document.createElement('style');
    skin.id = 'godSkin';
    skin.textContent = '#godApp input,#godApp select{-webkit-appearance:none;appearance:none;color-scheme:dark;background:#120c08;color:#f3e6d4;border:1px solid rgba(201,162,39,.22);border-radius:14px;padding:14px 16px;width:100%;font-size:16px;outline:none}#godApp input[type=number]::-webkit-inner-spin-button{-webkit-appearance:none}#godApp .god-setup{padding:18px 16px 20px}#godApp .god-fields{display:grid;grid-template-columns:1fr;gap:14px;margin-bottom:18px}#godApp .god-field{display:flex;flex-direction:column;gap:8px}#godApp .god-lab{color:#c9a227;font-size:12px;font-weight:700}#godApp .god-count{display:grid;grid-template-columns:48px 1fr 48px;gap:8px}#godApp .god-count button{height:52px;border-radius:14px;border:1px solid #c9a227;background:#1a1208;color:#e8c547;font-size:22px;font-weight:800;cursor:pointer}#godApp .god-count input{text-align:center;font-size:22px;font-weight:800;color:#e8c547;height:52px}#godApp .god-scens{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px}#godApp .god-scen{display:flex;flex-direction:column;gap:4px;text-align:right;padding:14px;min-height:72px;border-radius:16px;border:1px solid rgba(201,162,39,.22);background:#140e0a;color:#f3e6d4;cursor:pointer}#godApp .god-scen.is-on{border-color:#e8c547;background:#2a1c12;box-shadow:0 0 0 2px rgba(232,197,71,.28)}#godApp .god-scen.is-on b{color:#e8c547}#godApp .god-lock{width:100%;margin-top:18px}@media (min-width:720px){#godApp .god-fields{grid-template-columns:1.3fr .7fr}#godApp .god-scens{grid-template-columns:repeat(4,1fr)}}';
    document.head.appendChild(skin);
  }

  const NKEY = 'rokad-god-nights-v2';
  const CKEY = 'rokad-god-catalog-v2';
  const SIDE = { mafia: 'مافیا', indie: 'مستقل', citizen: 'شهروند' };

  const boot = window.GOD_BOOT || {};
  if (boot.scenarios && boot.scenarios.length) {
    try { localStorage.setItem(CKEY, JSON.stringify(boot)); } catch (e) {}
  }
  let catalog = boot;
  if (!catalog.scenarios || !catalog.scenarios.length) {
    try { catalog = JSON.parse(localStorage.getItem(CKEY) || 'null') || { scenarios: [], roles: [], people: [] }; } catch (e) {
      catalog = { scenarios: [], roles: [], people: [] };
    }
  }

  const fa = (n) => String(n).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const uid = () => 'n' + Date.now().toString(36) + Math.random().toString(36).slice(2, 5);

  const loadNights = () => {
    try { return JSON.parse(localStorage.getItem(NKEY) || '[]'); } catch (e) { return []; }
  };
  const saveNights = (list) => { try { localStorage.setItem(NKEY, JSON.stringify(list)); } catch (e) {} };
  const getNight = (id) => loadNights().find((n) => n.id === id);
  const putNight = (night) => {
    const list = loadNights().filter((n) => n.id !== night.id);
    list.unshift(night);
    saveNights(list);
  };
  const delNight = (id) => saveNights(loadNights().filter((n) => n.id !== id));

  const rolesOf = (scenId) => catalog.roles.filter((r) => String(r.scenario_id) === String(scenId));
  const roleById = (id) => catalog.roles.find((r) => String(r.id) === String(id));
  const scenById = (id) => (catalog.scenarios || []).find((s) => String(s.id) === String(id));

  const hash = () => (location.hash || '#/').replace(/^#/, '') || '/';
  const go = (h) => { location.hash = h; };

  const online = () => navigator.onLine !== false;

  function banner() {
    const off = !online();
    const empty = !(catalog.scenarios && catalog.scenarios.length);
    if (empty) {
      return '<div class="flash warn">نقش‌ها روی این گوشی نیست. یک‌بار با اینترنت وارد شو تا ذخیره شوند.</div>';
    }
    if (off) return '<div class="flash ok">آفلاین — میز روی همین گوشی است.</div>';
    return '';
  }

  function renderHome() {
    const nights = loadNights();
    let html = banner();
    html += '<p style="color:var(--creamd);line-height:1.9;margin-bottom:14px">سناریو را انتخاب کن، تعداد بازیکن را بگو، فقط نقش‌های همان سناریو را تا سقف تعداد بچین، اسم بنویس و گوشی را یکی‌یکی نشان بده.</p>';
    html += '<p><button type="button" class="btn btn-solid" data-go="#/new">میز جدید</button></p>';
    if (nights.length) {
      html += '<div class="card" style="padding:8px 12px;margin-top:18px"><h3 style="padding:12px">میزهای همین گوشی</h3><table class="data-table"><tbody>';
      nights.forEach((n) => {
        const named = (n.seats || []).filter((s) => s.name).length;
        html += '<tr><td><b>' + esc(n.title) + '</b><br><span style="color:var(--muted);font-size:12px">' + fa(n.count) + ' نفر · ' + fa(named) + ' اسم</span></td><td>';
        html += '<button type="button" class="btn btn-sm" data-go="#/n/' + esc(n.id) + '">باز کردن</button> ';
        html += '<button type="button" class="btn btn-sm btn-solid" data-go="#/n/' + esc(n.id) + '/deal">نمایش</button> ';
        html += '<button type="button" class="btn btn-sm btn-danger" data-del="' + esc(n.id) + '">حذف</button>';
        html += '</td></tr>';
      });
      html += '</tbody></table></div>';
    }
    app.innerHTML = html;
  }

  function renderNew() {
    if (!catalog.scenarios.length) { app.innerHTML = banner(); return; }
    const scenId = app.dataset.scen || String(catalog.scenarios[0].id);
    const count = Math.max(4, Math.min(24, parseInt(app.dataset.count || '10', 10) || 10));
    const qtys = JSON.parse(app.dataset.qtys || '{}');
    const roles = rolesOf(scenId);
    const by = { mafia: [], indie: [], citizen: [] };
    roles.forEach((r) => {
      const s = r.side === 'mafia' || r.side === 'indie' ? r.side : 'citizen';
      by[s].push(r);
    });
    const total = Object.values(qtys).reduce((s, n) => s + (parseInt(n, 10) || 0), 0);
    let html = banner();
    html += '<p><button type="button" class="btn btn-ghost btn-sm" data-go="#/">میزها</button></p>';
    html += '<div class="card god-setup">';
    html += '<div class="god-fields">';
    html += '<div class="god-field"><span class="god-lab">عنوان شب</span>';
    html += '<input id="gTitle" class="god-input" placeholder="مثلاً مافیا پنجشنبه" value="' + esc(app.dataset.title || '') + '" autocomplete="off"></div>';
    html += '<div class="god-field"><span class="god-lab">تعداد بازیکن</span>';
    html += '<div class="god-count">';
    html += '<button type="button" data-count-delta="-1" aria-label="کم">−</button>';
    html += '<input id="gCount" type="number" min="4" max="24" inputmode="numeric" value="' + count + '">';
    html += '<button type="button" data-count-delta="1" aria-label="زیاد">+</button></div></div></div>';
    html += '<span class="god-lab">سناریو</span><div class="god-scens">';
    catalog.scenarios.forEach((s) => {
      const on = String(s.id) === String(scenId) ? ' is-on' : '';
      html += '<button type="button" class="god-scen' + on + '" data-pick-scen="' + s.id + '">';
      html += '<b>' + esc(s.name) + '</b><small>' + esc(s.cat_name || 'مافیا') + '</small></button>';
    });
    html += '</div>';
    const cls = total === count ? ' is-ok' : (total > count ? ' is-over' : '');
    html += '<p class="god-sum' + cls + '" id="godSum">انتخاب‌شده: ' + fa(total) + ' از ' + fa(count) + (total === count ? ' — کامل است.' : (total > count ? ' — بیشتر از تعداد بازیکن است.' : ' — باید به تعداد بازیکن برسد.')) + '</p>';
    [['mafia', 'نقش‌های مافیا'], ['indie', 'نقش‌های مستقل'], ['citizen', 'نقش‌های شهروند']].forEach(([side, lab]) => {
      if (!by[side].length) return;
      html += '<h3 class="god-side-title">' + lab + '</h3><div class="role-picks">';
      by[side].forEach((r) => {
        const v = parseInt(qtys[r.id] || '0', 10) || 0;
        html += '<div class="role-pick' + (v ? ' is-on' : '') + '"><div><b>' + esc(r.name) + '</b><small>' + SIDE[side] + '</small></div>';
        html += '<div class="role-step"><button type="button" data-minus="' + r.id + '">−</button>';
        html += '<input data-qty="' + r.id + '" type="number" min="0" max="24" value="' + v + '">';
        html += '<button type="button" data-plus="' + r.id + '">+</button></div></div>';
      });
      html += '</div>';
    });
    html += '<button type="button" class="btn btn-solid god-lock" id="gLock">قفل نقش‌ها و لیست اسم</button></div>';
    app.innerHTML = html;
    app.dataset.scen = scenId;
    app.dataset.count = String(count);
    app.dataset.qtys = JSON.stringify(qtys);
  }

  function readQtys() {
    const q = {};
    app.querySelectorAll('[data-qty]').forEach((inp) => {
      const n = Math.max(0, parseInt(inp.value, 10) || 0);
      if (n) q[inp.getAttribute('data-qty')] = n;
    });
    return q;
  }

  function renderNight(id) {
    const n = getNight(id);
    if (!n) { go('/'); return; }
    const pool = (n.roles || []).map((x) => ({ ...roleById(x.id), qty: x.qty })).filter((r) => r && r.name);
    let html = banner();
    html += '<p><button type="button" class="btn btn-ghost btn-sm" data-go="#/">میزها</button></p>';
    html += '<div class="card" style="padding:16px;margin-bottom:14px"><p style="color:var(--creamd);line-height:1.8">' + esc(n.title) + ' · ' + fa(n.count) + ' نفر · ';
    pool.forEach((p) => { html += '<span class="pill">' + esc(p.name) + ' × ' + fa(p.qty) + '</span> '; });
    html += '</p></div>';
    html += '<div class="card" style="padding:16px"><p style="color:var(--muted);font-size:13px;line-height:1.8;margin-bottom:12px">اسم را بنویس و جلوی همان اسم نقش را بزن. بیشتر از سهم یک نقش نمی‌شود.</p>';
    const names = catalog.people || [];
    html += '<datalist id="gNames">';
    names.forEach((nm) => { html += '<option value="' + esc(nm) + '">'; });
    html += '</datalist><div class="seat-list">';
    (n.seats || []).forEach((s) => {
      html += '<div class="seat-row"><span class="seat-no">' + fa(s.no) + '</span>';
      html += '<input data-name="' + s.no + '" value="' + esc(s.name || '') + '" placeholder="اسم بازیکن" list="gNames" autocomplete="off">';
      html += '<select data-role="' + s.no + '"><option value="">نقش…</option>';
      pool.forEach((p) => {
        html += '<option value="' + p.id + '"' + (String(s.roleId) === String(p.id) ? ' selected' : '') + '>' + esc(p.name) + ' (' + SIDE[p.side] + ' ×' + fa(p.qty) + ')</option>';
      });
      html += '</select></div>';
    });
    html += '</div><div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px">';
    html += '<button type="button" class="btn" id="gRand">پخش تصادفی نقش</button>';
    html += '<button type="button" class="btn btn-solid" id="gSaveSeats">ثبت اسم‌ها و نقش‌ها</button>';
    html += '<button type="button" class="btn btn-solid" data-go="#/n/' + esc(id) + '/deal">نمایش به بازیکن‌ها</button>';
    html += '</div></div>';
    app.innerHTML = html;
  }

  function renderDeal(id) {
    const n = getNight(id);
    if (!n) { go('/'); return; }
    const seats = n.seats || [];
    let html = banner();
    html += '<p><button type="button" class="btn btn-ghost btn-sm" data-go="#/n/' + esc(id) + '">برگشت به لیست</button></p>';
    html += '<div class="deal-board" id="dealBoard">';
    seats.forEach((s, i) => {
      const r = roleById(s.roleId) || {};
      html += '<article class="deal-slide' + (i === 0 ? ' is-on' : '') + '" data-slide="' + i + '">';
      html += '<div class="deal-kicker">' + fa(i + 1) + ' از ' + fa(seats.length) + '</div>';
      html += '<h2 class="deal-name">' + esc(s.name || 'بدون اسم') + '</h2>';
      html += '<p class="deal-hint">گوشی را فقط به این بازیکن نشان بده، بعد کارت را بزن.</p>';
      html += '<button type="button" class="deal-card" data-reveal><span class="deal-back">بزن تا نقش «' + esc(s.name) + '» بیاید</span>';
      html += '<span class="deal-front"><span class="deal-sym">' + esc(r.symbol || '♠') + '</span><strong>' + esc(r.name || '—') + '</strong><em>' + esc(SIDE[r.side] || '') + '</em></span></button>';
      html += '</article>';
    });
    html += '<div class="deal-nav"><button type="button" class="btn" data-deal-prev>قبلی (پنهان)</button><button type="button" class="btn btn-solid" data-deal-next>بعدی</button></div></div>';
    app.innerHTML = html;
    const box = document.getElementById('dealBoard');
    const slides = [...box.querySelectorAll('.deal-slide')];
    let i = 0;
    const show = (k) => {
      i = Math.max(0, Math.min(slides.length - 1, k));
      slides.forEach((s, idx) => {
        s.classList.toggle('is-on', idx === i);
        if (idx !== i) s.querySelector('.deal-card')?.classList.remove('is-open');
      });
    };
    box.addEventListener('click', (e) => {
      if (e.target.closest('[data-reveal]')) { e.target.closest('[data-reveal]').classList.toggle('is-open'); return; }
      if (e.target.closest('[data-deal-next]')) show(i + 1);
      if (e.target.closest('[data-deal-prev]')) show(i - 1);
    });
  }

  function route() {
    const h = hash();
    const deal = h.match(/^\/n\/([^/]+)\/deal$/);
    const night = h.match(/^\/n\/([^/]+)$/);
    if (deal) return renderDeal(decodeURIComponent(deal[1]));
    if (night) return renderNight(decodeURIComponent(night[1]));
    if (h === '/new') return renderNew();
    renderHome();
  }

  app.addEventListener('click', (e) => {
    const goBtn = e.target.closest('[data-go]');
    if (goBtn) { go(goBtn.getAttribute('data-go')); return; }
    const del = e.target.closest('[data-del]');
    if (del) {
      if (confirm('این میز از روی گوشی پاک شود؟')) { delNight(del.getAttribute('data-del')); route(); }
      return;
    }
    const pick = e.target.closest('[data-pick-scen]');
    if (pick) {
      app.dataset.title = document.getElementById('gTitle')?.value || '';
      app.dataset.scen = pick.getAttribute('data-pick-scen');
      app.dataset.qtys = '{}';
      renderNew();
      return;
    }
    const deltaBtn = e.target.closest('[data-count-delta]');
    if (deltaBtn) {
      const now = Math.max(4, Math.min(24, parseInt(document.getElementById('gCount')?.value || '10', 10) || 10));
      const next = Math.max(4, Math.min(24, now + (parseInt(deltaBtn.getAttribute('data-count-delta'), 10) || 0)));
      app.dataset.title = document.getElementById('gTitle')?.value || '';
      app.dataset.count = String(next);
      renderNew();
      return;
    }
    const plus = e.target.closest('[data-plus]');
    const minus = e.target.closest('[data-minus]');
    if (plus || minus) {
      const id = (plus || minus).getAttribute(plus ? 'data-plus' : 'data-minus');
      const inp = app.querySelector('[data-qty="' + id + '"]');
      const cap = Math.max(4, Math.min(24, parseInt(document.getElementById('gCount')?.value || '10', 10) || 10));
      const qtys = readQtys();
      const sum = Object.values(qtys).reduce((s, n) => s + n, 0);
      let v = parseInt(inp.value, 10) || 0;
      if (plus && sum < cap) v += 1;
      if (minus) v = Math.max(0, v - 1);
      inp.value = v;
      qtys[id] = v;
      app.dataset.qtys = JSON.stringify(qtys);
      app.dataset.count = String(cap);
      app.dataset.title = document.getElementById('gTitle')?.value || '';
      renderNew();
      return;
    }
    if (e.target.id === 'gLock') {
      const scenId = app.dataset.scen || document.getElementById('gScen')?.value;
      const count = Math.max(4, Math.min(24, parseInt(document.getElementById('gCount').value, 10) || 0));
      const qtys = readQtys();
      const total = Object.values(qtys).reduce((s, n) => s + n, 0);
      if (total !== count) { alert('جمع نقش‌ها باید دقیقاً برابر تعداد بازیکن باشد و بیشتر نشود.'); return; }
      const allowed = new Set(rolesOf(scenId).map((r) => String(r.id)));
      for (const id of Object.keys(qtys)) {
        if (!allowed.has(String(id))) { alert('فقط نقش‌های همین سناریو را می‌شود انتخاب کرد.'); return; }
      }
      let title = (document.getElementById('gTitle').value || '').trim();
      const scen = scenById(scenId);
      if (!title) title = (scen ? scen.name : 'میز') + ' · ' + count + ' نفره';
      const roles = Object.keys(qtys).map((id) => ({ id: Number(id), qty: qtys[id] }));
      const seats = [];
      for (let i = 1; i <= count; i++) seats.push({ no: i, name: '', roleId: '' });
      const night = { id: uid(), title, scenId, catId: scen ? scen.category_id : 0, count, roles, seats, created: Date.now() };
      putNight(night);
      go('#/n/' + night.id);
      return;
    }
    if (e.target.id === 'gRand') {
      const id = (hash().match(/^\/n\/([^/]+)$/) || [])[1];
      const n = getNight(id);
      if (!n) return;
      const bag = [];
      (n.roles || []).forEach((r) => { for (let i = 0; i < r.qty; i++) bag.push(String(r.id)); });
      for (let i = bag.length - 1; i > 0; i--) { const j = Math.floor(Math.random() * (i + 1)); [bag[i], bag[j]] = [bag[j], bag[i]]; }
      app.querySelectorAll('[data-role]').forEach((sel, idx) => { if (bag[idx]) sel.value = bag[idx]; });
      return;
    }
    if (e.target.id === 'gSaveSeats') {
      const id = (hash().match(/^\/n\/([^/]+)$/) || [])[1];
      const n = getNight(id);
      if (!n) return;
      const used = {};
      const max = {};
      (n.roles || []).forEach((r) => { max[String(r.id)] = r.qty; });
      const seats = [];
      let err = '';
      for (let i = 1; i <= n.count; i++) {
        const name = (app.querySelector('[data-name="' + i + '"]')?.value || '').trim();
        const roleId = app.querySelector('[data-role="' + i + '"]')?.value || '';
        if (!name) { err = 'اسم صندلی ' + fa(i) + ' خالی است.'; break; }
        if (!roleId || max[roleId] == null) { err = 'نقش «' + name + '» از نقش‌های همین میز نیست.'; break; }
        used[roleId] = (used[roleId] || 0) + 1;
        if (used[roleId] > max[roleId]) { err = 'این نقش بیشتر از سهمش استفاده شده.'; break; }
        seats.push({ no: i, name, roleId });
      }
      if (!err) {
        for (const rid of Object.keys(max)) {
          if ((used[rid] || 0) !== max[rid]) { err = 'هر نقش باید دقیقاً به اندازه‌ی سهمش پخش شود.'; break; }
        }
      }
      if (err) { alert(err); return; }
      n.seats = seats;
      putNight(n);
      alert('لیست اسم و نقش روی همین گوشی ثبت شد.');
      route();
    }
  });

  app.addEventListener('change', (e) => {
    if (e.target.id === 'gScen') {
      app.dataset.scen = e.target.value;
      app.dataset.qtys = '{}';
      renderNew();
    }
    if (e.target.id === 'gCount') {
      app.dataset.count = e.target.value;
      renderNew();
    }
  });

  window.addEventListener('hashchange', route);
  window.addEventListener('online', route);
  window.addEventListener('offline', route);
  route();
})();
