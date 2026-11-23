<?php
return array (

    // CONEXÃO MYSQL
    'db_host' => 'localhost',
    'db_name' => 'u940098558_lootgamer',
    'db_user' => 'u940098558_lootgamer',

    // Mantenha sua senha real atual do banco
    'db_pass' => 'o/;Mf:7J',

    'debug' => false,

    // GOOGLE SHEETS E DRIVE
    'google_sheets' => array (
        'enabled' => true,

        'spreadsheet_id' => '1SEWGT9judOi0Y1GIZYphNf1XdRo1daXjww7xW3q6ExQ',

        'credentials_file' => '/home/u940098558/domains/xll.com.br/lootgamer.xll.com.br/private/lootgamer-google.json',

        'drive_folder_id' => '1nGLlrQjfU7liVqAnsROY1RgYQmKPmwk6',

        
        'tabs' => array (
            'products' => 'Produtos',
            'articles' => 'Artigos',
            'banners' => 'Banners',
            'settings' => 'Configuracoes',
        ),
    ),

    // INTELIGÊNCIA ARTIFICIAL OPENAI
    'openai' => array (
        'api_key' => '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',

        'text_model' => 'gpt-6-astra',

        'image_model' => 'gpt-image-2.6-flare',
    ),

);