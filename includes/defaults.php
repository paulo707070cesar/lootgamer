<?php
/**
 * Loot Gamer - valores padrão do site.
 *
 * Tudo aqui pode ser alterado pelo painel /admin e fica salvo no MySQL
 * (tabela `configuracoes`). Estes valores são usados na instalação e como
 * fallback quando alguma chave ainda não foi salva.
 */

declare(strict_types=1);

return [

    /* ------------------------------------------------------------------ */
    /* Identidade / SEO                                                    */
    /* ------------------------------------------------------------------ */
    'site_name'        => 'Loot Gamer',
    'site_slogan'      => 'Descoberta de ofertas gamer',
    'meta_description' => 'Ofertas gamer selecionadas com desconto em PC, consoles, periféricos e acessórios. Compare preços de Mercado Livre, Amazon e Shopee e compre direto na loja parceira.',
    'theme_color'      => '#01191f',

    'logo'             => 'assets/img/logo.webp',
    'logo_footer'      => 'assets/img/logo.webp',
    'favicon'          => 'assets/img/favicon-192.png',

    /* ------------------------------------------------------------------ */
    /* Cores (editáveis em Aparência)                                      */
    /* ------------------------------------------------------------------ */
    'colors' => [
        'background' => '#01191f',
        'card'       => '#0d3840',
        'border'     => '#0d4450',
        'text'       => '#f4f4f5',
        'muted'      => '#9bbcc2',
        'accent'     => '#00e676',
        'discount'   => '#ff5a1f',
        
        'button_text' => '#E0E2EC',
        'footer_title' => '#00E5FF',
        'radius'     => 6,
    ],

    /* ------------------------------------------------------------------ */
    /* Fontes (editáveis em Aparência)                                     */
    /* ------------------------------------------------------------------ */
    'fonts' => [
        'body'       => 'Inter',
        'heading'    => 'Inter',
        'body_size'  => 15,
        'h1_size'    => 30,
        'h2_size'    => 24,
        'h3_size'    => 20,
        'google_url' => '',
    ],

    /* ------------------------------------------------------------------ */
    /* Menu principal                                                      */
    /* ------------------------------------------------------------------ */
    'menu' => [
        ['label' => 'Início', 'url' => 'index.php', 'active' => true],
        ['label' => 'Ofertas', 'url' => 'ofertas.php', 'active' => true],
        ['label' => 'PC Gamer', 'url' => 'ofertas.php?categoria=PC%20Gamer', 'active' => true],
        ['label' => 'Consoles', 'url' => 'ofertas.php?categoria=Consoles', 'active' => true],
        ['label' => 'Periféricos', 'url' => 'ofertas.php?categoria=Perif%C3%A9ricos', 'active' => true],
        ['label' => 'Acessórios', 'url' => 'ofertas.php?categoria=Acess%C3%B3rios', 'active' => true],
    ],

    /* ------------------------------------------------------------------ */
    /* Hero / banner principal (carrossel)                                 */
    /* ------------------------------------------------------------------ */
    'hero' => [
        'enabled'  => true,
        'autoplay' => true,
        'speed'    => 6000,
        'slides'   => [
            [
                'image'  => 'assets/img/hero-ofertas.webp',
                'mobile' => '',
                'link'   => 'ofertas.php',
                'alt'    => 'Ofertas gamer em um só lugar',
                'active' => true,
            ],
            [
                'image'  => 'assets/img/hero-xll.webp',
                'mobile' => '',
                'link'   => 'ofertas.php',
                'alt'    => 'Loot Gamer — games, hardware, periféricos e consoles',
                'active' => true,
            ],
            [
                'image'  => 'assets/img/hero-batalha.webp',
                'mobile' => '',
                'link'   => 'ofertas.php',
                'alt'    => 'Entre na batalha — jogue, evolua, vença',
                'active' => true,
            ],
        ],
    ],

    /* ------------------------------------------------------------------ */
    /* Dois banners promocionais                                           */
    /* ------------------------------------------------------------------ */
    'promos' => [
        [
            'image'    => 'assets/img/promo-descontos.webp',
            'title'    => 'Os Melhores Descontos',
            'subtitle' => 'O Melhor Em Games',
            'cta'      => 'Conferir',
            'link'     => 'ofertas.php?desconto=1',
            'active'   => true,
        ],
        [
            'image'    => 'assets/img/promo-games.webp',
            'title'    => 'OS MELHORES GAMES PRA VOCÊ',
            'subtitle' => 'CONFIRA AS NOVIDADES EM GAMES',
            'cta'      => 'Saiba Mais',
            'link'     => 'ofertas.php?secao=Mais%20vendidos',
            'active'   => true,
        ],
    ],

    /* ------------------------------------------------------------------ */
    /* Diferenciais                                                        */
    /* ------------------------------------------------------------------ */
    'features' => [
        'title' => 'Por que usar a Loot Gamer?',
        'items' => [
            ['icon' => '🔎', 'icon_image' => '', 'title' => 'Ofertas selecionadas', 'text' => 'Encontre produtos gamer em diferentes lojas.'],
            ['icon' => '💰', 'icon_image' => '', 'title' => 'Compare preços', 'text' => 'Veja diferentes opções antes de comprar.'],
            ['icon' => '🛡️', 'icon_image' => '', 'title' => 'Compra diretamente na loja', 'text' => 'Você finaliza sua compra diretamente no marketplace.'],
            ['icon' => '🔗', 'icon_image' => '', 'title' => 'Tudo em um só lugar', 'text' => 'Pesquise produtos gamer sem precisar visitar várias lojas.'],
        ],
    ],

    /* ------------------------------------------------------------------ */
    /* Categorias da home                                                  */
    /* ------------------------------------------------------------------ */
    'categories' => [
        'title' => 'Encontre por categoria',
        'items' => [
            ['name' => 'Periféricos', 'description' => 'Teclados, mouses e mousepads', 'icon' => 'keyboard', 'image' => '', 'link' => '', 'active' => true],
            ['name' => 'Áudio', 'description' => 'Headsets, fones e microfones', 'icon' => 'headset', 'image' => '', 'link' => '', 'active' => true],
            ['name' => 'Hardware', 'description' => 'GPUs, CPUs, SSDs e memórias', 'icon' => 'cpu', 'image' => '', 'link' => '', 'active' => true],
            ['name' => 'Setup', 'description' => 'Cadeiras, mesas e iluminação', 'icon' => 'monitor', 'image' => '', 'link' => '', 'active' => true],
            ['name' => 'Consoles', 'description' => 'PlayStation, Xbox e Nintendo', 'icon' => 'gamepad', 'image' => '', 'link' => '', 'active' => true],
            ['name' => 'Mesas', 'description' => 'Mesas e suportes', 'icon' => 'ruler', 'image' => '', 'link' => '', 'active' => true],
            ['name' => 'Cadeiras', 'description' => 'Cadeiras gamer', 'icon' => 'chair', 'image' => '', 'link' => '', 'active' => true],
            ['name' => 'PC Gamer', 'description' => 'Computadores e setups completos', 'icon' => 'pc', 'image' => '', 'link' => '', 'active' => true],
        ],
    ],

    /* ------------------------------------------------------------------ */
    /* Seções de produtos da home                                          */
    /* ------------------------------------------------------------------ */
    'sections' => [
        'ofertas' => [
            'enabled'   => true,
            'title'     => '🔥 Ofertas em destaque',
            'subtitle'  => 'Confira produtos gamer selecionados e compare as opções antes de comprar.',
            'cta_text'  => 'VER MAIS',
            'cta_url'   => 'ofertas.php',
            'modo'      => 'secao',
            'valor'     => 'Destaque',
            'limite'    => 8,
        ],
        'setup' => [
            'enabled'   => true,
            'title'     => 'Produtos que podem melhorar seu setup',
            'subtitle'  => '',
            'cta_text'  => 'VER MAIS',
            'cta_url'   => 'ofertas.php',
            'modo'      => 'secao',
            'valor'     => 'Populares',
            'limite'    => 8,
        ],
        'bestsellers' => [
            'enabled'   => true,
            'title'     => '🏆 Mais vendidos',
            'subtitle'  => 'Os produtos mais procurados do catálogo.',
            'cta_text'  => 'VER MAIS',
            'cta_url'   => 'ofertas.php',
            'modo'      => 'secao',
            'valor'     => 'Mais vendidos',
            'limite'    => 8,
        ],
    ],

    /* ------------------------------------------------------------------ */
    /* Faixa do comparador                                                 */
    /* ------------------------------------------------------------------ */
    'compare' => [
        'enabled'    => true,
        'eyebrow'    => '// COMPARE ANTES DE COMPRAR',
        'title'      => 'Compare dois produtos lado a lado',
        'text'       => 'Selecione até dois produtos e veja preços, descontos e características em uma comparação direta para decidir qual vale mais a pena.',
        'cta_text'   => 'IR PARA COMPARAÇÃO',
        'empty_text' => 'Nenhum produto selecionado ainda',
        'bg_image'   => 'assets/img/cta-comparar.webp',
        'bg_color'   => '#04323c',
    ],

    /* ------------------------------------------------------------------ */
    /* CTA final                                                           */
    /* ------------------------------------------------------------------ */
    'final_cta' => [
        'enabled'  => true,
        'title'    => 'Pronto para melhorar seu setup?',
        'text'     => 'Encontre equipamentos e ofertas gamer em um só lugar.',
        'cta_text' => 'EXPLORAR OFERTAS',
        'cta_url'  => 'ofertas.php',
        'bg_image' => 'assets/img/cta-final.webp',
        'bg_color' => '#04323c',
    ],

    /* ------------------------------------------------------------------ */
    /* Rodapé, empresa e redes sociais                                     */
    /* ------------------------------------------------------------------ */
    'footer' => [
        'about_title' => 'Sobre a Loot Gamer',
        'about_text'  => 'A Loot Gamer ajuda gamers a encontrar produtos e ofertas para seus setups em diferentes lojas e marketplaces.',
        'socials'     => [
            'tiktok'    => 'https://www.tiktok.com/@xllgamingstore',
            'instagram' => 'https://www.instagram.com/xllgamingstore',
            'youtube'   => 'https://www.youtube.com/@xllgamingstore',
            'telegram'  => 'https://t.me/xllgamingstore',
            'whatsapp'  => 'https://wa.me/5551996600577',
        ],
        'columns' => [
            [
                'title' => 'Como funciona',
                'text'  => 'Pesquise, compare e escolha a oferta que mais combina com você. A compra é finalizada diretamente na loja parceira.',
                'links' => [],
            ],
            [
                'title' => 'Transparência',
                'text'  => 'A Loot Gamer pode receber comissão quando você realiza uma compra através de alguns dos nossos links. Isso não altera o preço pago por você.',
                'links' => [],
            ],
            [
                'title' => 'Importante',
                'text'  => 'A Loot Gamer não processa pagamentos, não realiza a venda dos produtos e não é responsável pelo envio ou atendimento da compra. Essas responsabilidades pertencem à loja ou marketplace onde a compra é finalizada.',
                'links' => [
                    ['label' => 'Política de Privacidade', 'url' => 'pagina.php?p=privacidade'],
                    ['label' => 'Termos de Uso', 'url' => 'pagina.php?p=termos'],
                    ['label' => 'Contato', 'url' => 'pagina.php?p=contato'],
                ],
            ],
        ],
        'copyright'  => '© 2026 Loot Gamer — Descoberta de ofertas gamer.',
        'note'       => 'Compra segura e transparente — você finaliza direto na loja parceira.',
        'disclosure' => 'Aviso de afiliados: a Loot Gamer participa de programas de afiliados (Mercado Livre, Amazon e Shopee) e pode receber comissão por compras realizadas pelos links. Os preços e a venda são de responsabilidade da loja parceira.',
        'company'    => [
            'name'     => 'Loot Gamer',
            'cnpj'     => '49.416.865/0001-88',
            'address'  => 'Rua Guilherme Fick 60, São Leopoldo-RS',
            'whatsapp' => '(51) 996600577',
            'email'    => 'contato@xll.com.br',
        ],
    ],

    /* ------------------------------------------------------------------ */
    /* Páginas institucionais                                              */
    /* ------------------------------------------------------------------ */
    'pages' => [
        'sobre' => [
            'title'   => 'Sobre a Loot Gamer',
            'content' => "A Loot Gamer é um hub de descoberta de ofertas gamer. Reunimos produtos de PC Gamer, consoles, periféricos, áudio, hardware e setup em um só lugar para você pesquisar, comparar preços e escolher a melhor oferta.\n\nNão vendemos produtos: você finaliza a compra diretamente na loja parceira (Mercado Livre, Amazon ou Shopee), com toda a segurança do marketplace.\n\nNosso objetivo é simples: economizar o seu tempo e ajudar você a montar o setup dos seus sonhos pagando menos.",
        ],
        'privacidade' => [
            'title'   => 'Política de Privacidade',
            'content' => "A Loot Gamer respeita a sua privacidade. Coletamos apenas informações necessárias para o funcionamento do site, como dados de navegação anônimos e informações enviadas por você em formulários de contato.\n\nNão vendemos nem compartilhamos seus dados pessoais com terceiros. Os links de afiliados podem utilizar cookies das lojas parceiras para registrar a origem da compra; isso não altera o preço pago por você.\n\nVocê pode solicitar a exclusão dos seus dados a qualquer momento pelo e-mail contato@xll.com.br.",
        ],
        'termos' => [
            'title'   => 'Termos de Uso',
            'content' => "Ao utilizar a Loot Gamer, você concorda que o site é um agregador de ofertas e não realiza vendas, pagamentos, entregas ou atendimento pós-venda.\n\nPreços, disponibilidade e condições de pagamento são definidos pelas lojas parceiras e podem mudar sem aviso. Sempre confirme as informações diretamente no marketplace antes de concluir a compra.\n\nA Loot Gamer pode receber comissão por compras realizadas através dos links de afiliados, sem qualquer custo adicional para você.",
        ],
        'contato' => [
            'title'   => 'Contato',
            'content' => "Fale com a Loot Gamer:\n\n• E-mail: contato@xll.com.br\n• WhatsApp: (51) 996600577\n• Endereço: Rua Guilherme Fick 60, São Leopoldo-RS\n\nRespondemos em até 1 dia útil. Para assuntos sobre pedidos, entrega ou pagamento, procure diretamente a loja ou marketplace onde a compra foi finalizada.",
        ],
    ],
];
