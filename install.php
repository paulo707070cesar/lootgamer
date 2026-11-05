<?php
/**
 * Instalador do Loot Gamer. Após instalar, o acesso é bloqueado automaticamente.
 * Depois de concluir, APAGUE este arquivo do servidor.
 */
require_once __DIR__ . '/includes/bootstrap.php';

function run_schema(PDO $pdo): void {
    $sql = file_get_contents(ROOT . '/database.sql');
    foreach (preg_split('/;\s*\n/', $sql) as $st) { $st = trim($st); if ($st !== '') $pdo->exec($st); }
}
function seed_base(PDO $pdo): void {
    $S = 'assets/images/seed/';
    $cats = [
        ['Jogos Digitais', 'jogos-digitais', 'cat-jogos-digitais.png', null, 1, 1], ['Consoles', 'consoles', 'cat-consoles.png', null, 1, 2],
        ['Acessórios Gamer', 'acessorios', 'cat-acessorios.png', null, 1, 3], ['PC Gamer', 'pc-gamer', 'cat-pc-gamer.png', null, 1, 4],
        ['Cadeiras Gamer', 'cadeiras', 'cat-cadeiras.png', null, 1, 5], ['Monitores', 'monitores', 'cat-monitores.png', null, 1, 6],
        ['Gift Cards', 'gift-cards', 'cat-gift-cards.png', null, 1, 7], ['Realidade Virtual', 'realidade-virtual', 'cat-realidade-virtual.png', null, 1, 8],
        ['Simuladores', 'simuladores', 'cat-simuladores.png', null, 1, 9], ['Ofertas Especiais', 'ofertas-especiais', 'cat-ofertas-especiais.png', null, 1, 10],
        ['PlayStation', 'playstation', null, 'consoles', 0, 11], ['Xbox', 'xbox', null, 'consoles', 0, 12], ['Nintendo', 'nintendo', null, 'consoles', 0, 13],
        ['Headsets', 'headsets', null, 'acessorios', 0, 14], ['Teclados Gamer', 'teclados-gamer', null, 'acessorios', 0, 15], ['Mouses Gamer', 'mouses-gamer', null, 'acessorios', 0, 16],
        ['Placas de Vídeo', 'placas-de-video', null, 'pc-gamer', 0, 17],
    ];
    $ins = $pdo->prepare('INSERT IGNORE INTO categories (name, slug, image_path, parent_id, is_main, sort_order, is_active) VALUES (?,?,?,?,?,?,1)');
    $find = $pdo->prepare('SELECT id FROM categories WHERE slug = ?');
    foreach ($cats as [$n, $s, $img, $par, $main, $ord]) {
        $pid = null; if ($par) { $find->execute([$par]); $pid = $find->fetchColumn() ?: null; }
        $ins->execute([$n, $s, $img ? $S . $img : null, $pid, $main, $ord]);
    }
    $site = 'Loot Gamer';
    $pages = [
      ['sobre', 'Sobre nós', "<p>O <b>$site</b> é um catálogo de divulgação de ofertas de games, consoles, acessórios gamer, computadores, periféricos, jogos digitais, gift cards e tecnologia.</p><p>Nós não vendemos produtos nem processamos pagamentos: ao clicar em “Confira Oferta”, você é direcionado ao site da loja parceira, onde a compra é concluída.</p>"],
      ['como-funciona', 'Como funciona', "<h2>Como utilizar os links</h2><ol><li>Navegue pelas categorias ou use a busca.</li><li>Clique em “Confira Oferta” no produto desejado.</li><li>Você será levado à loja parceira, que define preço, estoque, frete e condições.</li></ol><p>Alguns links são de afiliados: podemos receber uma comissão, sem custo adicional para você.</p>"],
      ['faq', 'Perguntas frequentes', "<h2>O site vende os produtos?</h2><p>Não. Somos um catálogo; a compra é feita na loja parceira.</p><h2>Os preços são garantidos?</h2><p>Não. Preços e disponibilidade são definidos pelas lojas e podem mudar a qualquer momento. Sempre confira na loja.</p><h2>Pago mais por usar os links?</h2><p>Não. O valor é o mesmo.</p>"],
      ['divulgacao-afiliados', 'Divulgação de afiliados', "<p>Este site participa de programas de afiliados e pode receber comissões por compras realizadas por meio de determinados links, sem custo adicional para você.</p><p>Preços, condições, prazos de entrega e disponibilidade são definidos exclusivamente pelas lojas de destino. Os links de afiliados são identificados com o atributo <code>sponsored</code>.</p>"],
      ['privacidade', 'Política de privacidade', "<p>Respeitamos a Lei Geral de Proteção de Dados (LGPD – Lei 13.709/2018).</p><h2>Dados que coletamos</h2><ul><li><b>Formulário de contato:</b> nome, e-mail e mensagem, usados apenas para responder você.</li><li><b>Newsletter:</b> e-mail (e nome, se informado) e a data/origem do seu consentimento. Você pode cancelar a qualquer momento.</li><li><b>Cliques em ofertas:</b> registramos apenas dados agregados (produto, loja, data/hora e página de origem), sem identificar você.</li></ul><h2>Seus direitos</h2><p>Você pode solicitar acesso, correção ou exclusão dos seus dados pela página de contato.</p><p>Revise este texto com seu assessor jurídico e complete com os dados do controlador (razão social/CNPJ) em Admin → Configurações.</p>"],
      ['termos', 'Termos de uso', "<p>Ao utilizar este site você concorda que as informações são de caráter informativo e que a compra é realizada exclusivamente nos sites das lojas parceiras, sujeitas aos termos de cada loja.</p><p>Não garantimos preços, promoções ou estoque. Reservamo-nos o direito de alterar ou remover conteúdos a qualquer momento.</p>"],
      ['cookies', 'Política de cookies', "<p>Usamos cookies essenciais para o funcionamento do site e, somente com o seu consentimento, cookies de medição de audiência (quando configurados). Você pode aceitar ou recusar no aviso exibido ao entrar e limpar os cookies no seu navegador quando quiser.</p>"],
    ];
    $pi = $pdo->prepare('INSERT IGNORE INTO pages (slug, title, content) VALUES (?,?,?)');
    foreach ($pages as $p) $pi->execute($p);
    $st = $pdo->prepare('INSERT IGNORE INTO site_settings (setting_key, setting_value) VALUES (?,?)');
    $st->execute(['site_name', $site]);
    $st->execute(['site_description', 'As melhores ofertas de games, consoles, acessórios e tecnologia gamer, reunidas em um só lugar.']);
}
function seed_demo(PDO $pdo): void {
    $S = 'assets/images/seed/';
    $cid = function ($slug) use ($pdo) { $s = $pdo->prepare('SELECT id FROM categories WHERE slug=?'); $s->execute([$slug]); return $s->fetchColumn() ?: null; };
    $rows = [
      ['PlayStation 5 Edição Digital', 'consoles', 3499, 3999, 'prod-playstation-5.jpg', 1, 'ps5-edicao-digital'],
      ['Xbox Series X 1TB', 'consoles', 3299, 3899, 'prod-xbox-series-x.jpg', 1, 'xbox-series-x-1tb'],
      ['Nintendo Switch OLED', 'consoles', 2299, 2599, 'prod-nintendo-switch-oled.jpg', 1, 'nintendo-switch-oled'],
      ['Headset Gamer HyperX Cloud III', 'acessorios', 499, 629, 'prod-headset-hyperx.jpg', 1, 'headset-gamer-hyperx-cloud-iii'],
      ['Kit Gamer Teclado + Mouse RGB', 'acessorios', 285, 349, 'prod-kit-teclado-mouse.jpg', 1, 'kit-gamer-teclado-mouse-rgb'],
      ['EA Sports FC 26', 'jogos-digitais', null, null, 'game-fc26.jpg', 0, 'ea-sports-fc-26'],
      ['Call of Duty: Black Ops 7', 'jogos-digitais', null, null, 'game-call-of-duty-black-ops-7.jpg', 0, 'call-of-duty-black-ops-7'],
      ['Minecraft', 'jogos-digitais', null, null, 'game-minecraft.jpg', 0, 'minecraft'],
      ['Fortnite', 'jogos-digitais', null, null, 'game-fortnite.jpg', 0, 'fortnite'],
      ['Roblox', 'jogos-digitais', null, null, 'game-roblox.jpg', 0, 'roblox'],
      ['Gift Card PlayStation', 'gift-cards', null, null, 'gift-playstation.png', 0, 'gift-card-playstation'],
      ['Gift Card Xbox', 'gift-cards', null, null, 'gift-xbox.png', 0, 'gift-card-xbox'],
      ['Gift Card Nintendo', 'gift-cards', null, null, 'gift-nintendo.png', 0, 'gift-card-nintendo'],
      ['Gift Card Steam', 'gift-cards', null, null, 'gift-steam.png', 0, 'gift-card-steam'],
    ];
    $i = $pdo->prepare("INSERT IGNORE INTO products (category_id,name,slug,short_description,price,old_price,promo_text,image_path,is_featured,is_promo,status,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?,'publicado',?)");
    $n = 0;
    foreach ($rows as [$name, $cat, $price, $old, $img, $feat, $slug]) {
        $i->execute([$cid($cat), $name, $slug, 'Produto de exemplo — edite no painel e informe o link de afiliado real.', $price, $old, $feat ? 'Oferta' : null, $S . $img, $feat, $feat, $n++]);
    }
    $pdo->prepare("INSERT INTO banners (internal_title, image_path, link_url, position, sort_order, is_active) SELECT 'Hero – Games, Consoles e Tecnologia Gamer', ?, '/ofertas', 'principal', 0, 1 FROM DUAL WHERE NOT EXISTS (SELECT 1 FROM banners WHERE position='principal')")->execute([$S . 'hero.jpg']);
}

