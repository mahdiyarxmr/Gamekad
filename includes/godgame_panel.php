<?php
$godScens = $boot['scenarios'] ?? [];
$firstScen = $godScens[0]['id'] ?? '';
?>
<style>
.god-setup{padding:18px 16px 22px;position:relative;z-index:2;background:#fff}
.god-setup.card::before{display:none}
.god-fields{display:grid;grid-template-columns:1fr;gap:16px;margin-bottom:18px}
.god-field{display:flex;flex-direction:column;gap:8px;min-width:0}
.god-lab{display:block;color:#21295a;font-size:13px;font-weight:800}
.god-input{
  width:100%;box-sizing:border-box;background:#fff;color:#292827;
  border:2px solid rgba(33,41,90,.22);border-radius:14px;padding:14px 16px;
  font-size:16px;outline:none;-webkit-appearance:none;appearance:none;color-scheme:light;font-weight:700
}
.god-input:focus{border-color:#58bdaf;box-shadow:0 0 0 3px rgba(88,189,175,.25)}
.god-input::placeholder{color:#8a89a0}
.god-count{display:grid;grid-template-columns:52px 1fr 52px;gap:8px}
.god-count .god-step{
  height:52px;border-radius:14px;border:2px solid #21295a;background:#21295a;
  color:#fff;font-size:24px;font-weight:800;cursor:pointer
}
.god-count .god-input{text-align:center;font-size:22px;font-weight:800;color:#21295a;height:52px;padding:0}
.god-scens{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin:8px 0 16px}
.god-scen{
  display:flex;flex-direction:column;align-items:flex-start;gap:4px;min-height:76px;
  text-align:right;padding:14px;border-radius:.8375rem 0 .8375rem 0;cursor:pointer;
  border:2px solid #21295a;background:#fff;color:#292827;box-shadow:4px 5px 0 0 #21295a
}
.god-scen b{font-size:15px;color:#21295a}
.god-scen small{color:#5b5a78;font-size:11px;font-weight:700}
.god-scen.is-on{background:#21295a;color:#fff;box-shadow:4px 5px 0 0 #58bdaf}
.god-scen.is-on b{color:#fff}
.god-lock{width:100%;margin-top:16px;min-height:48px}
.god-sum{margin:12px 0;padding:10px 12px;border:2px solid #21295a;border-radius:12px;color:#21295a;background:#fff;font-weight:700}
.god-sum.is-ok{border-color:#347e75;color:#347e75;background:#f2faf9}
.god-sum.is-over{border-color:#e0195b;color:#ce1754;background:#fefafb}
.role-picks{display:grid;gap:8px}
.role-pick{display:flex;align-items:center;justify-content:space-between;gap:12px;border:2px solid rgba(33,41,90,.16);border-radius:14px;padding:10px 12px;background:#fff}
.role-pick.is-on{border-color:#58bdaf;background:#f2faf9}
.role-pick b{display:block;color:#21295a}
.role-pick small{color:#5b5a78;font-size:12px}
.role-step{display:flex;align-items:center;gap:6px}
.role-step button{width:44px;height:44px;border-radius:12px;border:2px solid #21295a;background:#21295a;color:#fff;font-size:22px;cursor:pointer}
.role-step input{width:56px;text-align:center;background:#fff;border:2px solid rgba(33,41,90,.18);color:#292827;border-radius:10px;padding:10px 0;font-size:16px;-webkit-appearance:none;appearance:none;color-scheme:light}
@media (min-width:720px){
  .god-fields{grid-template-columns:1.35fr .65fr}
  .god-scens{grid-template-columns:repeat(4,1fr)}
}
.god-tabs{display:grid;grid-template-columns:1fr 1fr;gap:8px;margin:0 0 16px}
@media (min-width:720px){.god-tabs{grid-template-columns:repeat(4,1fr)}}
.god-tabs .tab{border:0;cursor:pointer;font:inherit;display:flex;align-items:center;justify-content:center;min-height:44px;width:100%;text-align:center;padding:8px 6px}
.host-card{padding:22px 18px 24px}
.host-card h2{color:var(--primary);font-weight:950;margin:6px 0 0}
.host-clocks{display:grid;grid-template-columns:minmax(0,1fr) minmax(0,1.55fr) minmax(0,1fr);gap:8px;align-items:end;margin:12px 0 14px}
.host-clock{border:2px solid var(--primary);border-radius:16px;padding:10px 8px 12px;background:#fff;text-align:center}
.host-clock.is-main{background:var(--bg-lavender);box-shadow:4px 5px 0 0 var(--primary)}
.host-clock-lab{font-size:11px;font-weight:800;color:var(--secondary-dark);margin-bottom:4px}
.host-clock-who{font-size:12px;font-weight:800;color:var(--primary);margin:0 0 4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.host-clock-num{font-size:28px;line-height:1;font-weight:950;color:var(--primary);font-family:IRANSans,sans-serif;direction:ltr}
.host-clock.is-main .host-clock-num{font-size:clamp(40px,7vw,64px)}
.host-clock-num.is-zero,.host-clock.is-zero .host-clock-num{color:var(--accent)}
.host-clock-btns{display:flex;gap:8px;justify-content:center;flex-wrap:wrap;margin-top:8px}
.host-clock-btns .btn,.host-clock-btns .clk-ico{
  flex:0 0 42px;width:42px;height:42px;min-width:42px;min-height:42px;padding:0;
  border-radius:50%;display:grid;place-items:center;box-shadow:2px 2px 0 0 var(--primary)
}
.host-clock-btns .clk-ico svg{display:block}
.host-clock.is-main .host-clock-btns .btn{flex-basis:48px;width:48px;height:48px;min-width:48px;min-height:48px}
.host-players-head{display:flex;align-items:center;gap:8px;margin:22px 0 10px;flex-wrap:wrap}
.host-players-head h3{margin:0}
.host-role-eye{position:relative;left:auto;top:auto;transform:none;width:40px;height:40px;border:2px solid var(--primary);border-radius:50%;background:#fff;flex-shrink:0}
.host-role-ph{font-size:12px;font-weight:800;color:var(--muted)}
.host-talk-next{margin-top:8px;width:100%}
.host-seat.is-talk{outline:2px solid var(--secondary);background:var(--bg-mint)}
.warn-step{display:flex;flex-direction:column;align-items:stretch;border:2px solid var(--primary);border-radius:10px;overflow:hidden;min-width:88px}
.warn-lab{display:block;text-align:center;font-size:10px;font-weight:800;color:var(--muted);background:var(--bg-lavender);padding:2px 4px;letter-spacing:0}
.warn-row{display:flex;align-items:center}
.warn-row button{width:28px;height:28px;border:0;background:var(--primary);color:#fff;font-weight:900;font-size:16px;cursor:pointer;line-height:1}
.warn-row span{min-width:28px;text-align:center;font-weight:900;color:var(--primary);background:#fff;font-size:12px}
@media (max-width:720px){
  .host-clocks{grid-template-columns:1fr}
  .host-clock.is-side{display:none}
  .host-clock.is-main{box-shadow:none}
  .host-seat{
    grid-template-columns:28px 36px minmax(0,1fr) 32px;
    grid-template-areas:"handle no who more" "handle no tools dot";
    align-items:center;
  }
  .host-handle{grid-area:handle;align-self:stretch;display:flex;align-items:center}
  .host-seat > .seat-no{grid-area:no;align-self:start;margin-top:6px}
  .host-who{grid-area:who}
  .host-who b,.host-who small{white-space:normal;overflow:visible;text-overflow:unset;word-break:break-word}
  .host-more{grid-area:more;justify-self:end;align-self:start}
  .warn-step{grid-area:tools;justify-self:start;min-width:96px}
  .host-dot,.host-dot-ph{grid-area:dot;justify-self:end}
}
.host-wake{text-align:center;padding:8px 0 4px}
.host-wake h3{font-size:clamp(22px,5vw,34px);color:var(--primary);font-weight:950;margin:8px 0 10px}
.host-kicker{letter-spacing:.2em;font-size:12px;color:var(--secondary-dark);font-weight:800;font-family:IRANSans,sans-serif;direction:ltr}
.host-timer{font-size:72px;line-height:1;font-weight:950;color:var(--primary);font-family:IRANSans,sans-serif}
.host-timer.is-zero{color:var(--accent)}
.host-sec{color:var(--muted);font-weight:700;margin:0 0 16px}
.host-actions,.host-picks{display:flex;flex-wrap:wrap;gap:10px;justify-content:center}
.host-pick{flex-direction:column;min-width:min(46%,160px);padding:12px 14px}
.host-pick small{color:var(--muted);font-size:11px}
.host-alive{display:grid;gap:10px}
.host-seat{display:grid;grid-template-columns:28px 36px minmax(0,1fr) auto auto 22px;align-items:center;gap:8px;border:2px solid var(--primary);border-radius:14px;padding:8px 10px;background:#fff;user-select:none}
.host-seat-pick{grid-template-columns:36px minmax(0,1fr) 22px}
.host-seat b{display:block;color:var(--primary)}
.host-seat small{color:var(--muted)}
.host-who{min-width:0}
.host-who b,.host-who small{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.host-handle{cursor:grab;letter-spacing:-.12em;font-weight:900;color:var(--muted);padding:8px 2px;touch-action:none;font-size:16px;line-height:1}
.host-seat.is-drag{opacity:.55}
.host-seat.is-nom{background:var(--bg-lavender)}
.host-seat.is-kicked{opacity:.72;background:var(--bg-blush)}
.warn-step{display:flex;flex-direction:column;align-items:stretch;border:2px solid var(--primary);border-radius:10px;overflow:hidden;min-width:88px}
.warn-lab{display:block;text-align:center;font-size:10px;font-weight:800;color:var(--muted);background:var(--bg-lavender);padding:2px 4px}
.warn-row{display:flex;align-items:center}
.warn-row button{width:28px;height:28px;border:0;background:var(--primary);color:#fff;font-weight:900;font-size:16px;cursor:pointer;line-height:1}
.warn-row span{min-width:28px;text-align:center;font-weight:900;color:var(--primary);background:#fff;font-size:12px}
.host-dots{border:0;background:transparent;font-size:22px;font-weight:900;padding:4px 6px;color:var(--primary);line-height:1;cursor:pointer}
.host-more{position:relative}
.host-menu{position:absolute;left:0;top:100%;z-index:12;background:#fff;border:2px solid var(--primary);border-radius:12px;box-shadow:4px 5px 0 0 var(--primary);min-width:128px;padding:6px}
.host-menu button{display:block;width:100%;border:0;background:transparent;color:var(--primary);font-weight:800;font-size:16px;padding:10px 12px;text-align:right;cursor:pointer}
.host-menu .host-kick,.host-menu .host-kick b{color:var(--accent);font-weight:950}
.host-menu hr{border:0;border-top:2px solid var(--line);margin:4px 8px}
.host-dot,.host-dot-ph{width:22px;height:22px;min-width:22px;min-height:22px;border-radius:50%;box-sizing:border-box;padding:0;margin:0;flex-shrink:0;align-self:center}
.host-dot{border:2px solid var(--primary);background:#fff;cursor:pointer;appearance:none;-webkit-appearance:none;display:inline-block}
.host-dot.is-on{background:var(--primary)}
.host-dot-ph{border:0;background:transparent}
.host-actions .host-start{width:auto;flex:1;min-width:140px}
.host-ask{position:fixed;inset:0;z-index:90;background:rgba(33,41,90,.48);display:grid;place-items:center;padding:20px;opacity:0;transition:opacity .22s ease}
.host-ask.is-on{opacity:1}
.host-ask-card{background:#fff;border:2px solid var(--primary);border-radius:22px;padding:22px 20px;width:min(92vw,360px);text-align:center;box-shadow:8px 10px 0 0 var(--primary);transform:translateY(18px) scale(.96);transition:transform .28s cubic-bezier(.2,.8,.2,1)}
.host-ask.is-on .host-ask-card{transform:none}
.host-ask-card h3{color:var(--primary);font-weight:950;margin:0 0 8px;font-size:22px}
.host-ask-card p{margin:0 0 16px;font-weight:700;color:var(--ink)}
.host-ask-actions{display:flex;gap:10px;justify-content:center}
.host-ask-actions .btn{flex:1;min-height:48px}
@media (max-width:520px){
  .host-seat{
    grid-template-columns:28px 36px minmax(0,1fr) 32px;
    grid-template-areas:"handle no who more" "handle no tools dot";
  }
}
.host-msg,.host-log{border:2px solid var(--primary);border-radius:14px;padding:12px 14px;margin:8px 0;background:#fff;font-weight:700;color:var(--primary)}
.host-start{min-height:52px;width:100%}
.host-morning{text-align:center}
.host-morning h3{color:var(--primary);font-size:28px;font-weight:950}
.host-dead-note{margin:0 0 8px;color:var(--muted);font-size:12px;font-weight:700}
.host-intel h3{font-size:clamp(20px,5vw,32px);line-height:1.7}
@media (max-width:720px){
  .host-clocks{grid-template-columns:1fr}
  .host-clock.is-side{display:none}
  .host-who b,.host-who small{white-space:normal;overflow:visible;text-overflow:unset;word-break:break-word}
  .host-seat{
    grid-template-columns:28px 36px minmax(0,1fr) 32px;
    grid-template-areas:"handle no who more" "handle no tools dot";
  }
  .host-handle{grid-area:handle}
  .host-seat > .seat-no{grid-area:no}
  .host-who{grid-area:who}
  .host-more{grid-area:more}
  .warn-step{grid-area:tools}
  .host-dot,.host-dot-ph{grid-area:dot}
  .host-seat-pick,.host-wake .host-seat-pick{
    display:flex !important;
    grid-template-columns:none !important;
    grid-template-areas:none !important;
    align-items:center;
    gap:10px;
    text-align:right;
  }
  .host-seat-pick > .seat-no,
  .host-seat-pick > .host-who,
  .host-seat-pick > .host-dot{grid-area:auto !important}
  .host-seat-pick .host-who{flex:1;min-width:0;text-align:right}
  .host-seat-pick .host-who b,
  .host-seat-pick .host-who small{
    display:block;white-space:nowrap !important;overflow:hidden !important;
    text-overflow:ellipsis !important;word-break:normal !important
  }
}
.host-rank{display:grid;grid-template-columns:28px 36px minmax(0,1fr) auto;align-items:center;gap:8px;border:2px solid var(--primary);border-radius:14px;padding:10px 12px;background:#fff;user-select:none}
.host-rank.is-win{background:var(--bg-mint)}
.host-rank.is-dead{opacity:.85}
.host-rank .host-who b,.host-rank .host-who small{overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.rank-pts{color:var(--primary);font-weight:950;direction:ltr;font-size:16px}
.host-seat-pick{
  display:flex !important;
  grid-template-columns:none !important;
  grid-template-areas:none !important;
  align-items:center;
  gap:10px;
  text-align:right;
}
.host-seat-pick > .seat-no,
.host-seat-pick > .host-who,
.host-seat-pick > .host-dot{grid-area:auto !important}
.host-seat-pick .host-who{flex:1;min-width:0;text-align:right}
.host-seat-pick .host-who b,
.host-seat-pick .host-who small{
  display:block;white-space:nowrap !important;overflow:hidden !important;
  text-overflow:ellipsis !important;word-break:normal !important
}
</style>
<div id="godApp" data-badge="<?= e(url('admin/badges')) ?>" data-lb="<?= e(url('admin/leaderboard')) ?>" data-recent="<?= e(url('admin/recent')) ?>" data-csrf="<?= e(csrf_token()) ?>">
  <div id="godHome"></div>
  <div id="godNew" hidden>
    <p><button type="button" class="btn btn-ghost btn-sm" data-go="#/">میزها</button></p>
    <div class="card god-setup">
      <div class="god-fields">
        <div class="god-field">
          <label class="god-lab" for="gTitle">عنوان شب</label>
          <input id="gTitle" class="god-input" type="text" placeholder="مثلاً مافیا پنجشنبه" autocomplete="off" value="">
        </div>
        <div class="god-field">
          <label class="god-lab" for="gCount">تعداد بازیکن</label>
          <div class="god-count">
            <button type="button" class="god-step" data-count-delta="-1" aria-label="کم">−</button>
            <input id="gCount" class="god-input" type="number" min="4" max="24" inputmode="numeric" value="10">
            <button type="button" class="god-step" data-count-delta="1" aria-label="زیاد">+</button>
          </div>
        </div>
        <div class="god-field" style="grid-column:1/-1">
          <label class="god-lab" for="gSess">از رزرو کدام نشست؟</label>
          <select id="gSess" class="god-input">
            <option value="">بدون رزرو — اسم‌ها دستی</option>
            <?php foreach (($boot['sessions'] ?? []) as $gs):
                $np = count($gs['players'] ?? []);
            ?>
              <option value="<?= (int)$gs['id'] ?>"><?= e($gs['title']) ?> · <?= e(audience_fa((string)($gs['audience'] ?? 'boys'))) ?> · <?= fa_num($np) ?> رزرو · <?= e(jdate_format(($gs['session_date'] ?? '') . ' ' . ($gs['start_time'] ?? ''))) ?></option>
            <?php endforeach; ?>
          </select>
          <small style="color:#5b5a78;font-weight:700;display:block;margin-top:6px">اسم‌های رزروشده می‌آیند. اگر کسی نیامد بعداً اسم را عوض کن. دختر وارد میز پسر نمی‌شود مگر ترکیبی باشد.</small>
        </div>
      </div>
      <div class="god-lab">سناریو</div>
      <div class="god-scens" id="gScens">
        <?php foreach ($godScens as $i => $s): ?>
          <button type="button" class="god-scen<?= $i === 0 ? ' is-on' : '' ?>" data-pick-scen="<?= (int)$s['id'] ?>">
            <b><?= e($s['name']) ?></b>
            <small><?= e($s['cat_name'] ?? 'مافیا') ?></small>
          </button>
        <?php endforeach; ?>
      </div>
      <p class="god-sum" id="godSum">نقش‌ها را تا سقف تعداد بازیکن بچین.</p>
      <div id="godRoles"></div>
      <button type="button" class="btn btn-solid god-lock" id="gLock">قفل نقش‌ها و لیست اسم</button>
    </div>
  </div>
  <div id="godWork"></div>
</div>
<script>window.GOD_BOOT = <?= json_encode($boot ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;</script>
<script src="<?= e(asset('js/god-night.js')) ?>?v=d17"></script>
