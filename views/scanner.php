<a href="?" class="btn btn-outline-primary mb-3 small">Back to home</a>
<h1 class="mb-2">Guest check-in</h1>
<p class="text-muted mb-3">Scan a guest’s QR code, review their details, then confirm check-in.</p>
<section id="scanner-method-choice" class="scanner-method-choice" aria-label="Choose how to scan a guest QR code">
  <p class="text-muted mb-3">Choose how you want to scan the guest’s QR code.</p>
  <div class="scanner-method-options">
    <button id="choose-camera" type="button" class="btn btn-primary">
      <span class="bi-camera me-1" aria-hidden="true"></span>Use camera
    </button>
    <button id="choose-upload" type="button" class="btn btn-outline-primary">
      <span class="bi-upload me-1" aria-hidden="true"></span>Upload QR image
    </button>
  </div>
</section>
<div id="scanner-camera-panel" class="scanner-camera-panel" hidden>
  <div id="reader"></div>
  <div class="scanner-status" role="status" aria-live="polite">
    <span id="scanner-spinner" class="bi-arrow-repeat scanner-spinner" aria-hidden="true"></span>
    <span id="scanner-loading">Preparing rear camera…</span>
    <button id="scanner-retry" type="button" class="btn btn-outline-primary btn-sm ms-2" hidden>Try camera again</button>
  </div>
</div>
<div id="scanner-upload-panel" class="scanner-upload" hidden>
  <label class="btn btn-outline-primary" for="qr-image-upload">
    <span class="bi-upload me-1" aria-hidden="true"></span>Choose QR image
  </label>
  <input id="qr-image-upload" type="file" accept="image/*" class="visually-hidden">
  <div id="qr-upload-status" class="small text-muted mt-2" role="status" aria-live="polite"></div>
</div>
<div id="file-reader" hidden></div>
<div id="scan-result" class="scan-result alert d-none mt-3" role="status" aria-live="polite"></div>
<input type="hidden" id="scanner-csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

