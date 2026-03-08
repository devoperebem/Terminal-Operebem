// Mobile Menu - Sincronizar ações com versão desktop
(function(){
  document.addEventListener('DOMContentLoaded', function(){
    // Snapshot mobile
    const snapshotMobile = document.getElementById('toggleSnapshotMenuMobile');
    const snapshotDesktop = document.getElementById('toggleSnapshotMenu');
    if (snapshotMobile && snapshotDesktop) {
      snapshotMobile.addEventListener('click', function(e){
        e.preventDefault();
        // Fechar offcanvas
        const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('mobileMenu'));
        if (offcanvas) offcanvas.hide();
        // Trigger desktop action
        snapshotDesktop.click();
      });
    }
    
    // Export Excel mobile
    const excelMobile = document.getElementById('exportExcelMenuMobile');
    const excelDesktop = document.getElementById('exportExcelMenu');
    if (excelMobile && excelDesktop) {
      excelMobile.addEventListener('click', function(e){
        e.preventDefault();
        // Fechar offcanvas
        const offcanvas = bootstrap.Offcanvas.getInstance(document.getElementById('mobileMenu'));
        if (offcanvas) offcanvas.hide();
        // Trigger desktop action
        excelDesktop.click();
      });
    }
  });
})();
