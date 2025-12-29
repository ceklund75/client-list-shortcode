document.addEventListener('DOMContentLoaded', function () {
  document.querySelectorAll('.client-tabs-container').forEach(function (container) {
    const nav = container.querySelectorAll('.client-tab-btn');
    const panels = container.querySelectorAll('.client-tabs-panel');

    nav.forEach(function (btn) {
      btn.addEventListener('click', function () {
        nav.forEach(b => b.classList.remove('active'));
        panels.forEach(p => p.classList.remove('active'));

        btn.classList.add('active');
        const panel = container.querySelector(
          '.client-tabs-panel[data-panel="' + btn.dataset.tab + '"]'
        );
        if (panel) panel.classList.add('active');
        
        nav.forEach(b => {
            b.setAttribute('aria-selected', b === btn ? 'true' : 'false');
            b.setAttribute('tabindex', b === btn ? '0' : '-1');
        });
      });
    });
  });
});