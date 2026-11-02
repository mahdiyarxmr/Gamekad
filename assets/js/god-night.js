(function () {
  const app = document.getElementById('godApp');
  if (!app) return;
  const home = document.getElementById('godHome');
  const panel = document.getElementById('godNew');
  const work = document.getElementById('godWork');
  const rolesBox = document.getElementById('godRoles');

  const NKEY = 'rokad-god-nights-v2';
  const CKEY = 'rokad-god-catalog-v2';
  const SIDE = { mafia: 'مافیا', indie: 'مستقل', citizen: 'شهروند' };
  const INP = 'width:56px;text-align:center;background:#fff;border:2px solid rgba(33,41,90,.18);color:#292827;border-radius:10px;padding:10px 0;font-size:16px;-webkit-appearance:none;appearance:none;color-scheme:light;font-weight:700';
  const BADGE_HREF = app.getAttribute('data-badge') || '/admin/badges';
  const LB_HREF = app.getAttribute('data-lb') || '/admin/leaderboard';

  const boot = window.GOD_BOOT || {};
  if (boot.scenarios && boot.scenarios.length) {
    try { localStorage.setItem(CKEY, JSON.stringify(boot)); } catch (e) {}
  }
  let catalog = boot;
  if (!catalog.scenarios || !catalog.scenarios.length) {
    try { catalog = JSON.parse(localStorage.getItem(CKEY) || 'null') || { scenarios: [], roles: [], people: [], nights: {} }; } catch (e) {
      catalog = { scenarios: [], roles: [], people: [], nights: {} };
    }
  }

  const fa = (n) => String(n).replace(/\d/g, (d) => '۰۱۲۳۴۵۶۷۸۹'[d]);
  const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const uid = () => 'n' + Date.now().toString(36) + Math.random().toString(36).slice(2, 5);
  const loadNights = () => { try { return JSON.parse(localStorage.getItem(NKEY) || '[]'); } catch (e) { return []; } };
  const saveNights = (list) => { try { localStorage.setItem(NKEY, JSON.stringify(list)); } catch (e) {} };
  const getNight = (id) => loadNights().find((n) => n.id === id);
  const putNight = (night) => { saveNights([night].concat(loadNights().filter((n) => n.id !== night.id))); };
  const delNight = (id) => saveNights(loadNights().filter((n) => n.id !== id));
  const rolesOf = (scenId) => catalog.roles.filter((r) => String(r.scenario_id) === String(scenId));
  const roleById = (id) => catalog.roles.find((r) => String(r.id) === String(id));
  const scenById = (id) => (catalog.scenarios || []).find((s) => String(s.id) === String(id));
  function peopleRows() {
    return (catalog.people || []).map((x) => {
      if (typeof x === 'string') return { name: x, gender: '' };
      return { name: String(x.display_name || x.name || ''), gender: String(x.gender || '') };
    }).filter((p) => p.name);
  }
  function sessionById(id) {
    return (catalog.sessions || []).find((s) => String(s.id) === String(id)) || null;
  }
  function genderGate(audience) {
    if (audience === 'girls') return 'girl';
    if (audience === 'boys') return 'boy';
    return 'all';
  }
  function playersOfSession(sess) {
    const gate = genderGate(sess && sess.audience);
    return ((sess && sess.players) || []).filter((p) => {
      if (gate === 'boy') return p.gender !== 'girl';
      if (gate === 'girl') return p.gender !== 'boy';
      return true;
    });
  }
  function fillSeatsFromPlayers(count, players) {
    const seats = [];
    for (let i = 1; i <= count; i++) {
      const p = players[i - 1];
      seats.push({ no: i, name: p ? String(p.name || '') : '', roleId: '', gender: p ? String(p.gender || '') : '' });
    }
    return seats;
  }
  function swapSeats(n, aNo, bNo) {
    if (!n || String(aNo) === String(bNo)) return;
    const sa = (n.seats || []).find((s) => String(s.no) === String(aNo));
    const sb = (n.seats || []).find((s) => String(s.no) === String(bNo));
    if (!sa || !sb) return;
    const tmp = { name: sa.name, roleId: sa.roleId, gender: sa.gender };
    sa.name = sb.name; sa.roleId = sb.roleId; sa.gender = sb.gender;
    sb.name = tmp.name; sb.roleId = tmp.roleId; sb.gender = tmp.gender;
    const h = ensureHost(n);
    const da = isDead(n, aNo);
    const db = isDead(n, bNo);
    h.dead = (h.dead || []).filter((x) => String(x) !== String(aNo) && String(x) !== String(bNo));
    if (da) h.dead.push(Number(bNo));
    if (db) h.dead.push(Number(aNo));
    const seen = n.dealSeen || [];
    const saSeen = seen.map(String).includes(String(aNo));
    const sbSeen = seen.map(String).includes(String(bNo));
    n.dealSeen = seen.filter((x) => String(x) !== String(aNo) && String(x) !== String(bNo));
    if (saSeen) n.dealSeen.push(Number(bNo));
    if (sbSeen) n.dealSeen.push(Number(aNo));
  }
  function seatGenderFilter() {
    const on = app.querySelector('[data-seat-g].is-on');
    return on ? on.getAttribute('data-seat-g') : 'all';
  }
  function usedSeatNames(exceptNo) {
    const used = new Set();
    app.querySelectorAll('[data-name]').forEach((inp) => {
      if (exceptNo != null && String(inp.getAttribute('data-name')) === String(exceptNo)) return;
      const v = (inp.value || '').trim();
      if (v) used.add(v);
    });
    return used;
  }
  function hideNameSuggest() {
    app.querySelectorAll('.name-suggest').forEach((el) => { el.hidden = true; el.innerHTML = ''; });
  }
  function fillNameSuggest(inp) {
    const box = inp.parentElement && inp.parentElement.querySelector('.name-suggest');
    if (!box) return;
    const g = seatGenderFilter();
    const used = usedSeatNames(inp.getAttribute('data-name'));
    const q = (inp.value || '').trim();
    let rows = peopleRows();
    if (g === 'boy' || g === 'girl') rows = rows.filter((p) => p.gender === g);
    rows = rows.filter((p) => !used.has(p.name));
    if (q) {
      rows = rows.filter((p) => p.name.indexOf(q) !== -1);
    }
    rows = rows.slice(0, 10);
    if (!rows.length) {
      box.innerHTML = '<p class="name-suggest-empty">اکانت در این فیلتر نیست — اسم مهمان را بنویس</p>';
      box.hidden = false;
      return;
    }
    box.innerHTML = rows.map((p) => {
      const tag = p.gender === 'girl' ? 'دختر' : (p.gender === 'boy' ? 'پسر' : 'اکانت');
      return '<button type="button" class="name-suggest-item" data-pick-name="' + esc(p.name) + '"><b>' + esc(p.name) + '</b><small>' + tag + '</small></button>';
    }).join('');
    box.hidden = false;
  }
  const hash = () => (location.hash || '#/').replace(/^#/, '') || '/';
  const go = (h) => { location.hash = h; };
  const online = () => navigator.onLine !== false;

  let tick = null;
  function stopTick() {
    if (tick) { clearInterval(tick); tick = null; }
  }

  function ensureHost(n) {
    if (!n.host) {
      n.host = { nightNum: 0, dead: [], log: [], usedOnce: {}, last: {}, phase: 'lobby', steps: [], step: 0, remain: 0, paused: false };
    }
    if (!n.host.dead) n.host.dead = [];
    if (!n.host.log) n.host.log = [];
    if (!n.host.usedOnce) n.host.usedOnce = {};
    if (!n.host.last) n.host.last = {};
    if (!n.host.kicked) n.host.kicked = [];
    if (!n.host.nominated) n.host.nominated = [];
    if (!n.host.warns) n.host.warns = {};
    if (n.host.introDone == null) n.host.introDone = (n.host.nightNum || 0) > 0;
    if (n.host.talkI == null) n.host.talkI = 0;
    if (n.host.hideRoles == null) n.host.hideRoles = true;
    ensureClocks(n.host);
    return n.host;
  }

  const CLK = {
    turn: { total: 60, lab: 'تایم turn' },
    defend: { total: 60, lab: 'تایم دفاع' },
    night: { total: 30, lab: 'تایم شب' }
  };
  function ensureClocks(h) {
    if (!h.clocks) h.clocks = {};
    ['turn', 'defend', 'night'].forEach((k) => {
      if (!h.clocks[k]) {
        h.clocks[k] = { remain: CLK[k].total, total: CLK[k].total, running: false, paused: false };
      }
      if (!h.clocks[k].total) h.clocks[k].total = CLK[k].total;
      if (h.clocks[k].remain == null) h.clocks[k].remain = h.clocks[k].total;
    });
    return h.clocks;
  }
  function armClock(h, key, seconds) {
    ensureClocks(h);
    const c = h.clocks[key];
    const t = seconds || c.total || CLK[key].total;
    c.total = t;
    c.remain = t;
    c.running = false;
    c.paused = false;
  }
  function fmtClock(sec) {
    sec = Math.max(0, parseInt(sec, 10) || 0);
    const m = Math.floor(sec / 60);
    const s = sec % 60;
    return fa(m) + ':' + fa(String(s).padStart(2, '0'));
  }
  function clockSlots(phase) {
    if (phase === 'defend') return ['night', 'defend', 'turn'];
    if (phase === 'wake' || phase === 'pick' || phase === 'intro' || phase === 'note') return ['turn', 'night', 'defend'];
    return ['night', 'turn', 'defend'];
  }
  function talkSeat(n) {
    const live = living(n);
    if (!live.length) return null;
    const i = ((ensureHost(n).talkI || 0) % live.length + live.length) % live.length;
    return live[i];
  }
  function clocksHtml(n) {
    const h = ensureHost(n);
    const phase = h.phase || 'lobby';
    const slots = clockSlots(phase);
    const main = slots[1];
    const day = phase === 'lobby' || !phase;
    const speaker = talkSeat(n);
    let html = '<div class="host-clocks">';
    slots.forEach((k) => {
      const c = h.clocks[k];
      const isMain = k === main;
      html += '<div class="host-clock' + (isMain ? ' is-main' : ' is-side') + (c.remain <= 0 ? ' is-zero' : '') + (c.running && !c.paused ? ' is-run' : '') + '" data-clock="' + k + '">';
      html += '<div class="host-clock-lab">' + CLK[k].lab + '</div>';
      if (k === 'turn' && day && speaker) {
        html += '<div class="host-clock-who" id="clk-turn-who">نوبت: ' + esc(seatName(speaker)) + '</div>';
      }
      html += '<div class="host-clock-num' + (c.remain <= 0 ? ' is-zero' : '') + '" id="clk-' + k + '">' + fmtClock(c.remain) + '</div>';
      html += '<div class="host-clock-btns">';
      html += '<button type="button" class="btn btn-sm clk-ico" data-clk-start="' + k + '" aria-label="شروع"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M8 5.2v13.6L19.2 12 8 5.2z"/></svg></button>';
      html += '<button type="button" class="btn btn-sm clk-ico" data-clk-reset="' + k + '" aria-label="ریست"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M12 6V2.8L7.6 7.2 12 11.6V8.2a4.8 4.8 0 1 1-4.8 4.8H4.4A7.6 7.6 0 1 0 12 6z"/></svg></button>';
      html += '<button type="button" class="btn btn-sm clk-ico" data-clk-pause="' + k + '" aria-label="توقف"><svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="currentColor" d="M6.4 5h4v14h-4zm7.2 0h4v14h-4z"/></svg></button>';
      html += '</div>';
      if (k === 'turn' && day && speaker) {
        html += '<button type="button" class="btn btn-sm btn-solid host-talk-next" data-talk-next>نفر بعدی</button>';
      }
      html += '</div>';
    });
    html += '</div>';
    return html;
  }
  function paintClockNums(h) {
    ['turn', 'defend', 'night'].forEach((k) => {
      const c = (h.clocks || {})[k];
      if (!c) return;
      const el = document.getElementById('clk-' + k);
      if (el) {
        el.textContent = fmtClock(c.remain);
        el.classList.toggle('is-zero', c.remain <= 0);
      }
      const box = document.querySelector('.host-clock[data-clock="' + k + '"]');
      if (box) {
        box.classList.toggle('is-zero', c.remain <= 0);
        box.classList.toggle('is-run', !!(c.running && !c.paused));
      }
    });
  }

  function seatRole(s) {
    return roleById(s.roleId) || {};
  }
  function seatName(s) {
    return s.name || ('صندلی ' + s.no);
  }
  function isDead(n, no) {
    return (n.host.dead || []).map(String).includes(String(no));
  }
  function isKicked(n, no) {
    return (n.host.kicked || []).map(String).includes(String(no));
  }
  function living(n) {
    return (n.seats || []).filter((s) => !isDead(n, s.no) && !isKicked(n, s.no));
  }
  function warnOf(n, no) {
    return Math.max(0, parseInt((n.host.warns || {})[String(no)] || '0', 10) || 0);
  }
  function wonSeat(n, s) {
    const h = n.host || {};
    return (seatRole(s).side === 'mafia') === (h.winner === 'mafia');
  }
  function defaultRank(n) {
    const seats = (n.seats || []).slice();
    const win = seats.filter((s) => wonSeat(n, s));
    const lose = seats.filter((s) => !wonSeat(n, s));
    return win.concat(lose).map((s) => s.no);
  }
  function rankList(n) {
    const h = ensureHost(n);
    const have = new Set((n.seats || []).map((s) => String(s.no)));
    if (!h.rank || !h.rank.length) h.rank = defaultRank(n);
    h.rank = h.rank.filter((no) => have.has(String(no)));
    (n.seats || []).forEach((s) => {
      if (!h.rank.map(String).includes(String(s.no))) h.rank.push(s.no);
    });
    return h.rank;
  }
  function remapSeatNos(n, map) {
    const conv = (arr) => (arr || []).map((x) => (map[String(x)] != null ? map[String(x)] : Number(x))).filter((x) => x > 0);
    const h = ensureHost(n);
    h.dead = conv(h.dead);
    h.kicked = conv(h.kicked);
    h.nominated = conv(h.nominated);
    if (h.rank && h.rank.length) h.rank = conv(h.rank);
    if (h.vote2pick && map[String(h.vote2pick)]) h.vote2pick = map[String(h.vote2pick)];
    const w = {};
    Object.keys(h.warns || {}).forEach((k) => {
      const nk = map[String(k)] != null ? map[String(k)] : k;
      w[String(nk)] = h.warns[k];
    });
    h.warns = w;
    n.dealSeen = conv(n.dealSeen);
    if (h.last) {
      ['shot', 'save', 'sniper'].forEach((k) => {
        if (h.last[k] && map[String(h.last[k])]) h.last[k] = map[String(h.last[k])];
      });
    }
  }
  function seatsWithRoles(n, names, withDead) {
    const set = new Set((names || []).map(String));
    const pool = withDead ? (n.seats || []) : living(n);
    return pool.filter((s) => set.has(String(seatRole(s).name || '')));
  }
  function winnerFa(w) {
    return w === 'mafia' ? 'مافیا' : 'شهر';
  }
  function gameEnd(n) {
    const live = living(n);
    if (!(n.seats || []).length) return null;
    const h = n.host || {};
    const started = (h.introDone && (h.nightNum || 0) > 1) || (h.dead || []).length > 0;
    const mafia = live.filter((s) => seatRole(s).side === 'mafia');
    const others = live.filter((s) => seatRole(s).side !== 'mafia');
    if (!mafia.length && started) return { winner: 'citizen', why: 'هیچ مافیایی در بازی نماند.' };
    if (!started) return null;
    if (mafia.length >= others.length) {
      return { winner: 'mafia', why: 'تعداد مافیا با شهروندها برابر یا بیشتر شد.' };
    }
    if (mafia.length === 1 && others.length === 2) {
      return { winner: null, hand: true, why: 'دو شهروند و یک مافیا مانده — دست بده است.' };
    }
    return null;
  }
  function nomSeats(n) {
    const set = new Set((ensureHost(n).nominated || []).map(String));
    return living(n).filter((s) => set.has(String(s.no))).sort((a, b) => a.no - b.no);
  }
  function applyKill(n, no, why) {
    const h = ensureHost(n);
    const s = (n.seats || []).find((x) => String(x.no) === String(no));
    if (!s || isDead(n, no)) return s;
    h.dead.push(s.no);
    h.nominated = (h.nominated || []).filter((x) => String(x) !== String(no));
    h.log.unshift({ night: h.nightNum || 0, msgs: [why || (seatName(s) + ' kill شد.')] });
    const end = gameEnd(n);
    if (end && end.winner) {
      h.winner = end.winner;
      h.endWhy = end.why;
      h.phase = 'ended';
    } else if (end && end.hand) {
      h.phase = 'hand';
      h.handFrom = 0;
      h.handTo = 0;
    }
    return s;
  }
  function closeAsk() {
    const el = document.getElementById('hostAsk');
    if (!el) return;
    el.classList.remove('is-on');
    window.setTimeout(() => { if (el.parentNode) el.parentNode.removeChild(el); }, 240);
  }
  function openAsk(opts) {
    closeAsk();
    app.querySelectorAll('.host-menu').forEach((m) => { m.hidden = true; });
    const wrap = document.createElement('div');
    wrap.id = 'hostAsk';
    wrap.className = 'host-ask';
    wrap.innerHTML = '<div class="host-ask-card">'
      + (opts.kicker ? '<p class="host-ask-kicker">' + esc(opts.kicker) + '</p>' : '')
      + '<h3>' + esc(opts.title || '') + '</h3>'
      + (opts.body ? '<p>' + esc(opts.body) + '</p>' : '')
      + '<div class="host-ask-actions">'
      + '<button type="button" class="btn" data-ask-no>' + esc(opts.no || 'خیر') + '</button>'
      + '<button type="button" class="btn ' + (opts.danger ? 'btn-danger' : 'btn-solid') + '" data-ask-yes>' + esc(opts.yes || 'بله') + '</button>'
      + '</div></div>';
    app.appendChild(wrap);
    window.requestAnimationFrame(() => { wrap.classList.add('is-on'); });
    wrap.querySelector('[data-ask-no]').addEventListener('click', () => closeAsk());
    wrap.querySelector('[data-ask-yes]').addEventListener('click', () => {
      closeAsk();
      if (opts.onYes) opts.onYes();
    });
    wrap.addEventListener('click', (e) => { if (e.target === wrap) closeAsk(); });
  }

  const HARD_NIGHT = ['classic', 'last-move', 'nato', 'execution', 'farewell', 'mist-village'];
  const EYE_SVG = '<svg viewBox="0 0 24 24" width="22" height="22" aria-hidden="true"><g class="eye-shut-g"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M4 13.5c2.4-1.8 4.9-2.7 8-2.7s5.6.9 8 2.7"/><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M8.2 15.4c.3.5.6.8 1 .8M12 16.2v.8M15.8 15.4c-.3.5-.6.8-1 .8"/></g><g class="eye-open-g"><path fill="currentColor" d="M12 5.2c5.1 0 9 4.1 10.2 6.1C21 13.4 17.1 18 12 18S3 13.4 1.8 11.3C3 9.3 6.9 5.2 12 5.2zm0 3.1a3.7 3.7 0 1 0 0 7.4 3.7 3.7 0 0 0 0-7.4zm0 2a1.7 1.7 0 1 1 0 3.4 1.7 1.7 0 0 1 0-3.4z"/></g></svg>';
  function hasHardNight(n) {
    const s = scenById(n.scenId) || {};
    const slug = String(s.slug || '');
    if (catalog.nights && ((catalog.nights[slug] && catalog.nights[slug].length) || (catalog.nights[n.scenId] && catalog.nights[n.scenId].length))) return true;
    return HARD_NIGHT.indexOf(slug) !== -1;
  }
  function nightScript(n) {
    const s = scenById(n.scenId) || {};
    const slug = s.slug || '';
    if (hasHardNight(n)) {
      const fromCat = (catalog.nights && (catalog.nights[slug] || catalog.nights[n.scenId])) || null;
      if (fromCat && fromCat.length) return fromCat;
      return [
        { id: 'mafia', kind: 'shot', title: 'تیم مافیا', wake: 'تیم مافیا را بیدار کن.', hint: 'دستور شات شب.', seconds: 45, pickLabel: 'مافیا چه کسی را زد؟', roles: [] },
        { id: 'doc', kind: 'save', title: 'نجات', wake: 'نقش نجات را بیدار کن.', hint: 'دکتر / پزشک / زره‌ساز / فرشته.', seconds: 30, pickLabel: 'چه کسی سیو گرفت؟', roles: ['دکتر', 'پزشک', 'زره‌ساز', 'فرشته'] },
        { id: 'cop', kind: 'inquire', title: 'استعلام', wake: 'نقش استعلام را بیدار کن.', hint: '', seconds: 25, pickLabel: 'استعلام چه کسی؟', roles: ['کارآگاه', 'راهنما', 'کنشگر'] },
        { id: 'snip', kind: 'sniper', title: 'اسنایپر', wake: 'تک‌تیرانداز را بیدار کن.', hint: 'اگر مافیا بزند نایس‌شات است.', seconds: 25, pickLabel: 'به چه کسی شلیک شد؟', roles: ['تک‌تیرانداز', 'اسنایپر', 'حرفه‌ای'], once: true },
      ];
    }
    const seen = {};
    const rows = [];
    (n.seats || []).forEach((seat) => {
      const r = seatRole(seat);
      const name = String(r.name || '');
      if (!name || seen[name]) return;
      const wake = r.night_wake === 1 || r.night_wake === true || r.night_wake === '1';
      if (!wake) return;
      seen[name] = true;
      rows.push({ name: name, side: r.side, no: seat.no });
    });
    rows.sort((a, b) => {
      const sa = a.side === 'mafia' ? 0 : 1;
      const sb = b.side === 'mafia' ? 0 : 1;
      if (sa !== sb) return sa - sb;
      return a.no - b.no;
    });
    return rows.map((w) => ({
      id: 'wake-' + w.name,
      kind: 'announce',
      title: w.name,
      wake: 'نقش «' + w.name + '» را بیدار کن.',
      hint: 'کار شب را بگو، بعد بخوابان.',
      seconds: 30,
      pickLabel: '',
      roles: [w.name]
    }));
  }

  function buildSteps(n) {
    const h = ensureHost(n);
    const nightNum = h.nightNum || 1;
    const steps = [];
    nightScript(n).forEach((st) => {
      if (st.firstNight && nightNum > 1) return;
      if (st.minPlayers && (n.count || 0) < st.minPlayers) return;
      if (st.once && h.usedOnce[st.id]) return;
      let actors;
      if (st.kind === 'shot' && (!st.roles || !st.roles.length)) {
        actors = (n.seats || []).filter((s) => seatRole(s).side === 'mafia');
      } else {
        actors = seatsWithRoles(n, st.roles, true);
      }
      if (!actors.length) return;
      const liveActors = actors.filter((s) => !isDead(n, s.no));
      const deadNames = [];
      actors.forEach((s) => {
        if (!isDead(n, s.no)) return;
        const nm = String(seatRole(s).name || '');
        if (nm && !deadNames.includes(nm)) deadNames.push(nm);
      });
      let deadNote = '';
      if (deadNames.length === 1) deadNote = '(' + deadNames[0] + ' مرده)';
      else if (deadNames.length > 1) deadNote = '(' + deadNames.join(' و ') + ' مرده‌اند)';
      steps.push({ ...st, actors: actors.map((s) => s.no), actorsDead: !liveActors.length, deadNote });
    });
    return steps;
  }

  function godTabs(active) {
    return '<div class="god-tabs">'
      + '<button type="button" class="tab' + (active === 'tables' ? ' is-on' : '') + '" data-go="#/">میزها</button>'
      + '<button type="button" class="tab' + (active === 'host' ? ' is-on' : '') + '" data-go="#/host">گرداندگی</button>'
      + '<button type="button" class="tab' + (active === 'badges' ? ' is-on' : '') + '" data-go="#/badges">بج‌ها</button>'
      + '<a class="tab" href="' + esc(LB_HREF) + '">امتیاز</a>'
      + '</div>';
  }

  function isFinished(n) {
    const h = n.host || {};
    return !!(n.finished || h.phase === 'ended' || h.winner);
  }
  function nightCard(n, actions) {
    const named = (n.seats || []).filter((s) => s.name).length;
    const h = n.host || {};
    const done = isFinished(n);
    return '<article class="night-card' + (done ? ' is-done' : '') + '">'
      + '<div class="night-card-head">'
      + (done ? '<span class="night-done">پایان یافته</span>' : '')
      + '<b>' + esc(n.title) + '</b>'
      + '<small>' + fa(n.count) + ' نفر · ' + fa(named) + ' اسم'
      + (h.nightNum ? ' · شب ' + fa(h.nightNum) : '')
      + (n.exported ? ' · رفته به اخیر' : '')
      + '</small></div>'
      + '<div class="night-actions">' + actions + '</div></article>';
  }

  function exportNight(n) {
    if (!n || n.exported) return Promise.resolve(true);
    const href = app.getAttribute('data-recent') || '';
    const csrf = app.getAttribute('data-csrf') || '';
    if (!href || !csrf || !online()) return Promise.resolve(false);
    const h = ensureHost(n);
    const order = rankList(n);
    const total = order.length || (n.seats || []).length;
    const ptsOf = {};
    order.forEach((no, i) => { ptsOf[String(no)] = Math.max(1, total - i); });
    const seats = (n.seats || []).map((s) => ({
      name: s.name || '',
      roleId: s.roleId || '',
      dead: isDead(n, s.no) ? 1 : 0,
      points: ptsOf[String(s.no)] || 0,
    }));
    const body = new URLSearchParams({
      csrf,
      act: 'import',
      ajax: '1',
      night_id: n.id,
      title: n.title || '',
      scen_id: String(n.scenId || ''),
      category_id: String(n.catId || ''),
      winner: h.winner || 'citizen',
      seats: JSON.stringify(seats),
    });
    return fetch(href, { method: 'POST', body, credentials: 'same-origin', headers: { Accept: 'application/json' } })
      .then((r) => r.json())
      .then((data) => {
        if (data && data.ok) {
          n.exported = true;
          n.recentId = data.id;
          n.finished = true;
          putNight(n);
          return true;
        }
        return false;
      })
      .catch(() => false);
  }

  function show(which) {
    if (home) home.hidden = which !== 'home';
    if (panel) panel.hidden = which !== 'new';
    if (work) work.hidden = which !== 'work';
    if (which !== 'work') stopTick();
  }

  function banner() {
    if (!(catalog.scenarios && catalog.scenarios.length)) {
      return '<div class="flash warn">نقش‌ها روی این گوشی نیست. یک‌بار با اینترنت وارد شو تا ذخیره شوند.</div>';
    }
    if (!online()) return '<div class="flash ok">آفلاین — میز روی همین گوشی است.</div>';
    return '';
  }

  function scenId() {
    const on = app.querySelector('.god-scen.is-on');
    return on ? on.getAttribute('data-pick-scen') : String((catalog.scenarios[0] || {}).id || '');
  }
  function countVal() {
    return Math.max(4, Math.min(24, parseInt(document.getElementById('gCount')?.value || '10', 10) || 10));
  }
  function readQtys() {
    const q = {};
    app.querySelectorAll('[data-qty]').forEach((inp) => {
      const n = Math.max(0, parseInt(inp.value, 10) || 0);
      if (n) q[inp.getAttribute('data-qty')] = n;
    });
    return q;
  }

  function fillRoles() {
    if (!rolesBox) return;
    const sid = scenId();
    const count = countVal();
    const qtys = readQtys();
    const roles = rolesOf(sid);
    const by = { mafia: [], indie: [], citizen: [] };
    roles.forEach((r) => {
      const s = r.side === 'mafia' || r.side === 'indie' ? r.side : 'citizen';
      by[s].push(r);
    });
    const total = Object.values(qtys).reduce((s, n) => s + (parseInt(n, 10) || 0), 0);
    let html = '';
    [['mafia', 'نقش‌های مافیا'], ['indie', 'نقش‌های مستقل'], ['citizen', 'نقش‌های شهروند']].forEach(([side, lab]) => {
      if (!by[side].length) return;
      html += '<h3 class="god-side-title">' + lab + '</h3><div class="role-picks">';
      by[side].forEach((r) => {
        const v = parseInt(qtys[r.id] || '0', 10) || 0;
        html += '<div class="role-pick' + (v ? ' is-on' : '') + '"><div><b>' + esc(r.name) + '</b><small>' + SIDE[side] + '</small></div>';
        html += '<div class="role-step"><button type="button" data-minus="' + r.id + '">−</button>';
        html += '<input data-qty="' + r.id + '" type="number" min="0" max="24" value="' + v + '" style="' + INP + '">';
        html += '<button type="button" data-plus="' + r.id + '">+</button></div></div>';
      });
      html += '</div>';
    });
    rolesBox.innerHTML = html;
    const sum = document.getElementById('godSum');
    if (sum) {
      sum.className = 'god-sum' + (total === count ? ' is-ok' : (total > count ? ' is-over' : ''));
      sum.textContent = 'انتخاب‌شده: ' + fa(total) + ' از ' + fa(count) + (total === count ? ' — کامل است.' : (total > count ? ' — بیشتر از تعداد بازیکن است.' : ' — باید به تعداد بازیکن برسد.'));
    }
  }

  function renderHome() {
    show('home');
    const nights = loadNights();
    let html = banner() + godTabs('tables');
    html += '<p class="god-lead">نشست رزروشده را انتخاب کن تا اسم‌ها بیایند، یا دستی بنویس. نقش همان سناریو را بچین. بازیکن اسم خودش را می‌زند و کارت را می‌بیند.</p>';
    html += '<p><button type="button" class="btn btn-solid" data-go="#/new">میز جدید</button></p>';
    if (nights.length) {
      html += '<h3 class="god-list-title">میزهای همین گوشی</h3><div class="night-list">';
      nights.forEach((n) => {
        html += nightCard(n,
          '<button type="button" class="btn btn-sm" data-go="#/n/' + esc(n.id) + '">باز کردن</button>'
          + '<button type="button" class="btn btn-sm btn-solid" data-go="#/n/' + esc(n.id) + '/deal">نمایش</button>'
          + '<button type="button" class="btn btn-sm" data-go="#/n/' + esc(n.id) + '/host">گرداندگی</button>'
          + '<button type="button" class="btn btn-sm btn-danger" data-del="' + esc(n.id) + '">حذف</button>'
        );
      });
      html += '</div>';
    }
    if (home) home.innerHTML = html;
  }

  function renderHostList() {
    show('home');
    const nights = loadNights();
    let html = banner() + godTabs('host');
    html += '<p class="god-lead">گرداندگی جدا از پخش نقش است. شب را با تایمر جلو ببر: بیدار کردن، سیو دکتر، شات مافیا، نایس‌شات اسنایپر.</p>';
    if (!nights.length) {
      html += '<p class="empty">اول یک میز بساز و نقش‌ها را پخش کن.</p>';
      html += '<p><button type="button" class="btn btn-solid" data-go="#/new">میز جدید</button></p>';
    } else {
      html += '<div class="night-list">';
      nights.forEach((n) => {
        html += nightCard(n, '<button type="button" class="btn btn-solid btn-sm" data-go="#/n/' + esc(n.id) + '/host">ورود به گرداندگی</button>');
      });
      html += '</div>';
    }
    if (home) home.innerHTML = html;
  }

  function renderNew() {
    show('new');
    fillRoles();
  }

  function renderNight(id) {
    const n = getNight(id);
    if (!n) { go('/'); return; }
    show('work');
    const pool = (n.roles || []).map((x) => ({ ...roleById(x.id), qty: x.qty })).filter((r) => r && r.name);
    let html = banner();
    html += '<p><button type="button" class="btn btn-ghost btn-sm" data-go="#/">میزها</button></p>';
    html += '<div class="card" style="padding:16px;margin-bottom:14px"><p style="color:var(--creamd);line-height:1.8">' + esc(n.title) + ' · ' + fa(n.count) + ' نفر · ';
    pool.forEach((p) => { html += '<span class="pill">' + esc(p.name) + ' × ' + fa(p.qty) + '</span> '; });
    html += '</p></div>';
    html += '<div class="card" style="padding:16px"><p style="color:var(--muted);font-size:13px;line-height:1.8;margin-bottom:12px">اسم رزرو آمده را می‌توانی عوض کنی اگر کسی نیامد. فیلتر دختر/پسر جلوی اشتباه را می‌گیرد؛ میز ترکیبی هر دو را می‌آورد.</p>';
    const gOn = n.pickGender || 'all';
    html += '<div class="seat-tools">';
    html += '<button type="button" class="tab' + (gOn === 'all' ? ' is-on' : '') + '" data-seat-g="all">همه اکانت‌ها</button>';
    html += '<button type="button" class="tab' + (gOn === 'boy' ? ' is-on' : '') + '" data-seat-g="boy">پسر</button>';
    html += '<button type="button" class="tab' + (gOn === 'girl' ? ' is-on' : '') + '" data-seat-g="girl">دختر</button>';
    html += '</div>';
    if ((catalog.sessions || []).length) {
      html += '<div class="seat-refill" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center;margin:0 0 12px">';
      html += '<select id="gFillSess" class="god-input" style="flex:1;min-width:180px"><option value="">پر کردن خالی‌ها از رزرو…</option>';
      (catalog.sessions || []).forEach((gs) => {
        html += '<option value="' + gs.id + '"' + (String(n.sessionId) === String(gs.id) ? ' selected' : '') + '>' + esc(gs.title) + ' · ' + fa((gs.players || []).length) + ' رزرو</option>';
      });
      html += '</select><button type="button" class="btn btn-sm" data-fill-sess>وارد کردن اسم رزرو</button></div>';
    }
    html += '<div class="seat-list">';
    (n.seats || []).forEach((s) => {
      html += '<div class="seat-row"><span class="seat-no">' + fa(s.no) + '</span>';
      html += '<div class="seat-name"><input data-name="' + s.no + '" value="' + esc(s.name || '') + '" placeholder="جستجو یا نوشتن اسم" autocomplete="off" style="width:100%;background:#fff;border:2px solid rgba(33,41,90,.18);color:#292827;padding:11px 12px;border-radius:12px;font-size:16px;-webkit-appearance:none;appearance:none;color-scheme:light;font-weight:700">';
      html += '<div class="name-suggest" hidden></div></div>';
      html += '<select data-role="' + s.no + '" style="width:100%;background:#fff;border:2px solid rgba(33,41,90,.18);color:#292827;padding:11px 12px;border-radius:12px;font-size:16px;-webkit-appearance:none;appearance:none;color-scheme:light;font-weight:700"><option value="">نقش…</option>';
      pool.forEach((p) => {
        html += '<option value="' + p.id + '"' + (String(s.roleId) === String(p.id) ? ' selected' : '') + '>' + esc(p.name) + ' (' + SIDE[p.side] + ' ×' + fa(p.qty) + ')</option>';
      });
      html += '</select></div>';
    });
    html += '</div><div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px">';
    html += '<button type="button" class="btn" id="gRand">پخش تصادفی نقش</button>';
    html += '<button type="button" class="btn btn-solid" id="gSaveSeats">ثبت اسم‌ها و نقش‌ها</button>';
    html += '<button type="button" class="btn btn-solid" data-go="#/n/' + esc(id) + '/deal">نمایش به بازیکن‌ها</button>';
    html += '<button type="button" class="btn" data-go="#/n/' + esc(id) + '/host">گرداندگی شب</button></div></div>';
    if (work) work.innerHTML = html;
  }

  function renderDeal(id) {
    const n = getNight(id);
    if (!n) { go('/'); return; }
    show('work');
    const seats = n.seats || [];
    n.dealSeen = (n.dealSeen || []).map(Number);
    const seenN = n.dealSeen.length;
    let html = banner();
    html += '<p><button type="button" class="btn btn-ghost btn-sm" data-go="#/n/' + esc(id) + '">برگشت به لیست</button></p>';
    html += '<div class="deal-board" id="dealBoard">';
    html += '<p class="deal-hint">اسم خودت را بزن، کارتت را ببین، «تمام» را بزن تا خاموش شود و کس دیگری کارتت را نبیند.</p>';
    html += '<p class="sub">' + fa(seenN) + ' از ' + fa(seats.length) + ' کارت دیده شد.</p>';
    html += '<div class="deal-names">';
    seats.forEach((s) => {
      const seen = n.dealSeen.map(String).includes(String(s.no));
      html += '<button type="button" class="deal-name-btn' + (seen ? ' is-seen' : '') + '" data-deal-open="' + s.no + '"' + (seen ? ' disabled' : '') + '>';
      html += '<b>' + esc(s.name || ('صندلی ' + fa(s.no))) + '</b>';
      html += '<small>' + (seen ? 'دیده شد' : ('صندلی ' + fa(s.no))) + '</small></button>';
    });
    html += '</div>';
    html += '<p style="margin-top:14px"><button type="button" class="btn btn-ghost btn-sm" data-deal-reset>بازنشانی دیده‌شده‌ها</button></p>';
    html += '<div class="deal-pop" id="dealPop" hidden><article class="deal-pop-card">';
    html += '<p class="deal-kicker" id="dealPopName"></p>';
    html += '<div class="deal-card is-open" id="dealPopFace"></div>';
    html += '<button type="button" class="btn btn-solid" data-deal-done style="margin-top:16px;width:100%">تمام</button>';
    html += '</article></div></div>';
    if (work) work.innerHTML = html;
    const pop = document.getElementById('dealPop');
    const face = document.getElementById('dealPopFace');
    const nam = document.getElementById('dealPopName');
    let openNo = 0;
    const closePop = () => { if (pop) pop.hidden = true; openNo = 0; };
    const openCard = (no) => {
      const s = seats.find((x) => String(x.no) === String(no));
      if (!s || n.dealSeen.map(String).includes(String(no))) return;
      const r = roleById(s.roleId) || {};
      openNo = Number(no);
      if (nam) nam.textContent = s.name || ('صندلی ' + fa(s.no));
      if (face) {
        face.innerHTML = '<span class="deal-front" style="position:relative;opacity:1;transform:none"><span class="deal-sym">' + esc(r.symbol || '♠') + '</span><strong>' + esc(r.name || '—') + '</strong><em>' + esc(SIDE[r.side] || '') + '</em></span>';
      }
      if (pop) pop.hidden = false;
    };
    const box = document.getElementById('dealBoard');
    if (!box) return;
    box.addEventListener('click', (e) => {
      const openBtn = e.target.closest('[data-deal-open]');
      if (openBtn && !openBtn.disabled) { openCard(openBtn.getAttribute('data-deal-open')); return; }
      if (e.target.closest('[data-deal-done]')) {
        if (openNo) {
          if (!n.dealSeen.map(String).includes(String(openNo))) n.dealSeen.push(openNo);
          putNight(n);
        }
        closePop();
        renderDeal(id);
        return;
      }
      if (e.target.closest('[data-deal-reset]')) {
        if (confirm('همه کارت‌ها دوباره قابل دیدن شوند؟')) {
          n.dealSeen = [];
          putNight(n);
          renderDeal(id);
        }
      }
    });
  }

  function pickButtons(n, opts) {
    const allowNone = opts.allowNone !== false;
    const attr = opts.attr || 'data-pick-seat';
    const skip = new Set((opts.skip || []).map(String));
    const seats = living(n).filter((s) => !skip.has(String(s.no)));
    let html = '<div class="host-picks">';
    if (allowNone) html += '<button type="button" class="btn host-pick" ' + attr + '="0">هیچ‌کس</button>';
    seats.forEach((s) => {
      const r = seatRole(s);
      html += '<button type="button" class="btn host-pick" ' + attr + '="' + s.no + '"><b>' + esc(seatName(s)) + '</b><small>' + esc(r.name || '') + '</small></button>';
    });
    html += '</div>';
    return html;
  }

  function startTick(id) {
    stopTick();
    tick = setInterval(() => {
      const n = getNight(id);
      if (!n || !n.host) { stopTick(); return; }
      const h = ensureHost(n);
      if (h.phase === 'ended' || h.phase === 'hand') { stopTick(); return; }
      let changed = false;
      ['turn', 'defend', 'night'].forEach((k) => {
        const c = h.clocks[k];
        if (c.running && !c.paused && c.remain > 0) {
          c.remain -= 1;
          if (c.remain <= 0) c.running = false;
          changed = true;
        }
      });
      if (h.phase === 'wake') h.remain = h.clocks.night.remain;
      if (!changed) return;
      putNight(n);
      paintClockNums(h);
    }, 1000);
  }

  function inquireResult(n, seatNo, step) {
    const s = (n.seats || []).find((x) => String(x.no) === String(seatNo));
    if (!s) return 'هدف خالی بود.';
    const r = seatRole(s);
    const neg = new Set((step.negative || []).map(String));
    const mafia = r.side === 'mafia' && !neg.has(String(r.name));
    return mafia
      ? 'استعلام «' + seatName(s) + '»: مثبت — مافیا.'
      : 'استعلام «' + seatName(s) + '»: منفی — مافیا نیست (یا نقش پوشیده است).';
  }

  function resolveMorning(n) {
    const h = ensureHost(n);
    const last = h.last || {};
    const msgs = [];
    const die = (no, why) => {
      if (!no || isDead(n, no)) return;
      h.dead.push(Number(no));
      const s = (n.seats || []).find((x) => String(x.no) === String(no));
      msgs.push((s ? seatName(s) : 'یک نفر') + ' ' + why);
    };
    const saved = last.save ? String(last.save) : '';
    if (last.shot) {
      if (saved && String(last.shot) === saved) {
        msgs.push('سیو خورد. کسی از شات مافیا نمرد.');
      } else {
        die(last.shot, 'با شات مافیا از بازی خارج شد.');
      }
    } else {
      msgs.push('مافیا امشب شات نداشت.');
    }
    if (last.sniper) {
      const t = (n.seats || []).find((x) => String(x.no) === String(last.sniper));
      const r = t ? seatRole(t) : {};
      const immune = ['پدرخوانده', 'رئیس مافیا', 'دن مافیا'];
      const sniperSeat = living(n).find((s) => ['تک‌تیرانداز', 'اسنایپر', 'حرفه‌ای'].includes(String(seatRole(s).name || '')))
        || (n.seats || []).find((s) => ['تک‌تیرانداز', 'اسنایپر', 'حرفه‌ای'].includes(String(seatRole(s).name || '')));
      if (saved && String(last.sniper) === saved) {
        msgs.push('تیر اسنایپر به سیو خورد؛ کسی از این تیر نمرد.');
      } else if (t && immune.includes(String(r.name || ''))) {
        msgs.push('تیر به «' + seatName(t) + '» اثر نکرد (زره نقش).');
      } else if (r.side === 'mafia') {
        die(last.sniper, 'خارج شد — نایس‌شات!');
      } else if (t) {
        msgs.push('شهروند زده شد.');
        if (sniperSeat) die(sniperSeat.no, 'با شلیک اشتباه از بازی خارج شد.');
      }
    }
    const end = gameEnd(n);
    if (end && end.winner) {
      msgs.push('بازی تمام شد — برنده ' + winnerFa(end.winner) + '.');
      h.winner = end.winner;
      h.endWhy = end.why;
      h.pendingEnd = true;
    } else if (end && end.hand) {
      msgs.push(end.why);
      h.pendingHand = true;
    }
    if (!msgs.length) msgs.push('صبح بدون خروج خاص شروع شد.');
    h.log.unshift({ night: h.nightNum, msgs: msgs.slice() });
    h.phase = 'morning';
    h.last = {};
    putNight(n);
    return msgs;
  }

  function paintHost(id) {
    closeAsk();
    const n = getNight(id);
    if (!n) { go('/host'); return; }
    const h = ensureHost(n);
    if (h.phase === 'lobby' || !h.phase) {
      const endNow = gameEnd(n);
      if (endNow && endNow.winner) {
        h.winner = endNow.winner;
        h.endWhy = endNow.why;
        h.phase = 'ended';
        n.finished = true;
        putNight(n);
      } else if (endNow && endNow.hand) {
        h.phase = 'hand';
        h.handFrom = h.handFrom || 0;
        h.handTo = h.handTo || 0;
        putNight(n);
      }
    }
    show('work');
    let html = banner();
    html += '<p><button type="button" class="btn btn-ghost btn-sm" data-go="#/host">میزهای گرداندگی</button> ';
    html += '<button type="button" class="btn btn-ghost btn-sm" data-go="#/n/' + esc(id) + '">نقش‌ها</button></p>';
    html += '<div class="card host-card"><div class="kicker">گرداندگی</div>';
    if (h.phase !== 'ended' && h.phase !== 'hand') html += clocksHtml(n);
    html += '<h2>' + esc(n.title) + '</h2>';
    html += '<p class="sub" style="margin:8px 0 16px">شب ' + fa(h.nightNum || 0) + ' · زنده ' + fa(living(n).length) + ' از ' + fa(n.count) + '</p>';

    if (h.phase === 'wake') {
      const st = (h.steps || [])[h.step] || {};
      html += '<div class="host-wake"><p class="host-kicker">نوبت</p><h3>' + esc(st.wake || st.title || '') + '</h3>';
      if (st.deadNote) html += '<p class="host-dead-note">' + esc(st.deadNote) + '</p>';
      html += '<p>' + esc(st.hint || '') + '</p>';
      html += '<div class="host-actions">';
      html += '<button type="button" class="btn btn-solid" data-host-ready>' + (st.kind === 'announce' ? 'بیدار شد — بخوابان' : 'بیدار شد — انتخاب') + '</button>';
      html += '</div></div>';
    } else if (h.phase === 'pick') {
      const st = (h.steps || [])[h.step] || {};
      html += '<div class="host-wake"><h3>' + esc(st.pickLabel || st.title) + '</h3><p>' + esc(st.hint || '') + '</p></div>';
      html += pickButtons(n, { allowNone: true });
    } else if (h.phase === 'note') {
      html += '<div class="host-wake host-intel">';
      html += '<p class="host-kicker">شب ' + fa(h.nightNum || 1) + '</p>';
      html += '<h3>' + esc((h.note && h.note.body) || '') + '</h3>';
      html += '<p class="sub">همین شب به کارآگاه بگو. صبح اعلام نمی‌شود.</p>';
      html += '<button type="button" class="btn btn-solid" data-host-noted style="margin-top:12px">گفتم — نقش بعد</button>';
      html += '</div>';
    } else if (h.phase === 'hand') {
      html += '<div class="host-wake">';
      html += '<p class="host-kicker">پایان میز</p>';
      html += '<h3>دست بده است</h3>';
      html += '<p>دو شهروند و یک مافیا مانده. کی دست می‌دهد و به چه کسی.</p>';
      if (!h.handFrom) {
        html += '<p class="sub" style="margin:12px 0 8px">کی دست می‌دهد؟</p>';
        html += pickButtons(n, { allowNone: false, attr: 'data-hand-from' });
      } else {
        const giver = (n.seats || []).find((s) => String(s.no) === String(h.handFrom));
        html += '<p class="sub" style="margin:12px 0 8px">' + esc(giver ? seatName(giver) : 'بازیکن') + ' دست می‌دهد به چه کسی؟</p>';
        html += pickButtons(n, { allowNone: false, attr: 'data-hand-to', skip: [h.handFrom] });
      }
      html += '</div>';
    } else if (h.phase === 'ended') {
      html += '<div class="host-morning">';
      html += '<h3>بازی تمام شد</h3>';
      html += '<p class="host-msg">برنده: ' + esc(winnerFa(h.winner)) + '</p>';
      if (h.endWhy) html += '<p class="sub">' + esc(h.endWhy) + '</p>';
      const order = rankList(n);
      const total = order.length;
      const byNo = {};
      (n.seats || []).forEach((s) => { byNo[String(s.no)] = s; });
      html += '<p class="sub" style="text-align:right;margin:16px 0 10px">برنده‌ها بالا هستند. با ⋮⋮ جابه‌جا کن. نفر اول ' + fa(total) + ' امتیاز می‌گیرد.</p>';
      html += '<div class="host-alive" id="rankDrag">';
      order.forEach((no, i) => {
        const s = byNo[String(no)];
        if (!s) return;
        const r = seatRole(s);
        const dead = isDead(n, s.no);
        const win = wonSeat(n, s);
        html += '<div class="host-rank' + (dead ? ' is-dead' : '') + (win ? ' is-win' : '') + '" data-rank-no="' + s.no + '">';
        html += '<span class="layout-handle host-handle" aria-label="جابه‌جایی">⋮⋮</span>';
        html += '<span class="seat-no rank-pos">' + fa(i + 1) + '</span>';
        html += '<div class="host-who"><b>' + esc(seatName(s)) + '</b><small>' + esc(r.name || '') + ' · ' + (win ? 'برد' : 'باخت') + (dead ? ' · مرده' : '') + '</small></div>';
        html += '<b class="rank-pts">+' + fa(total - i) + '</b>';
        html += '</div>';
      });
      html += '</div>';
      const recHref = app.getAttribute('data-recent') || '';
      if (n.exported) {
        html += '<p class="flash ok">امتیاز این میز ثبت شد.</p>';
        if (recHref) html += '<p><a class="btn btn-solid" href="' + esc(recHref) + '?tab=list">بازی‌های اخیر</a></p>';
      } else {
        html += '<button type="button" class="btn btn-solid" data-export-recent style="margin-top:16px">ثبت امتیاز و بفرست به بازی‌های اخیر</button>';
      }
      html += '<button type="button" class="btn btn-ghost" data-go="#/host" style="margin-top:16px">میزهای گرداندگی</button>';
      html += '</div>';
    } else if (h.phase === 'intro') {
      html += '<div class="host-wake">';
      html += '<p class="host-kicker">شب معارفه</p>';
      html += '<h3>مافیا بیدار شوند</h3>';
      html += '<p>همدیگر را ببینند. شات نیست — فقط معرفی.</p>';
      html += '<button type="button" class="btn btn-solid" data-intro-done style="margin-top:16px">تمام — برو به روز</button>';
      html += '</div>';
    } else if (h.phase === 'defend') {
      const defs = nomSeats(n);
      const i = Math.min(h.defendI || 0, Math.max(0, defs.length - 1));
      const cur = defs[i];
      html += '<div class="host-wake">';
      html += '<p class="host-kicker">دفاع</p>';
      if (!cur) {
        html += '<p>کسی برای دفاع نماند.</p>';
        html += '<button type="button" class="btn btn-solid host-start" data-vote2-start style="margin-top:16px">شروع رای ۲</button>';
      } else {
        html += '<h3>صندلی ' + fa(cur.no) + '</h3>';
        html += '<p class="deal-name" style="margin:0 0 8px">' + esc(seatName(cur)) + '</p>';
        html += '<p class="sub">نوبت دفاع ' + fa(i + 1) + ' از ' + fa(defs.length) + '</p>';
        html += '<div class="host-alive" style="margin-top:16px;text-align:right">';
        defs.forEach((s, j) => {
          html += '<div class="host-seat host-seat-pick' + (j === i ? ' is-nom' : '') + '"><span class="seat-no">' + fa(s.no) + '</span><div class="host-who"><b>' + esc(seatName(s)) + '</b><small>' + (j === i ? 'در حال دفاع' : (j < i ? 'تمام شد' : 'بعد')) + '</small></div></div>';
        });
        html += '</div>';
        if (i < defs.length - 1) {
          html += '<button type="button" class="btn btn-solid host-start" data-defend-next style="margin-top:16px">دفاع بعدی</button>';
        } else {
          html += '<button type="button" class="btn btn-solid host-start" data-vote2-start style="margin-top:16px">شروع رای ۲</button>';
        }
      }
      html += '</div>';
    } else if (h.phase === 'vote2') {
      const defs = nomSeats(n);
      html += '<div class="host-lobby">';
      html += '<p class="host-kicker" style="text-align:right">رای ۲</p>';
      html += '<p class="sub" style="text-align:right;margin:0 0 14px">یکی را انتخاب کن. دوباره بزن تا تیک برداشته شود — می‌شود هیچ‌کس بیرون نرود.</p>';
      html += '<div class="host-alive">';
      defs.forEach((s) => {
        const on = String(h.vote2pick || '') === String(s.no);
        html += '<div class="host-seat host-seat-pick' + (on ? ' is-nom' : '') + '" data-vote2="' + s.no + '">';
        html += '<span class="seat-no">' + fa(s.no) + '</span>';
        html += '<div class="host-who"><b>' + esc(seatName(s)) + '</b></div>';
        html += '<button type="button" class="host-dot' + (on ? ' is-on' : '') + '" data-vote2="' + s.no + '" aria-label="انتخاب"></button>';
        html += '</div>';
      });
      html += '</div>';
      if (h.vote2pick) {
        const picked = defs.find((s) => String(s.no) === String(h.vote2pick));
        html += '<button type="button" class="btn btn-solid host-start" data-vote2-out style="margin-top:16px">' + (picked ? esc(seatName(picked)) + ' بیرون برود' : 'بیرون برود') + '</button>';
      } else {
        html += '<button type="button" class="btn host-start" data-vote2-skip style="margin-top:16px">کسی بیرون نرود</button>';
      }
      html += '</div>';
    } else if (h.phase === 'morning') {
      const lastLog = (h.log || [])[0];
      html += '<div class="host-morning">';
      html += '<h3>صبح شب ' + fa(h.nightNum) + '</h3>';
      (lastLog?.msgs || ['کسی نمرد.']).forEach((m) => { html += '<p class="host-msg">' + esc(m) + '</p>'; });
      const nextLab = h.pendingEnd ? 'پایان بازی' : (h.pendingHand ? 'برو به دست بده' : 'برگشت به میز روز');
      html += '<button type="button" class="btn btn-solid" data-host-lobby style="margin-top:16px">' + nextLab + '</button>';
      html += '</div>';
    } else {
      const intro = !h.introDone;
      const noms = intro ? [] : nomSeats(n);
      const talk = talkSeat(n);
      html += '<div class="host-lobby">';
      html += '<p class="host-kicker" style="text-align:right">' + (intro ? 'روز معارفه' : ('روز ' + fa(h.nightNum || 1))) + '</p>';
      if (intro) {
        html += '<p class="sub" style="text-align:right;margin:0 0 14px">رای‌گیری نداریم. شب معارفه فقط برای دیدن مافیا است.</p>';
        html += '<button type="button" class="btn btn-solid host-start" data-host-intro>شروع شب معارفه</button>';
      } else if (noms.length) {
        html += '<p class="sub" id="hostDayHint" style="text-align:right;margin:0 0 14px">چند نفر را برای دفاع علامت بزن — بعد شروع دفاع.</p>';
        html += '<button type="button" class="btn btn-solid host-start" id="hostDayAct" data-defend-start>شروع دفاع (' + fa(noms.length) + ' نفر)</button>';
      } else {
        html += '<p class="sub" id="hostDayHint" style="text-align:right;margin:0 0 14px">دایره کنار اسم را پر کن اگر کسی باید دفاع کند. اگر کسی نیست، شب را شروع کن.</p>';
        html += '<button type="button" class="btn btn-solid host-start" id="hostDayAct" data-host-start>شروع شب ' + fa((h.nightNum || 0) + 1) + '</button>';
      }
      if ((h.intel || []).length) {
        html += '<h3 style="margin:22px 0 10px">استعلام‌ها (فقط گرداننده)</h3>';
        h.intel.forEach((row) => {
          html += '<div class="host-log"><b>شب ' + fa(row.night) + '</b><div>' + esc(row.msg) + '</div></div>';
        });
      }
      html += '<div class="host-players-head">';
      html += '<h3>بازیکن‌ها</h3>';
      html += '<button type="button" class="pass-eye host-role-eye' + (h.hideRoles ? '' : ' is-open') + '" data-toggle-roles aria-label="' + (h.hideRoles ? 'نمایش نقش‌ها' : 'پنهان نقش‌ها') + '">' + EYE_SVG + '</button>';
      html += '<span class="host-role-ph">' + (h.hideRoles ? 'نقش‌ها پنهان' : 'نقش‌ها پیدا') + '</span>';
      html += '</div>';
      html += '<p class="sub" style="text-align:right;margin:0 0 10px">از دستگیره ⋮⋮ بکش تا صندلی جابه‌جا شود.</p>';
      html += '<div class="host-alive" id="seatDrag">';
      (n.seats || []).forEach((s) => {
        const r = seatRole(s);
        const dead = isDead(n, s.no);
        const kicked = isKicked(n, s.no);
        const nom = (h.nominated || []).map(String).includes(String(s.no));
        const warns = warnOf(n, s.no);
        const talking = !dead && !kicked && talk && String(talk.no) === String(s.no);
        html += '<div class="host-seat' + (dead ? ' is-dead' : '') + (kicked ? ' is-kicked' : '') + (nom ? ' is-nom' : '') + (talking ? ' is-talk' : '') + '" data-seat-no="' + s.no + '">';
        html += '<span class="layout-handle host-handle" aria-label="جابه‌جایی">⋮⋮</span>';
        html += '<span class="seat-no">' + fa(s.no) + '</span>';
        html += '<div class="host-who"><b>' + esc(seatName(s)) + '</b><small>' + (h.hideRoles ? 'نقش پنهان' : esc(r.name || 'بدون نقش')) + '</small>';
        if (dead) html += '<em class="dead-tag">(مرده)</em>';
        if (kicked) html += '<em class="dead-tag">(Kick)</em>';
        html += '</div>';
        html += '<div class="warn-step" title="اخطار"><span class="warn-lab">اخطار</span><div class="warn-row"><button type="button" data-warn-minus="' + s.no + '">−</button><span>' + (warns ? fa(warns) : '—') + '</span><button type="button" data-warn-plus="' + s.no + '">+</button></div></div>';
        html += '<div class="host-more">';
        html += '<button type="button" class="host-dots" data-more="' + s.no + '" aria-label="منو">⋮</button>';
        html += '<div class="host-menu" hidden><button type="button" class="host-kick" data-kick="' + s.no + '"><b>Kick</b></button><hr><button type="button" class="host-kill" data-kill="' + s.no + '">Kill</button></div>';
        html += '</div>';
        if (!intro && !dead && !kicked) {
          html += '<button type="button" class="host-dot' + (nom ? ' is-on' : '') + '" data-nom="' + s.no + '" aria-label="دفاع"></button>';
        } else {
          html += '<span class="host-dot-ph"></span>';
        }
        html += '</div>';
      });
      html += '</div>';
      if ((h.log || []).length) {
        html += '<h3 style="margin:22px 0 10px">گزارش شب‌ها</h3>';
        h.log.forEach((row) => {
          html += '<div class="host-log"><b>شب ' + fa(row.night) + '</b>';
          (row.msgs || []).forEach((m) => { html += '<div>' + esc(m) + '</div>'; });
          html += '</div>';
        });
      }
      html += '</div>';
    }
    html += '</div>';
    if (work) work.innerHTML = html;
    bindSeatDrag(id);
    bindRankDrag(id);
    if (h.phase !== 'ended' && h.phase !== 'hand') startTick(id);
    else stopTick();
    if (h.phase === 'ended') {
      n.finished = true;
      putNight(n);
    }
  }

  function advanceHost(n) {
    const h = ensureHost(n);
    h.step += 1;
    if (h.step >= (h.steps || []).length) {
      resolveMorning(n);
      return;
    }
    const st = h.steps[h.step];
    h.phase = 'wake';
    h.remain = st.seconds || 30;
    h.paused = false;
    armClock(h, 'night', h.remain);
    putNight(n);
  }

  function applyPick(n, seatNo) {
    const h = ensureHost(n);
    const st = (h.steps || [])[h.step] || {};
    const no = Number(seatNo) || 0;
    if (st.once) h.usedOnce[st.id] = true;
    if (st.kind === 'shot') h.last.shot = no || null;
    else if (st.kind === 'save') h.last.save = no || null;
    else if (st.kind === 'sniper') h.last.sniper = no || null;
    if (st.kind === 'inquire') {
      if (no) {
        const msg = inquireResult(n, no, st);
        h.intel = h.intel || [];
        h.intel.unshift({ night: h.nightNum, msg });
        h.phase = 'note';
        h.note = { body: msg };
        putNight(n);
        return;
      }
      advanceHost(n);
      return;
    }
    advanceHost(n);
  }

  function commitSeatOrder(id, list) {
    const n = getNight(id);
    if (!n || !list) return;
    const nos = [...list.querySelectorAll('[data-seat-no]')].map((el) => el.getAttribute('data-seat-no'));
    const before = (n.seats || []).map((s) => String(s.no));
    if (nos.join() === before.join()) return;
    const by = {};
    (n.seats || []).forEach((s) => { by[String(s.no)] = s; });
    const map = {};
    const next = [];
    nos.forEach((old, i) => {
      const s = by[String(old)];
      if (!s) return;
      map[String(old)] = i + 1;
      s.no = i + 1;
      next.push(s);
    });
    if (next.length !== (n.seats || []).length) return;
    n.seats = next;
    remapSeatNos(n, map);
    putNight(n);
    paintHost(id);
  }

  function bindHandleSort(listId, itemSel, onEnd) {
    const list = document.getElementById(listId);
    if (!list) return;
    let drag = null;
    const endDrag = () => {
      if (!drag) return;
      drag.classList.remove('is-drag');
      drag = null;
      onEnd(list);
    };
    list.querySelectorAll(itemSel).forEach((item) => {
      const handle = item.querySelector('.host-handle');
      if (!handle) return;
      handle.addEventListener('pointerdown', (e) => {
        e.preventDefault();
        e.stopPropagation();
        drag = item;
        item.classList.add('is-drag');
        try { handle.setPointerCapture(e.pointerId); } catch (_) {}
      });
      handle.addEventListener('pointermove', (e) => {
        if (!drag) return;
        const el = document.elementFromPoint(e.clientX, e.clientY);
        const over = el && el.closest && el.closest('#' + listId + ' ' + itemSel);
        if (!over || over === drag) return;
        const rect = over.getBoundingClientRect();
        const before = e.clientY < rect.top + rect.height / 2;
        list.insertBefore(drag, before ? over : over.nextSibling);
      });
      handle.addEventListener('pointerup', endDrag);
      handle.addEventListener('pointercancel', endDrag);
    });
  }
  function bindSeatDrag(id) {
    bindHandleSort('seatDrag', '.host-seat', (list) => commitSeatOrder(id, list));
  }
  function commitRank(id, list) {
    const n = getNight(id);
    if (!n || !list) return;
    const h = ensureHost(n);
    const nos = [...list.querySelectorAll('[data-rank-no]')].map((el) => Number(el.getAttribute('data-rank-no')));
    h.rank = nos;
    putNight(n);
    const total = nos.length;
    list.querySelectorAll('[data-rank-no]').forEach((el, i) => {
      const pos = el.querySelector('.rank-pos');
      const pts = el.querySelector('.rank-pts');
      if (pos) pos.textContent = fa(i + 1);
      if (pts) pts.textContent = '+' + fa(Math.max(1, total - i));
    });
  }
  function bindRankDrag(id) {
    bindHandleSort('rankDrag', '.host-rank', (list) => commitRank(id, list));
  }

  function renderHost(id) {
    const n = getNight(id);
    if (!n) { go('/host'); return; }
    ensureHost(n);
    putNight(n);
    paintHost(id);
  }

  function renderBadgeGuide() {
    show('home');
    const list = catalog.badges || [];
    let html = banner() + godTabs('badges');
    html += '<p class="god-lead">هر بج برای یک جور بازی است. بخوان تا بدانی به چه کسی بدهی، بعد از میز ثبت کن.</p>';
    if (!list.length) {
      html += '<p class="empty">هنوز بج تعریف نشده.</p>';
    } else {
      html += '<div class="badge-guide">';
      list.forEach((b) => {
        const face = b.image
          ? '<img src="' + esc(b.image) + '" alt="">'
          : '<span class="medal-ico">' + esc(b.icon || '★') + '</span>';
        html += '<article class="badge-guide-item">'
          + '<div class="medal-coin is-sm">' + face + '</div>'
          + '<div><b>' + esc(b.name) + '</b><p>' + esc(b.hint || 'بدون توضیح') + '</p></div></article>';
      });
      html += '</div>';
    }
    html += '<p><a class="btn btn-solid" href="' + esc(BADGE_HREF) + '?tab=add">دادن بج به بازیکن</a></p>';
    if (home) home.innerHTML = html;
  }

  function route() {
    const h = hash();
    const hostOne = h.match(/^\/n\/([^/]+)\/host$/);
    const deal = h.match(/^\/n\/([^/]+)\/deal$/);
    const night = h.match(/^\/n\/([^/]+)$/);
    if (hostOne) return renderHost(decodeURIComponent(hostOne[1]));
    if (deal) return renderDeal(decodeURIComponent(deal[1]));
    if (night) return renderNight(decodeURIComponent(night[1]));
    if (h === '/new') return renderNew();
    if (h === '/host') return renderHostList();
    if (h === '/badges') return renderBadgeGuide();
    renderHome();
  }

  app.addEventListener('click', (e) => {
    const gFilter = e.target.closest('[data-seat-g]');
    if (gFilter) {
      app.querySelectorAll('[data-seat-g]').forEach((b) => b.classList.toggle('is-on', b === gFilter));
      const nid = (hash().match(/^\/n\/([^/]+)$/) || [])[1];
      const night = nid ? getNight(nid) : null;
      if (night) { night.pickGender = gFilter.getAttribute('data-seat-g'); putNight(night); }
      hideNameSuggest();
      return;
    }
    const fillSessBtn = e.target.closest('[data-fill-sess]');
    if (fillSessBtn) {
      const nid = (hash().match(/^\/n\/([^/]+)$/) || [])[1];
      const night = nid ? getNight(nid) : null;
      const sel = document.getElementById('gFillSess');
      const sess = sessionById(sel && sel.value);
      if (!night || !sess) { alert('نشست را انتخاب کن.'); return; }
      const players = playersOfSession(sess);
      let pi = 0;
      (night.seats || []).forEach((s) => {
        if (String(s.name || '').trim()) return;
        while (pi < players.length) {
          const p = players[pi++];
          const taken = (night.seats || []).some((x) => x !== s && x.name === p.name);
          if (taken) continue;
          s.name = p.name;
          s.gender = p.gender;
          break;
        }
      });
      night.sessionId = sess.id;
      night.pickGender = genderGate(sess.audience);
      putNight(night);
      route();
      return;
    }
    const pickNm = e.target.closest('[data-pick-name]');
    if (pickNm) {
      const wrap = pickNm.closest('.seat-name');
      const inp = wrap && wrap.querySelector('[data-name]');
      if (inp) inp.value = pickNm.getAttribute('data-pick-name') || '';
      hideNameSuggest();
      return;
    }
    if (!e.target.closest('.seat-name')) hideNameSuggest();
    const goBtn = e.target.closest('[data-go]');
    if (goBtn) { go(goBtn.getAttribute('data-go')); return; }
    const del = e.target.closest('[data-del]');
    if (del) {
      if (confirm('این میز از روی گوشی پاک شود؟')) { delNight(del.getAttribute('data-del')); route(); }
      return;
    }
    const pick = e.target.closest('[data-pick-scen]');
    if (pick) {
      app.querySelectorAll('.god-scen').forEach((b) => b.classList.toggle('is-on', b === pick));
      app.querySelectorAll('[data-qty]').forEach((inp) => { inp.value = 0; });
      fillRoles();
      return;
    }
    const deltaBtn = e.target.closest('[data-count-delta]');
    if (deltaBtn) {
      const next = Math.max(4, Math.min(24, countVal() + (parseInt(deltaBtn.getAttribute('data-count-delta'), 10) || 0)));
      const inp = document.getElementById('gCount');
      if (inp) inp.value = String(next);
      fillRoles();
      return;
    }
    const plus = e.target.closest('[data-plus]');
    const minus = e.target.closest('[data-minus]');
    if (plus || minus) {
      const id = (plus || minus).getAttribute(plus ? 'data-plus' : 'data-minus');
      const inp = app.querySelector('[data-qty="' + id + '"]');
      const cap = countVal();
      const qtys = readQtys();
      const sum = Object.values(qtys).reduce((s, n) => s + n, 0);
      let v = parseInt(inp.value, 10) || 0;
      if (plus && sum < cap) v += 1;
      if (minus) v = Math.max(0, v - 1);
      inp.value = v;
      fillRoles();
      return;
    }
    if (e.target.id === 'gLock') {
      const sid = scenId();
      const count = countVal();
      const qtys = readQtys();
      const total = Object.values(qtys).reduce((s, n) => s + n, 0);
      if (total !== count) { alert('جمع نقش‌ها باید دقیقاً برابر تعداد بازیکن باشد و بیشتر نشود.'); return; }
      const allowed = new Set(rolesOf(sid).map((r) => String(r.id)));
      for (const id of Object.keys(qtys)) {
        if (!allowed.has(String(id))) { alert('فقط نقش‌های همین سناریو را می‌شود انتخاب کرد.'); return; }
      }
      let title = (document.getElementById('gTitle').value || '').trim();
      const scen = scenById(sid);
      if (!title) title = (scen ? scen.name : 'میز') + ' · ' + count + ' نفره';
      const roles = Object.keys(qtys).map((id) => ({ id: Number(id), qty: qtys[id] }));
      const sessId = (document.getElementById('gSess') || {}).value || '';
      const sess = sessionById(sessId);
      const pickGender = sess ? genderGate(sess.audience) : 'all';
      const players = sess ? playersOfSession(sess) : [];
      const seats = fillSeatsFromPlayers(count, players);
      const night = { id: uid(), title, scenId: sid, catId: scen ? scen.category_id : 0, count, roles, seats, created: Date.now(), sessionId: sessId, pickGender };
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
      return;
    }

    const hostId = (hash().match(/^\/n\/([^/]+)\/host$/) || [])[1];
    if (hostId) {
      const n = getNight(hostId);
      if (!n) return;
      const h = ensureHost(n);
      if (!e.target.closest('.host-more')) {
        app.querySelectorAll('.host-menu').forEach((m) => { m.hidden = true; });
      }
      if (e.target.closest('[data-export-recent]')) {
        exportNight(n).then((ok) => {
          if (!ok) alert(online() ? 'نرفت. اسم‌ها باید با پروفایل بازیکن یکی باشد.' : 'آنلاین شو و دوباره بفرست.');
          paintHost(hostId);
        });
        return;
      }
      if (e.target.closest('[data-host-start]')) {
        const end = gameEnd(n);
        if (end && end.winner) {
          h.winner = end.winner;
          h.endWhy = end.why;
          h.phase = 'ended';
          putNight(n);
          paintHost(hostId);
          return;
        }
        if (end && end.hand) {
          h.phase = 'hand';
          h.handFrom = 0;
          h.handTo = 0;
          putNight(n);
          paintHost(hostId);
          return;
        }
        h.nightNum = (h.nightNum || 0) + 1;
        h.steps = buildSteps(n);
        h.step = 0;
        h.last = {};
        if (!h.steps.length) {
          h.phase = 'morning';
          h.log.unshift({ night: h.nightNum, msgs: ['نقشی برای بیدار شدن در این شب نماند.'] });
          putNight(n);
          paintHost(hostId);
          return;
        }
        h.phase = 'wake';
        h.remain = h.steps[0].seconds || 30;
        h.paused = false;
        h.nominated = [];
        armClock(h, 'night', h.remain);
        putNight(n);
        paintHost(hostId);
        return;
      }
      if (e.target.closest('[data-host-intro]')) {
        h.nightNum = 1;
        h.phase = 'intro';
        armClock(h, 'night', 30);
        putNight(n);
        paintHost(hostId);
        return;
      }
      if (e.target.closest('[data-intro-done]')) {
        h.introDone = true;
        h.phase = 'lobby';
        h.talkI = 0;
        armClock(h, 'turn');
        h.log.unshift({ night: 1, msgs: ['شب معارفه تمام شد. مافیا همدیگر را دیدند.'] });
        putNight(n);
        paintHost(hostId);
        return;
      }
      if (e.target.closest('[data-defend-start]')) {
        const defs = nomSeats(n);
        if (!defs.length) return;
        h.nominated = defs.map((s) => s.no);
        h.phase = 'defend';
        h.defendI = 0;
        armClock(h, 'defend');
        putNight(n);
        paintHost(hostId);
        return;
      }
      if (e.target.closest('[data-defend-next]')) {
        h.defendI = (h.defendI || 0) + 1;
        armClock(h, 'defend');
        putNight(n);
        paintHost(hostId);
        return;
      }
      if (e.target.closest('[data-vote2-start]')) {
        h.phase = 'vote2';
        h.vote2pick = 0;
        putNight(n);
        paintHost(hostId);
        return;
      }
      const vote2btn = e.target.closest('[data-vote2]');
      if (vote2btn) {
        const no = Number(vote2btn.getAttribute('data-vote2')) || 0;
        h.vote2pick = String(h.vote2pick || '') === String(no) ? 0 : no;
        putNight(n);
        paintHost(hostId);
        return;
      }
      if (e.target.closest('[data-vote2-skip]')) {
        h.phase = 'lobby';
        h.nominated = [];
        h.vote2pick = 0;
        h.defendI = 0;
        armClock(h, 'turn');
        putNight(n);
        paintHost(hostId);
        return;
      }
      if (e.target.closest('[data-vote2-out]')) {
        const no = Number(h.vote2pick) || 0;
        const s = (n.seats || []).find((x) => x.no === no);
        if (!s) return;
        applyKill(n, no, seatName(s) + ' با رای ۲ بیرون رفت.');
        if (h.phase === 'vote2') {
          h.phase = 'lobby';
          h.nominated = [];
          h.vote2pick = 0;
          h.defendI = 0;
          armClock(h, 'turn');
        }
        putNight(n);
        paintHost(hostId);
        return;
      }
      const clkStart = e.target.closest('[data-clk-start]');
      if (clkStart) {
        const k = clkStart.getAttribute('data-clk-start');
        ensureClocks(h);
        const c = h.clocks[k];
        if (!c) return;
        if (c.remain <= 0) c.remain = c.total || CLK[k].total;
        c.running = true;
        c.paused = false;
        putNight(n);
        paintClockNums(h);
        startTick(hostId);
        return;
      }
      const clkPause = e.target.closest('[data-clk-pause]');
      if (clkPause) {
        const k = clkPause.getAttribute('data-clk-pause');
        ensureClocks(h);
        const c = h.clocks[k];
        if (!c) return;
        c.paused = true;
        c.running = false;
        putNight(n);
        paintClockNums(h);
        return;
      }
      const clkReset = e.target.closest('[data-clk-reset]');
      if (clkReset) {
        const k = clkReset.getAttribute('data-clk-reset');
        armClock(h, k);
        putNight(n);
        paintClockNums(h);
        return;
      }
      if (e.target.closest('[data-talk-next]')) {
        const live = living(n);
        if (live.length) h.talkI = ((h.talkI || 0) + 1) % live.length;
        armClock(h, 'turn');
        putNight(n);
        paintHost(hostId);
        return;
      }
      if (e.target.closest('[data-host-pause]')) {
        h.paused = !h.paused;
        putNight(n);
        paintHost(hostId);
        return;
      }
      if (e.target.closest('[data-host-plus]')) {
        h.remain = (h.remain || 0) + 15;
        putNight(n);
        paintHost(hostId);
        return;
      }
      if (e.target.closest('[data-host-ready]')) {
        const st = (h.steps || [])[h.step] || {};
        if (st.actorsDead || st.kind === 'announce') {
          advanceHost(n);
          paintHost(hostId);
          return;
        }
        h.phase = 'pick';
        putNight(n);
        paintHost(hostId);
        return;
      }
      if (e.target.closest('[data-host-noted]')) {
        advanceHost(n);
        paintHost(hostId);
        return;
      }
      if (e.target.closest('[data-host-lobby]')) {
        if (h.pendingEnd) {
          h.phase = 'ended';
          h.pendingEnd = false;
        } else if (h.pendingHand) {
          h.phase = 'hand';
          h.pendingHand = false;
          h.handFrom = 0;
          h.handTo = 0;
        } else {
          h.phase = 'lobby';
          h.talkI = 0;
          armClock(h, 'turn');
        }
        putNight(n);
        paintHost(hostId);
        return;
      }
      const handFrom = e.target.closest('[data-hand-from]');
      if (handFrom) {
        h.handFrom = Number(handFrom.getAttribute('data-hand-from')) || 0;
        putNight(n);
        paintHost(hostId);
        return;
      }
      const handTo = e.target.closest('[data-hand-to]');
      if (handTo) {
        h.handTo = Number(handTo.getAttribute('data-hand-to')) || 0;
        const a = (n.seats || []).find((s) => String(s.no) === String(h.handFrom));
        const b = (n.seats || []).find((s) => String(s.no) === String(h.handTo));
        const mafiaHit = (a && seatRole(a).side === 'mafia') || (b && seatRole(b).side === 'mafia');
        h.winner = mafiaHit ? 'mafia' : 'citizen';
        h.endWhy = (a ? seatName(a) : 'یک نفر') + ' دست داد به ' + (b ? seatName(b) : 'یک نفر') + '.';
        h.log.unshift({ night: h.nightNum || 0, msgs: [h.endWhy, 'برنده: ' + winnerFa(h.winner)] });
        h.phase = 'ended';
        putNight(n);
        paintHost(hostId);
        return;
      }
      if (e.target.closest('[data-toggle-roles]')) {
        h.hideRoles = !h.hideRoles;
        putNight(n);
        paintHost(hostId);
        return;
      }
      const moreBtn = e.target.closest('[data-more]');
      if (moreBtn) {
        const menu = moreBtn.parentElement && moreBtn.parentElement.querySelector('.host-menu');
        const open = menu && !menu.hidden;
        app.querySelectorAll('.host-menu').forEach((m) => { m.hidden = true; });
        if (menu) menu.hidden = !!open;
        return;
      }
      const warnPlus = e.target.closest('[data-warn-plus]');
      const warnMinus = e.target.closest('[data-warn-minus]');
      if (warnPlus || warnMinus) {
        const no = (warnPlus || warnMinus).getAttribute(warnPlus ? 'data-warn-plus' : 'data-warn-minus');
        let v = warnOf(n, no) + (warnPlus ? 1 : -1);
        if (v < 0) v = 0;
        if (v > 9) v = 9;
        h.warns[String(no)] = v;
        putNight(n);
        paintHost(hostId);
        return;
      }
      const nomBtn = e.target.closest('[data-nom]');
      if (nomBtn) {
        const no = Number(nomBtn.getAttribute('data-nom')) || 0;
        const on = !(h.nominated || []).map(String).includes(String(no));
        h.nominated = (h.nominated || []).filter((x) => String(x) !== String(no));
        if (on) h.nominated.push(no);
        h.nominated.sort((a, b) => a - b);
        putNight(n);
        nomBtn.classList.toggle('is-on', on);
        const row = nomBtn.closest('.host-seat');
        if (row) row.classList.toggle('is-nom', on);
        const noms = nomSeats(n);
        const act = document.getElementById('hostDayAct');
        const hint = document.getElementById('hostDayHint');
        if (act) {
          if (noms.length) {
            act.textContent = 'شروع دفاع (' + fa(noms.length) + ' نفر)';
            act.setAttribute('data-defend-start', '');
            act.removeAttribute('data-host-start');
          } else {
            act.textContent = 'شروع شب ' + fa((h.nightNum || 0) + 1);
            act.setAttribute('data-host-start', '');
            act.removeAttribute('data-defend-start');
          }
        }
        if (hint) {
          hint.textContent = noms.length
            ? 'چند نفر را برای دفاع علامت بزن — بعد شروع دفاع.'
            : 'دایره کنار اسم را پر کن اگر کسی باید دفاع کند. اگر کسی نیست، شب را شروع کن.';
        }
        return;
      }
      const kick = e.target.closest('[data-kick]');
      if (kick) {
        const no = Number(kick.getAttribute('data-kick'));
        const s = (n.seats || []).find((x) => x.no === no);
        if (!s) return;
        openAsk({
          title: 'Kick شود؟',
          body: seatName(s),
          yes: 'Kick',
          no: 'خیر',
          danger: true,
          onYes: () => {
            const n2 = getNight(hostId);
            if (!n2) return;
            const h2 = ensureHost(n2);
            if (!isKicked(n2, no)) h2.kicked.push(no);
            h2.nominated = (h2.nominated || []).filter((x) => String(x) !== String(no));
            h2.log.unshift({ night: h2.nightNum || 0, msgs: [seatName(s) + ' kick شد.'] });
            putNight(n2);
            paintHost(hostId);
          }
        });
        return;
      }
      const kill = e.target.closest('[data-kill]') || e.target.closest('[data-day-kill]');
      if (kill) {
        const no = Number(kill.getAttribute('data-kill') || kill.getAttribute('data-day-kill'));
        const s = (n.seats || []).find((x) => x.no === no);
        if (!s) return;
        openAsk({
          title: 'Kill شود؟',
          body: seatName(s),
          yes: 'Kill',
          no: 'خیر',
          danger: false,
          onYes: () => {
            const n2 = getNight(hostId);
            if (!n2) return;
            applyKill(n2, no, seatName(s) + ' kill شد.');
            putNight(n2);
            paintHost(hostId);
          }
        });
        return;
      }
      const seatBtn = e.target.closest('[data-pick-seat]');
      if (seatBtn) {
        applyPick(n, seatBtn.getAttribute('data-pick-seat'));
        paintHost(hostId);
        return;
      }
    }
  });

  app.addEventListener('change', (e) => {
    if (e.target.id === 'gCount') fillRoles();
    if (e.target.id === 'gSess') {
      const sess = sessionById(e.target.value);
      if (!sess) return;
      const n = Math.max(4, Math.min(24, (playersOfSession(sess).length || 10)));
      const inp = document.getElementById('gCount');
      if (inp) inp.value = String(n);
      const t = document.getElementById('gTitle');
      if (t && !(t.value || '').trim()) t.value = sess.title || '';
      fillRoles();
    }
  });
  app.addEventListener('input', (e) => {
    if (e.target.matches('[data-name]')) fillNameSuggest(e.target);
  });
  app.addEventListener('focusin', (e) => {
    if (e.target.matches('[data-name]')) fillNameSuggest(e.target);
  });

  window.addEventListener('hashchange', route);
  window.addEventListener('online', route);
  window.addEventListener('offline', route);
  window.addEventListener('beforeunload', stopTick);
  route();
})();
