# 🎮 Loot Gamer — Site de Ofertas Gamer (PHP + MySQL)

Site de divulgação de ofertas de games, consoles, acessórios, PC Gamer e tecnologia,
com **painel administrativo completo** (framework XLL), tema escuro próprio,
sincronização automática de conteúdo e importação de catálogo.

> **Site:** https://lootgamer.xll.com.br
> **Painel:** https://lootgamer.xll.com.br/admin/

---

## ✨ Recursos

### Site público
- Home com **banner rotativo (hero)**, categorias com ícones (Material Symbols), ofertas em destaque, seções por categoria, comparador, CTA final
- Catálogo com **filtros** (ordem, loja, faixa de preço), paginação e busca
- Página de produto com galeria, descrição, informações de afiliado e produtos relacionados
- **Links de afiliado** com contagem de cliques (`/ir/{id}` → `go.php`)
- Blog, páginas institucionais, contato, newsletter, lojas parceiras
- **SEO**: título/descrição por página, canonical, Open Graph, JSON-LD, `sitemap.xml` dinâmico, `robots.txt`
- **Favicon** completo (`favicon.ico`, `favicon-32.png`, `apple-touch-icon.png`)
- **Tema escuro** configurável pelo painel (cores, fontes, ícones)
- Imagens externas via proxy opcional (`img.php`) com cache e conversão WebP

### Painel administrativo (XLL)
- **Painel** com métricas (produtos, categorias, descontos, banco, uploads)
- **Página inicial**: banner do topo, cards promocionais, diferenciais, categorias, seções de produtos, faixa comparador, CTA final, páginas
- **Produtos**: CRUD completo + formulário com imagem, preço antigo, desconto, link de afiliado, seção da home, ordem
- **Aparência**: logo, favicon, cores do site (fundo, cards, bordas, texto, texto suave, destaque, desconto, **texto dos botões**, **títulos do rodapé**), fontes
- **Rodapé, menu e páginas**: links de menu/rodapé e páginas institucionais
- **Importar / Exportar**: CSV/JSON de produtos com pré-visualização
- **Analytics de cliques**, **Mensagens de contato**, **Minha conta**
- Backup do admin anterior preservado em `admin-legacy/`

### Integrações
- **Sincronização automática** painel → site público: produtos, categorias, banners, logo/favicon, cores e configurações (`includes/lg-sync-legacy.php`)
- Compatibilidade com as tabelas legadas do site (o site público lê das tabelas antigas; o painel espelha automaticamente)

---

## 🧰 Requisitos

| Item | Versão mínima |
|---|---|
| PHP | **8.1+** (testado em 8.2/8.3) |
| MySQL/MariaDB | 5.7+ / 10.3+ |
| Extensões PHP | `pdo_mysql`, `mbstring`, `fileinfo`, `gd`, `curl` |
| Servidor | Apache/LiteSpeed (Hostinger) com `mod_rewrite` |
| Git LFS (opcional, para clonar imagens) | 3.x |

---

## 🚀 Instalação

### 1. Clonar / enviar os arquivos
```bash
git lfs install          # necessário para baixar as imagens via LFS
git clone https://github.com/paulo707070cesar/lootgamer.git
```
Envie os arquivos para o diretório público do site
(ex.: Hostinger → `public_html/lootgamer/`).

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
> `config/config.php` **não é versionado** (está no `.gitignore`). Use `config/config.local.example.php` como base.

### 3. Criar as tabelas
Abra `https://SEU-DOMINIO/install.php` e execute a instalação, **ou** importe:
```bash
mysql -u USUARIO -p BANCO < database.sql
```
Depois acesse `/admin/` e entre com o usuário criado.

### 4. Permissões
```bash
chmod -R 775 data/uploads data/sessions data/backups data/cache
chmod -R 775 uploads
```

### 5. Configuração inicial no painel
1. **Aparência** → logo, favicon, cores do tema, fontes
2. **Página inicial** → banner, categorias, diferenciais, seções, CTA
3. **Produtos** → cadastrar ou **Importar/Exportar** (CSV)
4. **Empresa** → rodapé, menu, páginas, SEO
5. **Conta** → trocar a senha do painel

