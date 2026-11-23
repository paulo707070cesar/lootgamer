/* ============================================================
   XLL Gaming Store — JavaScript do painel administrativo
   ============================================================ */
(function () {
  'use strict';

  var $ = function (selector, context) { return (context || document).querySelector(selector); };
  var $$ = function (selector, context) { return Array.prototype.slice.call((context || document).querySelectorAll(selector)); };

  /* ---------------- Sidebar mobile ---------------- */
  function initSidebar() {
    var button = $('.adm-menu-btn');
    var sidebar = $('.adm-sidebar');
    if (!button || !sidebar) { return; }
    button.addEventListener('click', function () {
      sidebar.classList.toggle('is-open');
    });
    document.addEventListener('click', function (event) {
      if (sidebar.classList.contains('is-open') && !sidebar.contains(event.target) && !button.contains(event.target)) {
        sidebar.classList.remove('is-open');
      }
    });
  }

  /* ---------------- Confirmações ---------------- */
  function initConfirms() {
    $$('[data-confirm]').forEach(function (element) {
      var message = element.getAttribute('data-confirm') || 'Tem certeza?';
      if (element.tagName === 'FORM') {
        element.addEventListener('submit', function (event) {
          if (!window.confirm(message)) { event.preventDefault(); }
        });
      } else {
        element.addEventListener('click', function (event) {
          if (!window.confirm(message)) { event.preventDefault(); }
        });
      }
    });
  }

  /* ---------------- Preview de imagens ---------------- */
  function bindImageInput(input) {
    if (input.dataset.previewBound === '1') { return; }
    input.dataset.previewBound = '1';
    input.addEventListener('change', function () {
      var field = input.closest('.adm-image-field');
      if (!field) { return; }
      var preview = $('.adm-image-preview', field);
      if (!preview) { return; }
      var file = input.files && input.files[0];
      if (!file) { return; }
      if (!/^image\//.test(file.type)) { return; }
      var url = URL.createObjectURL(file);
      preview.innerHTML = '<img src="' + url + '" alt="Pré-visualização">';
      preview.style.backgroundImage = 'none';
    });
  }

  function initImagePreviews() {
    $$('input[type="file"][data-preview]').forEach(bindImageInput);
  }

  /* ---------------- Repetidores ---------------- */
  function renumberRepeater(container) {
    var items = $$('[data-repeater-items] > .adm-repeater-item', container);
    items.forEach(function (item, index) {
      var number = $('[data-repeater-number]', item);
      if (number) { number.textContent = String(index + 1); }
    });
  }

  function bindRepeaterItem(item) {
    $('input[type="file"][data-preview]', item).forEach(bindImageInput);
    $('[data-svg-field]', item).forEach(bindSvgField);

    var remove = $('[data-repeater-remove]', item);
    if (remove && remove.dataset.bound !== '1') {
      remove.dataset.bound = '1';
      remove.addEventListener('click', function (event) {
        event.preventDefault();
        var message = remove.getAttribute('data-confirm') || 'Remover este item?';
        if (window.confirm(message)) {
          var container = item.closest('[data-repeater]');
          item.remove();
          if (container) { renumberRepeater(container); }
        }
      });
    }
  }

  /* ---------------- Ícones SVG personalizados ---------------- */
  function bindSvgField(field) {
    if (!field || field.dataset.bound === '1') { return; }
    field.dataset.bound = '1';

    var input = $('.adm-svg-input', field);
    var preview = $('[data-svg-preview]', field);
    if (!input || !preview) { return; }

    function presetSvg(name) {
      var btn = field.querySelector('[data-svg-preset="' + name.replace(/"/g, '\\"') + '"]');
      return btn ? btn.innerHTML : '';
    }

    function render() {
      var valor = input.value.trim();
      if (valor.charAt(0) === '<') {
        preview.innerHTML = valor;
        preview.classList.remove('is-preset');
      } else if (valor !== '') {
        preview.innerHTML = presetSvg(valor);
        preview.classList.add('is-preset');
      } else {
        preview.innerHTML = '';
      }
    }

    input.addEventListener('input', render);
    input.addEventListener('change', render);
    field.addEventListener('click', function (event) {
      var btn = event.target.closest('[data-svg-preset]');
      if (!btn) { return; }
      event.preventDefault();
      input.value = btn.getAttribute('data-svg-preset');
      render();
    });
    render();
  }

  function initSvgIconFields() {
    $('[data-svg-field]').forEach(bindSvgField);
  }

  function initRepeaters() {
    $$('[data-repeater]').forEach(function (container) {
      renumberRepeater(container);
      $$('[data-repeater-items] > .adm-repeater-item', container).forEach(bindRepeaterItem);

      var addButton = $('[data-repeater-add]', container);
      var template = $('template[data-repeater-template]', container);
      var itemsWrap = $('[data-repeater-items]', container);
      if (!addButton || !template || !itemsWrap) { return; }
      if (addButton.dataset.bound === '1') { return; }
      addButton.dataset.bound = '1';

      addButton.addEventListener('click', function () {
        var next = parseInt(container.getAttribute('data-next-index') || '0', 10);
        if (!next) {
          var max = 0;
          $$('.adm-repeater-item', container).forEach(function (item) {
            var index = parseInt(item.getAttribute('data-index') || '0', 10);
            if (index >= max) { max = index + 1; }
          });
          next = max;
        }
        container.setAttribute('data-next-index', String(next + 1));

        var html = template.innerHTML.replace(/__INDEX__/g, String(next));
        var wrapper = document.createElement('div');
        wrapper.innerHTML = html.trim();
        var item = wrapper.firstElementChild;
        if (!item) { return; }
        item.setAttribute('data-index', String(next));
        itemsWrap.appendChild(item);
        bindRepeaterItem(item);
        renumberRepeater(container);
        item.scrollIntoView({ behavior: 'smooth', block: 'center' });
      });
    });
  }

  /* ---------------- Cores / fontes em tempo real ---------------- */
  function initLivePreview() {
    var preview = $('.adm-theme-preview');
    if (!preview) { return; }

    function updateColors() {
      $$('[data-preview-var]').forEach(function (input) {
        var variable = input.getAttribute('data-preview-var');
        var value = input.value.trim();
        if (value) {
          preview.style.setProperty(variable, value);
        }
      });
    }

    function updateFonts() {
      $$('[data-preview-font]').forEach(function (select) {
        var variable = select.getAttribute('data-preview-font');
        var value = select.value.trim();
        if (value) {
          preview.style.setProperty(variable, "'" + value + "', Inter, sans-serif");
        }
      });
    }

    $$('[data-preview-var]').forEach(function (input) {
      input.addEventListener('input', updateColors);
      input.addEventListener('change', updateColors);
    });
    $$('[data-preview-font]').forEach(function (select) {
      select.addEventListener('change', updateFonts);
    });

    // Sincroniza input color com o campo de texto hexadecimal.
    $$('[data-color-picker]').forEach(function (picker) {
      var text = $(picker.getAttribute('data-color-picker'));
      if (!text) { return; }
      picker.addEventListener('input', function () {
        text.value = picker.value.toUpperCase();
        updateColors();
      });
      text.addEventListener('input', function () {
        if (/^#[0-9a-fA-F]{6}$/.test(text.value.trim())) {
          picker.value = text.value.trim();
          updateColors();
        }
      });
    });

    updateColors();
    updateFonts();
  }

  /* ---------------- Cálculo automático de desconto ---------------- */
  function initAutoDiscount() {
    var price = $('[data-auto-discount-price]');
    var old = $('[data-auto-discount-old]');
    var discount = $('[data-auto-discount-target]');
    if (!price || !old || !discount) { return; }

    function parseMoney(value) {
      if (!value) { return 0; }
      var raw = String(value).replace(/[^\d,.-]/g, '');
      if (raw.indexOf(',') >= 0) {
        raw = raw.replace(/\./g, '').replace(',', '.');
      }
      return Math.round((parseFloat(raw) || 0) * 100);
    }

    function update() {
      var current = parseMoney(price.value);
      var previous = parseMoney(old.value);
      if (current > 0 && previous > current) {
        discount.value = String(Math.round((1 - current / previous) * 100));
      }
    }

    price.addEventListener('input', update);
    old.addEventListener('input', update);
  }

  /* ---------------- Upload de CSV ---------------- */
  function initFileLabels() {
    $$('input[type="file"][data-filename-target]').forEach(function (input) {
      var target = $(input.getAttribute('data-filename-target'));
      if (!target) { return; }
      input.addEventListener('change', function () {
        var file = input.files && input.files[0];
        target.textContent = file ? file.name : '';
      });
    });
  }

  /* ---------------- Fechar mensagens ---------------- */
  function initFlashClose() {
    $$('[data-flash-close]').forEach(function (button) {
      button.addEventListener('click', function () {
        var flash = button.closest('.adm-flash');
        if (flash) { flash.remove(); }
      });
    });
  }

  function boot() {
    initSidebar();
    initConfirms();
    initImagePreviews();
    initSvgIconFields();
    initRepeaters();
    initLivePreview();
    initAutoDiscount();
    initFileLabels();
    initFlashClose();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
