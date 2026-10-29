/* أثر — admin panel scripts */
(function () {
  'use strict';
  const html = document.documentElement;
  const ADMIN = html.dataset.admin || '/admin';
  const BASE = html.dataset.base || '';
  const CSRF = html.dataset.csrf || '';
  const LANG = html.dataset.lang || 'en';
  const T = window.ATHAR_I18N || {};
  const $ = (s, r) => (r || document).querySelector(s);
  const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
  const sprite = (name) => { const t = $('#icon-sprite'); if (!t) return ''; const n = t.content.querySelector('[data-icon="' + name + '"]'); return n ? n.innerHTML : ''; };
  const esc = (s) => String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
  const mediaUrl = (p) => (!p ? '' : (/^(https?:)?\/\//.test(p) || p.startsWith('data:') ? p : BASE + '/' + p.replace(/^\//, '')));

  /* ---------- toasts ---------- */
  const toast = (msg, type) => {
    const box = $('#toasts'); if (!box) return;
    const el = document.createElement('div'); el.className = 'toast toast--' + (type || 'info'); el.textContent = msg; box.appendChild(el);
    setTimeout(() => { el.classList.add('is-leaving'); setTimeout(() => el.remove(), 400); }, 3800);
  };
  $$('#toasts .toast').forEach((el) => setTimeout(() => { el.classList.add('is-leaving'); setTimeout(() => el.remove(), 400); }, 4200));

  /* ---------- fetch helper ---------- */
  const api = async (url, opts) => {
    opts = opts || {};
    const headers = Object.assign({ 'X-CSRF-Token': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, opts.headers || {});
    if (opts.json) { headers['Content-Type'] = 'application/json'; opts.body = JSON.stringify(Object.assign({ _token: CSRF }, opts.json)); }
    const res = await fetch(url, { method: opts.method || 'POST', headers, body: opts.body, credentials: 'same-origin' });
    const data = await res.json().catch(() => ({}));
    if (!res.ok || data.ok === false) throw new Error(data.error || T.error || 'Error');
    return data;
  };

  /* ---------- sidebar ---------- */
  $$('[data-sidebar-open]').forEach((b) => b.addEventListener('click', () => document.body.classList.add('sidebar-open')));
  $$('[data-sidebar-close]').forEach((b) => b.addEventListener('click', () => document.body.classList.remove('sidebar-open')));

  /* ---------- modals ---------- */
  const openModal = (m) => { if (!m) return; m.classList.add('is-open'); m.setAttribute('aria-hidden', 'false'); const f = m.querySelector('input:not([type=hidden]), textarea, select, button'); f && setTimeout(() => f.focus(), 50); };
  const closeModal = (m) => { if (!m) return; m.classList.remove('is-open'); m.setAttribute('aria-hidden', 'true'); };
  document.addEventListener('click', (e) => {
    const o = e.target.closest('[data-modal-open]'); if (o) { e.preventDefault(); openModal($(o.dataset.modalOpen)); return; }
    const c = e.target.closest('[data-modal-close]'); if (c) { closeModal(c.closest('.modal')); }
  });
  document.addEventListener('keydown', (e) => { if (e.key === 'Escape') $$('.modal.is-open').forEach(closeModal); });

  /* ---------- confirm ---------- */
  const confirmDialog = (text) => new Promise((resolve) => {
    const m = $('#confirm-modal'); if (!m) { resolve(window.confirm(text)); return; }
    $('[data-confirm-text]', m).textContent = text;
    const ok = $('[data-confirm-ok]', m);
    const done = (v) => { closeModal(m); ok.replaceWith(ok.cloneNode(true)); resolve(v); };
    ok.addEventListener('click', () => done(true), { once: true });
    $$('[data-modal-close]', m).forEach((b) => b.addEventListener('click', () => done(false), { once: true }));
    openModal(m);
  });
  document.addEventListener('submit', (e) => {
    const f = e.target.closest('form[data-confirm]');
    if (!f || f.dataset.confirmed) return;
    e.preventDefault();
    confirmDialog(f.dataset.confirm).then((ok) => { if (ok) { f.dataset.confirmed = '1'; f.requestSubmit(); } });
  });

  /* ---------- unsaved changes guard ---------- */
  $$('form[data-dirty-guard]').forEach((form) => {
    let dirty = false;
    form.addEventListener('input', () => { dirty = true; });
    form.addEventListener('submit', () => { dirty = false; });
    window.addEventListener('beforeunload', (e) => { if (dirty) { e.preventDefault(); e.returnValue = ''; } });
  });

  /* ---------- slug helper ---------- */
  const slugify = (s) => s.toLowerCase().trim().replace(/[ً-ٰٟ]/g, '').replace(/[^\p{L}\p{N}]+/gu, '-').replace(/^-+|-+$/g, '');
  $$('[data-slug-from]').forEach((slug) => {
    const srcEn = $('#' + slug.dataset.slugFrom); const srcAr = $('#' + slug.dataset.slugFrom.replace(/_en$/, '_ar'));
    const fill = () => { if (slug.dataset.touched || slug.value) return; const v = (srcEn && srcEn.value) || (srcAr && srcAr.value) || ''; slug.value = slugify(v); };
    srcEn && srcEn.addEventListener('blur', fill); srcAr && srcAr.addEventListener('blur', fill);
    slug.addEventListener('input', () => { slug.dataset.touched = '1'; });
  });

  /* ---------- character counters + SERP preview ---------- */
  const bindCount = (el) => {
    const c = document.createElement('div'); c.className = 'char-count'; el.after(c);
    const max = el.name.includes('description') ? 160 : (el.name.includes('title') ? 60 : 0);
    const upd = () => { const n = el.value.length; c.textContent = (T.char_count || ':n').replace(':n', n) + (max ? ' / ' + max : ''); c.classList.toggle('is-warn', max && n > max); };
    el.addEventListener('input', upd); upd();
  };
  $$('[data-count]').forEach(bindCount);
  const serp = $('[data-serp]');
  if (serp) {
    const upd = () => {
      const t = $('[name="meta_title[' + LANG + ']"]') || $('[name="meta_title[ar]"]'); const t2 = $('[name="title[' + LANG + ']"]') || $('[name="title[ar]"]');
      const d = $('[name="meta_description[' + LANG + ']"]') || $('[name="meta_description[ar]"]');
      $('[data-serp-title]', serp).textContent = (t && t.value) || (t2 && t2.value) || '';
      $('[data-serp-desc]', serp).textContent = (d && d.value) || '';
    };
    document.addEventListener('input', (e) => { if (/^(meta_title|meta_description|title)\[/.test(e.target.name || '')) upd(); });
    upd();
  }

  /* ---------- icon picker ---------- */
  document.addEventListener('change', (e) => {
    const sel = e.target.closest('[data-icon-select]'); if (!sel) return;
    const prev = sel.closest('.icon-picker').querySelector('[data-icon-preview]');
    fetch(BASE + '/assets/icons/' + sel.value + '.svg').then((r) => r.text()).then((svg) => { prev.innerHTML = svg.replace('<svg', '<svg class="icon"'); }).catch(() => {});
  });

  /* ---------- rich text (Quill) ---------- */
  const initRichtext = (root) => {
    if (typeof Quill === 'undefined') { $$('[data-richtext]', root).forEach((w) => { const ta = w.querySelector('textarea'); ta.hidden = false; ta.className = 'input'; ta.rows = 8; }); return; }
    $$('[data-richtext]', root).forEach((w) => {
      if (w.dataset.ready) return; w.dataset.ready = '1';
      const ta = w.querySelector('textarea'); const ed = w.querySelector('.richtext__editor');
      const q = new Quill(ed, { theme: 'snow', modules: { toolbar: [[{ header: [2, 3, false] }], ['bold', 'italic', 'underline'], [{ list: 'ordered' }, { list: 'bullet' }], ['link', 'blockquote'], [{ direction: 'rtl' }, { align: [] }], ['clean']] } });
      if (ta.value) q.clipboard.dangerouslyPasteHTML(ta.value);
      if (w.dir === 'rtl') { q.format('direction', 'rtl'); q.format('align', 'right'); }
      q.on('text-change', () => { ta.value = q.getSemanticHTML ? q.getSemanticHTML() : ed.querySelector('.ql-editor').innerHTML; ta.dispatchEvent(new Event('input', { bubbles: true })); });
      w._quill = q;
    });
  };
  const whenReady = (fn) => { if (document.readyState === 'complete') fn(); else window.addEventListener('load', fn); };
  whenReady(() => initRichtext(document));

  /* ---------- repeaters (name-indexed inputs) ---------- */
  let counter = Date.now() % 100000;
  const addRepeaterItem = (name, values) => {
    const list = $('[data-repeater="' + name + '"]'); const tpl = $('[data-repeater-template="' + name + '"]'); if (!list || !tpl) return;
    const htmlStr = tpl.innerHTML.replace(/__i__/g, 'n' + (++counter));
    const wrap = document.createElement('div'); wrap.innerHTML = htmlStr; const item = wrap.firstElementChild;
    list.appendChild(item); initRichtext(item); $$('[data-count]', item).forEach(bindCount);
    const first = item.querySelector('input:not([type=hidden]), textarea'); first && first.focus();
  };
  document.addEventListener('click', (e) => {
    const add = e.target.closest('[data-repeater-add]'); if (add) { e.preventDefault(); addRepeaterItem(add.dataset.repeaterAdd); return; }
    const rm = e.target.closest('[data-repeater-remove]'); if (rm) { e.preventDefault(); rm.closest('[data-repeater-item]').remove(); }
  });
  const sortables = () => {
    if (typeof Sortable === 'undefined') return;
    $$('[data-repeater]').forEach((l) => { if (!l._s) l._s = new Sortable(l, { handle: '[data-handle]', animation: 180, ghostClass: 'sortable-ghost' }); });
    $$('[data-sortable-rows]').forEach((table) => {
      const tbody = table.querySelector('tbody'); if (!tbody || tbody._s) return;
      tbody._s = new Sortable(tbody, { handle: '.td-drag', animation: 180, ghostClass: 'sortable-ghost', onEnd: () => {
        const order = $$('tr[data-id]', tbody).map((tr) => tr.dataset.id);
        api(table.dataset.reorderUrl, { json: { order } }).then(() => toast(T.saved, 'success')).catch((err) => toast(err.message, 'error'));
      } });
    });
  };
  whenReady(sortables);

  /* ---------- image fields + media picker ---------- */
  let pickTarget = null;
  const setImage = (field, url) => {
    const input = $('[data-image-input]', field); const prev = $('[data-image-preview]', field);
    input.value = url || ''; prev.innerHTML = url ? '<img src="' + esc(mediaUrl(url)) + '" alt="">' : ''; field.classList.toggle('has-image', !!url);
    input.dispatchEvent(new Event('input', { bubbles: true }));
  };
  document.addEventListener('click', (e) => {
    const pick = e.target.closest('[data-image-pick]'); if (pick) { e.preventDefault(); openMedia((url) => setImage(pick.closest('[data-image-field]'), url)); return; }
    const clear = e.target.closest('[data-image-clear]'); if (clear) { e.preventDefault(); setImage(clear.closest('[data-image-field]'), ''); }
  });
  const mediaModal = $('#media-modal');
  let mediaPage = 1, mediaQuery = '', mediaTotal = 0;
  const renderMedia = (items, append) => {
    const grid = $('[data-media-grid]', mediaModal); if (!append) grid.innerHTML = '';
    if (!items.length && !append) grid.innerHTML = '<p class="empty">' + esc(T.no_media) + '</p>';
    items.forEach((m) => {
      const b = document.createElement('button'); b.type = 'button'; b.className = 'media-item'; b.title = T.select;
      b.innerHTML = '<img src="' + esc(mediaUrl(m.thumb)) + '" alt="" loading="lazy"><span class="media-item__name">' + esc(m.filename) + '</span>';
      b.addEventListener('click', () => { pickTarget && pickTarget(m.url); closeModal(mediaModal); });
      grid.appendChild(b);
    });
    const more = $('[data-media-more]', mediaModal); more.parentElement.hidden = grid.children.length >= mediaTotal; more.textContent = '+ ' + Math.max(0, mediaTotal - grid.children.length);
  };
  const loadMedia = (append) => {
    if (!append) mediaPage = 1; else mediaPage++;
    api(ADMIN + '/media/list?page=' + mediaPage + '&q=' + encodeURIComponent(mediaQuery), { method: 'GET' }).then((d) => { mediaTotal = d.total; renderMedia(d.items, append); }).catch((err) => toast(err.message, 'error'));
  };
  const openMedia = (cb) => {
    if (!mediaModal) return; pickTarget = cb; mediaQuery = ''; $('[data-media-search]', mediaModal).value = '';
    $$('[data-media-tabs] button', mediaModal).forEach((b) => b.classList.toggle('is-active', b.dataset.tab === 'library'));
    $$('[data-media-pane]', mediaModal).forEach((p) => p.hidden = p.dataset.mediaPane !== 'library');
    openModal(mediaModal); loadMedia(false);
  };
  if (mediaModal) {
    $('[data-media-more]', mediaModal).addEventListener('click', () => loadMedia(true));
    let t; $('[data-media-search]', mediaModal).addEventListener('input', (e) => { clearTimeout(t); t = setTimeout(() => { mediaQuery = e.target.value.trim(); loadMedia(false); }, 300); });
    $$('[data-media-tabs] button', mediaModal).forEach((b) => b.addEventListener('click', () => {
      $$('[data-media-tabs] button', mediaModal).forEach((x) => x.classList.toggle('is-active', x === b));
      $$('[data-media-pane]', mediaModal).forEach((p) => p.hidden = p.dataset.mediaPane !== b.dataset.tab);
    }));
    const dz = $('[data-media-dropzone]', mediaModal); const fileInput = dz.querySelector('input[type=file]');
    const uploadFiles = (files) => {
      if (!files.length) return;
      const fd = new FormData(); fd.append('_token', CSRF); Array.from(files).forEach((f) => fd.append('files[]', f));
      dz.classList.add('is-over');
      api(ADMIN + '/media/upload', { body: fd }).then((d) => {
        dz.classList.remove('is-over');
        if (d.errors && d.errors.length) toast(d.errors.join(' '), 'error');
        if (d.items && d.items.length) { toast(d.message, 'success'); pickTarget && pickTarget(d.items[0].url); closeModal(mediaModal); }
      }).catch((err) => { dz.classList.remove('is-over'); toast(err.message, 'error'); });
    };
    fileInput.addEventListener('change', () => uploadFiles(fileInput.files));
    dz.addEventListener('dragover', (e) => { e.preventDefault(); dz.classList.add('is-over'); });
    dz.addEventListener('dragleave', () => dz.classList.remove('is-over'));
    dz.addEventListener('drop', (e) => { e.preventDefault(); uploadFiles(e.dataTransfer.files); });
    $('[data-media-url-use]', mediaModal).addEventListener('click', () => { const u = $('[data-media-url]', mediaModal).value.trim(); if (/^https?:\/\//.test(u)) { pickTarget && pickTarget(u); closeModal(mediaModal); } });
  }

  /* ---------- media library page ---------- */
  const uploadForm = $('[data-upload-form]');
  if (uploadForm) {
    const dz = $('[data-dropzone]', uploadForm); const inp = $('[data-upload-input]', uploadForm);
    const send = (files) => {
      if (!files.length) return; const fd = new FormData(); fd.append('_token', CSRF); Array.from(files).forEach((f) => fd.append('files[]', f));
      dz.classList.add('is-over'); dz.querySelector('span').textContent = T.loading;
      api(ADMIN + '/media/upload', { body: fd }).then((d) => { if (d.errors && d.errors.length) toast(d.errors.join(' '), 'error'); if (d.items.length) { toast(d.message, 'success'); location.reload(); } else dz.classList.remove('is-over'); }).catch((err) => { toast(err.message, 'error'); dz.classList.remove('is-over'); });
    };
    inp.addEventListener('change', () => send(inp.files));
    dz.addEventListener('dragover', (e) => { e.preventDefault(); dz.classList.add('is-over'); });
    dz.addEventListener('dragleave', () => dz.classList.remove('is-over'));
    dz.addEventListener('drop', (e) => { e.preventDefault(); send(e.dataTransfer.files); });
  }
  const mdModal = $('#media-detail-modal');
  if (mdModal) {
    let current = null;
    document.addEventListener('click', (e) => {
      const b = e.target.closest('[data-media-detail]'); if (!b) return;
      current = JSON.parse(b.dataset.mediaDetail);
      $('[data-md-img]', mdModal).src = current.full; $('[data-md-url]', mdModal).value = current.full;
      $('[data-md-dim]', mdModal).textContent = current.width ? current.width + ' × ' + current.height : '—';
      $('[data-md-size]', mdModal).textContent = current.size ? (current.size / 1024).toFixed(0) + ' KB' : '—';
      $('[data-md-date]', mdModal).textContent = current.created_at;
      $('[data-md-alt-ar]', mdModal).value = (current.alt && current.alt.ar) || ''; $('[data-md-alt-en]', mdModal).value = (current.alt && current.alt.en) || '';
      $('[data-md-form]', mdModal).action = ADMIN + '/media/' + current.id;
      openModal(mdModal);
    });
    $('[data-md-delete]', mdModal).addEventListener('click', () => {
      confirmDialog(T.confirm_delete).then((ok) => { if (!ok) return; api(ADMIN + '/media/' + current.id + '/delete', { json: {} }).then(() => location.reload()).catch((err) => toast(err.message, 'error')); });
    });
  }
  document.addEventListener('click', (e) => {
    const c = e.target.closest('[data-copy-target]'); if (!c) return;
    const el = $(c.dataset.copyTarget); if (!el) return;
    navigator.clipboard.writeText(el.value || el.textContent).then(() => toast(T.copied, 'success'));
  });

  /* ---------- blocks editor ---------- */
  const editor = $('[data-blocks-editor]');
  if (editor) {
    const schema = JSON.parse(editor.dataset.schema); const icons = JSON.parse(editor.dataset.icons);
    let blocks = JSON.parse(editor.dataset.blocks || '[]');
    const lbl = (o) => (o && (o[LANG] || o.en || o.ar)) || '';
    const i18nInput = (name, label, type, val, rows) => {
      val = (val && typeof val === 'object') ? val : { ar: val || '', en: '' };
      const col = (l) => {
        const v = esc(val[l] || ''); const dir = l === 'ar' ? 'rtl' : 'ltr'; const n = name + '[' + l + ']';
        let inp;
        if (type === 'textarea' || type === 'code') inp = '<textarea class="input ' + (type === 'code' ? 'input--code' : '') + '" name="' + n + '" rows="' + (rows || 3) + '" dir="' + (type === 'code' ? 'ltr' : dir) + '">' + v + '</textarea>';
        else if (type === 'richtext') inp = '<div class="richtext" data-richtext dir="' + dir + '"><div class="richtext__editor"></div><textarea hidden name="' + n + '">' + v + '</textarea></div>';
        else inp = '<input class="input" type="text" name="' + n + '" value="' + v + '" dir="' + dir + '">';
        return '<div class="i18n-col"><span class="i18n-tag">' + (l === 'ar' ? 'عربي' : 'EN') + '</span>' + inp + '</div>';
      };
      return '<div class="field field--i18n"><div class="field__label"><label>' + esc(label) + '</label></div><div class="i18n-grid">' + col('ar') + col('en') + '</div></div>';
    };
    const renderField = (f, val, prefix) => {
      const name = prefix + '[' + f.name + ']'; const label = lbl(f.label);
      switch (f.type) {
        case 'text': case 'textarea': case 'richtext': case 'code':
          if (f.i18n || f.type === 'richtext' || f.type === 'code') return i18nInput(name, label, f.type, val, f.rows);
          return '<div class="field"><div class="field__label"><label>' + esc(label) + '</label></div><input class="input" type="text" name="' + name + '" value="' + esc(val || '') + '"></div>';
        case 'url': return '<div class="field"><div class="field__label"><label>' + esc(label) + '</label></div><input class="input" type="text" name="' + name + '" value="' + esc(val || '') + '" placeholder="' + esc(f.placeholder || '') + '" dir="ltr"></div>';
        case 'number': return '<div class="field"><div class="field__label"><label>' + esc(label) + '</label></div><input class="input" type="number" name="' + name + '" value="' + esc(val == null ? (f.default != null ? f.default : '') : val) + '" dir="ltr"></div>';
        case 'toggle': { const on = val == null ? !!f.default : (val === true || val === 'true' || val === 1 || val === '1'); return '<div class="field field--toggle"><label class="toggle"><input type="hidden" name="' + name + '" value="0"><input type="checkbox" name="' + name + '" value="1" ' + (on ? 'checked' : '') + '><span class="toggle__track"></span><span class="toggle__label">' + esc(label) + '</span></label></div>'; }
        case 'select': { let o = ''; Object.keys(f.options).forEach((k) => { o += '<option value="' + esc(k) + '" ' + ((val == null ? f.default : val) === k ? 'selected' : '') + '>' + esc(lbl(f.options[k])) + '</option>'; }); return '<div class="field"><div class="field__label"><label>' + esc(label) + '</label></div><select class="input" name="' + name + '">' + o + '</select></div>'; }
        case 'icon': { let o = '<option value="">' + esc(T.no_icon) + '</option>'; icons.forEach((ic) => { o += '<option value="' + ic + '" ' + (val === ic ? 'selected' : '') + '>' + ic + '</option>'; }); return '<div class="field"><div class="field__label"><label>' + esc(label) + '</label></div><div class="icon-picker"><span class="icon-picker__preview" data-icon-preview>' + (val ? '<img src="' + BASE + '/assets/icons/' + esc(val) + '.svg" width="20" height="20" alt="">' : '') + '</span><select class="input" name="' + name + '" data-icon-select>' + o + '</select></div></div>'; }
        case 'image': return '<div class="field"><div class="field__label"><label>' + esc(label) + '</label></div><div class="image-field ' + (val ? 'has-image' : '') + '" data-image-field><input type="hidden" name="' + name + '" value="' + esc(val || '') + '" data-image-input><div class="image-field__preview" data-image-preview>' + (val ? '<img src="' + esc(mediaUrl(val)) + '" alt="">' : '') + '</div><div class="image-field__actions"><button type="button" class="btn btn--ghost btn--sm" data-image-pick>' + sprite('image') + '<span>' + esc(T.choose_image) + '</span></button><button type="button" class="btn btn--ghost btn--sm btn--danger-text" data-image-clear>' + sprite('x') + '<span>' + esc(T.remove_image) + '</span></button></div></div></div>';
        case 'repeater': {
          const rows = Array.isArray(val) ? val : [];
          let items = ''; rows.forEach((row, i) => { items += repeaterItem(f, row, name + '[' + i + ']'); });
          return '<div class="field"><div class="field__label"><label>' + esc(label) + '</label></div><div class="repeater" data-block-repeater data-fields="' + esc(JSON.stringify(f.fields)) + '" data-name="' + esc(name) + '">' + items + '</div><button type="button" class="btn btn--ghost btn--sm" data-block-repeater-add>' + sprite('plus') + '<span>' + esc(T.add_item) + '</span></button></div>';
        }
      }
      return '';
    };
    const repeaterItem = (f, row, prefix) => {
      let inner = ''; f.fields.forEach((sf) => { inner += renderField(sf, row ? row[sf.name] : null, prefix); });
      return '<div class="repeater__item" data-repeater-item><div class="repeater__head"><span class="block__drag" data-handle>' + sprite('dots-six-vertical') + '</span><button type="button" class="btn btn--ghost btn--sm btn--danger-text" data-repeater-remove>' + sprite('x') + '</button></div><div class="repeater__body">' + inner + '</div></div>';
    };
    const summary = (b) => { const d = b.data || {}; const h = d.heading || d.title || d.text; return h ? lbl(h).replace(/\n/g, ' ').slice(0, 60) : ''; };
    const renderBlock = (b, i) => {
      const def = schema[b.type]; if (!def) return '';
      const prefix = 'blocks[' + i + '][data]';
      let fields = ''; def.fields.forEach((f) => { fields += renderField(f, (b.data || {})[f.name], prefix); });
      return '<div class="block ' + (b.hidden ? 'is-hidden-block' : '') + '" data-block data-type="' + esc(b.type) + '">' +
        '<input type="hidden" name="blocks[' + i + '][type]" value="' + esc(b.type) + '">' +
        '<div class="block__head"><span class="block__drag" data-handle>' + sprite('dots-six-vertical') + '</span>' +
        '<span class="block__type">' + sprite('block-' + b.type) + '<span>' + esc(lbl(def.label)) + '</span><span class="block__summary">' + esc(summary(b)) + '</span></span>' +
        '<div class="block__tools">' +
        '<button type="button" data-block-opts title="' + esc(T.block_settings) + '">' + sprite('gear') + '</button>' +
        '<button type="button" data-block-hide title="' + esc(T.hidden) + '" class="' + (b.hidden ? 'is-on' : '') + '">' + sprite(b.hidden ? 'eye-slash' : 'eye') + '</button>' +
        '<button type="button" data-block-up title="' + esc(T.move_up) + '">' + sprite('caret-up') + '</button>' +
        '<button type="button" data-block-down title="' + esc(T.move_down) + '">' + sprite('caret-down') + '</button>' +
        '<button type="button" data-block-collapse title="' + esc(T.collapse) + '">' + sprite('caret-' + (html.dir === 'rtl' ? 'left' : 'right')) + '</button>' +
        '<button type="button" data-block-remove title="' + esc(T.remove_block) + '">' + sprite('trash') + '</button></div></div>' +
        '<div class="block__body">' + fields + '</div>' +
        '<div class="block__options" hidden>' +
        '<div class="field"><div class="field__label"><label>' + esc(T.anchor) + '</label><span class="hint">' + esc(T.anchor_hint) + '</span></div><input class="input" type="text" name="blocks[' + i + '][anchor]" value="' + esc(b.anchor || '') + '" dir="ltr"></div>' +
        '<div class="field"><div class="field__label"><label>' + esc(T.theme) + '</label></div><select class="input" name="blocks[' + i + '][theme]"><option value="ink" ' + (b.theme !== 'ivory' ? 'selected' : '') + '>' + esc(T.theme_ink) + '</option><option value="ivory" ' + (b.theme === 'ivory' ? 'selected' : '') + '>' + esc(T.theme_ivory) + '</option></select></div>' +
        '<input type="hidden" name="blocks[' + i + '][hidden]" value="' + (b.hidden ? '1' : '0') + '" data-block-hidden>' +
        '</div></div>';
    };
    const renderAll = () => {
      editor.innerHTML = blocks.map(renderBlock).join('');
      $('[data-blocks-empty]').hidden = blocks.length > 0;
      initRichtext(editor); sortables();
      if (typeof Sortable !== 'undefined') {
        if (editor._s) editor._s.destroy();
        editor._s = new Sortable(editor, { handle: '.block__head > .block__drag', animation: 200, ghostClass: 'sortable-ghost', onEnd: () => markDirty() });
        $$('[data-block-repeater]', editor).forEach((l) => { if (!l._s) l._s = new Sortable(l, { handle: '[data-handle]', animation: 180, ghostClass: 'sortable-ghost' }); });
      }
    };
    const form = editor.closest('form');
    const markDirty = () => form.dispatchEvent(new Event('input', { bubbles: true }));
    // Serialise the DOM back into the blocks array (preserves live edits before re-rendering)
    const collect = () => {
      const out = [];
      $$('[data-block]', editor).forEach((el) => {
        const obj = { type: el.dataset.type, anchor: '', theme: 'ink', hidden: false, data: {} };
        $$('input, textarea, select', el).forEach((inp) => {
          if (!inp.name) return;
          const path = inp.name.replace(/^blocks\[\d+\]/, '').replace(/\]/g, '').split('[').filter(Boolean);
          if (inp.type === 'checkbox' && !inp.checked) return;
          if (inp.type === 'hidden' && inp.nextElementSibling && inp.nextElementSibling.type === 'checkbox' && inp.name === inp.nextElementSibling.name && inp.nextElementSibling.checked) return;
          let ref = obj; for (let k = 0; k < path.length - 1; k++) { const key = path[k]; const nextIsIdx = /^\d+$|^n\d+$/.test(path[k + 1]); if (ref[key] == null) ref[key] = nextIsIdx ? [] : {}; ref = ref[key]; }
          const last = path[path.length - 1];
          if (Array.isArray(ref)) { const idx = /^\d+$/.test(last) ? +last : ref.length; ref[idx] = inp.value; } else ref[last] = inp.value;
        });
        // normalise repeaters: arrays may be sparse/keyed with n123 -> object; convert objects with only numeric-ish keys into arrays
        const norm = (v) => { if (Array.isArray(v)) return v.filter((x) => x != null).map(norm); if (v && typeof v === 'object') { const keys = Object.keys(v); if (keys.length && keys.every((k) => /^\d+$|^n\d+$/.test(k))) return keys.map((k) => norm(v[k])); const o = {}; keys.forEach((k) => o[k] = norm(v[k])); return o; } return v; };
        obj.data = norm(obj.data || {}); obj.hidden = obj.hidden === '1' || obj.hidden === true; obj.anchor = obj.anchor || ''; obj.theme = obj.theme || 'ink';
        out.push(obj);
      });
      return out;
    };
    renderAll();
    // block picker
    const blockModal = $('#block-modal');
    $$('[data-add-block]').forEach((b) => b.addEventListener('click', () => openModal(blockModal)));
    $$('[data-block-type]', blockModal).forEach((b) => b.addEventListener('click', () => {
      blocks = collect(); const def = schema[b.dataset.blockType]; const data = {};
      def.fields.forEach((f) => { if (f.type === 'repeater') data[f.name] = []; else if (f.default != null) data[f.name] = f.default; });
      blocks.push({ type: b.dataset.blockType, anchor: '', theme: 'ink', hidden: false, data });
      renderAll(); closeModal(blockModal); markDirty();
      const last = editor.lastElementChild; last && last.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }));
    editor.addEventListener('click', (e) => {
      const blockEl = e.target.closest('[data-block]'); if (!blockEl) return;
      const idx = $$('[data-block]', editor).indexOf(blockEl);
      if (e.target.closest('[data-block-remove]')) { confirmDialog(T.confirm_remove_block).then((ok) => { if (ok) { blocks = collect(); blocks.splice(idx, 1); renderAll(); markDirty(); } }); return; }
      if (e.target.closest('[data-block-up]') && idx > 0) { blocks = collect(); [blocks[idx - 1], blocks[idx]] = [blocks[idx], blocks[idx - 1]]; renderAll(); markDirty(); return; }
      if (e.target.closest('[data-block-down]') && idx < blocks.length - 1) { blocks = collect(); [blocks[idx + 1], blocks[idx]] = [blocks[idx], blocks[idx + 1]]; renderAll(); markDirty(); return; }
      if (e.target.closest('[data-block-collapse]')) { blockEl.classList.toggle('is-collapsed'); return; }
      if (e.target.closest('[data-block-opts]')) { const o = $('.block__options', blockEl); o.hidden = !o.hidden; return; }
      const hideBtn = e.target.closest('[data-block-hide]');
      if (hideBtn) { const h = $('[data-block-hidden]', blockEl); const on = h.value !== '1'; h.value = on ? '1' : '0'; blockEl.classList.toggle('is-hidden-block', on); hideBtn.classList.toggle('is-on', on); hideBtn.innerHTML = sprite(on ? 'eye-slash' : 'eye'); markDirty(); return; }
      const addRep = e.target.closest('[data-block-repeater-add]');
      if (addRep) { const list = addRep.previousElementSibling; const f = { fields: JSON.parse(list.dataset.fields) }; const wrap = document.createElement('div'); wrap.innerHTML = repeaterItem(f, null, list.dataset.name + '[n' + (++counter) + ']'); const item = wrap.firstElementChild; list.appendChild(item); initRichtext(item); markDirty(); const first = item.querySelector('input:not([type=hidden]), textarea'); first && first.focus(); }
    });
    // on submit: serialise into the hidden JSON input and disable the raw inputs
    form.addEventListener('submit', () => {
      $$('[data-richtext]', editor).forEach((w) => { if (w._quill) w.querySelector('textarea').value = w._quill.getSemanticHTML ? w._quill.getSemanticHTML() : w.querySelector('.ql-editor').innerHTML; });
      $('[data-blocks-input]').value = JSON.stringify(collect());
      $$('input, textarea, select', editor).forEach((i) => { i.disabled = true; });
    });
  }

  /* ---------- SEO tasks kanban ---------- */
  const kanban = $('[data-kanban]');
  if (kanban) {
    const url = kanban.dataset.statusUrl; const modal = $('#task-modal'); const tform = $('[data-task-form]', modal);
    const clist = $('[data-checklist]', modal);
    const addCheck = (text, done) => { const row = document.createElement('div'); row.className = 'row'; row.innerHTML = '<input type="checkbox" ' + (done ? 'checked' : '') + '><input class="input input--sm" type="text" value="' + esc(text || '') + '"><button type="button" class="btn btn--ghost btn--sm btn--danger-text" data-check-remove>×</button>'; clist.appendChild(row); };
    $('[data-checklist-add]', modal).addEventListener('click', () => { addCheck('', false); clist.lastElementChild.querySelector('input[type=text]').focus(); });
    clist.addEventListener('click', (e) => { if (e.target.closest('[data-check-remove]')) e.target.closest('.row').remove(); });
    const fill = (t) => {
      tform.reset(); clist.innerHTML = '';
      tform.action = t ? url + '/' + t.id : url;
      $('[data-task-modal-title]', modal).textContent = t ? t.title : $('[data-task-new]').textContent.trim();
      ['title', 'description', 'category', 'priority', 'status', 'due_date', 'page_slug', 'url', 'notes'].forEach((k) => { const f = tform.elements[k]; if (f) f.value = (t && t[k]) || (k === 'priority' ? 'medium' : k === 'status' ? 'todo' : k === 'category' ? 'onpage' : ''); });
      ((t && t.checklist) || []).forEach((c) => addCheck(c.text, c.done));
      openModal(modal);
    };
    $('[data-task-new]').addEventListener('click', () => fill(null));
    tform.addEventListener('submit', () => { const items = $$('.row', clist).map((r) => ({ text: r.querySelector('input[type=text]').value, done: r.querySelector('input[type=checkbox]').checked })).filter((c) => c.text.trim()); $('[data-checklist-input]', modal).value = JSON.stringify(items); });
    kanban.addEventListener('click', (e) => {
      const card = e.target.closest('.task'); if (!card) return;
      const t = JSON.parse(card.dataset.task);
      if (e.target.closest('[data-task-edit]')) { fill(t); return; }
      if (e.target.closest('[data-task-done]')) { api(url + '/' + t.id + '/status', { json: { status: 'done' } }).then(() => location.reload()).catch((err) => toast(err.message, 'error')); return; }
      if (e.target.closest('[data-task-delete]')) { confirmDialog(T.confirm_delete).then((ok) => { if (ok) api(url + '/' + t.id + '/delete', { json: {} }).then(() => location.reload()).catch((err) => toast(err.message, 'error')); }); return; }
      if (!e.target.closest('button')) fill(t);
    });
    whenReady(() => {
      if (typeof Sortable === 'undefined') return;
      $$('[data-kanban-list]', kanban).forEach((list) => new Sortable(list, { group: 'kanban', animation: 200, ghostClass: 'sortable-ghost', onEnd: (ev) => {
        const id = ev.item.dataset.id; const status = ev.to.dataset.kanbanList; const order = $$('.task', ev.to).indexOf(ev.item);
        api(url + '/' + id + '/status', { json: { status, sort_order: (order + 1) * 10 } }).then(() => { ev.item.classList.toggle('is-done', status === 'done'); $$('.kanban__col', kanban).forEach((c) => { c.querySelector('.kanban__count').textContent = $$('.task', c).length; }); toast(T.saved, 'success'); }).catch((err) => { toast(err.message, 'error'); location.reload(); });
      } }));
    });
  }

  /* ---------- dashboard chart ---------- */
  const chartEl = $('#traffic-chart');
  if (chartEl) {
    whenReady(() => {
      if (typeof Chart === 'undefined') return;
      const daily = JSON.parse(chartEl.dataset.chart || '[]'); const labels = JSON.parse(chartEl.dataset.labels || '{}');
      const fmt = (d) => { const dt = new Date(d); return dt.toLocaleDateString(LANG === 'ar' ? 'ar-QA' : 'en-GB', { day: 'numeric', month: 'short' }); };
      const mk = (key, color, fill) => ({ label: labels[key], data: daily.map((d) => d[key]), borderColor: color, backgroundColor: fill, tension: .4, borderWidth: 2, pointRadius: daily.length > 40 ? 0 : 3, pointBackgroundColor: color, fill: !!fill });
      new Chart(chartEl, { type: 'line', data: { labels: daily.map((d) => fmt(d.day)), datasets: [mk('pageviews', '#B8954F', 'rgba(184,149,79,.12)'), mk('visitors', '#1B1915', null), mk('clicks', '#2E8B57', null)] },
        options: { responsive: true, interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8, font: { family: getComputedStyle(document.body).fontFamily } } } }, scales: { x: { grid: { display: false }, ticks: { maxTicksLimit: 10 } }, y: { beginAtZero: true, grid: { color: '#EFEBE1' }, ticks: { precision: 0 } } } } });
    });
  }
})();

/* appearance: colour picker <-> hex field */
(function () {
  document.querySelectorAll('[data-color-pick]').forEach((c) => {
    const inp = document.getElementById(c.dataset.colorPick); if (!inp) return;
    c.addEventListener('input', () => { inp.value = c.value.toUpperCase(); inp.dispatchEvent(new Event('input', { bubbles: true })); });
    inp.addEventListener('input', () => { if (/^#[0-9A-Fa-f]{6}$/.test(inp.value)) c.value = inp.value; });
  });
  document.querySelectorAll('[data-clear-input]').forEach((b) => b.addEventListener('click', () => { const inp = document.getElementById(b.dataset.clearInput); if (inp) { inp.value = ''; inp.dispatchEvent(new Event('input', { bubbles: true })); } }));
})();

/* ---------- email marketing ---------- */
(function () {
  const html = document.documentElement, CSRF = html.dataset.csrf || '', T = window.ATHAR_I18N || {};
  const $ = (s, r) => (r || document).querySelector(s), $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
  const toast = (m, t) => { const b = $('#toasts'); if (!b) return; const el = document.createElement('div'); el.className = 'toast toast--' + (t || 'info'); el.textContent = m; b.appendChild(el); setTimeout(() => { el.classList.add('is-leaving'); setTimeout(() => el.remove(), 400); }, 4000); };
  const post = async (url, body) => {
    const headers = { 'X-CSRF-Token': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/json' };
    const r = await fetch(url, { method: 'POST', headers, body: JSON.stringify(Object.assign({ _token: CSRF }, body || {})), credentials: 'same-origin' });
    const d = await r.json().catch(() => ({}));
    if (!r.ok || d.ok === false) throw new Error(d.error || T.error || 'Error');
    return d;
  };
  const postForm = async (url, fd) => {
    fd.set('_token', CSRF);
    const r = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-Token': CSRF, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } });
    return r.json();
  };

  /* live preview (campaign + template editors) */
  const bindPreview = (form) => {
    const ta = $('[data-html-editor]', form), frame = $('[data-preview-frame]', form); if (!ta || !frame) return null;
    const url = form.dataset.previewUrl;
    let t;
    const render = async () => {
      const fd = new FormData(); fd.append('_token', CSRF); fd.append('html', ta.value);
      fd.append('subject', (form.elements.subject && form.elements.subject.value) || ''); fd.append('preheader', (form.elements.preheader && form.elements.preheader.value) || '');
      try { const r = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', headers: { 'X-CSRF-Token': CSRF } }); frame.srcdoc = await r.text(); } catch (e) { /* ignore */ }
    };
    ta.addEventListener('input', () => { clearTimeout(t); t = setTimeout(render, 500); });
    render();
    $$('[data-merge]', form).forEach((b) => b.addEventListener('click', () => {
      const s = ta.selectionStart || 0, e = ta.selectionEnd || 0;
      ta.value = ta.value.slice(0, s) + b.dataset.merge + ta.value.slice(e);
      ta.focus(); ta.selectionStart = ta.selectionEnd = s + b.dataset.merge.length;
      ta.dispatchEvent(new Event('input', { bubbles: true }));
    }));
    return render;
  };
  $$('[data-template-form]').forEach(bindPreview);

  /* campaign editor */
  const cform = $('[data-campaign-form]');
  if (cform) {
    bindPreview(cform);
    const ta = $('[data-html-editor]', cform);
    const parse = (sel) => { const el = $(sel, cform); try { return JSON.parse(el ? el.innerHTML : '{}'); } catch (e) { return {}; } };
    const starters = parse('[data-starters]'), templates = parse('[data-templates]');
    const pick = $('[data-template-pick]', cform);
    pick && pick.addEventListener('change', () => {
      const v = pick.value; if (!v) return;
      const i = v.indexOf(':'); const kind = v.slice(0, i), key = v.slice(i + 1); const src = kind === 'tpl' ? templates[key] : starters[key];
      if (!src) { pick.value = ''; return; }
      if (ta.value.trim() && !confirm(T.leave_warning || 'Replace the current content?')) { pick.value = ''; return; }
      ta.value = src.html || '';
      if (src.subject && !cform.elements.subject.value) cform.elements.subject.value = src.subject;
      cform.elements.template_id.value = kind === 'tpl' ? key : '';
      ta.dispatchEvent(new Event('input', { bubbles: true })); pick.value = '';
    });
    const recount = () => {
      let n = 0;
      const skip = cform.elements.skip_risky && cform.elements.skip_risky.type === 'checkbox' ? cform.elements.skip_risky.checked : !!$('input[name="skip_risky"][type="checkbox"]:checked', cform);
      $$('input[name="list_ids[]"]:checked', cform).forEach((i) => { n += (+i.dataset.valid || 0) + (skip ? 0 : (+i.dataset.risky || 0)) + (+i.dataset.pending || 0); });
      const el = $('[data-recipients-count]', cform); if (el) el.textContent = n.toLocaleString();
      const senders = $$('input[name="sender_ids[]"]:checked', cform).length || 1;
      const rate = (+(cform.elements.throttle_per_hour && cform.elements.throttle_per_hour.value) || 150) * senders;
      const eta = $('[data-eta]'); if (eta) { const h = n / rate; eta.textContent = n ? (h < 1 ? Math.ceil(h * 60) + ' ' + (T.minutes || 'min') : h.toFixed(1) + ' ' + (T.hours || 'h')) : '—'; }
    };
    cform.addEventListener('change', recount); recount();
    const labels = window.ATHAR_SCORE_LABELS || {};
    const runScore = async () => {
      const url = cform.dataset.scoreUrl; if (!url) return;
      const btns = $$('[data-score-run]'); btns.forEach((b) => b.classList.add('is-busy'));
      try {
        const d = await postForm(url, new FormData(cform)); const s = d.score;
        $('[data-score-ring]').style.setProperty('--p', s.score); $('[data-score-value]').textContent = s.score; $('[data-score-grade]').textContent = s.grade;
        const list = $('[data-score-list]'); list.innerHTML = '';
        s.checks.forEach((c) => {
          const d2 = document.createElement('div'); d2.className = 'check-item check-item--' + c.state;
          d2.innerHTML = '<span class="icon-dot icon-dot--' + c.state + '"></span><div class="check-item__body"><strong></strong>' + (c.state !== 'pass' && c.tip ? '<div class="hint"></div>' : '') + '</div>';
          d2.querySelector('strong').textContent = labels[c.key] || c.key; const h = d2.querySelector('.hint'); if (h) h.textContent = c.tip; list.appendChild(d2);
        });
      } catch (e) { toast(e.message, 'error'); }
      btns.forEach((b) => b.classList.remove('is-busy'));
    };
    $$('[data-score-run]').forEach((b) => b.addEventListener('click', runScore));
    const testBtn = $('[data-test-send]');
    testBtn && testBtn.addEventListener('click', async () => {
      const msg = $('[data-test-msg]'); testBtn.classList.add('is-busy'); msg.textContent = T.loading || '…'; msg.style.color = '';
      const fd = new FormData(cform); fd.set('to', $('[data-test-to]').value); const first = $('input[name="sender_ids[]"]:checked', cform); fd.set('sender_id', first ? first.value : '');
      try { const d = await postForm(cform.dataset.testUrl, fd); msg.textContent = d.ok ? ('✓ ' + d.to) : ('✗ ' + (d.error || '')); msg.style.color = d.ok ? 'var(--ok)' : 'var(--danger)'; } catch (e) { msg.textContent = e.message; }
      testBtn.classList.remove('is-busy');
    });
    const sug = $('[data-use-suggested]'); sug && sug.addEventListener('click', () => { $('[data-schedule-input]').value = sug.dataset.useSuggested; });
  }

  /* campaign report: process batches + auto polling */
  const pf = $('[data-process-form]');
  if (pf) {
    const auto = $('[data-auto-send]'), msg = $('[data-process-msg]'); let busy = false, timer = null;
    const run = async () => {
      if (busy) return; busy = true; pf.querySelector('button').classList.add('is-busy');
      try {
        const d = await post(pf.action, {});
        Object.keys(d.stats || {}).forEach((k) => { const el = $('[data-stat="' + k + '"]'); if (el) el.textContent = (+d.stats[k]).toLocaleString(); });
        const total = +d.stats.total || 0, done = total ? Math.round(((+d.stats.sent) + (+d.stats.failed) + (+d.stats.skipped)) / total * 100) : 0;
        $('[data-progress-bar]').style.width = done + '%'; $('[data-progress-label]').textContent = done + '%';
        msg.textContent = '+' + d.sent + ' · ' + (d.remaining || 0) + ' left';
        if (d.done || d.status !== 'sending') { if (auto) auto.checked = false; if (d.done) setTimeout(() => location.reload(), 1200); }
      } catch (e) { msg.textContent = e.message; if (auto) auto.checked = false; }
      busy = false; pf.querySelector('button').classList.remove('is-busy');
    };
    pf.addEventListener('submit', (e) => { e.preventDefault(); run(); });
    const loop = () => { clearTimeout(timer); if (auto && auto.checked) { run().then(() => { timer = setTimeout(loop, 20000); }); } };
    auto && auto.addEventListener('change', loop);
    if (auto && auto.checked) timer = setTimeout(loop, 3000);
  }

  /* list verification with progress */
  const vc = $('[data-verify-card]');
  if (vc) {
    const url = vc.dataset.url, bar = $('[data-verify-bar]', vc), label = $('[data-verify-label]', vc);
    let running = false;
    const step = async (reset) => {
      const smtpBox = $('[data-verify-smtp]');
      const d = await post(url, { reset: reset ? 1 : 0, smtp: smtpBox && smtpBox.checked ? 1 : 0 });
      const s = d.summary || {}; const total = +s.total || 0, pending = +s.pending || 0;
      bar.style.width = (total ? Math.round((total - pending) / total * 100) : 0) + '%';
      label.textContent = (T.verifying || 'Verifying… :done / :total').replace(':done', total - pending).replace(':total', total);
      Object.keys(s).forEach((k) => { const el = $('[data-sum="' + k + '"]'); if (el) el.textContent = (+s[k]).toLocaleString(); });
      return pending;
    };
    $$('[data-verify-start]', vc).forEach((b) => b.addEventListener('click', async () => {
      if (running) return; running = true; $$('[data-verify-start]', vc).forEach((x) => x.classList.add('is-busy'));
      try { let pending = await step(!!b.dataset.reset); while (pending > 0) pending = await step(false); label.textContent = T.verified_all || 'Done'; setTimeout(() => location.reload(), 800); }
      catch (e) { toast(e.message, 'error'); }
      running = false; $$('[data-verify-start]', vc).forEach((x) => x.classList.remove('is-busy'));
    }));
  }
  const fi = $('[data-file-name]'); fi && fi.addEventListener('change', () => { const sp = fi.parentElement.querySelector('span'); if (fi.files[0]) sp.textContent = fi.files[0].name; });
})();

/* ---------- packages: live WhatsApp message preview ---------- */
(function () {
  const form = document.querySelector('[data-package-form]'); if (!form) return;
  const html = document.documentElement, CSRF = html.dataset.csrf || '';
  const url = form.dataset.previewUrl; const boxes = { ar: form.querySelector('[data-wa-preview="ar"]'), en: form.querySelector('[data-wa-preview="en"]') };
  let timer = null, inflight = null;
  const refresh = async () => {
    const fd = new FormData(form); fd.set('_token', CSRF);
    try {
      if (inflight) inflight.abort(); inflight = new AbortController();
      const r = await fetch(url, { method: 'POST', body: fd, credentials: 'same-origin', signal: inflight.signal, headers: { 'X-CSRF-Token': CSRF, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } });
      const d = await r.json();
      if (d && d.ok) { if (boxes.ar) boxes.ar.textContent = d.ar; if (boxes.en) boxes.en.textContent = d.en; }
    } catch (e) { /* ignore */ }
  };
  const watch = 'input[name^="name"], input[name="price"], select[name="currency"], select[name="category"], input[name="tier"], textarea[name^="whatsapp_message"], input[name="slug"], input[name^="price_prefix"]';
  form.addEventListener('input', (e) => { if (e.target.matches(watch)) { clearTimeout(timer); timer = setTimeout(refresh, 350); } });
  form.addEventListener('change', (e) => { if (e.target.matches(watch)) { clearTimeout(timer); timer = setTimeout(refresh, 100); } });
})();