---

## 📁 Estrutura do projeto

```
├── admin/                # Painel XLL (dashboard, produtos, aparência, importar...)
│   └── inc/layout.php    # Layout do painel + hook de sincronização
├── admin-legacy/         # Painel antigo (backup, não usado)
├── assets/               # CSS, JS, fontes e imagens do tema
├── includes/             # Framework XLL (bootstrap, db, functions, uploads...)
├── includes-site/        # Código do site público (templates e helpers)
├── config/               # Configurações (config.php não versionado)
├── data/                 # Uploads do painel, sessões, backups, cache
├── uploads/              # Uploads do site público (logo, banners, imagens)
├── index.php             # Home
├── produto.php           # Página de produto
├── ofertas.php           # Catálogo com filtros
├── categoria.php         # Listagem por categoria
├── go.php                # Redirecionador de afiliado (conta cliques)
├── img.php               # Proxy de imagens (cache/WebP)
├── sitemap.php           # Sitemap dinâmico
├── database.sql          # Estrutura inicial do banco
└── install.php           # Instalador
```

---

## 🎨 Personalização pelo painel

| O que mudar | Onde |
|---|---|
| Cores (fundo, cards, textos, botões, rodapé) | Aparência → Cores do site |
| Logo, favicon, nome do site | Aparência → Logo, favicon e nome |
| Banner do topo e cards promocionais | Página inicial |
| Categorias e ícones | Página inicial → Categorias |
| Menu e rodapé | Rodapé, menu e páginas |
| Fontes | Aparência → Fontes |
| Produtos | Produtos / Importar-Exportar |

> As cores salvas no painel são injetadas **inline** no `<head>` do site
> (`assets/css/theme-admin.css` → `includes-site/header.php`), evitando cache.

---

## 🔁 Deploy automático (GitHub → Hostinger)

O repositório está **conectado ao Hostinger Git** (hPanel → Avançado → GIT):

- **GitHub:** `paulo707070cesar/lootgamer`
- **Branch:** `main`
- **Diretório raiz:** `public_html`
- **Implantação automática:** ✅ ativada — todo push na `main` publica no site

### Como publicar alterações
```bash
git add -A
git commit -m "descrição da alteração"
git push origin main
```

O Hostinger aplica a implantação automaticamente; acompanhe em
**hPanel → Avançado → GIT → Implantações**.

> ⚠️ **Importante:** o deploy do Hostinger **não baixa objetos Git LFS**.
> Por isso o repositório mantém as imagens como arquivos normais (sem LFS),
> garantindo que os arquivos cheguem íntegros ao servidor.

### Cuidados
- Não versione `config/config.php` (credenciais) — já está no `.gitignore`
- O deploy não sobrescreve `config/config.php` (arquivo fora do repositório)
- Valide alterações localmente (`php -l`) antes do push
- Faça backup do banco antes de mudanças grandes

## 🔒 Segurança

- **Nunca** versione `config/config.php` (credenciais do banco)
- O painel usa sessão própria (`data/sessions`) + CSRF em todos os formulários
- Uploads validados por MIME/extensão (`includes/uploads.php`); SVG sanitizado
- Senha do painel com hash `password_hash()`
- `.htaccess` bloqueia `config/`, `includes/`, `data/` e logs
- Recomendado: HTTPS forçado, backup periódico do banco e de `data/uploads`

---

## 🧪 Diagnóstico rápido

| Sintoma | Verifique |
|---|---|
| Erro 500 | `logs/php-error.log`; permissões de `data/`; `php -l` nos arquivos alterados |
| Painel não loga | tabela `admin_usuarios`; sessão em `data/sessions` gravável |
| Mudanças no painel não aparecem no site | sincronização (`includes/lg-sync-legacy.php`) e cache do hPanel |
| Imagens quebradas | `data/uploads` existe e imagens importadas do marketplace/LFS |
| Cores não mudam | `theme-admin.css` gerado + CSS inline no header; limpar cache |

---

## 📄 Licença

Uso privado do projeto Loot Gamer. Código do framework XLL e do tema incluídos
no repositório para fins de manutenção e deploy.
