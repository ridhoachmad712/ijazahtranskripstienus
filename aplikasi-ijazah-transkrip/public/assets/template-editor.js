(() => {
  const form = document.getElementById('template-form');
  if (!form) return;

  const status = document.getElementById('preview-status');
  const frame = document.getElementById('live-pdf');
  const isTranscript = form.dataset.kind === 'transcript';
  const stage = document.getElementById('preview-stage');
  const sheet = document.getElementById('diploma-sheet');
  const grid = sheet?.querySelector('.sheet-grid');
  const gridToggle = document.getElementById('toggle-grid');
  const alignmentStatus = document.getElementById('alignment-status');
  const qrNode = document.getElementById('canvas-qr');
  const qrNote = document.getElementById('qr-preview-note');
  const positionSelect = document.getElementById('position-field-select');
  const positionPanels = [...form.querySelectorAll('[data-field-panel]')];
  const mmPx = 96 / 25.4;
  let scale = 1;
  let zoomed = false;
  let timer;
  let request;
  let generation = 0;

  const fieldInput = (key, part) => form.elements.namedItem(`field[${key}][${part}]`);
  const qrInput = (part) => form.elements.namedItem(`qr[${part}]`);
  const numberValue = (input, fallback = 0) => {
    const value = Number.parseFloat(input?.value ?? '');
    return Number.isFinite(value) ? value : fallback;
  };

  function resizeSheet() {
    if (!stage || !sheet) return;
    const fit = Math.min(1, stage.clientWidth / (297 * mmPx));
    scale = zoomed ? Math.max(fit, 0.78) : fit;
    sheet.style.transform = `scale(${scale})`;
    stage.style.height = `${zoomed ? Math.min(580, 210 * mmPx * scale) : 210 * mmPx * scale}px`;
    stage.style.overflow = zoomed ? 'auto' : 'hidden';
  }

  function renderCanvas() {
    if (!sheet) return;
    sheet.querySelectorAll('[data-field]').forEach((node) => {
      const key = node.dataset.field;
      const x = numberValue(fieldInput(key, 'x'));
      const y = numberValue(fieldInput(key, 'y'));
      const width = numberValue(fieldInput(key, 'w'), 10);
      const points = numberValue(fieldInput(key, 'pt'), 12);
      const align = fieldInput(key, 'align')?.value || 'center';
      const textInput = form.elements.namedItem(`text[${key}]`);
      const value = textInput ? textInput.value.trim() : node.dataset.fixedText;
      const empty = !value;
      node.textContent = empty ? `[${node.dataset.emptyLabel} belum diisi]` : value;
      node.classList.toggle('canvas-empty', empty);
      Object.assign(node.style, {
        left: `${x * mmPx}px`, top: `${y * mmPx}px`, width: `${width * mmPx}px`,
        fontSize: `${points * 96 / 72}px`, textAlign: align,
      });
    });
    if (qrNode) {
      const size = numberValue(qrInput('size'), 22);
      const enabled = !!form.elements.namedItem('qr_enabled')?.checked;
      qrNode.classList.toggle('canvas-qr-enabled', enabled);
      Object.assign(qrNode.style, {
        left: `${numberValue(qrInput('x')) * mmPx}px`,
        top: `${numberValue(qrInput('y')) * mmPx}px`,
        width: `${size * mmPx}px`, height: `${size * mmPx}px`,
      });
      const qrRect = qrNode.getBoundingClientRect();
      const collisions = [...sheet.querySelectorAll('.canvas-field:not(.canvas-empty)')]
        .filter((node) => {
          const rect = node.getBoundingClientRect();
          return qrRect.left < rect.right && qrRect.right > rect.left && qrRect.top < rect.bottom && qrRect.bottom > rect.top;
        })
        .map((node) => node.dataset.emptyLabel);
      qrNode.classList.toggle('canvas-qr-warning', collisions.length > 0);
      if (qrNote) {
        qrNote.textContent = collisions.length
          ? `Area QR beririsan dengan ${collisions.join(', ')}. Geser atau perkecil area QR.`
          : enabled
            ? 'QR aktif: area ini akan dicetak pada ijazah final.'
            : 'Lokasi QR disiapkan sebagai panduan; QR belum akan dicetak pada ijazah final.';
        qrNote.className = `small mb-2 ${collisions.length ? 'text-danger' : 'text-secondary'}`;
      }
    }
  }

  async function loadTranscriptPreview() {
    if (!isTranscript || !frame) return;
    const current = ++generation;
    request?.abort();
    request = new AbortController();
    status.textContent = 'Memperbarui pratinjau…';
    status.className = 'small text-secondary';
    try {
      const data = new FormData(form);
      data.append('preview_format', 'html');
      const response = await fetch(form.dataset.previewUrl, {
        method: 'POST', body: data, credentials: 'same-origin',
        signal: request.signal,
      });
      if (!response.ok) throw new Error((await response.text()).trim() || 'Pratinjau gagal dibuat.');
      const contentType = response.headers.get('content-type') || '';
      if (!contentType.includes('text/html')) throw new Error('Sesi Admin berakhir. Muat ulang halaman.');
      const html = await response.text();
      if (current !== generation) return;
      frame.srcdoc = html;
      status.textContent = 'Pratinjau sesuai perubahan saat ini';
      status.className = 'small text-success';
    } catch (error) {
      if (error.name === 'AbortError' || current !== generation) return;
      status.textContent = error.message;
      status.className = 'small text-danger';
    }
  }

  function schedulePreview() {
    if (!isTranscript) {
      status.textContent = 'Tampilan teks sesuai perubahan saat ini';
      status.className = 'small text-success';
      return;
    }
    clearTimeout(timer);
    status.textContent = 'Perubahan belum diperbarui di pratinjau…';
    status.className = 'small text-secondary';
    timer = setTimeout(loadTranscriptPreview, 500);
  }

  function showPositionPanel(key) {
    if (positionSelect) positionSelect.value = key;
    positionPanels.forEach((panel) => { panel.hidden = panel.dataset.fieldPanel !== key; });
  }

  function updateAlignmentFeedback(node) {
    if (!alignmentStatus || !node?.dataset.field) {
      if (alignmentStatus) alignmentStatus.textContent = 'Pilih teks untuk memeriksa posisi tengah.';
      grid?.classList.remove('center-active');
      return;
    }
    const key = node.dataset.field;
    const center = numberValue(fieldInput(key, 'x')) + numberValue(fieldInput(key, 'w')) / 2;
    const offset = Math.abs(center - 148.5);
    const centered = offset < 0.11;
    const textCentered = fieldInput(key, 'align')?.value === 'center';
    grid?.classList.toggle('center-active', centered && !!gridToggle?.checked);
    node.classList.toggle('canvas-centered', centered && textCentered);
    alignmentStatus.textContent = centered
      ? textCentered ? '✓ Teks tepat di tengah halaman.' : 'Area tepat di tengah; pilih perataan Tengah agar teks ikut rata tengah.'
      : `Belum di tengah: selisih ${offset.toFixed(1).replace('.', ',')} mm.`;
    alignmentStatus.className = `small mb-3 ${centered && textCentered ? 'text-success fw-semibold' : 'text-secondary'}`;
  }

  function selectNode(node) {
    sheet?.querySelectorAll('.canvas-selected, .canvas-centered').forEach((item) => item.classList.remove('canvas-selected', 'canvas-centered'));
    node.classList.add('canvas-selected');
    const key = node.dataset.field;
    if (key) {
      showPositionPanel(key);
      status.textContent = `Dipilih: ${node.dataset.emptyLabel}`;
      updateAlignmentFeedback(node);
    } else {
      status.textContent = 'Dipilih: area QR';
      updateAlignmentFeedback(null);
    }
  }

  function moveNode(node, x, y, snap = false) {
    const key = node.dataset.field;
    const xInput = key ? fieldInput(key, 'x') : qrInput('x');
    const yInput = key ? fieldInput(key, 'y') : qrInput('y');
    const width = key ? numberValue(fieldInput(key, 'w'), 10) : numberValue(qrInput('size'), 22);
    const height = key ? 1 : width;
    let clampedX = Math.max(0, Math.min(297 - width, x));
    let clampedY = Math.max(0, Math.min(210 - height, y));
    if (snap && gridToggle?.checked) {
      const centerX = (297 - width) / 2;
      const gridX = Math.round(clampedX / 10) * 10;
      const gridY = Math.round(clampedY / 10) * 10;
      if (key && Math.abs(clampedX - centerX) <= 3) clampedX = centerX;
      else if (Math.abs(clampedX - gridX) <= 2) clampedX = gridX;
      if (Math.abs(clampedY - gridY) <= 2) clampedY = gridY;
    }
    xInput.value = (Math.round(clampedX * 10) / 10).toFixed(1);
    yInput.value = (Math.round(clampedY * 10) / 10).toFixed(1);
    renderCanvas();
    schedulePreview();
    updateAlignmentFeedback(node);
  }

  if (sheet) {
    sheet.querySelectorAll('[data-field]').forEach((node) => {
      const key = node.dataset.field;
      if (!form.elements.namedItem(`text[${key}]`)) node.dataset.fixedText = node.textContent;
    });
    sheet.querySelectorAll('[data-field], #canvas-qr').forEach((node) => {
      node.addEventListener('pointerdown', (event) => {
        if (event.button !== 0) return;
        event.preventDefault();
        selectNode(node);
        node.focus();
        node.setPointerCapture(event.pointerId);
        const key = node.dataset.field;
        const start = {
          px: event.clientX, py: event.clientY,
          x: numberValue(key ? fieldInput(key, 'x') : qrInput('x')),
          y: numberValue(key ? fieldInput(key, 'y') : qrInput('y')),
        };
        const drag = (move) => moveNode(node,
          start.x + (move.clientX - start.px) / scale / mmPx,
          start.y + (move.clientY - start.py) / scale / mmPx, true);
        const stop = () => {
          node.removeEventListener('pointermove', drag);
          node.removeEventListener('pointerup', stop);
          node.removeEventListener('pointercancel', stop);
        };
        node.addEventListener('pointermove', drag);
        node.addEventListener('pointerup', stop);
        node.addEventListener('pointercancel', stop);
      });
      node.addEventListener('keydown', (event) => {
        if (!['ArrowLeft','ArrowRight','ArrowUp','ArrowDown'].includes(event.key)) return;
        event.preventDefault();
        selectNode(node);
        const key = node.dataset.field;
        const x = numberValue(key ? fieldInput(key, 'x') : qrInput('x'));
        const y = numberValue(key ? fieldInput(key, 'y') : qrInput('y'));
        const step = event.shiftKey ? 0.1 : 1;
        moveNode(node, x + (event.key === 'ArrowRight' ? step : event.key === 'ArrowLeft' ? -step : 0),
          y + (event.key === 'ArrowDown' ? step : event.key === 'ArrowUp' ? -step : 0));
      });
    });
    new ResizeObserver(resizeSheet).observe(stage);
    resizeSheet();
    renderCanvas();
  }

  positionSelect?.addEventListener('change', () => {
    showPositionPanel(positionSelect.value);
    const node = [...sheet.querySelectorAll('[data-field]')].find((item) => item.dataset.field === positionSelect.value);
    if (node) selectNode(node);
  });
  form.addEventListener('invalid', (event) => {
    const panel = event.target.closest('[data-field-panel]');
    if (panel) showPositionPanel(panel.dataset.fieldPanel);
  }, true);
  form.addEventListener('input', (event) => { if (event.target !== positionSelect) { renderCanvas(); schedulePreview(); updateAlignmentFeedback(sheet?.querySelector('.canvas-selected')); } });
  form.addEventListener('change', (event) => { if (event.target !== positionSelect) { renderCanvas(); schedulePreview(); updateAlignmentFeedback(sheet?.querySelector('.canvas-selected')); } });
  gridToggle?.addEventListener('change', () => { grid.hidden = !gridToggle.checked; updateAlignmentFeedback(sheet?.querySelector('.canvas-selected')); });
  document.getElementById('toggle-zoom')?.addEventListener('click', (event) => {
    zoomed = !zoomed;
    event.currentTarget.textContent = zoomed ? 'Pas halaman' : 'Perbesar pratinjau';
    event.currentTarget.setAttribute('aria-pressed', String(zoomed));
    resizeSheet();
  });
  if (isTranscript) loadTranscriptPreview();
  else schedulePreview();
})();
