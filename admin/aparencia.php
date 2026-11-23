<?php
/**
 * Loot Gamer - aparência: marca, cores e fontes.
 */

declare(strict_types=1);

require __DIR__ . '/inc/layout.php';

$defaults = xll_defaults();

$fontOptions = [
    'Inter'           => 'Inter (padrão)',
    'Plus Jakarta Sans' => 'Plus Jakarta Sans',
    'Poppins'         => 'Poppins',
    'Montserrat'      => 'Montserrat',
    'Manrope'         => 'Manrope',
    'Space Grotesk'   => 'Space Grotesk',
    'Sora'            => 'Sora',
    'Rubik'           => 'Rubik',
    'Barlow'          => 'Barlow',
    'DM Sans'         => 'DM Sans',
    'Nunito'          => 'Nunito',
    'Work Sans'       => 'Work Sans',
    'Outfit'          => 'Outfit',
    'Urbanist'        => 'Urbanist',
    'Rajdhani'        => 'Rajdhani',
    'Orbitron'        => 'Orbitron',
    'Oxanium'         => 'Oxanium',
    'Chakra Petch'    => 'Chakra Petch',
    'Exo 2'           => 'Exo 2',
];

if (xll_is_post()) {
    xll_require_csrf();
    $acao = adm_text('acao');

    if ($acao === 'branding') {
        $logo = adm_text('logo');
        [$logo, $error] = adm_upload('logo_upload', 'logo', $logo);
        if ($error !== '') {
            adm_redirect('aparencia.php', 'Logo: ' . $error, 'error');
        }
        $logoFooter = adm_text('logo_footer');
        [$logoFooter, $error] = adm_upload('logo_footer_upload', 'logo-footer', $logoFooter);
        if ($error !== '') {
            adm_redirect('aparencia.php', 'Logo do rodapé: ' . $error, 'error');
        }
        $favicon = adm_text('favicon');
        [$favicon, $error] = adm_upload('favicon_upload', 'favicon', $favicon);
        if ($error !== '') {
            adm_redirect('aparencia.php', 'Favicon: ' . $error, 'error');
        }

        $siteUrl = trim((string) ($_POST['site_url'] ?? ''));
        if ($siteUrl !== '' && !preg_match('~^https?://[^\s]+$~i', $siteUrl)) {
            $siteUrl = '';
        }

        xll_settings_save_many([
            'site_name'        => adm_text('site_name', (string) $defaults['site_name']),
            'site_slogan'      => adm_text('site_slogan'),
            'meta_description' => adm_text('meta_description'),
            'theme_color'      => xll_valid_hex(adm_text('theme_color'), (string) $defaults['theme_color']),
            'logo'             => $logo !== '' ? $logo : $defaults['logo'],
            'logo_footer'      => $logoFooter !== '' ? $logoFooter : $defaults['logo_footer'],
            'favicon'          => $favicon !== '' ? $favicon : $defaults['favicon'],
            'site_url'         => rtrim($siteUrl, '/'),
            'pretty_urls'      => adm_checked('pretty_urls') ? '1' : '0',
            'track_clicks'     => adm_checked('track_clicks') ? '1' : '0',
        ]);
        adm_redirect('aparencia.php?sec=marca', 'Identidade visual e SEO atualizados.');
    }

    if ($acao === 'limpar_cache') {
        $removed = 0;
        $cacheDir = XLL_ROOT . '/data/cache/img';
        foreach (glob($cacheDir . '/*') ?: [] as $file) {
            if (is_file($file) && @unlink($file)) {
                $removed++;
            }
        }
        adm_redirect('aparencia.php?sec=marca', 'Cache de imagens limpo (' . $removed . ' arquivo(s)).');
    }

    if ($acao === 'colors') {
        $posted = adm_array('colors');
        $colors = [];
        foreach (['background', 'card', 'border', 'text', 'muted', 'accent', 'discount', 'button_text', 'footer_title'] as $key) {
            $colors[$key] = xll_valid_hex((string) ($posted[$key] ?? ''), (string) $defaults['colors'][$key]);
        }
        $colors['radius'] = max(0, min(24, (int) ($posted['radius'] ?? 6)));
        xll_setting_save('colors', $colors);
        adm_redirect('aparencia.php?sec=cores', 'Cores atualizadas.');
    }

    if ($acao === 'fonts') {
        $body = adm_text('body', 'Inter');
        $heading = adm_text('heading', 'Inter');
        if (!isset($fontOptions[$body])) {
            $body = 'Inter';
        }
        if (!isset($fontOptions[$heading])) {
            $heading = 'Inter';
        }
        $googleUrl = trim((string) ($_POST['google_url'] ?? ''));
        if ($googleUrl !== '' && !preg_match('#^https://fonts\.googleapis\.com/#', $googleUrl)) {
            $googleUrl = '';
        }
        xll_setting_save('fonts', [
            'body'       => $body,
            'heading'    => $heading,
            'body_size'  => max(12, min(22, adm_int('body_size', 15))),
            'h1_size'    => max(20, min(64, adm_int('h1_size', 30))),
            'h2_size'    => max(16, min(52, adm_int('h2_size', 24))),
            'h3_size'    => max(14, min(44, adm_int('h3_size', 20))),
            'google_url' => $googleUrl,
        ]);
        adm_redirect('aparencia.php?sec=fontes', 'Fontes atualizadas.');
    }

    if ($acao === 'reset_colors') {
        xll_setting_save('colors', $defaults['colors']);
        adm_redirect('aparencia.php?sec=cores', 'Cores restauradas para o padrão.');
    }

    if ($acao === 'reset_fonts') {
        xll_setting_save('fonts', $defaults['fonts']);
        adm_redirect('aparencia.php?sec=fontes', 'Fontes restauradas para o padrão.');
    }
}

