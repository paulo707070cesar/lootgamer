# ðŸŽ® Loot Gamer â€” Site de Ofertas Gamer (PHP + MySQL)

Site de divulgaÃ§Ã£o de ofertas de games, consoles, acessÃ³rios, PC Gamer e tecnologia,
com **painel administrativo completo** (framework XLL), tema escuro prÃ³prio,
sincronizaÃ§Ã£o automÃ¡tica de conteÃºdo e importaÃ§Ã£o de catÃ¡logo.

> **Site:** https://lootgamer.xll.com.br
> **Painel:** https://lootgamer.xll.com.br/admin/

---

## âœ¨ Recursos

### Site pÃºblico
- Home com **banner rotativo (hero)**, categorias com Ã­cones (Material Symbols), ofertas em destaque, seÃ§Ãµes por categoria, comparador, CTA final
- CatÃ¡logo com **filtros** (ordem, loja, faixa de preÃ§o), paginaÃ§Ã£o e busca
- PÃ¡gina de produto com galeria, descriÃ§Ã£o, informaÃ§Ãµes de afiliado e produtos relacionados
- **Links de afiliado** com contagem de cliques (`/ir/{id}` â†’ `go.php`)
- Blog, pÃ¡ginas institucionais, contato, newsletter, lojas parceiras
- **SEO**: tÃ­tulo/descriÃ§Ã£o por pÃ¡gina, canonical, Open Graph, JSON-LD, `sitemap.xml` dinÃ¢mico, `robots.txt`
- **Favicon** completo (`favicon.ico`, `favicon-32.png`, `apple-touch-icon.png`)
- **Tema escuro** configurÃ¡vel pelo painel (cores, fontes, Ã­cones)
- Imagens externas via proxy opcional (`img.php`) com cache e conversÃ£o WebP

### Painel administrativo (XLL)
- **Painel** com mÃ©tricas (produtos, categorias, descontos, banco, uploads)
- **PÃ¡gina inicial**: banner do topo, cards promocionais, diferenciais, categorias, seÃ§Ãµes de produtos, faixa comparador, CTA final, pÃ¡ginas
- **Produtos**: CRUD completo + formulÃ¡rio com imagem, preÃ§o antigo, desconto, link de afiliado, seÃ§Ã£o da home, ordem
- **AparÃªncia**: logo, favicon, cores do site (fundo, cards, bordas, texto, texto suave, destaque, desconto, **texto dos botÃµes**, **tÃ­tulos do rodapÃ©**), fontes
- **RodapÃ©, menu e pÃ¡ginas**: links de menu/rodapÃ© e pÃ¡ginas institucionais
- **Importar / Exportar**: CSV/JSON de produtos com prÃ©-visualizaÃ§Ã£o
- **Analytics de cliques**, **Mensagens de contato**, **Minha conta**
- Backup do admin anterior preservado em `admin-legacy/`

### IntegraÃ§Ãµes
- **SincronizaÃ§Ã£o automÃ¡tica** painel â†’ site pÃºblico: produtos, categorias, banners, logo/favicon, cores e configuraÃ§Ãµes (`includes/lg-sync-legacy.php`)
- Compatibilidade com as tabelas legadas do site (o site pÃºblico lÃª das tabelas antigas; o painel espelha automaticamente)

---

## ðŸ§° Requisitos

| Item | VersÃ£o mÃ­nima |
|---|---|
| PHP | **8.1+** (testado em 8.2/8.3) |
| MySQL/MariaDB | 5.7+ / 10.3+ |
| ExtensÃµes PHP | `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `curl` |
| Servidor | Apache/LiteSpeed (Hostinger) com `mod_rewrite` |
| Git LFS (opcional, para clonar imagens) | 3.x |

---

## ðŸš€ InstalaÃ§Ã£o

### 1. Clonar / enviar os arquivos
```bash
git lfs install          # necessÃ¡rio para baixar as imagens via LFS
git clone https://github.com/paulo707070cesar/lootgamer.git
```
Envie os arquivos para o diretÃ³rio pÃºblico do site
(ex.: Hostinger â†’ `public_html/lootgamer/`).

### 2. Configurar o banco de dados
Crie o banco no hPanel (MySQL) e edite **`config/config.php`**:
```php
return [
    'db' => [
        'host'     => 'localhost',
        'port'     => 3306,
        'database' => 'u940098558_lootgamer',
        'user'     => 'u940098558_lootgamer',
        'password' => 'SUA_SENHA',
        'charset'  => 'utf8mb4',
    ],
    'install' => [
        'admin_user' => 'admin',
        'admin_pass' => 'DEFINA_UMA_SENHA_FORTE',
    ],
    'base_url'   => '',
    'timezone'   => 'America/Sao_Paulo',
    'upload_max' => 6 * 1024 * 1024,
];
```
> `config/config.php` **nÃ£o Ã© versionado** (estÃ¡ no `.gitignore`). Use `config/config.local.example.php` como base.

### 3. Criar as tabelas
Abra `https://SEU-DOMINIO/install.php` e execute a instalaÃ§Ã£o, **ou** importe:
```bash
mysql -u USUARIO -p BANCO < database.sql
```
Depois acesse `/admin/` e entre com o usuÃ¡rio criado.

### 4. PermissÃµes
```bash
chmod -R 775 data/uploads data/sessions data/backups data/cache
chmod -R 775 uploads
```

### 5. ConfiguraÃ§Ã£o inicial no painel
1. **AparÃªncia** â†’ logo, favicon, cores do tema, fontes
2. **PÃ¡gina inicial** â†’ banner, categorias, diferenciais, seÃ§Ãµes, CTA
3. **Produtos** â†’ cadastrar ou **Importar/Exportar** (CSV)
4. **Empresa** â†’ rodapÃ©, menu, pÃ¡ginas, SEO
5. **Conta** â†’ trocar a senha do painel

