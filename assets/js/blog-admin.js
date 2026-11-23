(function () {
  var $ = function (s) { return document.querySelector(s); };
  var form = $('#postForm');
  if (!form) return;

  var siteName = form.dataset.site || '', domain = form.dataset.domain || '';
  var titleEl = $('#postTitle'), slugEl = $('#slugInput'), excerptEl = $('#excerpt'), contentEl = $('#content');
  var kwEl = $('#focusKw'), seoT = $('#seoTitle'), metaEl = $('#metaDesc'), noindexEl = $('#noindex');
  var featFile = $('#featFile'), featPrev = $('#featPrev'), featAlt = $('#featAlt');
  var removeImg = form.querySelector('input[name=remove_image]');
  var seoList = $('#seoChecks'), readList = $('#readChecks'), light = $('#seoLight');
  var slugTouched = slugEl.value !== '';
  var metaTouched = metaEl.value.trim() !== '';
  var copyBtn = $('#copyExcerpt');

  /* resumo -> meta description (até 160 caracteres, cortado em palavra inteira) */
  function summaryFrom(t) {
    t = t.replace(/\s+/g, ' ').trim();
    if (t.length <= 160) return t;
    var cut = t.slice(0, 157), sp = cut.lastIndexOf(' ');
    return (sp > 100 ? cut.slice(0, sp) : cut) + '…';
  }

  function norm(s) { return (s || '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/\s+/g, ' ').trim(); }
  function esc(s) { return s.replace(/[.*+?^${}()|[\]\\]/g, '\\$&'); }
  function plain(html) {
    var doc = new DOMParser().parseFromString(html || '', 'text/html');
    return (doc.body ? doc.body.textContent : '').replace(/\s+/g, ' ').trim();
  }
  function slugify(s) {
    return norm(s).replace(/[^a-z0-9]+/g, '-').replace(/^-+|-+$/g, '') || 'artigo';
  }

  /* ---------- contadores ---------- */
  function counter(el) {
    var max = +el.dataset.max, min = +el.dataset.min || 0;
    var src = document.getElementById(el.dataset.countFor);
    var len = src.value.length;
    el.textContent = len + '/' + max + ' caracteres' + (min ? ' (ideal ' + min + '–' + max + ')' : '');
    el.className = 'count ' + (len > max ? 'warn' : (min && len < min ? '' : (len ? 'ok' : '')));
  }

  /* ---------- análise ---------- */
  function item(list, level, text) {
    var li = document.createElement('li');
    li.className = 'chk chk-' + level;
    var ic = document.createElement('span'); ic.className = 'chk-ic';
    ic.textContent = level === 'good' ? '✔' : (level === 'ok' ? '⚠' : '✖');
    var tx = document.createElement('span'); tx.textContent = text;
    li.appendChild(ic); li.appendChild(tx); list.appendChild(li);
    return level;
  }

  function analyse() {
    var kw = norm(kwEl.value);
    var title = titleEl.value.trim();
    var st = seoT.value.trim() || title;
    var md = metaEl.value.trim();
    var raw = contentEl.value;
    var text = plain(raw);
    var normText = norm(text);
    var words = text ? text.split(' ').length : 0;
    var kwWords = kw ? kw.split(' ').length : 0;
    var slugText = norm((slugEl.value || '').replace(/-/g, ' '));
    var levels = [];

    var occ = 0;
    if (kw && normText) occ = (normText.match(new RegExp('(^|\\s)' + esc(kw) + '(?=\\s|$|[.,;:!?])', 'g')) || []).length;

    seoList.innerHTML = ''; readList.innerHTML = '';

    if (!kw) {
      levels.push(item(seoList, 'bad', 'Defina uma palavra-chave foco para receber a análise completa.'));
    } else {
      levels.push(item(seoList, norm(st).indexOf(kw) > -1 ? 'good' : 'bad',
        norm(st).indexOf(kw) > -1 ? 'A palavra-chave aparece no título SEO.' : 'A palavra-chave não aparece no título SEO.'));
      levels.push(item(seoList, slugText.indexOf(kw) > -1 ? 'good' : 'bad',
        slugText.indexOf(kw) > -1 ? 'A palavra-chave aparece no permalink.' : 'A palavra-chave não aparece no permalink.'));
      levels.push(item(seoList, norm(md).indexOf(kw) > -1 ? 'good' : 'bad',
        norm(md).indexOf(kw) > -1 ? 'A palavra-chave aparece na meta description.' : 'A palavra-chave não aparece na meta description.'));
      var intro = norm(text.split(' ').slice(0, 100).join(' '));
      levels.push(item(seoList, intro.indexOf(kw) > -1 ? 'good' : 'ok',
        intro.indexOf(kw) > -1 ? 'A palavra-chave aparece no início do texto.' : 'Use a palavra-chave nas primeiras 100 palavras.'));
      if (words === 0) {
        levels.push(item(seoList, 'bad', 'A palavra-chave não aparece no conteúdo.'));
      } else {
        var density = occ * kwWords / words * 100;
        if (occ === 0) levels.push(item(seoList, 'bad', 'A palavra-chave não aparece no conteúdo.'));
        else if (density > 3) levels.push(item(seoList, 'bad', 'Palavra-chave repetida demais (' + density.toFixed(1) + '%). Ideal: entre 0,5% e 3%.'));
        else if (density < 0.5) levels.push(item(seoList, 'ok', 'Densidade baixa (' + density.toFixed(1) + '%). Use a palavra-chave mais algumas vezes.'));
        else levels.push(item(seoList, 'good', 'Densidade da palavra-chave ideal (' + density.toFixed(1) + '%).'));
      }
      var heads = [], hm, reH = /<h[23][^>]*>([\s\S]*?)<\/h[23]>/gi;
      while ((hm = reH.exec(raw))) heads.push(norm(plain(hm[1])));
      if (heads.length) {
        var inHead = heads.some(function (h) { return h.indexOf(kw) > -1; });
        levels.push(item(seoList, inHead ? 'good' : 'ok', inHead ? 'A palavra-chave aparece em um subtítulo.' : 'Nenhum subtítulo contém a palavra-chave.'));
      }
    }

    var tl = st.length;
    levels.push(item(seoList, tl >= 30 && tl <= 60 ? 'good' : (tl === 0 ? 'bad' : 'ok'),
      tl === 0 ? 'Defina o título SEO.' : (tl >= 30 && tl <= 60 ? 'Título SEO com tamanho ideal (' + tl + ' caracteres).' : 'Título SEO com ' + tl + ' caracteres. Ideal: entre 30 e 60.')));
    var ml = md.length;
    levels.push(item(seoList, ml >= 120 && ml <= 160 ? 'good' : (ml === 0 ? 'bad' : 'ok'),
      ml === 0 ? 'Escreva a meta description.' : (ml >= 120 && ml <= 160 ? 'Meta description com tamanho ideal (' + ml + ' caracteres).' : 'Meta description com ' + ml + ' caracteres. Ideal: entre 120 e 160.')));
    var internal = (raw.match(/href=["'](?!https?:|mailto:|tel:|#)[^"']+["']/gi) || []).length;
    var external = (raw.match(/href=["']https?:\/\//gi) || []).length;
    levels.push(item(seoList, internal ? 'good' : 'ok', internal ? internal + ' link(s) interno(s) no texto.' : 'Adicione links internos para outros artigos ou ofertas do site.'));
    levels.push(item(seoList, external ? 'good' : 'ok', external ? external + ' link(s) externo(s) no texto.' : 'Considere citar uma fonte externa confiável.'));

    var hasImg = (featFile.files && featFile.files.length) || (featPrev.dataset.has === '1' && !(removeImg && removeImg.checked));
    var alt = featAlt.value.trim();
    if (!hasImg) levels.push(item(seoList, 'ok', 'Defina uma imagem destacada.'));
    else if (alt === '') levels.push(item(seoList, 'bad', 'Preencha o texto alternativo da imagem destacada.'));
    else if (kw && norm(alt).indexOf(kw) > -1) levels.push(item(seoList, 'good', 'O texto alternativo contém a palavra-chave.'));
    else levels.push(item(seoList, 'ok', 'Inclua a palavra-chave no texto alternativo da imagem.'));

    if (noindexEl && noindexEl.checked) levels.push(item(seoList, 'ok', 'Página marcada como noindex: não aparecerá nas buscas.'));

    /* legibilidade */
    var sentences = text ? text.split(/[.!?]+(\s|$)/).filter(function (s) { return s && s.trim().length > 2; }) : [];
    var avg = sentences.length ? words / sentences.length : 0;
    levels.push(item(readList, words >= 300 ? 'good' : (words >= 150 ? 'ok' : 'bad'),
      words >= 300 ? 'Conteúdo com ' + words + ' palavras.' : 'Conteúdo com ' + words + ' palavras. Recomendado: pelo menos 300.'));
    if (sentences.length) levels.push(item(readList, avg <= 20 ? 'good' : (avg <= 25 ? 'ok' : 'bad'),
      avg <= 20 ? 'Frases curtas e fáceis de ler (média de ' + avg.toFixed(0) + ' palavras).' : 'Frases longas (média de ' + avg.toFixed(0) + ' palavras). Tente encurtar.'));
    var paras = /<p[\s>]/i.test(raw) ? (raw.match(/<p[\s>]/gi) || []).length : raw.split(/\n\s*\n/).filter(function (b) { return b.trim(); }).length;
    levels.push(item(readList, paras >= 3 ? 'good' : 'ok', paras >= 3 ? 'Texto dividido em parágrafos.' : 'Divida o texto em parágrafos com <p>.'));
    var ex = excerptEl.value.trim().length;
    levels.push(item(readList, ex >= 50 && ex <= 300 ? 'good' : 'ok', ex >= 50 && ex <= 300 ? 'Resumo preenchido.' : 'Escreva um resumo entre 50 e 300 caracteres para as listagens.'));

    var bad = levels.filter(function (l) { return l === 'bad'; }).length;
    var ok = levels.filter(function (l) { return l === 'ok'; }).length;
    var good = levels.filter(function (l) { return l === 'good'; }).length;
    light.className = 'seo-light ' + (bad ? 'l-bad' : (ok ? 'l-ok' : 'l-good'));
    light.textContent = (bad ? 'Precisa de atenção' : (ok ? 'Quase lá' : 'Ótimo')) + ' · ✔ ' + good + '  ⚠ ' + ok + '  ✖ ' + bad;

    $('#wordCount').textContent = words;
    $('#readTime').textContent = Math.max(1, Math.ceil(words / 220));
    updateSerp(text);
  }

  function updateSerp(text) {
    var slug = slugEl.value.trim() || slugify(titleEl.value) || 'artigo';
    var t = (seoT.value.trim() || titleEl.value.trim() || 'Título do artigo') + ' | ' + siteName;
    var d = metaEl.value.trim() || excerptEl.value.trim() || (text ? text.slice(0, 155) + (text.length > 155 ? '…' : '') : 'A meta description aparecerá aqui.');
    $('#serpUrl').textContent = domain + '/blog/' + slug;
    $('#serpTitle').textContent = t.length > 70 ? t.slice(0, 67) + '…' : t;
    $('#serpDesc').textContent = d.length > 165 ? d.slice(0, 162) + '…' : d;
  }

  function refreshCounters() {
    document.querySelectorAll('.count[data-count-for]').forEach(counter);
  }

  function onInput() { refreshCounters(); analyse(); }

  excerptEl.addEventListener('input', function () {
    if (!metaTouched) metaEl.value = summaryFrom(excerptEl.value);
    onInput();
  });
  metaEl.addEventListener('input', function () { metaTouched = metaEl.value.trim() !== ''; onInput(); });
  if (copyBtn) copyBtn.addEventListener('click', function () {
    metaEl.value = summaryFrom(excerptEl.value); metaTouched = true; onInput();
  });

  /* slug automático enquanto o usuário não editar o campo */
  titleEl.addEventListener('input', function () {
    if (!slugTouched) slugEl.value = slugify(titleEl.value);
    onInput();
  });
  slugEl.addEventListener('input', function () { slugTouched = true; onInput(); });
  ['input', 'change'].forEach(function (ev) {
    [contentEl, kwEl, seoT, featAlt].forEach(function (el) { el.addEventListener(ev, onInput); });
    if (noindexEl) noindexEl.addEventListener(ev, onInput);
    if (removeImg) removeImg.addEventListener(ev, onInput);
    featFile.addEventListener(ev, function () {
      var f = featFile.files[0];
      if (f) {
        if (f.size > 3 * 1024 * 1024) { alert('Imagem maior que 3 MB.'); featFile.value = ''; return; }
        featPrev.src = URL.createObjectURL(f); featPrev.dataset.has = '1';
      }
      onInput();
    });
  });
  featPrev.addEventListener('error', function () { featPrev.alt = 'Imagem indisponível'; });

  /* abas do painel SEO */
  document.querySelectorAll('.seo-tabs button').forEach(function (b) {
    b.addEventListener('click', function () {
      document.querySelectorAll('.seo-tabs button').forEach(function (x) { x.classList.toggle('on', x === b); });
      document.querySelectorAll('.seo-panel').forEach(function (p) { p.hidden = p.id !== b.dataset.panel; });
    });
  });

  /* barra de formatação */
  function insert(ins, selStart, selLen) {
    var ta = contentEl, s = ta.selectionStart, e = ta.selectionEnd;
    ta.value = ta.value.slice(0, s) + ins + ta.value.slice(e);
    ta.focus();
    var start = s + (selStart || 0);
    ta.setSelectionRange(start, start + (selLen !== undefined ? selLen : ins.length));
    onInput();
  }
  function wrap(open, close, placeholder) {
    var ta = contentEl, s = ta.selectionStart, e = ta.selectionEnd;
    var sel = ta.value.slice(s, e) || placeholder;
    insert(open + sel + close, open.length, sel.length);
  }
  document.querySelectorAll('.toolbar button').forEach(function (b) {
    b.addEventListener('click', function () {
      var tag = b.dataset.tag;
      if (tag === 'h2') wrap('<h2>', '</h2>', 'Subtítulo');
      else if (tag === 'h3') wrap('<h3>', '</h3>', 'Subtítulo menor');
      else if (tag === 'p') wrap('<p>', '</p>', 'Texto do parágrafo');
      else if (tag === 'strong') wrap('<strong>', '</strong>', 'Destaque');
      else if (tag === 'ul') insert('<ul>\n  <li>Item</li>\n  <li>Item</li>\n</ul>\n');
      else if (tag === 'link') {
        var url = prompt('URL do link (https://... ou /caminho-interno):'); if (!url) return;
        var ta = contentEl, sel = ta.value.slice(ta.selectionStart, ta.selectionEnd) || 'texto do link';
        insert('<a href="' + url.replace(/"/g, '&quot;') + '">' + sel + '</a>');
      } else if (tag === 'img') {
        var src = prompt('URL da imagem (https://...):'); if (!src) return;
        var alt = prompt('Texto alternativo da imagem (descreva a imagem):') || '';
        insert('<img src="' + src.replace(/"/g, '&quot;') + '" alt="' + alt.replace(/"/g, '&quot;') + '" loading="lazy">');
      }
    });
  });

  if (!metaEl.value.trim() && excerptEl.value.trim()) metaEl.value = summaryFrom(excerptEl.value);
  refreshCounters();
  analyse();
})();
