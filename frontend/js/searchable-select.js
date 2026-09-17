/* ============================================================
   FILE: frontend/js/searchable-select.js
   Turns any <select class="searchable"> into a dropdown you can
   TYPE to filter. No libraries. Works with options added later
   (e.g. students loaded from an API) — it watches for changes.

   Usage:
     1) add   class="searchable"   to a <select>
     2) include this file:  <script src="../../js/searchable-select.js"></script>
   The original <select> still holds the value, so all existing
   code (its .value, its onchange) keeps working unchanged.
   ============================================================ */
(function () {
  function build(select) {
    if (select.dataset.ssReady) return;      // already enhanced
    select.dataset.ssReady = "1";

    // Wrapper
    const wrap = document.createElement('div');
    wrap.className = 'ss-wrap';
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);
    select.style.display = 'none';           // hide the native select

    // Button that shows the current choice
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'ss-button form-input';
    wrap.appendChild(btn);

    // Panel with search box + list
    const panel = document.createElement('div');
    panel.className = 'ss-panel';
    panel.style.display = 'none';
    panel.innerHTML =
      '<input type="text" class="ss-search" placeholder="Type to search…">' +
      '<div class="ss-list"></div>';
    wrap.appendChild(panel);

    const search = panel.querySelector('.ss-search');
    const list   = panel.querySelector('.ss-list');

    function currentLabel() {
      const o = select.options[select.selectedIndex];
      return o ? o.textContent : '';
    }
    function refreshButton() {
      btn.textContent = currentLabel() || '— Select —';
      btn.classList.toggle('ss-placeholder', !select.value);
    }

    function renderList(filter) {
      const f = (filter || '').toLowerCase();
      list.innerHTML = '';
      let any = false;
      Array.from(select.options).forEach((opt, i) => {
        if (opt.value === '' && opt.textContent.trim().startsWith('—')) return; // skip the placeholder row
        const text = opt.textContent;
        if (f && !text.toLowerCase().includes(f)) return;
        any = true;
        const item = document.createElement('div');
        item.className = 'ss-item' + (i === select.selectedIndex ? ' ss-active' : '');
        item.textContent = text;
        item.addEventListener('mousedown', (e) => {
          e.preventDefault();
          select.selectedIndex = i;
          select.dispatchEvent(new Event('change', { bubbles: true }));
          refreshButton();
          close();
        });
        list.appendChild(item);
      });
      if (!any) {
        const empty = document.createElement('div');
        empty.className = 'ss-empty';
        empty.textContent = 'No matches';
        list.appendChild(empty);
      }
    }

    function open() {
      panel.style.display = 'block';
      search.value = '';
      renderList('');
      setTimeout(() => search.focus(), 0);
    }
    function close() { panel.style.display = 'none'; }

    btn.addEventListener('click', () => {
      panel.style.display === 'block' ? close() : open();
    });
    search.addEventListener('input', () => renderList(search.value));
    search.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') { close(); btn.focus(); }
      if (e.key === 'Enter') {
        const first = list.querySelector('.ss-item');
        if (first) { first.dispatchEvent(new MouseEvent('mousedown')); }
      }
    });
    document.addEventListener('click', (e) => {
      if (!wrap.contains(e.target)) close();
    });

    // Keep the button in sync if options change (API loads students later)
    const mo = new MutationObserver(() => refreshButton());
    mo.observe(select, { childList: true });
    // Also if code sets .value programmatically
    select.addEventListener('change', refreshButton);

    refreshButton();
  }

  function initAll() {
    document.querySelectorAll('select.searchable').forEach(build);
  }

  // Exposed so code that injects a NEW <select class="searchable"> after page
  // load (e.g. into a modal via innerHTML) can turn it into a search box too.
  window.enhanceSearchable = initAll;

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initAll);
  } else {
    initAll();
  }
  // expose in case a page adds selects dynamically
  window.initSearchableSelects = initAll;
})();