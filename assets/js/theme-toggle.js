(function () {
  'use strict';

  var KEY = 'khajatime-theme';
  var root = document.documentElement;

  function savedTheme() {
    try {
      return localStorage.getItem(KEY) === 'dark' ? 'dark' : 'light';
    } catch (e) {
      return 'light';
    }
  }

  function apply(theme) {
    root.setAttribute('data-theme', theme);
    try { localStorage.setItem(KEY, theme); } catch (e) {}
    var btn = document.querySelector('.kt-theme-toggle');
    if (btn) {
      var dark = theme === 'dark';
      btn.innerHTML = dark ? '☀️ Light' : '🌙 Dark';
      btn.setAttribute('aria-label', dark ? 'Switch to light mode' : 'Switch to dark mode');
      btn.setAttribute('title', dark ? 'Light mode' : 'Dark mode');
    }
  }

  /* Apply immediately so the page does not stay in the wrong theme. */
  apply(savedTheme());

  function addButton() {
    /* cart.php and kitchen-menu.php already contain their own toggle. */
    if (document.querySelector('.kt-theme-toggle')) {
      apply(savedTheme());
      return;
    }

    var btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'kt-theme-toggle';
    btn.addEventListener('click', function () {
      apply(root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
    });

    document.body.appendChild(btn);
    apply(root.getAttribute('data-theme') || 'light');
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addButton);
  } else {
    addButton();
  }
})();
