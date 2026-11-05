</main>
<?php
$siteName = setting('site_name', 'Loot Gamer');
$logo = setting('logo_path') ? url(setting('logo_path')) : asset('images/seed/logo.png');
$footCats = q('SELECT name,slug FROM categories WHERE is_active = 1 AND is_main = 1 ORDER BY sort_order, name LIMIT 7')->fetchAll();
$socials = ['youtube' => 'YouTube', 'instagram' => 'Instagram', 'tiktok' => 'TikTok', 'facebook' => 'Facebook', 'x' => 'X'];
$stores = q('SELECT name FROM affiliate_stores WHERE is_active = 1 ORDER BY name LIMIT 6')->fetchAll(PDO::FETCH_COLUMN);
?>
<section class="wrap trust">
  <div><span>🔗</span><b>Links Oficiais</b><small>direto nas lojas parceiras</small></div>
  <div><span>🏷️</span><b>Ofertas Selecionadas</b><small>preços definidos pelas lojas</small></div>
  <div><span>🔄</span><b>Sempre Atualizado</b><small>conferimos os destaques</small></div>
  <div><span>🛡️</span><b>Dados Protegidos</b><small>respeitamos a LGPD</small></div>
</section>
<footer class="site-footer">
  <div class="wrap fgrid">
    <div class="fcol fabout">
      <img src="<?= e($logo) ?>" alt="<?= e($siteName) ?>" width="170" height="58" loading="lazy">
      <p><?= e(setting('site_description', 'As melhores ofertas de games, consoles, acessórios e tecnologia gamer, reunidas em um só lugar.')) ?></p>
      <div class="social">
        <?php foreach ($socials as $k => $label): if (setting('social_' . $k)): ?>
          <a href="<?= e(setting('social_' . $k)) ?>" target="_blank" rel="noopener nofollow" aria-label="<?= e($label) ?>"><img src="<?= asset('images/seed/social-' . $k . '.png') ?>" alt="<?= e($label) ?>" width="30" height="30" loading="lazy"></a>
        <?php endif; endforeach; ?>
      </div>
    </div>
    <div class="fcol"><h4>Sobre a <?= e($siteName) ?></h4><ul>
      <li><a href="<?= url('sobre') ?>">Sobre nós</a></li><li><a href="<?= url('como-funciona') ?>">Como funciona</a></li>
      <li><a href="<?= url('contato') ?>">Contato</a></li><li><a href="<?= url('divulgacao-afiliados') ?>">Transparência e afiliados</a></li><li><a href="<?= url('blog') ?>">Blog</a></li></ul></div>
    <div class="fcol"><h4>Ajuda</h4><ul>
      <li><a href="<?= url('faq') ?>">Perguntas frequentes</a></li><li><a href="<?= url('como-funciona') ?>">Como utilizar os links</a></li>
      <li><a href="<?= url('privacidade') ?>">Política de privacidade</a></li><li><a href="<?= url('cookies') ?>">Política de cookies</a></li><li><a href="<?= url('termos') ?>">Termos de uso</a></li></ul></div>
    <div class="fcol"><h4>Categorias</h4><ul>
      <?php foreach ($footCats as $c): ?><li><a href="<?= url('categoria/' . $c['slug']) ?>"><?= e($c['name']) ?></a></li><?php endforeach; ?>
      <li><a href="<?= url('ofertas') ?>">Ofertas</a></li></ul></div>
    <div class="fcol"><h4>Parceiros</h4><ul>
      <?php foreach ($stores as $s): ?><li><?= e($s) ?></li><?php endforeach; ?>
      <li><a href="<?= url('lojas') ?>">Ver todas as lojas</a></li></ul></div>
    <div class="fcol news">
      <h4>✉️ Receba Ofertas e Novidades</h4>
      <p>Cadastre seu e-mail e seja o primeiro a saber das melhores promoções!</p>
      <form action="<?= url('newsletter.php') ?>" method="post" class="news-form">
        <?= csrf_field() ?>
        <input type="text" name="website" class="hp" tabindex="-1" autocomplete="off" aria-hidden="true">
        <input type="hidden" name="source" value="rodape">
        <div class="row"><input type="email" name="email" placeholder="Seu e-mail" required aria-label="Seu e-mail"><button type="submit" aria-label="Inscrever">➜</button></div>
        <label class="consent"><input type="checkbox" name="consent" value="1" required> Concordo em receber e-mails e com a <a href="<?= url('privacidade') ?>">Política de Privacidade</a>.</label>
      </form>
    </div>
  </div>
  <div class="wrap disclosure"><?= e(setting('affiliate_notice', 'Este site participa de programas de afiliados e pode receber comissões por compras realizadas por meio de determinados links, sem custo adicional para você. Preços, condições e disponibilidade são definidos pelas lojas de destino.')) ?></div>
  <div class="wrap fbottom">
    <span><?= e(setting('copyright', '© ' . date('Y') . ' ' . $siteName . ' – Ofertas e Achadinhos da Net. Todos os direitos reservados.')) ?></span>
    <span><a href="<?= url('privacidade') ?>">Privacidade</a> · <a href="<?= url('termos') ?>">Termos</a> · <a href="<?= url('cookies') ?>">Cookies</a></span>
  </div>
  <?php if (setting('legal_info')): ?><div class="wrap legal"><?= nl2br(e(setting('legal_info'))) ?></div><?php endif; ?>
</footer>
<div class="cookie-bar" id="cookieBar" hidden>
  <p>Usamos cookies para melhorar sua experiência<?= setting('analytics_id') ? ' e medir audiência (somente com seu consentimento)' : '' ?>. Saiba mais na <a href="<?= url('cookies') ?>">Política de Cookies</a>.</p>
  <button id="cookieOk">Aceitar</button> <button id="cookieNo" class="ghost">Recusar</button>
</div>
<script>window.LG={base:<?= json_encode(BASE) ?>};</script>
<script src="<?= asset('js/main.js') ?>?v=3" defer></script>
</body>
</html>