# Loot Gamer — Ofertas e Achadinhos da Net

Catálogo de afiliados em PHP 8.2+ / MySQL (sem Node, sem frameworks). Compra concluída na loja parceira.

## Instalação na Hostinger
1. hPanel → Bancos de dados → crie um banco MySQL e um usuário com todas as permissões; anote host (normalmente `localhost`), nome, usuário e senha.
2. Envie o conteúdo da pasta `loot-gamer/` para `public_html` (ou a pasta do domínio) via Gerenciador de Arquivos/FTP.
3. Ative o SSL (hPanel → Segurança → SSL) e, depois, descomente as 3 linhas de HTTPS no `.htaccess`.
4. Acesse `https://seudominio.com.br/install.php`: informe os dados do banco (o instalador importa `database.sql`, cria categorias e páginas legais; marque "demonstração" para carregar o visual do template).
5. Crie o primeiro administrador (senha mínima de 10 caracteres). O instalador se bloqueia sozinho. **Apague `install.php` do servidor.**
6. Permissões: `uploads/` e subpastas 755 (ou 775 se o upload falhar); `config/` e `logs/` não são acessíveis publicamente (`.htaccess`).
7. Entre em `/admin/`, vá em Configurações (nome, logo, e-mail, redes, Analytics), Lojas e Produtos.
8. Edite `robots.txt` trocando `SEU-DOMINIO.com.br`.
9. Backup: exporte o banco no phpMyAdmin e baixe `uploads/` e `config/config.php`.

## Testes manuais recomendados
Login/logout · criar/editar/duplicar/desativar/excluir produto · upload e URL de imagem (prévia) · botão “Confira Oferta” → `/ir/ID` registra clique e redireciona · filtros/busca · newsletter e contato (aparecem no admin) · URLs amigáveis (`/categoria/consoles`, `/ofertas`, `/produto/slug`) · celular.

## Atualizar o esquema do banco
Com `install.php` presente e logado no admin: `install.php?update=1` (idempotente: `CREATE TABLE IF NOT EXISTS`).

## Observações
- Nunca envie `config/config.php` ao GitHub (já está no `.gitignore`).
- Não há envio automático de e-mails; configure SMTP antes de prometer isso.
- As imagens em `assets/images/seed/` foram recortadas do template (baixa resolução): substitua pelas imagens reais no painel.

## Blog com SEO (versão 2026)
- Painel: `/admin/` → **Blog** (listagem, categorias, filtros) e **Novo artigo**.
- Editor com painel de SEO estilo Yoast: palavra-chave foco, análise em tempo real (título SEO, permalink, meta description, densidade, subtítulos, links internos/externos, imagem destacada com alt, legibilidade), prévia de resultado no Google, aba de redes sociais (Open Graph/Twitter), robots (noindex/nofollow), URL canônica e tipo de dado estruturado (Schema.org).
- Público: `/blog`, `/blog/categoria/{slug}`, `/blog/{slug}`. Artigos agendados (data futura) só aparecem na data marcada.
- Dados estruturados: BreadcrumbList e BlogPosting/Article; metas article:*, Open Graph e Twitter Card.
- Sitemap inclui artigos publicados sem noindex.
- Em instalações existentes, rode `install.php?update=1` (logado no admin) para criar as tabelas `blog_posts` e `blog_categories`.
