(function(){
  document.querySelectorAll('[data-toggle]').forEach(function(b){
    b.addEventListener('click',function(){var t=document.querySelector(b.getAttribute('data-toggle'));if(t)t.classList.toggle('open');});
  });
  document.querySelectorAll('form[data-confirm]').forEach(function(f){
    f.addEventListener('submit',function(ev){if(!confirm(f.getAttribute('data-confirm')))ev.preventDefault();});
  });
  document.querySelectorAll('[data-print]').forEach(function(b){b.addEventListener('click',function(){window.print();});});
  var all=document.querySelector('[data-check-all]');
  if(all){all.addEventListener('change',function(){document.querySelectorAll('input[name="ids[]"]').forEach(function(c){c.checked=all.checked;});});}
  /* Autocomplete anggota / koleksi (datalist) */
  document.querySelectorAll('input[data-suggest]').forEach(function(inp){
    var dl=document.getElementById(inp.getAttribute('list')),type=inp.getAttribute('data-suggest'),timer;
    inp.addEventListener('input',function(){
      clearTimeout(timer);var term=inp.value.trim();if(term.length<2)return;
      timer=setTimeout(function(){
        fetch(inp.getAttribute('data-url')+'?type='+type+'&term='+encodeURIComponent(term),{credentials:'same-origin'})
          .then(function(r){return r.json();}).then(function(list){
            dl.innerHTML='';list.forEach(function(it){var o=document.createElement('option');o.value=it.value;o.label=it.label;dl.appendChild(o);});
          }).catch(function(){});
      },200);
    });
  });
  var auto=document.querySelector('[data-autofocus]');if(auto)auto.focus();
})();
