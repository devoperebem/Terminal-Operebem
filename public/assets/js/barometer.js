(function(){
  const BAROMETER_API_URL = "/api/usmb/data";
  let barometerData;

  async function fetchBarometerData() {
    try {
      const response = await fetch(BAROMETER_API_URL, {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
      });
      if (!response.ok) throw new Error("Erro na requisição: " + response.status);
      return await response.json();
    } catch (error) {
      console.error("Erro ao buscar dados do barômetro:", error);
      return null;
    }
  }

  function getBarometerColorForValue(val, period) {
    if (period === "1d") {
      if (val <= -1.25) return "rgb(255, 0, 0)";
      else if (val > -1.25 && val <= -0.65) return "rgb(255, 102, 102)";
      else if (val > -0.65 && val < 0) return "rgb(255, 179, 179)";
      else if (val === 0) return "rgb(229, 229, 229)";
      else if (val > 0 && val < 0.65) return "rgb(179, 231, 198)";
      else if (val >= 0.65 && val < 1.25) return "rgb(102, 207, 141)";
      else if (val >= 1.25) return "rgb(0, 175, 65)";
    } else if (period === "1m") {
      if (val <= -4) return "rgb(255, 0, 0)";
      else if (val > -4 && val <= -2) return "rgb(255, 102, 102)";
      else if (val > -2 && val < 0) return "rgb(255, 179, 179)";
      else if (val === 0) return "rgb(229, 229, 229)";
      else if (val > 0 && val < 2) return "rgb(179, 231, 198)";
      else if (val >= 2 && val < 4) return "rgb(102, 207, 141)";
      else if (val >= 4) return "rgb(0, 175, 65)";
    } else if (period === "3m") {
      if (val <= -8) return "rgb(255, 0, 0)";
      else if (val > -8 && val <= -4) return "rgb(255, 102, 102)";
      else if (val > -4 && val < 0) return "rgb(255, 179, 179)";
      else if (val === 0) return "rgb(229, 229, 229)";
      else if (val > 0 && val < 4) return "rgb(179, 231, 198)";
      else if (val >= 4 && val < 8) return "rgb(102, 207, 141)";
      else if (val >= 8) return "rgb(0, 175, 65)";
    } else if (period === "1y" || period === "5y" || period === "10y") {
      if (val <= -20) return "rgb(255, 0, 0)";
      else if (val > -20 && val <= -10) return "rgb(255, 102, 102)";
      else if (val > -10 && val < 0) return "rgb(255, 179, 179)";
      else if (val === 0) return "rgb(229, 229, 229)";
      else if (val > 0 && val < 10) return "rgb(179, 231, 198)";
      else if (val >= 10 && val < 20) return "rgb(102, 207, 141)";
      else if (val >= 20) return "rgb(0, 175, 65)";
    }
    return "rgb(229, 229, 229)";
  }

  async function updateBarometerData(period) {
    if (!barometerData) {
      const fetchedData = await fetchBarometerData();
      if (fetchedData) {
        barometerData = fetchedData;
      } else {
        return;
      }
    }
    const grid = document.getElementById('barometer-grid');
    if (!grid) return;
    grid.innerHTML = '';

    const isDarkBlue = document.documentElement.classList.contains('dark-blue');
    const isAllBlack = document.documentElement.classList.contains('all-black');
    const isDarkTheme = isDarkBlue || isAllBlack;

    ['Large', 'Mid', 'Small'].forEach(cat => {
      const row = document.createElement('tr');
      row.className = 'h-16';

      ['Value', 'Core', 'Growth'].forEach(type => {
        const val = barometerData.data[cat][type][period];
        const sign = val > 0 ? '+' : '';
        const color = getBarometerColorForValue(val, period);
        let textColor = isDarkTheme ? 'text-white' : 'text-black';
        if (!isDarkTheme) {
          if ((period === '1d' && val <= -1.25) ||
              (period === '1m' && val <= -4) ||
              (period === '3m' && val <= -8) ||
              ((period === '1y' || period === '5y' || period === '10y') && val <= -20)) {
            textColor = 'text-white';
          }
        }
        const square = `
          <div class="barometer-square" style="background: ${color}; width: 60px; height: 60px; ${isDarkTheme ? 'border: 1px solid rgba(255,255,255,0.1);' : ''}">
            <span class="font-medium ${textColor} barometer-text" style="font-size: 0.85rem;">${sign}${val.toFixed(2)}%</span>
          </div>`;
        row.innerHTML += `<td class="barometer-cell p-1">${square}</td>`;
      });

      row.innerHTML += `<td class="text-left ps-2 font-light barometer-label" style="font-size: 0.8rem; ${isDarkTheme ? 'color: #fff;' : ''}">${cat}</td>`;
      grid.appendChild(row);
    });

    const spacerRow = document.createElement('tr');
    spacerRow.innerHTML = `<td colspan="4" class="py-1"></td>`;
    grid.appendChild(spacerRow);

    const footerRow = document.createElement('tr');
    footerRow.className = 'h-8';
    footerRow.innerHTML = `
      <td class="text-center font-normal barometer-label" style="font-size: 0.75rem; ${isDarkTheme ? 'color: #fff;' : ''}">Value</td>
      <td class="text-center font-normal barometer-label" style="font-size: 0.75rem; ${isDarkTheme ? 'color: #fff;' : ''}">Core</td>
      <td class="text-center font-normal barometer-label" style="font-size: 0.75rem; ${isDarkTheme ? 'color: #fff;' : ''}">Growth</td>
      <td></td>`;
    grid.appendChild(footerRow);

    let rawDate = barometerData.last_updated;
    // Interpretar como UTC se vier no formato 'YYYY-MM-DD HH:mm:ss'
    let dateObj = new Date((rawDate || '').replace(" ", "T") + 'Z');
    const tz = (typeof window !== 'undefined' && window.USER_TIMEZONE) ? window.USER_TIMEZONE : 'America/Sao_Paulo';
    const formattedDate = dateObj instanceof Date && !isNaN(dateObj) 
      ? dateObj.toLocaleString('pt-BR', { hour12: false, timeZone: tz })
      : String(rawDate || '');

    const updateRow = document.createElement('tr');
    updateRow.innerHTML = `<td colspan="4" class="text-center barometer-label pt-2" style="font-size: 0.7rem; ${isDarkTheme ? 'color: #fff;' : ''}">
      Última atualização: ${formattedDate}
    </td>`;
    grid.appendChild(updateRow);
  }

  document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('barometer-period');
    if (sel) {
      updateBarometerData(sel.value || '1d');
      sel.addEventListener('change', (e) => {
        updateBarometerData(e.target.value);
      });
    }
    new MutationObserver(muts => {
      if (muts.some(m => m.attributeName === 'class')) {
        const s = document.getElementById('barometer-period');
        if (s) updateBarometerData(s.value || '1d');
      }
    }).observe(document.documentElement, { attributes: true });
  });
})();
