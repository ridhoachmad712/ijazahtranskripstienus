(() => {
  const form = document.getElementById('template-form');
  if (!form) return;

  const status = document.getElementById('preview-status');
  const stage = document.getElementById('preview-stage');
  const sheet = document.getElementById('diploma-sheet');
  const grid = sheet?.querySelector('.sheet-grid');
  const gridToggle = document.getElementById('toggle-grid');
  const alignmentStatus = document.getElementById('alignment-status');
  const qrNode = document.getElementById('canvas-qr');
  const qrNote = document.getElementById('qr-preview-note');
  const positionSelect = document.getElementById('position-field-select');
  const positionPanels = [...form.querySelectorAll('[data-field-panel]')];
  const inspectorEmpty = document.getElementById('inspector-empty');
  const inspectorBody = document.getElementById('inspector-body');
  const inspectorLabel = document.getElementById('inspector-label');
  const inspectorWidthLabel = document.getElementById('inspector-width-label');
  const inspectorQrNote = document.getElementById('inspector-qr-note');
  const inspectorFitNote = document.getElementById('inspector-fit-note');
  const inspectInputs = [...document.querySelectorAll('[data-inspect]')];
  const alignButtons = [...document.querySelectorAll('#field-inspector [data-align]')];
  const inspectWrap = (name) => document.querySelector(`[data-inspect-wrap="${name}"]`);
  const resetButton = document.getElementById('reset-changes');
  const dirtyStatus = document.getElementById('dirty-status');
  const labelInput = form.elements.namedItem('label');
  const mmPx = 96 / 25.4;
  const pageW = Number(sheet?.dataset.pageW) || 297;
  const pageH = Number(sheet?.dataset.pageH) || 210;
  let scale = 1;
  let zoomed = false;
  let selectedNode = null;
  let leaving = false;

  const fieldInput = (key, part) => form.elements.namedItem(`field[${key}][${part}]`);
  const qrInput = (part) => form.elements.namedItem(`qr[${part}]`);
  const numberValue = (input, fallback = 0) => {
    const value = Number.parseFloat(input?.value ?? '');
    return Number.isFinite(value) ? value : fallback;
  };

  // Nama versi dan token CSRF tidak dihitung sebagai perubahan template.
  const serializeForm = () => {
    const data = new FormData(form);
    data.delete('label');
    data.delete('csrf');
    return new URLSearchParams(data).toString();
  };
  const initialState = serializeForm();

  function updateDirty() {
    const dirty = serializeForm() !== initialState;
    if (resetButton) resetButton.disabled = !dirty;
    if (dirtyStatus) {
      dirtyStatus.textContent = dirty ? 'Ada perubahan yang belum disimpan.' : 'Belum ada perubahan.';
      dirtyStatus.className = `small ${dirty ? 'text-warning-emphasis fw-semibold' : 'text-secondary'}`;
    }
    return dirty;
  }

  // Input pada panel atas kertas hanya mewakili input formulir milik bidang yang dipilih.
  function inspectTarget(part) {
    if (!selectedNode) return null;
    const key = selectedNode.dataset.field;
    if (!key) return part === 'w' ? qrInput('size') : ['x', 'y'].includes(part) ? qrInput(part) : null;
    return part === 'text' ? form.elements.namedItem(`text[${key}]`) : fieldInput(key, part);
  }

  function syncInspector() {
    if (!inspectorBody) return;
    inspectorEmpty.hidden = !!selectedNode;
    inspectorBody.hidden = !selectedNode;
    if (!selectedNode) return;
    const isQr = !selectedNode.dataset.field;
    const hasText = !!inspectTarget('text');
    inspectorLabel.textContent = isQr ? 'Area QR' : selectedNode.dataset.emptyLabel;
    inspectorWidthLabel.textContent = isQr ? 'Ukuran (mm)' : 'Lebar (mm)';
    inspectWrap('pt').hidden = isQr;
    inspectWrap('align').hidden = isQr;
    const key = selectedNode.dataset.field;
    inspectWrap('text').hidden = isQr || !hasText;
    inspectWrap('auto').hidden = isQr || hasText;
    inspectWrap('qr').hidden = !isQr;
    inspectWrap('h').hidden = isQr || !fieldInput(key, 'h');
    inspectWrap('fit').hidden = !selectedNode.dataset.fit;
    if (selectedNode.dataset.fit && inspectorFitNote) {
      const wanted = numberValue(fieldInput(key, 'pt'), 9);
      const used = Number(selectedNode.dataset.fitPt || wanted);
      inspectorFitNote.textContent = used < wanted
        ? `Agar tetap satu lembar, huruf tabel diperkecil otomatis menjadi ${String(used).replace('.', ',')} pt. Tambah tinggi area jika ingin huruf lebih besar.`
        : 'Seluruh mata kuliah muat pada ukuran huruf ini.';
    }
    inspectInputs.forEach((input) => {
      const target = inspectTarget(input.dataset.inspect);
      if (target && input !== document.activeElement) input.value = target.value;
    });
    const align = isQr ? '' : fieldInput(key, 'align')?.value;
    alignButtons.forEach((button) => {
      const active = button.dataset.align === align;
      button.classList.toggle('active', active);
      button.setAttribute('aria-pressed', String(active));
    });
  }

  function resizeSheet() {
    if (!stage || !sheet) return;
    const fit = Math.min(1, stage.clientWidth / (pageW * mmPx));
    scale = zoomed ? Math.max(fit, 0.78) : fit;
    sheet.style.transform = `scale(${scale})`;
    stage.style.height = `${zoomed ? Math.min(580, pageH * mmPx * scale) : pageH * mmPx * scale}px`;
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
      const heightInput = fieldInput(key, 'h');
      Object.assign(node.style, {
        left: `${x * mmPx}px`, top: `${y * mmPx}px`, width: `${width * mmPx}px`,
        height: heightInput ? `${numberValue(heightInput, 10) * mmPx}px` : '',
        fontSize: `${points * 96 / 72}px`, textAlign: align,
      });
      if (node.classList.contains('canvas-block')) {
        if (node.dataset.fit) fitBlock(node, points);
        return;
      }
      const textInput = form.elements.namedItem(`text[${key}]`);
      const issuePlace = key === 'issue' ? form.elements.namedItem('text[issue_place]')?.value.trim() : '';
      const value = textInput ? textInput.value.trim()
        : issuePlace ? node.dataset.fixedText.replace(/^(Diberikan di )?.*?, /, (_, prefix = '') => `${prefix}${issuePlace}, `) : node.dataset.fixedText;
      const empty = !value;
      node.textContent = empty ? `[${node.dataset.emptyLabel} belum diisi]` : value;
      node.classList.toggle('canvas-empty', empty);
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
        qrNote.textContent = collisions.length ? `Area QR beririsan dengan ${collisions.join(', ')}. Geser atau perkecil area QR.` : '';
        qrNote.hidden = !collisions.length;
      }
      if (inspectorQrNote) {
        inspectorQrNote.textContent = enabled
          ? 'QR aktif: area ini akan dicetak pada ijazah final.'
          : 'QR belum dicetak pada ijazah final. Centang "Cetak QR pada ijazah final" di formulir setelah posisinya disetujui.';
      }
    }
    syncInspector();
  }

  // Sama seperti PDF: huruf tabel diperkecil bertahap sampai seluruh baris muat di tinggi area.
  function fitBlock(node, points) {
    let size = points;
    while (size > 5 && node.scrollHeight > node.clientHeight + 1) {
      size -= 0.25;
      node.style.fontSize = `${size * 96 / 72}px`;
    }
    node.dataset.fitPt = String(size);
    node.classList.toggle('canvas-overflow', node.scrollHeight > node.clientHeight + 1);
  }

  function schedulePreview() {
    if (status) status.textContent = '';
  }

  function showPositionPanel(key) {
    if (positionSelect) positionSelect.value = key;
    positionPanels.forEach((panel) => { panel.hidden = panel.dataset.fieldPanel !== key; });
  }

  function updateAlignmentFeedback(node) {
    if (!alignmentStatus || !node?.dataset.field) {
      if (alignmentStatus) alignmentStatus.textContent = '';
      grid?.classList.remove('center-active');
      return;
    }
    const key = node.dataset.field;
    const center = numberValue(fieldInput(key, 'x')) + numberValue(fieldInput(key, 'w')) / 2;
    const offset = Math.abs(center - pageW / 2);
    const centered = offset < 0.11;
    const textCentered = fieldInput(key, 'align')?.value === 'center';
    grid?.classList.toggle('center-active', centered && !!gridToggle?.checked);
    node.classList.toggle('canvas-centered', centered && textCentered);
    alignmentStatus.textContent = centered
      ? textCentered ? '✓ Teks tepat di tengah halaman.' : 'Area tepat di tengah; pilih perataan Tengah agar teks ikut rata tengah.'
      : `Belum di tengah: selisih ${offset.toFixed(1).replace('.', ',')} mm.`;
    alignmentStatus.className = `small ${centered && textCentered ? 'text-success fw-semibold' : 'text-secondary'}`;
  }

  function selectNode(node) {
    sheet?.querySelectorAll('.canvas-selected, .canvas-centered').forEach((item) => item.classList.remove('canvas-selected', 'canvas-centered'));
    node.classList.add('canvas-selected');
    selectedNode = node;
    const key = node.dataset.field;
    if (key) showPositionPanel(key);
    updateAlignmentFeedback(key ? node : null);
    syncInspector();
  }

  function moveNode(node, x, y, snap = false) {
    const key = node.dataset.field;
    const xInput = key ? fieldInput(key, 'x') : qrInput('x');
    const yInput = key ? fieldInput(key, 'y') : qrInput('y');
    const width = key ? numberValue(fieldInput(key, 'w'), 10) : numberValue(qrInput('size'), 22);
    const height = key ? numberValue(fieldInput(key, 'h'), 1) : width;
    let clampedX = Math.max(0, Math.min(pageW - width, x));
    let clampedY = Math.max(0, Math.min(pageH - height, y));
    if (snap && gridToggle?.checked) {
      const centerX = (pageW - width) / 2;
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
    updateDirty();
  }

  if (sheet) {
    sheet.querySelectorAll('[data-field]:not(.canvas-block)').forEach((node) => {
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
  form.addEventListener('input', (event) => { if (event.target !== positionSelect) { renderCanvas(); schedulePreview(); updateAlignmentFeedback(selectedNode); updateDirty(); } });
  form.addEventListener('change', (event) => { if (event.target !== positionSelect) { renderCanvas(); schedulePreview(); updateAlignmentFeedback(selectedNode); updateDirty(); } });
  inspectInputs.forEach((input) => input.addEventListener('input', () => {
    const target = inspectTarget(input.dataset.inspect);
    if (!target) return;
    target.value = input.value;
    target.dispatchEvent(new Event('input', { bubbles: true }));
  }));
  alignButtons.forEach((button) => button.addEventListener('click', () => {
    const target = inspectTarget('align');
    if (!target) return;
    target.value = button.dataset.align;
    target.dispatchEvent(new Event('change', { bubbles: true }));
  }));
  resetButton?.addEventListener('click', () => {
    if (!confirm('Kembalikan semua isian ke versi aktif? Perubahan yang belum disimpan akan hilang.')) return;
    const label = labelInput?.value ?? '';
    form.reset();
    if (labelInput) labelInput.value = label;
    if (selectedNode?.dataset.field) showPositionPanel(selectedNode.dataset.field);
    renderCanvas();
    schedulePreview();
    updateAlignmentFeedback(selectedNode);
    updateDirty();
  });
  const backgroundForm = document.getElementById('background-form');
  const backgroundFile = document.getElementById('background-file');
  const backgroundImage = document.getElementById('sheet-background');
  const backgroundToggle = document.getElementById('toggle-background');
  const backgroundOpacity = document.getElementById('background-opacity');
  const backgroundOpacityValue = document.getElementById('background-opacity-value');
  const storedPreference = (key) => { try { return localStorage.getItem(key); } catch { return null; } };
  const storePreference = (key, value) => { try { localStorage.setItem(key, value); } catch { /* opsional */ } };
  const confirmLeave = () => !updateDirty() || confirm('Ada perubahan template yang belum disimpan dan akan hilang saat halaman dimuat ulang. Lanjutkan?');

  function applyBackground() {
    if (!backgroundImage) return;
    const opacity = Number(backgroundOpacity?.value || 60);
    backgroundImage.hidden = !backgroundToggle?.checked;
    backgroundImage.style.opacity = String(opacity / 100);
    if (backgroundOpacityValue) backgroundOpacityValue.textContent = `${opacity}%`;
  }

  document.querySelectorAll('[data-background-pick]').forEach((button) => button.addEventListener('click', () => backgroundFile?.click()));
  backgroundFile?.addEventListener('change', () => {
    const file = backgroundFile.files?.[0];
    if (!file) return;
    if (file.size > 10 * 1024 * 1024) {
      alert('Ukuran gambar latar maksimal 10 MB.');
      backgroundFile.value = '';
      return;
    }
    if (!confirmLeave()) {
      backgroundFile.value = '';
      return;
    }
    leaving = true;
    backgroundForm.submit();
  });
  document.getElementById('background-delete-form')?.addEventListener('submit', (event) => {
    if (!confirm('Hapus latar blanko? Template tidak berubah.') || !confirmLeave()) {
      event.preventDefault();
      return;
    }
    leaving = true;
  });
  if (backgroundToggle) backgroundToggle.checked = storedPreference('stie-background-visible') !== '0';
  if (backgroundOpacity) backgroundOpacity.value = storedPreference('stie-background-opacity') || backgroundOpacity.value;
  backgroundToggle?.addEventListener('change', () => { storePreference('stie-background-visible', backgroundToggle.checked ? '1' : '0'); applyBackground(); });
  backgroundOpacity?.addEventListener('input', () => { storePreference('stie-background-opacity', backgroundOpacity.value); applyBackground(); });
  applyBackground();

  // Tombol "Buka PDF saat ini" membuka tab baru, jadi perubahan tetap dianggap belum tersimpan.
  form.addEventListener('submit', (event) => { if (event.submitter?.formTarget !== '_blank') leaving = true; });
  window.addEventListener('beforeunload', (event) => {
    if (leaving || !updateDirty()) return;
    event.preventDefault();
    event.returnValue = '';
  });
  gridToggle?.addEventListener('change', () => { grid?.toggleAttribute('hidden', !gridToggle.checked); updateAlignmentFeedback(sheet?.querySelector('.canvas-selected')); });
  document.getElementById('toggle-zoom')?.addEventListener('click', (event) => {
    zoomed = !zoomed;
    event.currentTarget.textContent = zoomed ? 'Pas halaman' : 'Perbesar pratinjau';
    event.currentTarget.setAttribute('aria-pressed', String(zoomed));
    resizeSheet();
  });
  schedulePreview();
})();
