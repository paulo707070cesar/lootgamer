<?php
/**
 * Loot Gamer - rodapé, empresa, menu e páginas institucionais.
 */

declare(strict_types=1);

require __DIR__ . '/inc/layout.php';

$defaults = xll_defaults();

/**
 * Converte linhas "Rótulo | URL" em lista de links.
 *
 * @return array<int,array{label:string,url:string}>
 */
function adm_parse_links(string $text): array
{
    $links = [];
    foreach (preg_split('/\r?\n/', $text) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = array_map('trim', explode('|', $line, 2));
        $label = $parts[0] ?? '';
        $url = $parts[1] ?? '#';
        if ($label !== '') {
            $links[] = ['label' => $label, 'url' => $url !== '' ? $url : '#'];
        }
    }

    return $links;
}

function adm_links_to_text(array $links): string
{
    $lines = [];
    foreach ($links as $link) {
        if (is_array($link) && !empty($link['label'])) {
            $lines[] = ($link['label'] ?? '') . ' | ' . ($link['url'] ?? '#');
        }
    }

    return implode("\n", $lines);
}

if (xll_is_post()) {
    xll_require_csrf();
    $acao = adm_text('acao');

    if ($acao === 'footer') {
        $columns = [];
        foreach (adm_array('columns') as $row) {
            if (!is_array($row) || trim((string) ($row['title'] ?? '')) === '') {
                continue;
            }
            $columns[] = [
                'title' => trim((string) ($row['title'] ?? '')),
                'text'  => trim((string) ($row['text'] ?? '')),
                'links' => adm_parse_links((string) ($row['links'] ?? '')),
            ];
        }

        xll_setting_save('footer', [
            'about_title' => adm_text('about_title'),
            'about_text'  => adm_text('about_text'),
            'socials'     => [
                'tiktok'    => adm_text('social_tiktok'),
                'instagram' => adm_text('social_instagram'),
                'youtube'   => adm_text('social_youtube'),
                'telegram'  => adm_text('social_telegram'),
                'whatsapp'  => adm_text('social_whatsapp'),
            ],
            'columns'    => $columns ?: $defaults['footer']['columns'],
            'copyright'  => adm_text('copyright'),
            'note'       => adm_text('note'),
            'disclosure' => adm_text('disclosure'),
            'company'    => [
                'name'     => adm_text('company_name'),
                'cnpj'     => adm_text('company_cnpj'),
                'address'  => adm_text('company_address'),
                'whatsapp' => adm_text('company_whatsapp'),
                'email'    => adm_text('company_email'),
            ],
        ]);
        xll_settings_save_many([
            'contact_email'     => adm_text('contact_email'),
            'contact_subject'   => adm_text('contact_subject', 'Novo contato pelo site'),
            'notify_on_contact' => adm_check('notify_on_contact') ? '1' : '0',
            'mail_from'         => adm_text('mail_from'),
        ]);
        adm_redirect('empresa.php?sec=footer', 'Rodapé e dados da empresa atualizados.');
    }

    if ($acao === 'menu') {
        $items = [];
        foreach (adm_array('menu') as $row) {
            if (!is_array($row) || trim((string) ($row['label'] ?? '')) === '') {
                continue;
            }
            $items[] = [
                'label'  => trim((string) ($row['label'] ?? '')),
                'url'    => trim((string) ($row['url'] ?? '')),
                'active' => !empty($row['active']),
            ];
        }
        xll_setting_save('menu', $items ?: $defaults['menu']);
        adm_redirect('empresa.php?sec=menu', 'Menu do site atualizado.');
    }

    if ($acao === 'pages') {
        $posted = adm_array('pages');
        $pages = [];
        foreach (['sobre', 'privacidade', 'termos', 'contato'] as $slug) {
            $row = is_array($posted[$slug] ?? null) ? $posted[$slug] : [];
            $pages[$slug] = [
                'title'   => trim((string) ($row['title'] ?? '')),
                'content' => trim((string) ($row['content'] ?? '')),
            ];
        }
        xll_setting_save('pages', $pages);
        adm_redirect('empresa.php?sec=paginas', 'Páginas institucionais atualizadas.');
    }
}

$footer = xll_setting_group('footer');
$menu = xll_setting_group('menu');
$pages = xll_setting_group('pages');
$socials = is_array($footer['socials'] ?? null) ? $footer['socials'] : [];
$columns = array_values(is_array($footer['columns'] ?? null) ? $footer['columns'] : []);
$company = is_array($footer['company'] ?? null) ? $footer['company'] : [];
$scrollTo = (string) ($_GET['sec'] ?? '');