$siteName = (string) xll_setting('site_name', 'Loot Gamer');
$slogan = (string) xll_setting('site_slogan', '');
$metaDescription = (string) xll_setting('meta_description', '');
$themeColor = (string) xll_setting('theme_color', '#01191f');
$logo = (string) xll_setting('logo', 'assets/img/logo.webp');
$logoFooter = (string) xll_setting('logo_footer', 'assets/img/logo.webp');
$favicon = (string) xll_setting('favicon', 'assets/img/favicon-192.png');
$colors = xll_setting_group('colors');
$fonts = xll_setting_group('fonts');
$scrollTo = (string) ($_GET['sec'] ?? '');

$previewStyle = sprintf(
    '--adm-preview-bg:%s;--adm-preview-card:%s;--adm-preview-border:%s;--adm-preview-text:%s;--adm-preview-muted:%s;--adm-preview-accent:%s;--adm-preview-discount:%s;--adm-preview-radius:%dpx',
    (string) $colors['background'],
    (string) $colors['card'],
    (string) $colors['border'],
    (string) $colors['text'],
    (string) $colors['muted'],
    (string) $colors['accent'],
    (string) $colors['discount'],
    (int) ($colors['radius'] ?? 6)
);

adm_header('Aparência', 'aparencia');
?>

<div class="adm-tabs">
    <a class="adm-tab" href="#marca">Logo, favicon e nome</a>
    <a class="adm-tab" href="#cores">Cores</a>
    <a class="adm-tab" href="#fontes">Fontes</a>
</div>

