(() => {
  const formatNumber = (value) => value.toLocaleString('id-ID', { maximumFractionDigits: 2 });
  const formatGpa = (value) => value.toFixed(2).replace('.', ',');
  const serialize = (form) => {
    const data = new FormData(form);
    data.delete('csrf');
    return new URLSearchParams(data).toString();
  };
  const dirtyChecks = [];
  const isDirty = () => dirtyChecks.some((check) => check());
  let leaving = false;

  // Formulir lain di halaman yang sama memuat ulang halaman, jadi minta konfirmasi bila ada isian belum tersimpan.
  document.querySelectorAll('form').forEach((form) => form.addEventListener('submit', (event) => {
    if (form.hasAttribute('data-leave-check') && isDirty() && !confirm('Ada perubahan yang belum disimpan dan akan hilang. Lanjutkan?')) {
      event.preventDefault();
      return;
    }
    leaving = true;
  }));
  window.addEventListener('beforeunload', (event) => {
    if (leaving || !isDirty()) return;
    event.preventDefault();
    event.returnValue = '';
  });

  function trackDirty(form, statusNode, cleanText, dirtyText) {
    const initial = serialize(form);
    const startsDirty = form.dataset.draft === '1';
    const dirty = () => startsDirty || serialize(form) !== initial;
    dirtyChecks.push(dirty);
    return () => {
      if (!statusNode) return;
      const changed = dirty();
      statusNode.textContent = changed ? dirtyText : cleanText;
      statusNode.className = `small ${changed ? 'text-warning-emphasis fw-semibold' : 'text-secondary'}`;
    };
  }

  const curriculumForm = document.getElementById('curriculum-form');
  if (curriculumForm) {
    const body = document.getElementById('curriculum-rows');
    const template = document.getElementById('course-row-template');
    const emptyNote = document.getElementById('curriculum-empty');
    const updateDirty = trackDirty(curriculumForm, document.getElementById('curriculum-dirty'), 'Belum ada perubahan.', 'Ada perubahan yang belum disimpan.');

    const refresh = () => {
      const rows = [...body.rows];
      let credits = 0;
      rows.forEach((row, index) => {
        row.querySelector('.row-number').textContent = String(index + 1);
        credits += Number.parseFloat(row.querySelector('[name="credits[]"]').value) || 0;
      });
      document.getElementById('curriculum-count').textContent = String(rows.length);
      document.getElementById('curriculum-credits').textContent = formatNumber(credits);
      emptyNote.hidden = rows.length > 0;
      updateDirty();
    };
    const addRow = () => {
      body.append(template.content.firstElementChild.cloneNode(true));
      refresh();
      return body.lastElementChild;
    };

    document.getElementById('add-course').addEventListener('click', () => addRow().querySelector('input').focus());
    body.addEventListener('click', (event) => {
      const button = event.target.closest('button');
      if (!button) return;
      const row = button.closest('tr');
      if (button.hasAttribute('data-remove')) row.remove();
      if (button.dataset.move === '-1' && row.previousElementSibling) row.previousElementSibling.before(row);
      if (button.dataset.move === '1' && row.nextElementSibling) row.nextElementSibling.after(row);
      refresh();
      if (button.isConnected) button.focus();
    });
    // Enter pindah ke baris berikutnya (membuat baris baru di akhir) agar daftar panjang cepat diketik.
    body.addEventListener('keydown', (event) => {
      if (event.key !== 'Enter' || !event.target.matches('input')) return;
      event.preventDefault();
      const column = event.target.closest('td').cellIndex;
      const next = event.target.closest('tr').nextElementSibling || addRow();
      next.cells[column].querySelector('input')?.focus();
    });
    curriculumForm.addEventListener('input', refresh);
    refresh();
  }

  const scaleBody = document.getElementById('scale-rows');
  if (scaleBody) {
    scaleBody.addEventListener('click', (event) => {
      if (event.target.closest('[data-remove]') && scaleBody.rows.length > 1) event.target.closest('tr').remove();
    });
    document.getElementById('add-scale').addEventListener('click', () => {
      const row = scaleBody.rows[scaleBody.rows.length - 1].cloneNode(true);
      row.querySelectorAll('input').forEach((input) => { input.value = ''; });
      scaleBody.append(row);
      row.querySelector('input').focus();
    });
  }

  const gradeForm = document.getElementById('grade-sheet-form');
  if (gradeForm) {
    const scale = JSON.parse(gradeForm.dataset.scale || '{}');
    const summary = document.getElementById('grade-summary');
    const officialCredits = summary.dataset.officialCredits === '' ? null : Number(summary.dataset.officialCredits);
    const officialGpa = summary.dataset.officialGpa === '' ? null : Number(summary.dataset.officialGpa);
    const outsideCredits = Number(summary.dataset.outsideCredits) || 0;
    const outsideQuality = Number(summary.dataset.outsideQuality) || 0;
    const statusNode = document.getElementById('sum-status');
    const updateDirty = trackDirty(gradeForm, document.getElementById('grade-dirty'), 'Belum ada perubahan.', 'Ada perubahan yang belum disimpan.');

    // Aturan cocok sama dengan pemeriksaan server saat transkrip diterbitkan.
    const refresh = () => {
      let count = 0;
      let credits = outsideCredits;
      let quality = outsideQuality;
      gradeForm.querySelectorAll('tbody tr[data-credits]').forEach((row) => {
        const letter = row.querySelector('select').value;
        const weight = scale[letter];
        const rowCredits = Number(row.dataset.credits);
        const graded = letter !== '' && weight !== undefined;
        row.classList.toggle('grade-empty', letter === '');
        row.querySelector('.grade-weight').textContent = graded ? formatNumber(weight) : '—';
        row.querySelector('.grade-quality').textContent = graded ? formatNumber(rowCredits * weight) : '—';
        if (!graded) return;
        count += 1;
        credits += rowCredits;
        quality += rowCredits * weight;
      });
      const gpa = credits > 0 ? quality / credits : null;
      document.getElementById('sum-count').textContent = String(count);
      document.getElementById('sum-credits').textContent = formatNumber(credits);
      document.getElementById('sum-gpa').textContent = gpa === null ? '—' : formatGpa(gpa);
      if (officialCredits === null || officialGpa === null) {
        statusNode.textContent = 'SKS/IPK resmi belum diisi, jadi belum dapat dibandingkan.';
        statusNode.className = 'small text-secondary';
      } else if (Math.abs(credits - officialCredits) <= 0.001 && gpa !== null && Math.abs(gpa - officialGpa) <= 0.011) {
        statusNode.textContent = '✓ Cocok dengan SKS dan IPK resmi.';
        statusNode.className = 'small text-success fw-semibold';
      } else {
        const gaps = [];
        if (Math.abs(credits - officialCredits) > 0.001) gaps.push(`SKS selisih ${formatNumber(credits - officialCredits)}`);
        if (gpa === null || Math.abs(gpa - officialGpa) > 0.011) gaps.push(gpa === null ? 'IPK belum dapat dihitung' : `IPK selisih ${formatGpa(gpa - officialGpa)}`);
        statusNode.textContent = `Belum cocok: ${gaps.join(', ')}.`;
        statusNode.className = 'small text-danger fw-semibold';
      }
      updateDirty();
    };
    gradeForm.addEventListener('change', refresh);
    refresh();
  }
})();
