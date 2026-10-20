(function(){
  function isMobile(){return window.matchMedia('(max-width: 767.98px)').matches;}
  function prepareTables(){
    if(!isMobile()) return;
    document.querySelectorAll('.page-content table tbody tr').forEach(function(row){
      row.querySelectorAll('td').forEach(function(cell){
        var text=(cell.textContent||'').replace(/\s+/g,' ').trim();
        if(text.length>30&&!cell.querySelector('button,input,select,textarea')){
          cell.classList.add('admin-mobile-clamp');
          cell.title='Tap the row to show full details';
        }
      });
    });
  }
  document.addEventListener('DOMContentLoaded',function(){
    if(!document.body.classList.contains('admin-pwa-shell')) return;
    prepareTables();
    var params=new URLSearchParams(window.location.search);
    if(params.has('installed')) document.body.classList.add('admin-pwa-installed');
  });
  document.addEventListener('click',function(event){
    if(!document.body.classList.contains('admin-pwa-shell')||!isMobile()) return;
    if(event.target.closest('[data-admin-more]')){
      var menuButton=document.querySelector('.button-toggle-menu');
      if(menuButton) menuButton.click();
      return;
    }
    if(document.body.classList.contains('sidebar-enable')&&!event.target.closest('.main-nav')&&!event.target.closest('.admin-mobile-nav')){
      document.body.classList.remove('sidebar-enable');
      return;
    }
    var cell=event.target.closest('td.admin-mobile-clamp');
    if(cell&&!event.target.closest('a,button,input,select,textarea')) cell.closest('tr').classList.toggle('admin-mobile-expanded');
  });
  window.addEventListener('resize',prepareTables,{passive:true});
})();