<!-- ================= MARCA ================= -->
<div class="adm-card" id="marca">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('image') ?> Identidade visual</div>
            <div class="adm-card-sub">Logo do topo, logo do rodapé, favicon, nome do site e informações de SEO.</div>
        </div>
    </div>
    <form method="post" action="<?= e(xll_url('admin/aparencia.php')) ?>" enctype="multipart/form-data">
        <?= xll_csrf_field() ?>
        <input type="hidden" name="acao" value="branding">
        <div class="adm-grid">
            <div class="adm-field">
                <label class="adm-label" for="site_name">Nome do site</label>
                <input class="adm-input" id="site_name" type="text" name="site_name" value="<?= e($siteName) ?>" required>
            </div>
            <div class="adm-field">
                <label class="adm-label" for="site_slogan">Slogan</label>
                <input class="adm-input" id="site_slogan" type="text" name="site_slogan" value="<?= e($slogan) ?>">
            </div>
            <div class="adm-field adm-field-full">
                <label class="adm-label" for="meta_description">Descrição para buscadores (SEO)</label>
                <textarea class="adm-textarea" id="meta_description" name="meta_description" style="min-height:70px"><?= e($metaDescription) ?></textarea>
            </div>
            <div class="adm-field">
                <label class="adm-label" for="theme_color">Cor do tema (barra do navegador)</label>
                <div class="adm-color-row">
                    <input type="color" value="<?= e(xll_valid_hex($themeColor, '#01191f')) ?>" data-color-picker="#theme_color">
                    <input class="adm-input" id="theme_color" type="text" name="theme_color" value="<?= e($themeColor) ?>">
                </div>
            </div>
            <div class="adm-field">
                <label class="adm-label" for="site_url">URL canônica do site (SEO)</label>
                <input class="adm-input" id="site_url" type="url" name="site_url" value="<?= e((string) xll_setting('site_url', '')) ?>" placeholder="https://gamingstore.xll.com.br">
                <span class="adm-help">Usada em canonical, Open Graph e sitemap. Vazio = detectar automaticamente.</span>
            </div>
            <div class="adm-field adm-field-full">
                <div class="adm-check-row">
                    <label class="adm-check"><input type="checkbox" name="pretty_urls" <?= xll_pretty_urls() ? 'checked' : '' ?>> Usar URLs amigáveis (/produto/slug, /ofertas/categoria)</label>
                    <label class="adm-check"><input type="checkbox" name="track_clicks" <?= (string) xll_setting('track_clicks', '1') !== '0' ? 'checked' : '' ?>> Registrar cliques nos links de afiliado</label>
                </div>
                <span class="adm-help">Desligue as URLs amigáveis apenas se o servidor não tiver mod_rewrite; o site volta a usar .php.</span>
            </div>
        </div>

        <?php adm_image_field('logo', $logo, 'logo_upload', 'Logo principal (topo do site)', 'PNG/WEBP com fundo transparente. Altura recomendada: 90–120 px.'); ?>
        <?php adm_image_field('logo_footer', $logoFooter, 'logo_footer_upload', 'Logo do rodapé', 'Pode ser a mesma imagem do topo.'); ?>
        <?php adm_image_field('favicon', $favicon, 'favicon_upload', 'Favicon', 'PNG 192×192 ou ICO. Você também pode colar a URL.'); ?>

        <div class="adm-actions" style="margin-top:16px">
            <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Salvar identidade</button>
        </div>
    </form>

    <form method="post" action="<?= e(xll_url('admin/aparencia.php')) ?>" style="margin-top:14px">
        <?= xll_csrf_field() ?>
        <input type="hidden" name="acao" value="limpar_cache">
        <div class="adm-actions">
            <button type="submit" class="adm-btn adm-btn-secondary"><?= adm_icon('import') ?> Limpar cache de imagens</button>
            <span class="adm-help">As imagens externas são armazenadas em <code>data/cache/img</code> e servidas com cache longo.</span>
        </div>
    </form>
</div>

