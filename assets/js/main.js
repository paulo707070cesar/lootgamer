(function(){
  var $=function(s,c){return(c||document).querySelector(s)},$$=function(s,c){return Array.prototype.slice.call((c||document).querySelectorAll(s))};
  // menu responsivo
  var mt=$('#menuToggle'),nav=$('#mainNav');
  if(mt&&nav)mt.addEventListener('click',function(){var o=nav.classList.toggle('open');mt.setAttribute('aria-expanded',o)});
  var ab=$('#allcatsBtn'),al=$('#allcatsList');
  if(ab&&al){ab.addEventListener('click',function(e){e.stopPropagation();al.hidden=!al.hidden;ab.setAttribute('aria-expanded',!al.hidden)});document.addEventListener('click',function(){al.hidden=true})}
  // carrossel
  var hero=$('#hero');
  if(hero){var slides=$$('.hero-slide',hero),dots=$$('.hero-dots button',hero),i=0,t;
    var go=function(n){i=(n+slides.length)%slides.length;slides.forEach(function(s,k){s.classList.toggle('active',k===i)});dots.forEach(function(d,k){d.classList.toggle('on',k===i)})};
    var auto=function(){clearInterval(t);if(slides.length>1)t=setInterval(function(){go(i+1)},5500)};
    var p=$('.prev',hero),n=$('.next',hero);
    if(p)p.addEventListener('click',function(){go(i-1);auto()});if(n)n.addEventListener('click',function(){go(i+1);auto()});
    dots.forEach(function(d,k){d.addEventListener('click',function(){go(k);auto()})});auto();}
  // imagens quebradas -> placeholder
  var ph=(window.LG?window.LG.base:'')+'/assets/images/placeholder.svg';
  $$('img[data-fallback]').forEach(function(im){var f=function(){if(im.dataset.fb)return;im.dataset.fb=1;im.src=ph};im.addEventListener('error',f);if(im.complete&&im.naturalWidth===0&&im.src)f()});
  // galeria do produto
  $$('.pd-thumbs button').forEach(function(b){b.addEventListener('click',function(){var m=$('#pdMain');if(m)m.src=b.dataset.src})});
  // filtros automáticos
  $$('select[data-auto]').forEach(function(s){s.addEventListener('change',function(){s.form.submit()})});
  // busca com sugestões
  var si=$('#searchInput'),sb=$('#suggestBox'),tm;
  if(si&&sb){si.addEventListener('input',function(){clearTimeout(tm);var v=si.value.trim();if(v.length<2){sb.hidden=true;return}
    tm=setTimeout(function(){fetch((window.LG.base||'')+'/suggest.php?q='+encodeURIComponent(v)).then(function(r){return r.json()}).then(function(d){
      sb.innerHTML='';d.forEach(function(x){var a=document.createElement('a');a.href=x.url;a.textContent=x.name;sb.appendChild(a)});sb.hidden=!d.length}).catch(function(){})},250)});
    document.addEventListener('click',function(e){if(!sb.contains(e.target)&&e.target!==si)sb.hidden=true})}
  // cookies + analytics somente com consentimento
  var bar=$('#cookieBar'),KEY='lg_cookie';
  function analytics(){var m=document.querySelector('meta[name="lg-analytics"]');if(!m||window.__ga)return;window.__ga=1;
    var s=document.createElement('script');s.async=true;s.src='https://www.googletagmanager.com/gtag/js?id='+m.content;document.head.appendChild(s);
    window.dataLayer=window.dataLayer||[];window.gtag=function(){dataLayer.push(arguments)};gtag('js',new Date());gtag('config',m.content)}
  var c=null;try{c=localStorage.getItem(KEY)}catch(e){}
  if(c==='yes')analytics();else if(!c&&bar)bar.hidden=false;
  function set(v){try{localStorage.setItem(KEY,v)}catch(e){}bar.hidden=true;bar.style.display='none';if(bar.parentNode)bar.parentNode.removeChild(bar);if(v==='yes')analytics()}
  var ok=$('#cookieOk'),no=$('#cookieNo');if(ok)ok.addEventListener('click',function(){set('yes')});if(no)no.addEventListener('click',function(){set('no')});
})();