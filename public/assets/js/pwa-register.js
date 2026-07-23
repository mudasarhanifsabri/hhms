(function () {
  var installPrompt = null;
  var liveTaskPollTimer = null;
  var liveTaskSeen = {};
  var liveTaskInitialized = false;
  var bellAudioReady = false;

  if (!('serviceWorker' in navigator)) {
    // Menu and notification permission still work without a service worker.
  } else {
    window.addEventListener('load', function () {
      navigator.serviceWorker.register('/sw.js').catch(function () {
        // PWA registration is best-effort; normal web use should continue.
      });
    });
  }

  window.addEventListener('beforeinstallprompt', function (event) {
    event.preventDefault();
    installPrompt = event;
    setInstallButtonsReady(true);
  });

  document.addEventListener('DOMContentLoaded', function () {
    lockMaintainerZoom();
    setInstallButtonsReady(false);
    syncCostForm();
    notifyVisibleAlerts();
    initLiveTaskAlerts();
  });

  document.addEventListener('click', function (event) {
    var openButton = event.target.closest('[data-pwa-menu-open]');
    var closeButton = event.target.closest('[data-pwa-menu-close]');
    var installButton = event.target.closest('[data-install-pwa]');
    var notificationButton = event.target.closest('[data-enable-notifications]');

    if (openButton) {
      document.body.classList.add('pwa-menu-open');
    }

    if (closeButton) {
      document.body.classList.remove('pwa-menu-open');
    }

    if (installButton && installPrompt) {
      installPrompt.prompt();
      installPrompt.userChoice.finally(function () {
        installPrompt = null;
        setInstallButtonsReady(false);
      });
    } else if (installButton) {
      showInstallHelp();
    }

    if (notificationButton && 'Notification' in window) {
      if (!window.isSecureContext) {
        updateNotificationState('insecure');
        return;
      }

      Notification.requestPermission().then(function (permission) {
        updateNotificationState(permission);
        if (permission === 'granted') {
          new Notification('HHMS Tasks', {
            body: 'Notifications are enabled for new jobs.',
            icon: '/assets/images/logo-sm.png'
          });
        }
      });
    } else if (notificationButton) {
      updateNotificationState('unsupported');
    }

    if (event.target.closest('[data-install-help-close]')) {
      document.body.classList.remove('pwa-install-help-open');
    }
  });

  ['click', 'touchstart'].forEach(function (eventName) {
    document.addEventListener(eventName, function () {
      bellAudioReady = true;
    }, { once: true, passive: true });
  });

  function updateNotificationState(permission) {
    document.querySelectorAll('[data-notification-state]').forEach(function (node) {
      node.textContent = permission === 'insecure'
        ? 'Browser notifications need HTTPS on mobile. In-app task alerts will still show here.'
        : permission === 'unsupported'
          ? 'This browser does not support notifications.'
          : permission === 'granted'
        ? 'Notifications enabled on this device.'
        : permission === 'denied'
          ? 'Notifications are blocked in browser settings.'
          : 'Notifications are not enabled yet.';
    });
  }

  if ('Notification' in window) {
    updateNotificationState(window.isSecureContext ? Notification.permission : 'insecure');
  }

  document.addEventListener('change', function (event) {
    if (event.target.matches('input[name="type"]')) {
      syncCostForm();
      return;
    }

    var input = event.target.closest('[data-upload-preview]');
    if (!input) {
      return;
    }

    var container = findPreviewContainer(input);
    if (!container) {
      return;
    }

    container.innerHTML = '';
    Array.prototype.slice.call(input.files || []).forEach(function (file) {
      var item = document.createElement('div');
      item.className = 'pwa-upload-item';

      var thumb = document.createElement('div');
      thumb.className = 'pwa-upload-thumb';
      thumb.innerHTML = '<i class="ri-file-line"></i>';

      var details = document.createElement('div');
      var name = document.createElement('span');
      name.className = 'pwa-upload-name';
      name.textContent = file.name;
      var progress = document.createElement('div');
      progress.className = 'pwa-upload-progress';
      var bar = document.createElement('span');
      progress.appendChild(bar);
      details.appendChild(name);
      details.appendChild(progress);

      var check = document.createElement('div');
      check.className = 'pwa-upload-check';
      check.innerHTML = '<i class="ri-check-line"></i>';

      item.appendChild(thumb);
      item.appendChild(details);
      item.appendChild(check);
      container.appendChild(item);

      if (file.type && file.type.indexOf('image/') === 0 && !input.hasAttribute('data-safe-preview')) {
        var reader = new FileReader();
        reader.onload = function (readerEvent) {
          thumb.style.backgroundImage = 'url("' + readerEvent.target.result + '")';
          thumb.innerHTML = '';
        };
        reader.readAsDataURL(file);
      } else if (file.type && file.type.indexOf('image/') === 0) {
        thumb.innerHTML = '<i class="ri-image-line"></i>';
      }

      requestAnimationFrame(function () {
        bar.style.width = '35%';
        setTimeout(function () { bar.style.width = '75%'; }, 120);
        setTimeout(function () {
          bar.style.width = '100%';
          item.classList.add('is-complete');
        }, 260);
      });
    });
  });

  document.addEventListener('submit', function (event) {
    var form = event.target;
    if (!form.querySelector('[data-upload-preview]')) {
      return;
    }

    var hasFiles = Array.prototype.some.call(form.querySelectorAll('[data-upload-preview]'), function (input) {
      return input.files && input.files.length;
    });

    if (!hasFiles) {
      return;
    }

    ensureSubmitState();
    document.body.classList.add('pwa-submitting');
    form.querySelectorAll('button[type="submit"]').forEach(function (button) {
      button.disabled = true;
      button.dataset.originalText = button.textContent;
      button.textContent = 'Uploading...';
    });
  });

  function syncCostForm() {
    var selected = document.querySelector('input[name="type"]:checked');
    if (!selected) {
      return;
    }

    document.querySelectorAll('[data-cost-field]').forEach(function (field) {
      var types = field.getAttribute('data-cost-field').split(/\s+/);
      var isActive = types.indexOf(selected.value) !== -1;
      field.hidden = !isActive;
      field.querySelectorAll('input, select, textarea').forEach(function (input) {
        input.disabled = !isActive;
      });
    });

    document.querySelectorAll('[data-cost-submit]').forEach(function (button) {
      var label = selected.value.charAt(0).toUpperCase() + selected.value.slice(1);
      button.textContent = 'Save ' + label;
    });
  }

  function findPreviewContainer(input) {
    var field = input.closest('.pwa-field');
    if (field) {
      return field.querySelector('[data-upload-preview-list]');
    }

    var label = input.closest('.pwa-file-line');
    if (label && label.nextElementSibling && label.nextElementSibling.matches('[data-upload-preview-list]')) {
      return label.nextElementSibling;
    }

    return null;
  }

  function setInstallButtonsReady(hasPrompt) {
    document.querySelectorAll('[data-install-pwa]').forEach(function (button) {
      button.hidden = false;
      button.dataset.nativeInstall = hasPrompt ? '1' : '0';
    });
  }

  function showInstallHelp() {
    ensureInstallHelp();
    document.body.classList.add('pwa-install-help-open');
    document.body.classList.remove('pwa-menu-open');
  }

  function ensureInstallHelp() {
    if (document.querySelector('.pwa-install-help')) {
      return;
    }

    var isIos = /iphone|ipad|ipod/i.test(navigator.userAgent || '');
    var steps = isIos
      ? ['Tap Share in Safari.', 'Choose Add to Home Screen.', 'Tap Add.']
      : ['Open this page in Chrome.', 'Tap the browser menu.', 'Choose Install app or Add to Home screen.'];

    var modal = document.createElement('div');
    modal.className = 'pwa-install-help';
    modal.innerHTML = '<div class="pwa-install-card"><h3>Install Maintainer App</h3><p>The browser did not show the automatic install prompt.</p><ol>' +
      steps.map(function (step) { return '<li>' + step + '</li>'; }).join('') +
      '</ol><button type="button" class="pwa-primary-button purple" data-install-help-close>Done</button></div>';
    document.body.appendChild(modal);
  }

  function ensureSubmitState() {
    if (document.querySelector('.pwa-submit-state')) {
      return;
    }

    var state = document.createElement('div');
    state.className = 'pwa-submit-state';
    state.innerHTML = '<i class="ri-upload-cloud-2-line"></i><span>Submitting uploads...</span>';
    document.body.appendChild(state);
  }

  function notifyVisibleAlerts() {
    if (!('Notification' in window) || Notification.permission !== 'granted' || !window.isSecureContext) {
      return;
    }

    var alertNode = document.querySelector('body.maintainer-pwa-shell .alert');
    if (!alertNode) {
      return;
    }

    var message = alertNode.textContent.replace(/\s+/g, ' ').trim();
    if (message) {
      new Notification('HHMS Tasks', { body: message, icon: '/assets/images/logo-sm.png' });
    }
  }

  function lockMaintainerZoom() {
    if (!document.body.classList.contains('maintainer-pwa-shell')) {
      return;
    }

    var lastTouchEnd = 0;

    document.addEventListener('gesturestart', function (event) {
      event.preventDefault();
    }, { passive: false });

    document.addEventListener('touchmove', function (event) {
      if (event.touches && event.touches.length > 1) {
        event.preventDefault();
      }
    }, { passive: false });

    document.addEventListener('touchend', function (event) {
      var now = Date.now();
      if (now - lastTouchEnd <= 300) {
        event.preventDefault();
      }
      lastTouchEnd = now;
    }, { passive: false });

    document.addEventListener('dblclick', function (event) {
      event.preventDefault();
    }, { passive: false });
  }

  function initLiveTaskAlerts() {
    if (!document.body.classList.contains('maintainer-pwa-shell')) {
      return;
    }

    pollLiveTasks();
    liveTaskPollTimer = window.setInterval(pollLiveTasks, 7000);
    document.addEventListener('visibilitychange', function () {
      if (!document.hidden) {
        pollLiveTasks();
      }
    });
  }

  function pollLiveTasks() {
    window.fetch('/maintainer/tasks/live', {
      headers: {
        'Accept': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      credentials: 'same-origin'
    })
      .then(function (response) {
        if (!response.ok) {
          throw new Error('Live task check failed');
        }
        return response.json();
      })
      .then(function (payload) {
        var tasks = payload.tasks || [];

        if (!liveTaskInitialized) {
          tasks.forEach(function (task) {
            liveTaskSeen[task.id] = true;
          });
          liveTaskInitialized = true;
          return;
        }

        tasks.slice().reverse().forEach(function (task) {
          if (liveTaskSeen[task.id]) {
            return;
          }

          liveTaskSeen[task.id] = true;
          showLiveTaskPopup(task);
          prependLiveTaskCard(task);
        });
      })
      .catch(function () {
        window.clearInterval(liveTaskPollTimer);
        liveTaskPollTimer = window.setInterval(pollLiveTasks, 15000);
      });
  }

  function showLiveTaskPopup(task) {
    ensureLiveTaskPopup();
    playBellTone();

    if ('vibrate' in navigator) {
      navigator.vibrate([220, 90, 220, 90, 320]);
    }

    var popup = document.querySelector('[data-live-task-popup]');
    popup.querySelector('[data-live-task-number]').textContent = task.number;
    popup.querySelector('[data-live-task-title]').textContent = task.title;
    popup.querySelector('[data-live-task-meta]').textContent = task.property + ' • ' + task.unit;
    popup.querySelector('[data-live-task-open]').setAttribute('href', task.url);
    popup.classList.add('show');

    window.clearTimeout(popup._hideTimer);
    popup._hideTimer = window.setTimeout(function () {
      popup.classList.remove('show');
    }, 14000);
  }

  function ensureLiveTaskPopup() {
    if (document.querySelector('[data-live-task-popup]')) {
      return;
    }

    var popup = document.createElement('div');
    popup.className = 'pwa-live-task-popup';
    popup.setAttribute('data-live-task-popup', '');
    popup.innerHTML = '<div class="pwa-live-task-bell"><i class="ri-notification-3-line"></i></div>' +
      '<div class="pwa-live-task-copy"><span data-live-task-number></span><strong>Task Received</strong><p data-live-task-title></p><small data-live-task-meta></small></div>' +
      '<a data-live-task-open href="#" class="pwa-live-task-open">Open</a>';
    document.body.appendChild(popup);
  }

  function prependLiveTaskCard(task) {
    var list = document.querySelector('.pwa-home-screen .pwa-task-list');
    if (!list || list.querySelector('[data-task-card-id="' + cssEscape(task.id) + '"]')) {
      return;
    }

    var empty = list.querySelector('.pwa-empty');
    if (empty) {
      empty.remove();
    }

    var card = document.createElement('a');
    card.href = task.url;
    card.className = 'pwa-task-card is-new-live';
    card.setAttribute('data-task-card-id', task.id);
    card.innerHTML = '<div class="pwa-task-card-head"><span class="pwa-chip purple">' + escapeHtml(task.number) + '</span><span class="pwa-status-pill">' + escapeHtml(task.status_label) + '</span></div>' +
      '<h3>' + escapeHtml(task.title) + '</h3>' +
      '<p>' + escapeHtml(task.property) + ' &bull; ' + escapeHtml(task.unit) + '</p>' +
      '<div class="pwa-task-card-meta"><span><small>Priority</small><strong class="priority-' + escapeHtml(task.priority) + '">' + escapeHtml(task.priority_label) + '</strong></span><span><small>Due Date</small><strong>' + escapeHtml(task.due_date) + '</strong></span></div>' +
      '<i class="ri-more-2-fill"></i>';
    list.prepend(card);
  }

  function playBellTone() {
    if (!bellAudioReady || !('AudioContext' in window || 'webkitAudioContext' in window)) {
      return;
    }

    var AudioContextClass = window.AudioContext || window.webkitAudioContext;
    var context = new AudioContextClass();
    var masterGain = context.createGain();
    masterGain.connect(context.destination);
    masterGain.gain.setValueAtTime(0.0001, context.currentTime);
    masterGain.gain.exponentialRampToValueAtTime(0.58, context.currentTime + 0.03);
    masterGain.gain.exponentialRampToValueAtTime(0.0001, context.currentTime + 2.6);

    [880, 1175, 880, 1320, 1046, 1320].forEach(function (frequency, index) {
      var oscillator = context.createOscillator();
      oscillator.type = 'sine';
      oscillator.frequency.setValueAtTime(frequency, context.currentTime + (index * 0.26));
      oscillator.connect(masterGain);
      oscillator.start(context.currentTime + (index * 0.26));
      oscillator.stop(context.currentTime + 0.42 + (index * 0.26));
    });

    window.setTimeout(function () {
      context.close();
    }, 3000);
  }

  function escapeHtml(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
      return {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
      }[char];
    });
  }

  function cssEscape(value) {
    if (window.CSS && CSS.escape) {
      return CSS.escape(value);
    }

    return String(value).replace(/"/g, '\\"');
  }
})();