<!-- ================= CORES ================= -->
<div class="adm-card" id="cores">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('palette') ?> Cores do site</div>
            <div class="adm-card-sub">As cores são aplicadas em todo o site (home, catálogo, produto, rodapé). O preview mostra o resultado.</div>
        </div>
        <form method="post" action="<?= e(xll_url('admin/aparencia.php')) ?>" data-confirm="Restaurar as cores padrão do tema?">
            <?= xll_csrf_field() ?>
            <input type="hidden" name="acao" value="reset_colors">
            <button type="submit" class="adm-btn adm-btn-secondary adm-btn-sm">Restaurar padrão</button>
        </form>
    </div>
    <form method="post" action="<?= e(xll_url('admin/aparencia.php')) ?>">
        <?= xll_csrf_field() ?>
        <input type="hidden" name="acao" value="colors">
        <div class="adm-grid adm-grid-3">
            <?php
            $colorFields = [
                'background' => ['Fundo do site', '--adm-preview-bg'],
                'card'       => ['Cartões e cards', '--adm-preview-card'],
                'border'     => ['Bordas', '--adm-preview-border'],
                'text'       => ['Texto principal', '--adm-preview-text'],
                'muted'      => ['Texto secundário', '--adm-preview-muted'],
                'accent'     => ['Destaque (verde neon)', '--adm-preview-accent'],
                'discount'   => ['Badge de desconto', '--adm-preview-discount'],
                'button_text' => ['Texto dos botões', '--adm-preview-text'],
                'footer_title' => ['Títulos do rodapé', '--adm-preview-accent'],
            ];
            foreach ($colorFields as $key => [$label, $previewVar]):
                $value = (string) ($colors[$key] ?? '#000000');
                ?>
                <div class="adm-field">
                    <label class="adm-label"><?= e($label) ?></label>
                    <div class="adm-color-row">
                        <input type="color" value="<?= e(xll_valid_hex($value, '#000000')) ?>" data-color-picker="#color_<?= e($key) ?>">
                        <input class="adm-input" id="color_<?= e($key) ?>" type="text" name="colors[<?= e($key) ?>]" value="<?= e($value) ?>" data-preview-var="<?= e($previewVar) ?>">
                    </div>
                </div>
            <?php endforeach; ?>
            <div class="adm-field">
                <label class="adm-label">Arredondamento dos cards (px)</label>
                <input class="adm-input" type="number" name="colors[radius]" min="0" max="24" value="<?= (int) ($colors['radius'] ?? 6) ?>">
            </div>
        </div>

        <div class="adm-theme-preview" style="<?= e($previewStyle) ?>">
            <h3>Seu site com as novas cores</h3>
            <p>Este é um exemplo de como os textos e cartões ficam com a paleta escolhida. O resultado final é aplicado em todas as páginas.</p>
            <span class="preview-btn">COMPRAR</span>
            <div class="adm-preview-card">
                <div class="pc-media"></div>
                <div class="pc-body">
                    <div class="pc-title">Produto gamer de exemplo</div>
                    <div class="pc-price">R$ 199,90</div>
                </div>
            </div>
        </div>

        <div class="adm-actions" style="margin-top:16px">
            <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Salvar cores</button>
        </div>
    </form>
</div>