adm_header('Rodapé e páginas', 'empresa');
?>

<div class="adm-tabs">
    <a class="adm-tab" href="#footer">Rodapé e empresa</a>
    <a class="adm-tab" href="#menu">Menu do topo</a>
    <a class="adm-tab" href="#paginas">Páginas (sobre, privacidade, termos, contato)</a>
</div>

<!-- ================= FOOTER ================= -->
<div class="adm-card" id="footer">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('footer') ?> Rodapé</div>
            <div class="adm-card-sub">Colunas, redes sociais, aviso de afiliados e dados da empresa exibidos no rodapé.</div>
        </div>
    </div>
    <form method="post" action="<?= e(xll_url('admin/empresa.php')) ?>">
        <?= xll_csrf_field() ?>
        <input type="hidden" name="acao" value="footer">
        <div class="adm-grid">
            <div class="adm-field">
                <label class="adm-label">Título da coluna “sobre”</label>
                <input class="adm-input" type="text" name="about_title" value="<?= e($footer['about_title'] ?? '') ?>">
            </div>
            <div class="adm-field">
                <label class="adm-label">Texto da coluna “sobre”</label>
                <input class="adm-input" type="text" name="about_text" value="<?= e($footer['about_text'] ?? '') ?>">
            </div>
        </div>

        <div class="adm-label" style="margin:20px 0 10px">Redes sociais (deixe vazio para ocultar)</div>
        <div class="adm-grid adm-grid-3">
            <?php
            $socialFields = [
                'tiktok'    => 'TikTok',
                'instagram' => 'Instagram',
                'youtube'   => 'YouTube',
                'telegram'  => 'Telegram',
                'whatsapp'  => 'WhatsApp',
            ];
            foreach ($socialFields as $key => $label): ?>
                <div class="adm-field">
                    <label class="adm-label"><?= e($label) ?></label>
                    <input class="adm-input" type="text" name="social_<?= e($key) ?>" value="<?= e($socials[$key] ?? '') ?>" placeholder="https://...">
                </div>
            <?php endforeach; ?>
        </div>

        <div class="adm-label" style="margin:22px 0 10px">Colunas de conteúdo do rodapé</div>
        <div class="adm-repeater" data-repeater data-next-index="<?= count($columns) ?>">
            <div class="adm-repeater-items" data-repeater-items>
                <?php foreach ($columns as $index => $column): ?>
                    <div class="adm-repeater-item" data-index="<?= $index ?>">
                        <div class="adm-repeater-head">
                            <span class="adm-repeater-title">Coluna <span data-repeater-number><?= $index + 1 ?></span></span>
                            <button type="button" class="adm-repeater-remove" data-repeater-remove data-confirm="Remover esta coluna?">Remover</button>
                        </div>
                        <div class="adm-grid">
                            <div class="adm-field">
                                <label class="adm-label">Título</label>
                                <input class="adm-input" type="text" name="columns[<?= $index ?>][title]" value="<?= e($column['title'] ?? '') ?>">
                            </div>
                            <div class="adm-field">
                                <label class="adm-label">Links (um por linha: Rótulo | URL)</label>
                                <textarea class="adm-textarea" name="columns[<?= $index ?>][links]" style="min-height:80px" placeholder="Política de Privacidade | pagina.php?p=privacidade"><?= e(adm_links_to_text(is_array($column['links'] ?? null) ? $column['links'] : [])) ?></textarea>
                            </div>
                            <div class="adm-field adm-field-full">
                                <label class="adm-label">Texto</label>
                                <textarea class="adm-textarea" name="columns[<?= $index ?>][text]"><?= e($column['text'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <template data-repeater-template>
                <div class="adm-repeater-item" data-index="__INDEX__">
                    <div class="adm-repeater-head">
                        <span class="adm-repeater-title">Coluna <span data-repeater-number></span></span>
                        <button type="button" class="adm-repeater-remove" data-repeater-remove data-confirm="Remover esta coluna?">Remover</button>
                    </div>
                    <div class="adm-grid">
                        <div class="adm-field"><label class="adm-label">Título</label><input class="adm-input" type="text" name="columns[__INDEX__][title]" value=""></div>
                        <div class="adm-field"><label class="adm-label">Links (um por linha)</label><textarea class="adm-textarea" name="columns[__INDEX__][links]" style="min-height:80px"></textarea></div>
                        <div class="adm-field adm-field-full"><label class="adm-label">Texto</label><textarea class="adm-textarea" name="columns[__INDEX__][text]"></textarea></div>
                    </div>
                </div>
            </template>
            <button type="button" class="adm-btn adm-btn-secondary" data-repeater-add><?= adm_icon('plus') ?> Adicionar coluna</button>
        </div>

        <div class="adm-grid" style="margin-top:22px">
            <div class="adm-field">
                <label class="adm-label">Copyright</label>
                <input class="adm-input" type="text" name="copyright" value="<?= e($footer['copyright'] ?? '') ?>">
            </div>
            <div class="adm-field">
                <label class="adm-label">Frase de compra segura</label>
                <input class="adm-input" type="text" name="note" value="<?= e($footer['note'] ?? '') ?>">
            </div>
            <div class="adm-field adm-field-full">
                <label class="adm-label">Aviso de afiliados</label>
                <textarea class="adm-textarea" name="disclosure"><?= e($footer['disclosure'] ?? '') ?></textarea>
            </div>
        </div>

        <div class="adm-label" style="margin:22px 0 10px">Dados da empresa (exibidos no rodapé e na página de contato)</div>
        <div class="adm-grid adm-grid-3">
            <div class="adm-field"><label class="adm-label">Nome</label><input class="adm-input" type="text" name="company_name" value="<?= e($company['name'] ?? '') ?>"></div>
            <div class="adm-field"><label class="adm-label">CNPJ</label><input class="adm-input" type="text" name="company_cnpj" value="<?= e($company['cnpj'] ?? '') ?>"></div>
            <div class="adm-field"><label class="adm-label">WhatsApp</label><input class="adm-input" type="text" name="company_whatsapp" value="<?= e($company['whatsapp'] ?? '') ?>"></div>
            <div class="adm-field"><label class="adm-label">Endereço</label><input class="adm-input" type="text" name="company_address" value="<?= e($company['address'] ?? '') ?>"></div>
            <div class="adm-field"><label class="adm-label">E-mail</label><input class="adm-input" type="email" name="company_email" value="<?= e($company['email'] ?? '') ?>"></div>
        </div>

        <div class="adm-label" style="margin:22px 0 10px">Notificações de contato (formulário do site)</div>
        <div class="adm-grid adm-grid-3">
            <div class="adm-field">
                <label class="adm-label">E-mail de destino</label>
                <input class="adm-input" type="email" name="contact_email" value="<?= e((string) xll_setting('contact_email', '')) ?>" placeholder="<?= e($company['email'] ?? 'contato@xll.com.br') ?>">
                <span class="adm-help">Vazio = usa o e-mail da empresa.</span>
            </div>
            <div class="adm-field">
                <label class="adm-label">Prefixo do assunto</label>
                <input class="adm-input" type="text" name="contact_subject" value="<?= e((string) xll_setting('contact_subject', 'Novo contato pelo site')) ?>">
            </div>
            <div class="adm-field">
                <label class="adm-label">Remetente (opcional)</label>
                <input class="adm-input" type="email" name="mail_from" value="<?= e((string) xll_setting('mail_from', '')) ?>" placeholder="no-reply@seudominio.com">
            </div>
            <div class="adm-field adm-field-full">
                <label class="adm-check"><input type="checkbox" name="notify_on_contact" <?= (string) xll_setting('notify_on_contact', '1') !== '0' ? 'checked' : '' ?>> Enviar notificação por e-mail a cada mensagem</label>
                <span class="adm-help">Se o servidor bloquear o envio de e-mail, a mensagem continua salva em “Mensagens de contato”.</span>
            </div>
        </div>

        <div class="adm-actions" style="margin-top:18px">
            <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Salvar rodapé</button>
        </div>
    </form>
</div>

<!-- ================= MENU ================= -->
<div class="adm-card" id="menu">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('home') ?> Menu do topo</div>
            <div class="adm-card-sub">Links exibidos no cabeçalho do site.</div>
        </div>
    </div>
    <form method="post" action="<?= e(xll_url('admin/empresa.php')) ?>">
        <?= xll_csrf_field() ?>
        <input type="hidden" name="acao" value="menu">
        <div class="adm-repeater" data-repeater data-next-index="<?= count($menu) ?>">
            <div class="adm-repeater-items" data-repeater-items>
                <?php foreach ($menu as $index => $item): ?>
                    <div class="adm-repeater-item" data-index="<?= $index ?>">
                        <div class="adm-repeater-head">
                            <span class="adm-repeater-title">Item <span data-repeater-number><?= $index + 1 ?></span></span>
                            <label class="adm-check"><input type="checkbox" name="menu[<?= $index ?>][active]" <?= !isset($item['active']) || $item['active'] ? 'checked' : '' ?>> Visível</label>
                            <button type="button" class="adm-repeater-remove" data-repeater-remove data-confirm="Remover este item do menu?">Remover</button>
                        </div>
                        <div class="adm-grid">
                            <div class="adm-field">
                                <label class="adm-label">Rótulo</label>
                                <input class="adm-input" type="text" name="menu[<?= $index ?>][label]" value="<?= e($item['label'] ?? '') ?>">
                            </div>
                            <div class="adm-field">
                                <label class="adm-label">URL</label>
                                <input class="adm-input" type="text" name="menu[<?= $index ?>][url]" value="<?= e($item['url'] ?? '') ?>" placeholder="ofertas.php?categoria=Consoles">
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            <template data-repeater-template>
                <div class="adm-repeater-item" data-index="__INDEX__">
                    <div class="adm-repeater-head">
                        <span class="adm-repeater-title">Item <span data-repeater-number></span></span>
                        <label class="adm-check"><input type="checkbox" name="menu[__INDEX__][active]" checked> Visível</label>
                        <button type="button" class="adm-repeater-remove" data-repeater-remove data-confirm="Remover este item do menu?">Remover</button>
                    </div>
                    <div class="adm-grid">
                        <div class="adm-field"><label class="adm-label">Rótulo</label><input class="adm-input" type="text" name="menu[__INDEX__][label]" value=""></div>
                        <div class="adm-field"><label class="adm-label">URL</label><input class="adm-input" type="text" name="menu[__INDEX__][url]" value=""></div>
                    </div>
                </div>
            </template>
            <button type="button" class="adm-btn adm-btn-secondary" data-repeater-add><?= adm_icon('plus') ?> Adicionar item</button>
        </div>
        <div class="adm-actions" style="margin-top:18px">
            <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Salvar menu</button>
        </div>
    </form>
</div>

<!-- ================= PÁGINAS ================= -->
<div class="adm-card" id="paginas">
    <div class="adm-card-head">
        <div>
            <div class="adm-card-title"><?= adm_icon('box') ?> Páginas institucionais</div>
            <div class="adm-card-sub">Textos exibidos em Sobre, Política de Privacidade, Termos de Uso e Contato.</div>
        </div>
    </div>
    <form method="post" action="<?= e(xll_url('admin/empresa.php')) ?>">
        <?= xll_csrf_field() ?>
        <input type="hidden" name="acao" value="pages">
        <?php
        $pageLabels = [
            'sobre'       => 'Sobre',
            'privacidade' => 'Política de Privacidade',
            'termos'      => 'Termos de Uso',
            'contato'     => 'Contato',
        ];
        foreach ($pageLabels as $slug => $label):
            $page = is_array($pages[$slug] ?? null) ? $pages[$slug] : [];
            ?>
            <div class="adm-repeater-item" style="margin-bottom:14px">
                <div class="adm-repeater-head">
                    <span class="adm-repeater-title"><?= e($label) ?> — /<?= e($slug) ?></span>
                    <a class="adm-btn adm-btn-secondary adm-btn-sm" style="margin-left:auto" href="<?= e(xll_url('pagina.php?p=' . $slug)) ?>" target="_blank" rel="noopener">Ver página</a>
                </div>
                <div class="adm-grid">
                    <div class="adm-field">
                        <label class="adm-label">Título da página</label>
                        <input class="adm-input" type="text" name="pages[<?= e($slug) ?>][title]" value="<?= e($page['title'] ?? '') ?>">
                    </div>
                    <div class="adm-field adm-field-full">
                        <label class="adm-label">Conteúdo</label>
                        <textarea class="adm-textarea" name="pages[<?= e($slug) ?>][content]" style="min-height:140px"><?= e($page['content'] ?? '') ?></textarea>
                        <span class="adm-help">Quebras de linha são preservadas. Links podem ser escritos como texto (ex.: contato@xll.com.br).</span>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
        <div class="adm-actions">
            <button type="submit" class="adm-btn"><?= adm_icon('save') ?> Salvar páginas</button>
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
