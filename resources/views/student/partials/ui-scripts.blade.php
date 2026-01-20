{{-- resources/views/student/partials/ui-scripts.blade.php --}}
<script>
  (function () {
    if (window.StudentUI) return;

    function ensureToastWrap() {
      let wrap = document.querySelector('.sp-toast-wrap');
      if (!wrap) {
        wrap = document.createElement('div');
        wrap.className = 'sp-toast-wrap';
        document.body.appendChild(wrap);
      }
      return wrap;
    }

    function toast(type, title, msg, timeoutMs) {
      const wrap = ensureToastWrap();

      const el = document.createElement('div');
      el.className = 'sp-toast ' + (type || 'info');

      const ico = document.createElement('div');
      ico.className = 'sp-toast-ico';
      ico.textContent = type === 'success' ? '✓' : (type === 'error' ? '!' : 'i');

      const body = document.createElement('div');
      const h = document.createElement('div');
      h.className = 'sp-toast-title';
      h.textContent = title || (type === 'success' ? 'Success' : (type === 'error' ? 'Error' : 'Info'));

      const p = document.createElement('div');
      p.className = 'sp-toast-msg';
      p.textContent = msg || '';

      body.appendChild(h);
      body.appendChild(p);

      el.appendChild(ico);
      el.appendChild(body);

      wrap.appendChild(el);

      const ms = typeof timeoutMs === 'number' ? timeoutMs : 2400;
      setTimeout(function () {
        try { el.remove(); } catch(e) {}
      }, ms);
    }

    function pad2(n){ return String(n).padStart(2,'0'); }
    function formatTime(seconds) {
      seconds = Number.isFinite(+seconds) ? parseInt(seconds, 10) : 0;
      seconds = Math.max(0, seconds);

      const h = Math.floor(seconds / 3600);
      const m = Math.floor((seconds % 3600) / 60);
      const s = seconds % 60;

      if (h > 0) return h + ':' + pad2(m) + ':' + pad2(s);
      return m + ':' + pad2(s);
    }

    function scrollToEl(el) {
      try {
        el.scrollIntoView({ behavior: 'smooth', block: 'start', inline: 'nearest' });
      } catch(e) {}
    }

    function redirect(url){ window.location.href = url; }

    window.StudentUI = {
      success: function (msg) { toast('success', 'Saved', msg || 'Saved'); },
      error: function (msg) { toast('error', 'Error', msg || 'Something went wrong'); },
      info: function (msg) { toast('info', 'Info', msg || ''); },
      confirm: function (msg) { return window.confirm(msg || 'Are you sure?'); },
      formatTime: formatTime,
      scrollTo: scrollToEl,
      redirect: redirect,
    };

    // Add a class to <body> for portal targeting + detect focus mode saved state
    try {
      document.body.classList.add('student-portal-body');
    } catch(e) {}
  })();
</script>