<!-- ================= FONTES ================= -->
<div class="adm-card" id="fontes">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('palette') ?> Fontes e tamanhos</div>
            <div class="adm-card-sub">Escolha a fonte dos textos e dos títulos. As demais fontes são carregadas do Google Fonts automaticamente.</div>
        </div>
        <form method="post" action="<?= e(xll_url('admin/aparencia.php')) ?>" data-confirm="Restaurar as fontes padrão do tema?">
            <?= xll_csrf_field() ?>
            <input type="hidden" name="acao" value="reset_fonts">
            <button type="submit" class="adm-btn adm-btn-secondary adm-btn-sm">Restaurar padrão</button>
        </form>
    </div>
    <form method="post" action="<?= e(xll_url('admin/aparencia.php')) ?>">
        <?= xll_csrf_field() ?>
        <input type="hidden" name="acao" value="fonts">
        <div class="adm-grid adm-grid-3">
            <div class="adm-field">
                <label class="adm-label" for="font_body">Fonte dos textos</label>
                <select class="adm-select" id="font_body" name="body" data-preview-font="--adm-preview-body">
                    <?php foreach ($fontOptions as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($fonts['body'] ?? 'Inter') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="adm-field">
                <label class="adm-label" for="font_heading">Fonte dos títulos</label>
                <select class="adm-select" id="font_heading" name="heading" data-preview-font="--adm-preview-heading">
                    <?php foreach ($fontOptions as $value => $label): ?>
                        <option value="<?= e($value) ?>" <?= ($fonts['heading'] ?? 'Inter') === $value ? 'selected' : '' ?>><?= e($label) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="adm-field">
                <label class="adm-label" for="body_size">Tamanho base (px)</label>
                <input class="adm-input" id="body_size" type="number" name="body_size" min="12" max="22" value="<?= (int) ($fonts['body_size'] ?? 15) ?>">
            </div>
            <div class="adm-field">
                <label class="adm-label" for="h1_size">Tamanho H1 (px)</label>
                <input class="adm-input" id="h1_size" type="number" name="h1_size" min="20" max="64" value="<?= (int) ($fonts['h1_size'] ?? 30) ?>">
            </div>
            <div class="adm-field">
                <label class="adm-label" for="h2_size">Tamanho H2 (px)</label>
                <input class="adm-input" id="h2_size" type="number" name="h2_size" min="16" max="52" value="<?= (int) ($fonts['h2_size'] ?? 24) ?>">
            </div>
            <div class="adm-field">
                <label class="adm-label" for="h3_size">Tamanho H3 (px)</label>
                <input class="adm-input" id="h3_size" type="number" name="h3_size" min="14" max="44" value="<?= (int) ($fonts['h3_size'] ?? 20) ?>">
            </div>
            <div class="adm-field adm-field-full">
                <label class="adm-label" for="google_url">URL personalizada do Google Fonts (opcional)</label>
                <input class="adm-input" id="google_url" type="text" name="google_url" value="<?= e($fonts['google_url'] ?? '') ?>" placeholder="https://fonts.googleapis.com/css2?family=...">
                <span class="adm-help">Use quando quiser uma combinação específica de pesos/fontes. Deixe vazio para gerar automaticamente.</span>
            </div>
        </div>

        <div class="adm-theme-preview" style="<?= e($previewStyle) ?>;--adm-preview-body:<?= e((string) ($fonts['body'] ?? 'Inter')) ?>;--adm-preview-heading:<?= e((string) ($fonts['heading'] ?? 'Inter')) ?>">
            <h3>Aa — Título de exemplo</h3>
            <p>Texto de exemplo para visualizar a fonte selecionada. A Loot Gamer reúne ofertas de PC Gamer, consoles e periféricos.</p>
            <span class="preview-btn">VER OFERTAS</span>
        </div>

        <div class="adm-actions" style="margin-top:16px">
            <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Salvar fontes</button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var families = ['Inter'];
    document.querySelectorAll('[data-preview-font]').forEach(function (select) {
        if (select.value && families.indexOf(select.value) === -1) { families.push(select.value); }
        select.addEventListener('change', function () {
            if (families.indexOf(select.value) === -1 && select.value !== 'Inter') {
                families.push(select.value);
                var link = document.getElementById('adm-google-fonts');
                if (!link) {
                    link = document.createElement('link');
                    link.id = 'adm-google-fonts';
                    link.rel = 'stylesheet';
                    document.head.appendChild(link);
                }
                var query = families.filter(function (f) { return f !== 'Inter'; }).map(function (f) {
                    return 'family=' + encodeURIComponent(f).replace(/%20/g, '+') + ':wght@400;500;600;700;800';
                }).join('&');
                if (query) { link.href = 'https://fonts.googleapis.com/css2?' + query + '&display=swap'; }
            }
        });
    });
});
</script>

<?php if ($scrollTo !== ''): ?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var target = document.getElementById(<?= json_encode($scrollTo) ?>);
    if (target) { target.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
});
</script>
<?php endif; ?>

<?php adm_footer(); ?>
