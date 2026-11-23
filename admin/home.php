<?php
/**
 * Loot Gamer - personalização da página inicial.
 *
 * Banners do hero, cards promocionais, diferenciais, categorias,
 * seções de produtos, faixa de comparação e CTA final.
 */

declare(strict_types=1);

require __DIR__ . '/inc/layout.php';

$defaults = xll_defaults();
$uploadErrors = [];

if (xll_is_post()) {
    xll_require_csrf();
    $acao = adm_text('acao');

    switch ($acao) {
        case 'hero':
            $slides = [];
            $rows = adm_array('slides');
            foreach ($rows as $index => $row) {
                if (!is_array($row)) {
                    continue;
                }
                $image = trim((string) ($row['image'] ?? ''));
                [$image, $error] = adm_upload('slide_image_' . $index, 'hero', $image);
                if ($error !== '') {
                    $uploadErrors[] = 'Banner: ' . $error;
                }
                if ($image === '') {
                    continue;
                }
                $slides[] = [
                    'image'  => $image,
                    'mobile' => trim((string) ($row['mobile'] ?? '')),
                    'link'   => trim((string) ($row['link'] ?? '')),
                    'alt'    => trim((string) ($row['alt'] ?? '')),
                    'active' => !empty($row['active']),
                ];
            }
            if ($uploadErrors) {
                adm_redirect('home.php', implode(' ', $uploadErrors), 'error');
            }
            $value = [
                'enabled'  => adm_checked('enabled'),
                'autoplay' => adm_checked('autoplay'),
                'speed'    => max(2000, min(30000, adm_int('speed', 6000))),
                'slides'   => $slides ?: $defaults['hero']['slides'],
            ];
            xll_setting_save('hero', $value);
            adm_redirect('home.php?sec=hero', 'Banners do topo atualizados.');

        case 'promos':
            $promos = [];
            foreach (adm_array('promos') as $index => $row) {
                if (!is_array($row)) {
                    continue;
                }
                $image = trim((string) ($row['image'] ?? ''));
                [$image, $error] = adm_upload('promo_image_' . $index, 'promo', $image);
                if ($error !== '') {
                    $uploadErrors[] = 'Banner promocional: ' . $error;
                }
                if ($image === '' && trim((string) ($row['title'] ?? '')) === '') {
                    continue;
                }
                $promos[] = [
                    'image'    => $image,
                    'title'    => trim((string) ($row['title'] ?? '')),
                    'subtitle' => trim((string) ($row['subtitle'] ?? '')),
                    'cta'      => trim((string) ($row['cta'] ?? '')),
                    'link'     => trim((string) ($row['link'] ?? '')),
                    'active'   => !empty($row['active']),
                ];
            }
            if ($uploadErrors) {
                adm_redirect('home.php', implode(' ', $uploadErrors), 'error');
            }
            xll_setting_save('promos', $promos ?: $defaults['promos']);
            adm_redirect('home.php?sec=promos', 'Cards promocionais atualizados.');

        case 'features':
            $items = [];
            foreach (adm_array('features') as $index => $row) {
                if (!is_array($row)) {
                    continue;
                }
                if (trim((string) ($row['title'] ?? '')) === '') {
                    continue;
                }
                $iconImage = trim((string) ($row['icon_image'] ?? ''));
                [$iconImage, $error] = adm_upload('feature_image_' . $index, 'icone', $iconImage);
                if ($error !== '') {
                    $uploadErrors[] = 'Diferencial: ' . $error;
                }
                $iconRaw = trim((string) ($row['icon'] ?? ''));
                if ($iconRaw !== '' && stripos($iconRaw, '<svg') !== false) {
                    $iconRaw = xll_svg_sanitize_icon($iconRaw);
                }
                $items[] = [
                    'icon'       => $iconRaw,
                    'icon_image' => $iconImage,
                    'title'      => trim((string) ($row['title'] ?? '')),
                    'text'       => trim((string) ($row['text'] ?? '')),
                ];
            }
            if ($uploadErrors) {
                adm_redirect('home.php', implode(' ', $uploadErrors), 'error');
            }
            xll_setting_save('features', [
                'title' => adm_text('features_title'),
                'items' => $items ?: $defaults['features']['items'],
            ]);
            adm_redirect('home.php?sec=features', 'Cards de diferenciais atualizados.');

        case 'categories':
            $items = [];
            foreach (adm_array('categories') as $index => $row) {
                if (!is_array($row)) {
                    continue;
                }
                $name = trim((string) ($row['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                $image = trim((string) ($row['image'] ?? ''));
                [$image, $error] = adm_upload('category_image_' . $index, 'categoria', $image);
                if ($error !== '') {
                    $uploadErrors[] = 'Categoria: ' . $error;
                }
                $items[] = [
                    'name'        => $name,
                    'description' => trim((string) ($row['description'] ?? '')),
                    'icon'        => trim((string) ($row['icon'] ?? 'gamepad')),
                    'image'       => $image,
                    'link'        => trim((string) ($row['link'] ?? '')),
                    'active'      => !empty($row['active']),
                ];
            }
            if ($uploadErrors) {
                adm_redirect('home.php', implode(' ', $uploadErrors), 'error');
            }
            xll_setting_save('categories', [
                'title' => adm_text('categories_title'),
                'items' => $items ?: $defaults['categories']['items'],
            ]);
            adm_redirect('home.php?sec=categories', 'Ícones e categorias atualizados.');

        case 'sections':
            $sections = [];
            $posted = adm_array('sections');
            foreach (['ofertas', 'setup', 'bestsellers'] as $key) {
                $row = is_array($posted[$key] ?? null) ? $posted[$key] : [];
                $sections[$key] = [
                    'enabled'  => !empty($row['enabled']),
                    'title'    => trim((string) ($row['title'] ?? '')),
                    'subtitle' => trim((string) ($row['subtitle'] ?? '')),
                    'cta_text' => trim((string) ($row['cta_text'] ?? '')),
                    'cta_url'  => trim((string) ($row['cta_url'] ?? '')),
                    'modo'     => in_array((string) ($row['modo'] ?? ''), ['secao', 'desconto', 'todos', 'recentes'], true) ? (string) $row['modo'] : 'secao',
                    'valor'    => trim((string) ($row['valor'] ?? '')),
                    'limite'   => max(1, min(24, (int) ($row['limite'] ?? 8))),
                ];
            }
            xll_setting_save('sections', $sections);
            adm_redirect('home.php?sec=sections', 'Seções de produtos atualizadas.');

        case 'compare':
            $image = adm_text('bg_image');
            [$image, $error] = adm_upload('compare_bg_upload', 'comparar', $image);
            if ($error !== '') {
                adm_redirect('home.php', 'Faixa comparador: ' . $error, 'error');
            }
            xll_setting_save('compare', [
                'enabled'    => adm_checked('enabled'),
                'eyebrow'    => adm_text('eyebrow'),
                'title'      => adm_text('title'),
                'text'       => adm_text('text'),
                'cta_text'   => adm_text('cta_text'),
                'empty_text' => adm_text('empty_text'),
                'bg_image'   => $image,
                'bg_color'   => xll_valid_hex(adm_text('bg_color'), '#04323c'),
            ]);
            adm_redirect('home.php?sec=compare', 'Faixa de comparação atualizada.');

        case 'final_cta':
            $image = adm_text('bg_image');
            [$image, $error] = adm_upload('final_bg_upload', 'cta-final', $image);
            if ($error !== '') {
                adm_redirect('home.php', 'CTA final: ' . $error, 'error');
            }
            xll_setting_save('final_cta', [
                'enabled'  => adm_checked('enabled'),
                'title'    => adm_text('title'),
                'text'     => adm_text('text'),
                'cta_text' => adm_text('cta_text'),
                'cta_url'  => adm_text('cta_url'),
                'bg_image' => $image,
                'bg_color' => xll_valid_hex(adm_text('bg_color'), '#04323c'),
            ]);
            adm_redirect('home.php?sec=final', 'CTA final atualizado.');
    }
}

$hero       = xll_setting_group('hero');
$promos     = xll_setting_group('promos');
$features   = xll_setting_group('features');
$cats       = xll_setting_group('categories');
$sections   = xll_setting_group('sections');
$compare    = xll_setting_group('compare');
$finalCta   = xll_setting_group('final_cta');
$heroSlides = array_values(is_array($hero['slides'] ?? null) ? $hero['slides'] : []);
$featureItems = array_values(is_array($features['items'] ?? null) ? $features['items'] : []);
$categoryItems = array_values(is_array($cats['items'] ?? null) ? $cats['items'] : []);
$promoItems = array_values(is_array($promos) ? $promos : []);
$icons = xll_icon_options();
$scrollTo = (string) ($_GET['sec'] ?? '');

adm_header('Página inicial', 'home');
?>

<div class="adm-tabs" aria-label="Seções da página inicial">
    <a class="adm-tab" href="#hero">Banner do topo</a>
    <a class="adm-tab" href="#promos">Cards promocionais</a>
    <a class="adm-tab" href="#features">Diferenciais</a>
    <a class="adm-tab" href="#categories">Categorias</a>
    <a class="adm-tab" href="#sections">Seções de produtos</a>
    <a class="adm-tab" href="#compare">Faixa comparador</a>
    <a class="adm-tab" href="#final">CTA final</a>
    <a class="adm-tab" href="<?= e(xll_url('index.php')) ?>" target="_blank" rel="noopener">Ver página ↗</a>
</div>

<!-- ================= HERO ================= -->
<div class="adm-card" id="hero">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('image') ?> Banner principal (carrossel)</div>
            <div class="adm-card-sub">Imagens do topo da home. Recomendado: 1600 × 533 px (proporção 3:1), formato JPG/PNG/WEBP.</div>
        </div>
    </div>
    <form method="post" action="<?= e(xll_url('admin/home.php')) ?>" enctype="multipart/form-data">
        <?= xll_csrf_field() ?>
        <input type="hidden" name="acao" value="hero">
        <div class="adm-check-row" style="margin-bottom:14px">
            <label class="adm-check"><input type="checkbox" name="enabled" <?= !empty($hero['enabled']) ? 'checked' : '' ?>> Exibir banner na home</label>
            <label class="adm-check"><input type="checkbox" name="autoplay" <?= !empty($hero['autoplay']) ? 'checked' : '' ?>> Rodar automaticamente</label>
            <label class="adm-check" style="gap:8px">Velocidade (ms)
                <input class="adm-input" style="width:100px;height:32px" type="number" name="speed" min="2000" max="30000" step="500" value="<?= (int) ($hero['speed'] ?? 6000) ?>">
            </label>
        </div>

        <div class="adm-repeater" data-repeater data-next-index="<?= count($heroSlides) ?>">
            <div class="adm-repeater-items" data-repeater-items>
                <?php foreach ($heroSlides as $index => $slide): ?>
                    <div class="adm-repeater-item" data-index="<?= $index ?>">
                        <div class="adm-repeater-head">
                            <span class="adm-repeater-title">Banner <span data-repeater-number><?= $index + 1 ?></span></span>
                            <label class="adm-check"><input type="checkbox" name="slides[<?= $index ?>][active]" <?= !isset($slide['active']) || $slide['active'] ? 'checked' : '' ?>> Ativo</label>
                            <button type="button" class="adm-repeater-remove" data-repeater-remove data-confirm="Remover este banner?">Remover</button>
                        </div>
                        <div class="adm-grid">
                            <div class="adm-field adm-field-full">
                                <label class="adm-label">Imagem do banner</label>
                                <div class="adm-image-field">
                                    <div class="adm-image-preview">
                                        <?php if (!empty($slide['image'])): ?><img src="<?= e(xll_media_url((string) $slide['image'])) ?>" alt=""><?php endif; ?>
                                    </div>
                                    <div class="adm-image-fields">
                                        <input class="adm-input" type="text" name="slides[<?= $index ?>][image]" value="<?= e($slide['image'] ?? '') ?>" placeholder="assets/img/hero-ofertas.webp">
                                        <input class="adm-file" type="file" name="slide_image_<?= $index ?>" accept="image/*" data-preview="1">
                                        <span class="adm-help">Envie um arquivo para substituir a imagem atual.</span>
                                    </div>
                                </div>
                            </div>
                            <div class="adm-field">
                                <label class="adm-label">Link ao clicar</label>
                                <input class="adm-input" type="text" name="slides[<?= $index ?>][link]" value="<?= e($slide['link'] ?? '') ?>" placeholder="ofertas.php">
                            </div>
                            <div class="adm-field">
                                <label class="adm-label">Texto alternativo (SEO/acessibilidade)</label>
                                <input class="adm-input" type="text" name="slides[<?= $index ?>][alt]" value="<?= e($slide['alt'] ?? '') ?>" placeholder="Ofertas gamer em um só lugar">
                            </div>
                            <div class="adm-field adm-field-full">
                                <label class="adm-label">Imagem mobile (opcional)</label>
                                <input class="adm-input" type="text" name="slides[<?= $index ?>][mobile]" value="<?= e($slide['mobile'] ?? '') ?>" placeholder="Deixe vazio para usar a mesma imagem">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <template data-repeater-template>
                <div class="adm-repeater-item" data-index="__INDEX__">
                    <div class="adm-repeater-head">
                        <span class="adm-repeater-title">Banner <span data-repeater-number></span></span>
                        <label class="adm-check"><input type="checkbox" name="slides[__INDEX__][active]" checked> Ativo</label>
                        <button type="button" class="adm-repeater-remove" data-repeater-remove data-confirm="Remover este banner?">Remover</button>
                    </div>
                    <div class="adm-grid">
                        <div class="adm-field adm-field-full">
                            <label class="adm-label">Imagem do banner</label>
                            <div class="adm-image-field">
                                <div class="adm-image-preview">Nenhuma imagem</div>
                                <div class="adm-image-fields">
                                    <input class="adm-input" type="text" name="slides[__INDEX__][image]" value="" placeholder="assets/img/hero-ofertas.webp">
                                    <input class="adm-file" type="file" name="slide_image___INDEX__" accept="image/*" data-preview="1">
                                </div>
                            </div>
                        </div>
                        <div class="adm-field">
                            <label class="adm-label">Link ao clicar</label>
                            <input class="adm-input" type="text" name="slides[__INDEX__][link]" value="ofertas.php">
                        </div>
                        <div class="adm-field">
                            <label class="adm-label">Texto alternativo</label>
                            <input class="adm-input" type="text" name="slides[__INDEX__][alt]" value="">
                        </div>
                        <div class="adm-field adm-field-full">
                            <label class="adm-label">Imagem mobile (opcional)</label>
                            <input class="adm-input" type="text" name="slides[__INDEX__][mobile]" value="">
                        </div>
                    </div>
                </div>
            </template>

            <button type="button" class="adm-btn adm-btn-secondary" data-repeater-add><?= adm_icon('plus') ?> Adicionar banner</button>
        </div>

        <div class="adm-actions" style="margin-top:18px">
            <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Salvar banners</button>
        </div>
    </form>
</div>

<!-- ================= PROMOS ================= -->
<div class="adm-card" id="promos">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('image') ?> Cards promocionais</div>
            <div class="adm-card-sub">Os dois banners abaixo do hero. A imagem é o fundo do card e os textos são sobrepostos.</div>
        </div>
    </div>
    <form method="post" action="<?= e(xll_url('admin/home.php')) ?>" enctype="multipart/form-data">
        <?= xll_csrf_field() ?>
        <input type="hidden" name="acao" value="promos">
        <div class="adm-repeater" data-repeater data-next-index="<?= count($promoItems) ?>">
            <div class="adm-repeater-items" data-repeater-items>
                <?php foreach ($promoItems as $index => $promo): ?>
                    <div class="adm-repeater-item" data-index="<?= $index ?>">
                        <div class="adm-repeater-head">
                            <span class="adm-repeater-title">Card <span data-repeater-number><?= $index + 1 ?></span></span>
                            <label class="adm-check"><input type="checkbox" name="promos[<?= $index ?>][active]" <?= !isset($promo['active']) || $promo['active'] ? 'checked' : '' ?>> Ativo</label>
                            <button type="button" class="adm-repeater-remove" data-repeater-remove data-confirm="Remover este card?">Remover</button>
                        </div>
                        <div class="adm-grid">
                            <div class="adm-field adm-field-full">
                                <label class="adm-label">Imagem de fundo</label>
                                <div class="adm-image-field">
                                    <div class="adm-image-preview">
                                        <?php if (!empty($promo['image'])): ?><img src="<?= e(xll_media_url((string) $promo['image'])) ?>" alt=""><?php endif; ?>
                                    </div>
                                    <div class="adm-image-fields">
                                        <input class="adm-input" type="text" name="promos[<?= $index ?>][image]" value="<?= e($promo['image'] ?? '') ?>" placeholder="assets/img/promo-descontos.webp">
                                        <input class="adm-file" type="file" name="promo_image_<?= $index ?>" accept="image/*" data-preview="1">
                                    </div>
                                </div>
                            </div>
                            <div class="adm-field">
                                <label class="adm-label">Título</label>
                                <input class="adm-input" type="text" name="promos[<?= $index ?>][title]" value="<?= e($promo['title'] ?? '') ?>">
                            </div>
                            <div class="adm-field">
                                <label class="adm-label">Subtítulo</label>
                                <input class="adm-input" type="text" name="promos[<?= $index ?>][subtitle]" value="<?= e($promo['subtitle'] ?? '') ?>">
                            </div>
                            <div class="adm-field">
                                <label class="adm-label">Texto do botão</label>
                                <input class="adm-input" type="text" name="promos[<?= $index ?>][cta]" value="<?= e($promo['cta'] ?? '') ?>">
                            </div>
                            <div class="adm-field">
                                <label class="adm-label">Link do card</label>
                                <input class="adm-input" type="text" name="promos[<?= $index ?>][link]" value="<?= e($promo['link'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <template data-repeater-template>
                <div class="adm-repeater-item" data-index="__INDEX__">
                    <div class="adm-repeater-head">
                        <span class="adm-repeater-title">Card <span data-repeater-number></span></span>
                        <label class="adm-check"><input type="checkbox" name="promos[__INDEX__][active]" checked> Ativo</label>
                        <button type="button" class="adm-repeater-remove" data-repeater-remove data-confirm="Remover este card?">Remover</button>
                    </div>
                    <div class="adm-grid">
                        <div class="adm-field adm-field-full">
                            <label class="adm-label">Imagem de fundo</label>
                            <div class="adm-image-field">
                                <div class="adm-image-preview">Nenhuma imagem</div>
                                <div class="adm-image-fields">
                                    <input class="adm-input" type="text" name="promos[__INDEX__][image]" value="">
                                    <input class="adm-file" type="file" name="promo_image___INDEX__" accept="image/*" data-preview="1">
                                </div>
                            </div>
                        </div>
                        <div class="adm-field"><label class="adm-label">Título</label><input class="adm-input" type="text" name="promos[__INDEX__][title]" value=""></div>
                        <div class="adm-field"><label class="adm-label">Subtítulo</label><input class="adm-input" type="text" name="promos[__INDEX__][subtitle]" value=""></div>
                        <div class="adm-field"><label class="adm-label">Texto do botão</label><input class="adm-input" type="text" name="promos[__INDEX__][cta]" value="Conferir"></div>
                        <div class="adm-field"><label class="adm-label">Link do card</label><input class="adm-input" type="text" name="promos[__INDEX__][link]" value="ofertas.php"></div>
                    </div>
                </div>
            </template>
            <button type="button" class="adm-btn adm-btn-secondary" data-repeater-add><?= adm_icon('plus') ?> Adicionar card</button>
        </div>
        <div class="adm-actions" style="margin-top:18px">
            <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Salvar cards</button>
        </div>
    </form>
</div>

<!-- ================= FEATURES ================= -->
<div class="adm-card" id="features">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('box') ?> Diferenciais (“Por que usar”)</div>
            <div class="adm-card-sub">Informe o código SVG (ou um emoji) e/ou envie uma imagem.</div>
        </div>
    </div>
    <form method="post" action="<?= e(xll_url('admin/home.php')) ?>" enctype="multipart/form-data">
        <?= xll_csrf_field() ?>
        <input type="hidden" name="acao" value="features">
        <div class="adm-field" style="margin-bottom:16px">
            <label class="adm-label">Título da seção</label>
            <input class="adm-input" type="text" name="features_title" value="<?= e($features['title'] ?? '') ?>">
        </div>
        <div class="adm-repeater" data-repeater data-next-index="<?= count($featureItems) ?>">
            <div class="adm-repeater-items" data-repeater-items>
                <?php foreach ($featureItems as $index => $item): ?>
                    <div class="adm-repeater-item" data-index="<?= $index ?>">
                        <div class="adm-repeater-head">
                            <span class="adm-repeater-title">Diferencial <span data-repeater-number><?= $index + 1 ?></span></span>
                            <button type="button" class="adm-repeater-remove" data-repeater-remove data-confirm="Remover este diferencial?">Remover</button>
                        </div>
                        <div class="adm-grid">
<div class="adm-field adm-field-full">
                                <label class="adm-label">Ícone</label>
                                <div class="adm-svg-field" data-svg-field>
                                    <div class="adm-svg-editor">
                                        <textarea class="adm-input adm-svg-input" name="features[<?= $index ?>][icon]" rows="3" spellcheck="false" placeholder="Cole o código &lt;svg&gt;...&lt;/svg&gt; ou um emoji"><?= e((string) ($item['icon'] ?? '')) ?></textarea>
                                        <div class="adm-svg-preview" data-svg-preview><?= xll_svg_icon((string) ($item['icon'] ?? '')) ?></div>
                                    </div>
                                    <div class="adm-svg-presets">
                                        <?php foreach ($icons as $value => $label): ?>
                                            <button type="button" class="adm-svg-preset" data-svg-preset="<?= e($value) ?>" title="<?= e($label) ?>"><?= xll_svg_icon($value) ?><span><?= e($label) ?></span></button>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="adm-help">Cole um SVG (Figma, Iconify) ou clique em um preset. Emoji também funciona.</div>
                                </div>
                            </div>
                            <div class="adm-field adm-field-full">
                                <label class="adm-label">Imagem do ícone (opcional)</label>
                                <div class="adm-image-field">
                                    <div class="adm-image-preview" style="width:70px;height:70px">
                                        <?php if (!empty($item['icon_image'])): ?><img src="<?= e(xll_media_url((string) $item['icon_image'])) ?>" alt=""><?php endif; ?>
                                    </div>
                                    <div class="adm-image-fields">
                                        <input class="adm-input" type="text" name="features[<?= $index ?>][icon_image]" value="<?= e($item['icon_image'] ?? '') ?>" placeholder="assets/img/icone.webp">
                                        <input class="adm-file" type="file" name="feature_image_<?= $index ?>" accept="image/*" data-preview="1">
                                    </div>
                                </div>
                            </div>
                            <div class="adm-field adm-field-full">
                                <label class="adm-label">Título</label>
                                <input class="adm-input" type="text" name="features[<?= $index ?>][title]" value="<?= e($item['title'] ?? '') ?>">
                            </div>
                            <div class="adm-field adm-field-full">
                                <label class="adm-label">Texto</label>
                                <input class="adm-input" type="text" name="features[<?= $index ?>][text]" value="<?= e($item['text'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <template data-repeater-template>
                <div class="adm-repeater-item" data-index="__INDEX__">
                    <div class="adm-repeater-head">
                        <span class="adm-repeater-title">Diferencial <span data-repeater-number></span></span>
                        <button type="button" class="adm-repeater-remove" data-repeater-remove data-confirm="Remover este diferencial?">Remover</button>
                    </div>
                    <div class="adm-grid">
                        <div class="adm-field adm-field-full">
                                <label class="adm-label">Ícone</label>
                                <div class="adm-svg-field" data-svg-field>
                                    <div class="adm-svg-editor">
                                        <textarea class="adm-input adm-svg-input" name="features[__INDEX__][icon]" rows="3" spellcheck="false" placeholder="Cole o código &lt;svg&gt;...&lt;/svg&gt; ou um emoji"></textarea>
                                        <div class="adm-svg-preview" data-svg-preview></div>
                                    </div>
                                    <div class="adm-svg-presets">
                                        <?php foreach ($icons as $value => $label): ?>
                                            <button type="button" class="adm-svg-preset" data-svg-preset="<?= e($value) ?>" title="<?= e($label) ?>"><?= xll_svg_icon($value) ?><span><?= e($label) ?></span></button>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="adm-help">Cole um SVG (Figma, Iconify) ou clique em um preset. Emoji também funciona.</div>
                                </div>
                            </div>
                        <div class="adm-field adm-field-full"><label class="adm-label">Imagem do ícone (opcional)</label>
                            <div class="adm-image-field">
                                <div class="adm-image-preview" style="width:70px;height:70px"></div>
                                <div class="adm-image-fields">
                                    <input class="adm-input" type="text" name="features[__INDEX__][icon_image]" value="">
                                    <input class="adm-file" type="file" name="feature_image___INDEX__" accept="image/*" data-preview="1">
                                </div>
                            </div>
                        </div>
                        <div class="adm-field adm-field-full"><label class="adm-label">Título</label><input class="adm-input" type="text" name="features[__INDEX__][title]" value=""></div>
                        <div class="adm-field adm-field-full"><label class="adm-label">Texto</label><input class="adm-input" type="text" name="features[__INDEX__][text]" value=""></div>
                    </div>
                </div>
            </template>
            <button type="button" class="adm-btn adm-btn-secondary" data-repeater-add><?= adm_icon('plus') ?> Adicionar diferencial</button>
        </div>
        <div class="adm-actions" style="margin-top:18px">
            <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Salvar diferenciais</button>
        </div>
    </form>
</div>

<!-- ================= CATEGORIAS ================= -->
<div class="adm-card" id="categories">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('box') ?> Categorias da home</div>
            <div class="adm-card-sub">Os ícones e a quantidade de ofertas são atualizados automaticamente conforme os produtos cadastrados.</div>
        </div>
    </div>
    <form method="post" action="<?= e(xll_url('admin/home.php')) ?>" enctype="multipart/form-data">
        <?= xll_csrf_field() ?>
        <input type="hidden" name="acao" value="categories">
        <div class="adm-field" style="margin-bottom:16px;max-width:420px">
            <label class="adm-label">Título da seção</label>
            <input class="adm-input" type="text" name="categories_title" value="<?= e($cats['title'] ?? '') ?>">
        </div>
        <div class="adm-repeater" data-repeater data-next-index="<?= count($categoryItems) ?>">
            <div class="adm-repeater-items" data-repeater-items>
                <?php foreach ($categoryItems as $index => $item): ?>
                    <div class="adm-repeater-item" data-index="<?= $index ?>">
                        <div class="adm-repeater-head">
                            <span class="adm-repeater-title">Categoria <span data-repeater-number><?= $index + 1 ?></span></span>
                            <label class="adm-check"><input type="checkbox" name="categories[<?= $index ?>][active]" <?= !isset($item['active']) || $item['active'] ? 'checked' : '' ?>> Ativa</label>
                            <button type="button" class="adm-repeater-remove" data-repeater-remove data-confirm="Remover esta categoria?">Remover</button>
                        </div>
                        <div class="adm-grid adm-grid-3">
                            <div class="adm-field">
                                <label class="adm-label">Nome</label>
                                <input class="adm-input" type="text" name="categories[<?= $index ?>][name]" value="<?= e($item['name'] ?? '') ?>">
                            </div>
                            <div class="adm-field">
                                <label class="adm-label">Ícone SVG</label>
                                <select class="adm-select" name="categories[<?= $index ?>][icon]">
                                    <?php foreach ($icons as $value => $label): ?>
                                        <option value="<?= e($value) ?>" <?= ($item['icon'] ?? '') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="adm-field">
                                <label class="adm-label">Link (opcional)</label>
                                <input class="adm-input" type="text" name="categories[<?= $index ?>][link]" value="<?= e($item['link'] ?? '') ?>" placeholder="ofertas.php?categoria=...">
                            </div>
                            <div class="adm-field adm-field-full">
                                <label class="adm-label">Descrição</label>
                                <input class="adm-input" type="text" name="categories[<?= $index ?>][description]" value="<?= e($item['description'] ?? '') ?>">
                            </div>
                            <div class="adm-field adm-field-full">
                                <label class="adm-label">Imagem do ícone (opcional, substitui o SVG)</label>
                                <div class="adm-image-field">
                                    <div class="adm-image-preview" style="width:70px;height:70px">
                                        <?php if (!empty($item['image'])): ?><img src="<?= e(xll_media_url((string) $item['image'])) ?>" alt=""><?php endif; ?>
                                    </div>
                                    <div class="adm-image-fields">
                                        <input class="adm-input" type="text" name="categories[<?= $index ?>][image]" value="<?= e($item['image'] ?? '') ?>">
                                        <input class="adm-file" type="file" name="category_image_<?= $index ?>" accept="image/*" data-preview="1">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <template data-repeater-template>
                <div class="adm-repeater-item" data-index="__INDEX__">
                    <div class="adm-repeater-head">
                        <span class="adm-repeater-title">Categoria <span data-repeater-number></span></span>
                        <label class="adm-check"><input type="checkbox" name="categories[__INDEX__][active]" checked> Ativa</label>
                        <button type="button" class="adm-repeater-remove" data-repeater-remove data-confirm="Remover esta categoria?">Remover</button>
                    </div>
                    <div class="adm-grid adm-grid-3">
                        <div class="adm-field"><label class="adm-label">Nome</label><input class="adm-input" type="text" name="categories[__INDEX__][name]" value=""></div>
                        <div class="adm-field"><label class="adm-label">Ícone SVG</label>
                            <select class="adm-select" name="categories[__INDEX__][icon]">
                                <?php foreach ($icons as $value => $label): ?>
                                    <option value="<?= e($value) ?>"><?= e($label) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="adm-field"><label class="adm-label">Link (opcional)</label><input class="adm-input" type="text" name="categories[__INDEX__][link]" value=""></div>
                        <div class="adm-field adm-field-full"><label class="adm-label">Descrição</label><input class="adm-input" type="text" name="categories[__INDEX__][description]" value=""></div>
                        <div class="adm-field adm-field-full"><label class="adm-label">Imagem do ícone (opcional)</label>
                            <div class="adm-image-field">
                                <div class="adm-image-preview" style="width:70px;height:70px"></div>
                                <div class="adm-image-fields">
                                    <input class="adm-input" type="text" name="categories[__INDEX__][image]" value="">
                                    <input class="adm-file" type="file" name="category_image___INDEX__" accept="image/*" data-preview="1">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
            <button type="button" class="adm-btn adm-btn-secondary" data-repeater-add><?= adm_icon('plus') ?> Adicionar categoria</button>
        </div>
        <div class="adm-actions" style="margin-top:18px">
            <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Salvar categorias</button>
        </div>
    </form>
</div>

<!-- ================= SEÇÕES ================= -->
<div class="adm-card" id="sections">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('box') ?> Seções de produtos</div>
            <div class="adm-card-sub">Escolha o que aparece em cada seção da home: filtro por seção do produto ou ordenação automática.</div>
        </div>
    </div>
    <form method="post" action="<?= e(xll_url('admin/home.php')) ?>">
        <?= xll_csrf_field() ?>
        <input type="hidden" name="acao" value="sections">
        <?php
        $sectionLabels = [
            'ofertas'     => '🔥 Ofertas em destaque',
            'setup'       => 'Produtos que podem melhorar seu setup',
            'bestsellers' => '🏆 Mais vendidos',
        ];
        foreach ($sectionLabels as $key => $label):
            $cfg = $sections[$key] ?? [];
            ?>
            <div class="adm-repeater-item" style="margin-bottom:14px">
                <div class="adm-repeater-head">
                    <span class="adm-repeater-title"><?= e($label) ?></span>
                    <label class="adm-check"><input type="checkbox" name="sections[<?= $key ?>][enabled]" <?= !empty($cfg['enabled']) ? 'checked' : '' ?>> Exibir seção</label>
                </div>
                <div class="adm-grid adm-grid-4">
                    <div class="adm-field adm-field-full">
                        <label class="adm-label">Título</label>
                        <input class="adm-input" type="text" name="sections[<?= $key ?>][title]" value="<?= e($cfg['title'] ?? '') ?>">
                    </div>
                    <div class="adm-field adm-field-full">
                        <label class="adm-label">Subtítulo</label>
                        <input class="adm-input" type="text" name="sections[<?= $key ?>][subtitle]" value="<?= e($cfg['subtitle'] ?? '') ?>">
                    </div>
                    <div class="adm-field">
                        <label class="adm-label">Texto do botão</label>
                        <input class="adm-input" type="text" name="sections[<?= $key ?>][cta_text]" value="<?= e($cfg['cta_text'] ?? '') ?>">
                    </div>
                    <div class="adm-field">
                        <label class="adm-label">Link do botão</label>
                        <input class="adm-input" type="text" name="sections[<?= $key ?>][cta_url]" value="<?= e($cfg['cta_url'] ?? '') ?>">
                    </div>
                    <div class="adm-field">
                        <label class="adm-label">Como escolher</label>
                        <select class="adm-select" name="sections[<?= $key ?>][modo]">
                            <option value="secao" <?= ($cfg['modo'] ?? '') === 'secao' ? 'selected' : '' ?>>Filtrar por seção do produto</option>
                            <option value="desconto" <?= ($cfg['modo'] ?? '') === 'desconto' ? 'selected' : '' ?>>Maiores descontos</option>
                            <option value="recentes" <?= ($cfg['modo'] ?? '') === 'recentes' ? 'selected' : '' ?>>Ordem inversa (últimos)</option>
                            <option value="todos" <?= ($cfg['modo'] ?? '') === 'todos' ? 'selected' : '' ?>>Ordem padrão do catálogo</option>
                        </select>
                    </div>
                    <div class="adm-field">
                        <label class="adm-label">Nome da seção (se filtrar)</label>
                        <input class="adm-input" type="text" name="sections[<?= $key ?>][valor]" value="<?= e($cfg['valor'] ?? '') ?>" placeholder="Destaque">
                        <span class="adm-help">Use exatamente o valor do campo “Seção da home” dos produtos.</span>
                    </div>
                    <div class="adm-field">
                        <label class="adm-label">Quantidade de produtos</label>
                        <input class="adm-input" type="number" name="sections[<?= $key ?>][limite]" min="1" max="24" value="<?= (int) ($cfg['limite'] ?? 8) ?>">
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="adm-actions" style="margin-top:6px">
            <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Salvar seções</button>
        </div>
    </form>
</div>

<!-- ================= COMPARE ================= -->
<div class="adm-card" id="compare">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('chart') ?> Faixa “Compare dois produtos”</div>
            <div class="adm-card-sub">Faixa com fundo configurável entre as seções de ofertas e de setup.</div>
        </div>
    </div>
    <form method="post" action="<?= e(xll_url('admin/home.php')) ?>" enctype="multipart/form-data">
        <?= xll_csrf_field() ?>
        <input type="hidden" name="acao" value="compare">
        <div class="adm-check-row" style="margin-bottom:14px">
            <label class="adm-check"><input type="checkbox" name="enabled" <?= !empty($compare['enabled']) ? 'checked' : '' ?>> Exibir faixa na home</label>
        </div>
        <div class="adm-grid">
            <div class="adm-field">
                <label class="adm-label">Sobretítulo</label>
                <input class="adm-input" type="text" name="eyebrow" value="<?= e($compare['eyebrow'] ?? '') ?>">
            </div>
            <div class="adm-field">
                <label class="adm-label">Texto quando vazio</label>
                <input class="adm-input" type="text" name="empty_text" value="<?= e($compare['empty_text'] ?? '') ?>">
            </div>
            <div class="adm-field adm-field-full">
                <label class="adm-label">Título</label>
                <input class="adm-input" type="text" name="title" value="<?= e($compare['title'] ?? '') ?>">
            </div>
            <div class="adm-field adm-field-full">
                <label class="adm-label">Texto</label>
                <textarea class="adm-textarea" name="text"><?= e($compare['text'] ?? '') ?></textarea>
            </div>
            <div class="adm-field">
                <label class="adm-label">Texto do botão</label>
                <input class="adm-input" type="text" name="cta_text" value="<?= e($compare['cta_text'] ?? '') ?>">
            </div>
            <div class="adm-field">
                <label class="adm-label">Cor de fundo</label>
                <input class="adm-input" type="text" name="bg_color" value="<?= e($compare['bg_color'] ?? '#04323c') ?>">
            </div>
        </div>
        <?php adm_image_field('bg_image', (string) ($compare['bg_image'] ?? ''), 'compare_bg_upload', 'Imagem de fundo da faixa', 'Recomendado: 1600 × 533 px.'); ?>
        <div class="adm-actions" style="margin-top:16px">
            <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Salvar faixa</button>
        </div>
    </form>
</div>

<!-- ================= FINAL CTA ================= -->
<div class="adm-card" id="final">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('chart') ?> CTA final</div>
            <div class="adm-card-sub">Chamada final acima do rodapé (“Pronto para melhorar seu setup?”).</div>
        </div>
    </div>
    <form method="post" action="<?= e(xll_url('admin/home.php')) ?>" enctype="multipart/form-data">
        <?= xll_csrf_field() ?>
        <input type="hidden" name="acao" value="final_cta">
        <div class="adm-check-row" style="margin-bottom:14px">
            <label class="adm-check"><input type="checkbox" name="enabled" <?= !empty($finalCta['enabled']) ? 'checked' : '' ?>> Exibir CTA final</label>
        </div>
        <div class="adm-grid">
            <div class="adm-field adm-field-full">
                <label class="adm-label">Título</label>
                <input class="adm-input" type="text" name="title" value="<?= e($finalCta['title'] ?? '') ?>">
            </div>
            <div class="adm-field adm-field-full">
                <label class="adm-label">Texto</label>
                <input class="adm-input" type="text" name="text" value="<?= e($finalCta['text'] ?? '') ?>">
            </div>
            <div class="adm-field">
                <label class="adm-label">Texto do botão</label>
                <input class="adm-input" type="text" name="cta_text" value="<?= e($finalCta['cta_text'] ?? '') ?>">
            </div>
            <div class="adm-field">
                <label class="adm-label">Link do botão</label>
                <input class="adm-input" type="text" name="cta_url" value="<?= e($finalCta['cta_url'] ?? '') ?>">
            </div>
            <div class="adm-field">
                <label class="adm-label">Cor de fundo</label>
                <input class="adm-input" type="text" name="bg_color" value="<?= e($finalCta['bg_color'] ?? '#04323c') ?>">
            </div>
        </div>
        <?php adm_image_field('bg_image', (string) ($finalCta['bg_image'] ?? ''), 'final_bg_upload', 'Imagem de fundo do CTA', 'Recomendado: 1600 × 533 px.'); ?>
        <div class="adm-actions" style="margin-top:16px">
            <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Salvar CTA</button>
        </div>
    </form>
</div>

<?php if ($scrollTo !== ''): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var target = document.getElementById(<?= json_encode($scrollTo) ?>);
    if (target) { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
});
</script>
<?php endif; ?>

<?php adm_footer(); ?>
