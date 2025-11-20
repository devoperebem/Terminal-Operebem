(function(){
  const trigger = document.getElementById('exportExcelMenu');
  if (!trigger) return;
  function sanitizeSectionName(name){
    try { const re = new RegExp('[^\\p{L}\\p{N}\\s|\\-:.]', 'gu'); return name.replace(re, '').trim(); }
    catch(e) { return name.replace(/[^A-Za-z0-9\s|\-:.ÁÉÍÓÚÂÊÎÔÛÃÕÀÇáéíóúâêîôûãõàç]/g, '').trim(); }
  }
  trigger.addEventListener('click', function(e){
    e.preventDefault();
    try {
      const wb = XLSX.utils.book_new();
      const rows = [];
      const tables = document.querySelectorAll('.card_indices:not([style*="display: none"]):not([style*="display:none"]) table');
      const headerRow = ['Ativo', 'Último', 'Var.%', 'Hora'];
      tables.forEach((table) => {
        const card = table.closest('.card');
        const headerEl = card ? card.querySelector('.card-header.title-card') : null;
        let sectionName = headerEl ? headerEl.textContent.trim() : '';
        sectionName = sanitizeSectionName(sectionName);
        if (sectionName) {
          if (rows.length) rows.push(['']);
          rows.push([sectionName]);
        }
        rows.push(headerRow.slice());
        const tableRows = table.querySelectorAll('tr');
        tableRows.forEach(tr => {
          const tds = tr.querySelectorAll('td');
          if (tds.length) {
            const name = (tds[0]?.textContent || '').trim().replace(/\s+/g, ' ');
            const last = (tds[1]?.textContent || '').trim();
            const perc = (tds[2]?.textContent || '').trim();
            const hora = (tds[3]?.textContent || '').trim();
            rows.push([name, last, perc, hora]);
          }
        });
      });

      if (!rows.length) {
        alert('Nenhuma tabela visível para exportar. Certifique-se de que há cards visíveis no dashboard.');
        return;
      }
      const ws = XLSX.utils.aoa_to_sheet(rows);
      const colWidths = [];
      const maxCols = 4;
      for (let c = 0; c < maxCols; c++) {
        let maxLen = 8;
        rows.forEach(r => {
          const v = (r[c] ?? '').toString();
          if (v && v.length > maxLen) maxLen = Math.min(v.length + 2, 60);
        });
        colWidths.push({ wch: maxLen });
      }
      ws['!cols'] = colWidths;
      XLSX.utils.book_append_sheet(wb, ws, 'Dashboard');

      const timestamp = new Date().toISOString().slice(0,19).replace(/:/g,'-');
      const filename = `Dashboard_Terminal_Operebem_${timestamp}.xlsx`;
      XLSX.writeFile(wb, filename);
      console.info(`✅ Dashboard exportado com sucesso: ${filename}`);
    } catch (error) {
      console.error('❌ Erro ao exportar dashboard:', error);
      alert('Erro ao exportar dashboard para Excel. Verifique o console para mais detalhes.');
    }
  });
})();

