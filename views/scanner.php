<a href="?" class="btn btn-outline-primary mb-3 small">Back to home</a>
<h1 class="mb-2">Guest check-in</h1>
<p class="text-muted mb-3">Scan a guest’s QR code. The guest will be checked in automatically.</p>
<div id="reader"></div>
<div class="scanner-status" role="status" aria-live="polite">
  <span id="scanner-spinner" class="bi-arrow-repeat scanner-spinner" aria-hidden="true"></span>
  <span id="scanner-loading">Starting rear camera… Allow camera access when prompted.</span>
  <button id="scanner-retry" type="button" class="btn btn-outline-primary btn-sm ms-2" hidden>Try camera again</button>
</div>
<div id="scan-result" class="scan-result alert d-none mt-3" role="status" aria-live="polite"></div>
<input type="hidden" id="scanner-csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

<script>
  (() => {
    const loading = document.getElementById('scanner-loading');
    const spinner = document.getElementById('scanner-spinner');
    const retryButton = document.getElementById('scanner-retry');
    const resultBox = document.getElementById('scan-result');
    const csrfToken = document.getElementById('scanner-csrf').value;
    let scanner;
    let processing = false;
    let starting = false;
    let previousCode = '';
    let previousCodeAt = 0;

    function showResult(type, title, details = []) {
      resultBox.className = `scan-result alert alert-${type} mt-3`;
      const heading = document.createElement('strong');
      heading.textContent = title;
      resultBox.replaceChildren(heading);
      for (const detail of details) {
        if (!detail.value) continue;
        const line = document.createElement('div');
        line.textContent = `${detail.label}: ${detail.value}`;
        resultBox.appendChild(line);
      }
    }

    async function onScanSuccess(decodedText) {
      const now = Date.now();
      if (processing || (decodedText === previousCode && now - previousCodeAt < 3000)) return;
      processing = true;
      previousCode = decodedText;
      previousCodeAt = now;
      scanner.pause(false);
      showResult('info', 'Checking guest…');

      try {
        const body = new URLSearchParams({
          ajax_checkin: '1',
          guest_id: decodedText,
          csrf_token: csrfToken
        });
        const response = await fetch('/admin?scanner', {
          method: 'POST',
          credentials: 'same-origin',
          headers: { 'Accept': 'application/json' },
          body
        });
        const data = await response.json();
        if (!response.ok || !data.ok) {
          showResult('danger', data.message || 'Could not check in this guest.');
          return;
        }

        const guest = data.guest || {};
        const isDuplicate = data.status === 'already_checked_in';
        const title = isDuplicate
          ? `${guest.name || 'Guest'} is already checked in.`
          : `${guest.name || 'Guest'} checked in.`;
        showResult(isDuplicate ? 'warning' : 'success', title, [
          { label: 'Email', value: guest.email },
          { label: 'Phone', value: guest.phone },
          { label: 'Category', value: guest.category }
        ]);
      } catch (error) {
        showResult('danger', 'The check-in request failed. Check the connection and scan again.');
      } finally {
        window.setTimeout(() => {
          processing = false;
          try {
            scanner.resume();
          } catch (error) {
            loading.textContent = 'Scanner paused. Tap “Try camera again” to continue.';
            retryButton.hidden = false;
          }
        }, 900);
      }
    }

    async function startScanner() {
      if (starting) return;
      if (!window.Html5Qrcode) {
        loading.textContent = 'The scanner could not load. Reload the page and try again.';
        retryButton.hidden = true;
        return;
      }

      starting = true;
      retryButton.hidden = true;
      spinner.hidden = false;
      loading.textContent = 'Starting rear camera… Allow camera access when prompted.';

      try {
        if (!scanner) {
          scanner = new Html5Qrcode('reader', {
            formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE]
          }, false);
        }

        await scanner.start(
          { facingMode: { exact: 'environment' } },
          {
            fps: 15,
            qrbox: (viewWidth, viewHeight) => {
              const size = Math.max(200, Math.min(300, Math.floor(Math.min(viewWidth, viewHeight) * 0.78)));
              return { width: size, height: size };
            }
          },
          onScanSuccess,
          () => {}
        );
        spinner.hidden = true;
        loading.textContent = 'Camera ready. Hold a guest QR code in the frame.';
      } catch (error) {
        spinner.hidden = true;
        loading.textContent = 'Could not start the rear camera. Allow camera access, then try again.';
        retryButton.hidden = false;
      } finally {
        starting = false;
      }
    }

    retryButton.addEventListener('click', startScanner);
    window.startGuestScanner = startScanner;
    window.scannerLibraryFailed = () => {
      spinner.hidden = true;
      loading.textContent = 'The scanner could not load. Reload the page and try again.';
    };

  })();
</script>
<script src="/assets/html5-qrcode.min.js?v=2" onload="startGuestScanner()" onerror="scannerLibraryFailed()" type="text/javascript"></script>
