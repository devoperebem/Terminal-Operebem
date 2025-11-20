(function(){
  let captured = false;

  function getRow() {
    const col1 = document.getElementById('dash-col-1');
    return col1 ? col1.parentElement : document.querySelector('.container-fluid > .row');
  }

  function captureOriginal() {
    if (captured) return;
    const cols = [1,2,3].map(i => document.getElementById(`dash-col-${i}`)).filter(Boolean);
    cols.forEach((col, idx) => {
      const cards = Array.from(col.querySelectorAll(':scope > .card'));
      cards.forEach((card, i) => {
        card.dataset.originalCol = String(idx + 1);
        card.dataset.originalIndex = String(i);
      });
    });
    captured = true;
  }

  function collectCards(columns) {
    const list = [];
    columns.forEach(col => {
      list.push(...Array.from(col.querySelectorAll(':scope > .card')));
    });
    return list;
  }

  function clearColumns(columns) {
    columns.forEach(col => {
      Array.from(col.querySelectorAll(':scope > .card')).forEach(card => col.removeChild(card));
    });
  }

  function distribute(cards, columns) {
    const n = columns.length;
    const total = cards.length;
    const base = Math.floor(total / n);
    const extra = total % n;
    let k = 0;
    for (let i = 0; i < n; i++) {
      const take = base + (i < extra ? 1 : 0);
      for (let j = 0; j < take; j++) {
        if (k < total) columns[i].appendChild(cards[k++]);
      }
    }
  }

  function restoreOriginal() {
    const col1 = document.getElementById('dash-col-1');
    const col2 = document.getElementById('dash-col-2');
    const col3 = document.getElementById('dash-col-3');
    const col4 = document.getElementById('dash-col-4');
    if (!col1 || !col2 || !col3) return;

    const cards = collectCards([col1, col2, col3].concat(col4 ? [col4] : []));
    cards.sort((a,b) => {
      const ca = Number(a.dataset.originalCol || 1);
      const cb = Number(b.dataset.originalCol || 1);
      if (ca !== cb) return ca - cb;
      const ia = Number(a.dataset.originalIndex || 0);
      const ib = Number(b.dataset.originalIndex || 0);
      return ia - ib;
    });

    clearColumns([col1, col2, col3]);
    cards.forEach(card => {
      const targetCol = Number(card.dataset.originalCol || 1);
      (targetCol === 1 ? col1 : targetCol === 2 ? col2 : col3).appendChild(card);
    });

    if (col4) col4.remove();
  }

  function ensureBalancedColumns() {
    const wide = window.innerWidth >= 1700;
    const row = getRow();
    if (!row) return;
    captureOriginal();

    const col1 = document.getElementById('dash-col-1');
    const col2 = document.getElementById('dash-col-2');
    const col3 = document.getElementById('dash-col-3');
    if (!col1 || !col2 || !col3) return;

    let col4 = document.getElementById('dash-col-4');
    const allCards = collectCards([col1, col2, col3].concat(col4 ? [col4] : []));
    const visibleCards = allCards.filter(c => !c.classList.contains('d-none'));
    const hiddenCards = allCards.filter(c => c.classList.contains('d-none'));

    if (wide) {
      const totalVisible = visibleCards.length;
      if (totalVisible < 4) {
        if (col4) { col4.remove(); col4 = null; }
        clearColumns([col1, col2, col3]);
        distribute(visibleCards, [col1, col2, col3]);
        hiddenCards.forEach(card => {
          const targetCol = Number(card.dataset.originalCol || 1);
          (targetCol === 1 ? col1 : targetCol === 2 ? col2 : col3).appendChild(card);
        });
        return;
      }
      if (!col4) {
        col4 = document.createElement('div');
        col4.id = 'dash-col-4';
        col4.className = 'col-12 col-md-6 col-lg-6 col-xl-4 col-xxl-3 mb-3';
        row.appendChild(col4);
      }
      clearColumns([col1, col2, col3, col4]);
      distribute(visibleCards, [col1, col2, col3, col4]);
      hiddenCards.forEach(card => {
        const targetCol = Number(card.dataset.originalCol || 1);
        (targetCol === 1 ? col1 : targetCol === 2 ? col2 : col3).appendChild(card);
      });
    } else {
      if (col4) { col4.remove(); col4 = null; }
      clearColumns([col1, col2, col3]);
      distribute(visibleCards, [col1, col2, col3]);
      hiddenCards.forEach(card => {
        const targetCol = Number(card.dataset.originalCol || 1);
        (targetCol === 1 ? col1 : targetCol === 2 ? col2 : col3).appendChild(card);
      });
    }
  }

  window.addEventListener('resize', ensureBalancedColumns);
  window.addEventListener('DOMContentLoaded', ensureBalancedColumns);
  ensureBalancedColumns();
  window.reflowDashboard = ensureBalancedColumns;
})();
