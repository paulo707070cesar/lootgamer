(function(){
  var $=function(s){return document.querySelector(s)};
  // confirmação antes de ações destrutivas
  document.querySelectorAll('form[data-confirm]').forEach(function(f){f.addEventListener('submit',function(e){if(!confirm(f.dataset.confirm))e.preventDefault()})});
  // pré-visualização de upload / URL
  var file=$('#imgFile'),url=$('#imgUrl'),prev=$('#imgPrev'),info=$('#imgInfo');
  if(prev){
    prev.addEventListener('error',function(){prev.alt='Imagem indisponível'});
    if(file)file.addEventListener('change',function(){var f=file.files[0];if(!f)return;
      if(f.size>3*1024*1024){alert('Imagem maior que 3 MB.');file.value='';return}
      prev.src=URL.createObjectURL(f);if(info)info.textContent=f.name+' ('+Math.round(f.size/1024)+' KB)'});
    if(url)url.addEventListener('input',function(){var v=url.value.trim();if(/^https:\/\//i.test(v)&&!(file&&file.files.length)){prev.src=v;if(info)info.textContent='Prévia da URL'}});
  }
  // validação visual simples
  document.querySelectorAll('form[data-validate]').forEach(function(f){f.addEventListener('submit',function(e){
    var bad=false;f.querySelectorAll('[required]').forEach(function(i){var ok=i.value.trim()!=='';i.classList.toggle('invalid',!ok);if(!ok)bad=true});
    f.querySelectorAll('input[type=url]').forEach(function(i){if(i.value&&!/^https?:\/\//i.test(i.value)){i.classList.add('invalid');bad=true}});
    if(bad){e.preventDefault();toast('Corrija os campos destacados.')}})});
  function toast(m){var t=$('#toast');if(!t)return;t.textContent=m;t.hidden=false;setTimeout(function(){t.hidden=true},3500)}
  // some alerts automaticamente
  setTimeout(function(){document.querySelectorAll('.alert-ok').forEach(function(a){a.style.display='none'})},6000);
})();