$cfgExists = is_file(ROOT . '/config/config.php');
$hasUsers = false;
if ($cfgExists) { try { $hasUsers = (int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0; } catch (Throwable $e) { $hasUsers = false; } }
$msg = ''; $err = ''; $step = 1;

// Atualização de esquema (somente admin logado)
if ($hasUsers) {
    require_once __DIR__ . '/includes/auth.php';
    if (admin_user() && isset($_GET['update'])) { try { run_schema(db()); $msg = 'Esquema do banco verificado/atualizado sem duplicar tabelas.'; } catch (Throwable $e) { error_log($e->getMessage()); $err = 'Falha ao atualizar o esquema. Veja logs/php-error.log.'; } }
    ?><!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="robots" content="noindex"><title>Instalador bloqueado</title><link rel="stylesheet" href="<?= asset('css/admin.css') ?>"></head><body class="login"><div class="loginbox"><h1>Instalação concluída</h1>
    <?php if ($msg): ?><div class="alert alert-ok"><?= e($msg) ?></div><?php endif; if ($err): ?><div class="alert alert-err"><?= e($err) ?></div><?php endif; ?>
    <p>O instalador está bloqueado. <b>Apague o arquivo install.php</b> do servidor.</p>
    <?php if (admin_user()): ?><a class="btn" href="?update=1">Verificar/atualizar esquema do banco</a><?php endif; ?><a class="btn ghost" href="<?= url('admin/') ?>">Ir para o painel</a></div></body></html><?php exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    try {
        if (($_POST['step'] ?? '') === '1') {
            $h = trim($_POST['db_host']); $n = trim($_POST['db_name']); $u = trim($_POST['db_user']); $p = (string)$_POST['db_pass'];
            if (!preg_match('/^[A-Za-z0-9_.\-]+$/', $h) || !preg_match('/^[A-Za-z0-9_\-]+$/', $n)) throw new RuntimeException('Host ou nome do banco com caracteres inválidos.');
            $pdo = new PDO("mysql:host=$h;dbname=$n;charset=utf8mb4", $u, $p, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
            run_schema($pdo); seed_base($pdo);
            if (!empty($_POST['demo'])) seed_demo($pdo);
            $cfg = "<?php\nreturn " . var_export(['db_host' => $h, 'db_name' => $n, 'db_user' => $u, 'db_pass' => $p, 'debug' => false], true) . ";\n";
            if (file_put_contents(ROOT . '/config/config.php', $cfg, LOCK_EX) === false) throw new RuntimeException('Não foi possível gravar config/config.php (verifique permissões).');
            @chmod(ROOT . '/config/config.php', 0640);
            $_SESSION['install_ok'] = true; $step = 2; $msg = 'Banco configurado e tabelas criadas.';
        } elseif (($_POST['step'] ?? '') === '2' && !empty($_SESSION['install_ok'])) {
            $step = 2; $nm = trim($_POST['name']); $em = trim($_POST['email']); $pw = (string)$_POST['password'];
            if ($nm === '' || !filter_var($em, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Informe nome e e-mail válidos.');
            if (strlen($pw) < 10 || $pw !== (string)$_POST['password2']) throw new RuntimeException('A senha deve ter ao menos 10 caracteres e ser igual à confirmação.');
            if ((int)db()->query('SELECT COUNT(*) FROM users')->fetchColumn() > 0) throw new RuntimeException('Já existe administrador.');
            q('INSERT INTO users (name,email,password_hash,role) VALUES (?,?,?,?)', [$nm, $em, password_hash($pw, PASSWORD_DEFAULT), 'admin']);
            unset($_SESSION['install_ok']); redirect(url('install.php'));
        }
    } catch (Throwable $ex) { $err = $ex instanceof RuntimeException ? $ex->getMessage() : 'Erro: não foi possível conectar/configurar o banco. Confira as credenciais.'; if (!$ex instanceof RuntimeException) error_log('install: ' . $ex->getMessage()); }
}
if (!empty($_SESSION['install_ok']) && $cfgExists) $step = 2;
?><!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><meta name="robots" content="noindex"><title>Instalar Loot Gamer</title><link rel="stylesheet" href="<?= asset('css/admin.css') ?>"></head>
<body class="login"><form method="post" class="loginbox"><h1>🎮 Instalar Loot Gamer</h1><?= csrf_field() ?>
<?php if ($msg): ?><div class="alert alert-ok"><?= e($msg) ?></div><?php endif; if ($err): ?><div class="alert alert-err"><?= e($err) ?></div><?php endif; ?>
<?php if ($step === 1): ?><input type="hidden" name="step" value="1"><p>Passo 1 de 2 — dados do banco MySQL (criados no hPanel da Hostinger)</p>
<label>Host<input name="db_host" value="localhost" required></label><label>Nome do banco<input name="db_name" required></label><label>Usuário<input name="db_user" required></label><label>Senha<input type="password" name="db_pass"></label>
<label class="chk"><input type="checkbox" name="demo" value="1"> Carregar conteúdo de demonstração do template (produtos exemplo sem link de afiliado)</label><button class="btn">Criar tabelas</button>
<?php else: ?><input type="hidden" name="step" value="2"><p>Passo 2 de 2 — criar o primeiro administrador</p>
<label>Nome<input name="name" required></label><label>E-mail<input type="email" name="email" required></label><label>Senha (mín. 10 caracteres)<input type="password" name="password" minlength="10" required></label><label>Confirmar senha<input type="password" name="password2" required></label><button class="btn">Criar administrador</button><?php endif; ?></form></body></html>