<script>
  (() => {
    const loading = document.getElementById('scanner-loading');
    const spinner = document.getElementById('scanner-spinner');
    const retryButton = document.getElementById('scanner-retry');
    const methodChoice = document.getElementById('scanner-method-choice');
    const cameraPanel = document.getElementById('scanner-camera-panel');
    const uploadPanel = document.getElementById('scanner-upload-panel');
    const chooseCameraButton = document.getElementById('choose-camera');
    const chooseUploadButton = document.getElementById('choose-upload');
    const fileInput = document.getElementById('qr-image-upload');
    const fileStatus = document.getElementById('qr-upload-status');
    const resultBox = document.getElementById('scan-result');
    const csrfToken = document.getElementById('scanner-csrf').value;
    let scanner;
    let imageScanner;
    let processing = false;
    let starting = false;
    let scannerLibraryReady = false;
    let cameraChosen = false;
    let scanMode = '';
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

    function addActionButton(label, className, onClick) {
      let actions = resultBox.querySelector('.scan-actions');
      if (!actions) {
        actions = document.createElement('div');
        actions.className = 'scan-actions d-flex flex-wrap gap-2 mt-3';
        resultBox.appendChild(actions);
      }
      const button = document.createElement('button');
      button.type = 'button';
      button.className = `btn ${className}`;
      button.textContent = label;
      button.addEventListener('click', onClick);
      actions.appendChild(button);
      return button;
    }

    function addScanNextButton() {
      addActionButton('Scan next guest', 'btn-outline-secondary', () => {
        resultBox.className = 'scan-result alert d-none mt-3';
        if (scanMode === 'upload') {
          fileStatus.textContent = '';
          fileInput.click();
          return;
        }
        try {
          scanner.resume();
        } catch (error) {
          loading.textContent = 'Scanner paused. Tap “Try camera again” to continue.';
          retryButton.hidden = false;
        }
      });
    }

    function guestDetails(guest) {
      return [
        { label: 'Email', value: guest.email },
        { label: 'Phone', value: guest.phone },
        { label: 'Category', value: guest.category }
      ];
    }

    async function confirmCheckin(guest, button) {
      button.disabled = true;
      button.textContent = 'Saving check-in…';
      try {
        const body = new URLSearchParams({
          ajax_checkin: '1',
          guest_id: guest.id,
          group_size: String(guest.group_size || 1),
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
          showResult('danger', data.message || 'Check-in could not be saved.', guestDetails(guest));
          addActionButton('Try check-in again', 'btn-primary', (event) => confirmCheckin(guest, event.currentTarget));
          addScanNextButton();
          return;
        }

        const isDuplicate = data.status === 'already_checked_in';
        const title = isDuplicate
          ? `${guest.name || 'Guest'} is already checked in.`
          : `${guest.name || 'Guest'} checked in.`;
        showResult(isDuplicate ? 'warning' : 'success', title, guestDetails(guest));
        addScanNextButton();
      } catch (error) {
        showResult('danger', 'The check-in request failed. Check the connection and try again.', guestDetails(guest));
        addActionButton('Try check-in again', 'btn-primary', (event) => confirmCheckin(guest, event.currentTarget));
        addScanNextButton();
      }
    }

    async function handleScannedCode(decodedText, cameraAlreadyPaused = false, allowRecentDuplicate = false) {
      const now = Date.now();
      if (processing || (!allowRecentDuplicate && decodedText === previousCode && now - previousCodeAt < 3000)) return;
      processing = true;
      previousCode = decodedText;
      previousCodeAt = now;
      if (!cameraAlreadyPaused && scanner) {
        try {
          scanner.pause(false);
        } catch (error) {
          // The camera may still be starting or may not be available.
        }
      }
      showResult('info', 'Looking up guest…');

      try {
        const body = new URLSearchParams({
          scan_guest: '1',
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
          addScanNextButton();
          return;
        }

        const guest = data.guest || {};
        const isDuplicate = data.status === 'already_checked_in';
        const title = isDuplicate
          ? `${guest.name || 'Guest'} is already checked in.`
          : `Confirm check-in for ${guest.name || 'this guest'}?`;
        showResult(isDuplicate ? 'warning' : 'info', title, guestDetails(guest));
        if (!isDuplicate) {
          addActionButton('Confirm check-in', 'btn-primary', (event) => confirmCheckin(guest, event.currentTarget));
        }
        addScanNextButton();
      } catch (error) {
        showResult('danger', 'Could not look up this guest. Check the connection and try again.');
        addScanNextButton();
      } finally {
        processing = false;
      }
    }

    function onCameraScanSuccess(decodedText) {
      handleScannedCode(decodedText);
    }

    async function scanUploadedImage() {
      const image = fileInput.files && fileInput.files[0];
      if (!image) return;
      fileInput.value = '';

      if (!image.type.startsWith('image/')) {
        fileStatus.textContent = 'Choose an image file.';
        return;
      }
      if (image.size > 15 * 1024 * 1024) {
        fileStatus.textContent = 'Choose an image smaller than 15 MB.';
        return;
      }
      if (processing) return;

      let cameraPaused = false;
      if (scanner && !starting) {
        try {
          scanner.pause(false);
          cameraPaused = true;
        } catch (error) {
          // Camera may not have started; image scanning can still proceed.
        }
      }

      fileStatus.textContent = 'Reading QR image…';
      try {
        if (!imageScanner) {
          imageScanner = new Html5Qrcode('file-reader', {
            formatsToSupport: [Html5QrcodeSupportedFormats.QR_CODE]
          }, false);
        }
        const decodedText = await imageScanner.scanFile(image, false);
        fileStatus.textContent = '';
        await handleScannedCode(decodedText, cameraPaused, true);
      } catch (error) {
        fileStatus.textContent = 'No QR code found in that image. Try a clearer image.';
        if (cameraPaused) {
          try {
            scanner.resume();
          } catch (resumeError) {
            loading.textContent = 'Scanner paused. Tap “Try camera again” to continue.';
            retryButton.hidden = false;
          }
        }
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
          onCameraScanSuccess,
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
    fileInput.addEventListener('change', scanUploadedImage);
    chooseCameraButton.addEventListener('click', () => {
      scanMode = 'camera';
      cameraChosen = true;
      methodChoice.hidden = true;
      cameraPanel.hidden = false;
      if (scannerLibraryReady) startScanner();
      else loading.textContent = 'Loading camera scanner…';
    });
    chooseUploadButton.addEventListener('click', () => {
      scanMode = 'upload';
      methodChoice.hidden = true;
      uploadPanel.hidden = false;
      fileInput.click();
    });
    window.startGuestScanner = () => {
      scannerLibraryReady = true;
      if (cameraChosen) startScanner();
    };
    window.scannerLibraryFailed = () => {
      if (cameraChosen) {
        spinner.hidden = true;
        loading.textContent = 'The camera scanner could not load. Reload the page and try again.';
      }
    };

  })();
</script>
<script src="/assets/html5-qrcode.min.js?v=2" onload="startGuestScanner()" onerror="scannerLibraryFailed()" type="text/javascript"></script>