---

## ðŸ“ Estrutura do projeto

```
â”œâ”€â”€ admin/                # Painel XLL (dashboard, produtos, aparÃªncia, importar...)
â”‚   â””â”€â”€ inc/layout.php    # Layout do painel + hook de sincronizaÃ§Ã£o
â”œâ”€â”€ admin-legacy/         # Painel antigo (backup, nÃ£o usado)
â”œâ”€â”€ assets/               # CSS, JS, fontes e imagens do tema
â”œâ”€â”€ includes/             # Framework XLL (bootstrap, db, functions, uploads...)
â”œâ”€â”€ includes-site/        # CÃ³digo do site pÃºblico (templates e helpers)
â”œâ”€â”€ config/               # ConfiguraÃ§Ãµes (config.php nÃ£o versionado)
â”œâ”€â”€ data/                 # Uploads do painel, sessÃµes, backups, cache
â”œâ”€â”€ uploads/              # Uploads do site pÃºblico (logo, banners, imagens)
â”œâ”€â”€ index.php             # Home
â”œâ”€â”€ produto.php           # PÃ¡gina de produto
â”œâ”€â”€ ofertas.php           # CatÃ¡logo com filtros
â”œâ”€â”€ categoria.php         # Listagem por categoria
â”œâ”€â”€ go.php                # Redirecionador de afiliado (conta cliques)
â”œâ”€â”€ img.php               # Proxy de imagens (cache/WebP)
â”œâ”€â”€ sitemap.php           # Sitemap dinÃ¢mico
â”œâ”€â”€ database.sql          # Estrutura inicial do banco
â””â”€â”€ install.php           # Instalador
```

---

## ðŸŽ¨ PersonalizaÃ§Ã£o pelo painel

| O que mudar | Onde |
|---|---|
| Cores (fundo, cards, textos, botÃµes, rodapÃ©) | AparÃªncia â†’ Cores do site |
| Logo, favicon, nome do site | AparÃªncia â†’ Logo, favicon e nome |
| Banner do topo e cards promocionais | PÃ¡gina inicial |
| Categorias e Ã­cones | PÃ¡gina inicial â†’ Categorias |
| Menu e rodapÃ© | RodapÃ©, menu e pÃ¡ginas |
| Fontes | AparÃªncia â†’ Fontes |
| Produtos | Produtos / Importar-Exportar |

> As cores salvas no painel sÃ£o injetadas **inline** no `<head>` do site
> (`assets/css/theme-admin.css` â†’ `includes-site/header.php`), evitando cache.

---

## ðŸ” Deploy automÃ¡tico (GitHub â†’ Hostinger)

### OpÃ§Ã£o A â€” GitHub Actions (FTP/SFTP) âœ… incluso
O workflow [`.github/workflows/deploy.yml`](.github/workflows/deploy.yml) publica
automaticamente no Hostinger a cada push na branch `main`.

Configure em **Settings â†’ Secrets and variables â†’ Actions**:

| Secret | Exemplo / descriÃ§Ã£o |
|---|---|
| `FTP_SERVER` | `ftp.lootgamer.xll.com.br` (ou IP do servidor) |
| `FTP_USERNAME` | usuÃ¡rio FTP do hPanel |
| `FTP_PASSWORD` | senha FTP |
| `FTP_SERVER_DIR` | `/public_html/lootgamer/` (ou caminho completo) |

> Para SFTP, troque a action por `wlixcc/SFTP-Deploy-Action` ou use o script `deploy.sh` com `rsync`.

### OpÃ§Ã£o B â€” Manual
```bash
git lfs pull                     # garante as imagens
# envie via FTP/Gerenciador de Arquivos os arquivos alterados
```

### OpÃ§Ã£o C â€” Hostinger Git (se disponÃ­vel no plano)
hPanel â†’ **AvanÃ§ado â†’ Git** â†’ conectar repositÃ³rio `paulo707070cesar/lootgamer`
(branch `main`, diretÃ³rio `public_html/lootgamer`) e habilitar deploy automÃ¡tico.

---

## ðŸ”’ SeguranÃ§a

- **Nunca** versione `config/config.php` (credenciais do banco)
- O painel usa sessÃ£o prÃ³pria (`data/sessions`) + CSRF em todos os formulÃ¡rios
- Uploads validados por MIME/extensÃ£o (`includes/uploads.php`); SVG sanitizado
- Senha do painel com hash `password_hash()`
- `.htaccess` bloqueia `config/`, `includes/`, `data/` e logs
- Recomendado: HTTPS forÃ§ado, backup periÃ³dico do banco e de `data/uploads`

---

## ðŸ§ª DiagnÃ³stico rÃ¡pido

| Sintoma | Verifique |
|---|---|
| Erro 500 | `logs/php-error.log`; permissÃµes de `data/`; `php -l` nos arquivos alterados |
| Painel nÃ£o loga | tabela `admin_usuarios`; sessÃ£o em `data/sessions` gravÃ¡vel |
| MudanÃ§as no painel nÃ£o aparecem no site | sincronizaÃ§Ã£o (`includes/lg-sync-legacy.php`) e cache do hPanel |
| Imagens quebradas | `data/uploads` existe e imagens importadas do marketplace/LFS |
| Cores nÃ£o mudam | `theme-admin.css` gerado + CSS inline no header; limpar cache |

---

## ðŸ“„ LicenÃ§a

Uso privado do projeto Loot Gamer. CÃ³digo do framework XLL e do tema incluÃ­dos
no repositÃ³rio para fins de manutenÃ§Ã£o e deploy.

