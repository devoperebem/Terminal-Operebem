// ====================================
// DEBUG PATCH para home-preview.js
// ====================================
// Adicione estas linhas logo após a linha 78 (onde está o console.log do flagHtml)

// APÓS a linha:
// console.log(`🚩 FLAG HTML for ${item.apelido}: icone_bandeira="${item.icone_bandeira}" → HTML: ${flagHtml}`);

// ADICIONE:
console.log(`🔍 [buildRow] Processing: ${item.apelido}`, {
    code: item.code,
    icone_bandeira: item.icone_bandeira,
    bandeira: item.bandeira,
    hasFlag: !!item.icone_bandeira,
    flagHtmlLength: flagHtml.length,
    flagHtmlIsEmpty: flagHtml === ''
});

// Também adicione este log no final da função buildRow, ANTES do return:
const finalHtml = `
  <tr order="${item.order_tabela ?? ''}" style="font-weight: 600 !important">
    <td width="50%">
      <div class="d-flex align-items-center">
        ${statusHtml}
        ${flagHtml}
        ${nameHtml}
      </div>
    </td>
    <td class="text-right vlr_field vlr_${itemKey}" last="${escapeAttr(item.last ?? '0')}"><span class="vlr-text">${escapeAttr(item.last ?? '')}</span></td>
    <td class="text-right ${classPerc} perc_${itemKey} tooltip-target perc" data-tooltip="${escapeAttr(item.pc || '')}" data-tooltip-color="${colorPerc}" style="font-weight: 900 !important; color: ${colorPerc} !important;">${pDisp}</td>
    <td class="text-right text-muted hr_${itemKey} tooltip-target-left" data-tooltip="${escapeAttr(timeInfo.full)}">${item.last ? escapeAttr(timeInfo.time) : ''}</td>
  </tr>
`;

console.log(`📋 [buildRow] Final HTML for ${item.apelido}:`, finalHtml.substring(0, 300));
console.log(`📋 Flag in HTML: ${finalHtml.includes('class="fi fi-')}`);

return finalHtml;
