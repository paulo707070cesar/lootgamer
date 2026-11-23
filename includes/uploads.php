<?php
/**
 * Loot Gamer - uploads de imagens do painel.
 */

declare(strict_types=1);

/**
 * @return array<string,string> mime => extensão
 */
function xll_upload_mimes(): array
{
    return [
        'image/jpeg'             => 'jpg',
        'image/png'              => 'png',
        'image/webp'             => 'webp',
        'image/gif'              => 'gif',
        'image/svg+xml'          => 'svg',
        'image/x-icon'           => 'ico',
        'image/vnd.microsoft.icon' => 'ico',
        'image/avif'             => 'avif',
    ];
}

function xll_upload_dir(): string
{
    $dir = XLL_UPLOAD_DIR;
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }

    return $dir;
}

function xll_upload_error(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'A imagem excede o tamanho máximo permitido.',
        UPLOAD_ERR_PARTIAL  => 'O envio da imagem foi interrompido. Tente novamente.',
        UPLOAD_ERR_NO_FILE  => 'Nenhum arquivo enviado.',
        UPLOAD_ERR_NO_TMP_DIR => 'Pasta temporária do PHP indisponível.',
        UPLOAD_ERR_CANT_WRITE => 'Não foi possível gravar o arquivo no servidor.',
        UPLOAD_ERR_EXTENSION => 'O envio foi bloqueado por uma extensão do PHP.',
        default => 'Falha ao enviar a imagem.',
    };
}

function xll_svg_sanitize(string $content): string
{
    if (trim($content) === '' || !class_exists('DOMDocument')) {
        return $content;
    }
    $doc = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $loaded = $doc->loadXML($content, LIBXML_NONET);
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    if (!$loaded) {
        return '';
    }
    $xpath = new DOMXPath($doc);
    foreach (iterator_to_array($xpath->query('//script') ?: []) as $node) {
        $node->parentNode?->removeChild($node);
    }
    foreach (iterator_to_array($xpath->query('//*') ?: []) as $node) {
        if (!$node instanceof DOMElement) {
            continue;
        }
        foreach (iterator_to_array($node->attributes) as $attribute) {
            $name = strtolower($attribute->nodeName);
            $value = trim($attribute->nodeValue ?? '');
            if (str_starts_with($name, 'on') || stripos($value, 'javascript:') !== false) {
                $node->removeAttribute($attribute->nodeName);
            }
        }
    }

    return (string) $doc->saveXML();
}

/**
 * Salva um arquivo enviado e devolve o caminho relativo (data/uploads/...).
 *
 * @param array<string,mixed> $file Item de $_FILES.
 */
function xll_save_upload(array $file, string $prefix = 'img'): string
{
    $error = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($error !== UPLOAD_ERR_OK) {
        throw new RuntimeException(xll_upload_error($error));
    }
    $tmp = (string) ($file['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp)) {
        throw new RuntimeException('Arquivo de upload inválido.');
    }
    $size = (int) ($file['size'] ?? 0);
    $max = (int) xll_config('upload_max', 6 * 1024 * 1024);
    if ($size <= 0 || $size > $max) {
        throw new RuntimeException('A imagem precisa ter no máximo ' . round($max / 1024 / 1024) . ' MB.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = (string) $finfo->file($tmp);
    $mimes = xll_upload_mimes();
    if (!isset($mimes[$mime])) {
        throw new RuntimeException('Formato não permitido. Envie JPG, PNG, WEBP, GIF, AVIF, SVG ou ICO.');
    }
    $ext = $mimes[$mime];

    $prefix = strtolower(preg_replace('/[^a-z0-9_\-]/i', '', $prefix) ?? 'img');
    if ($prefix === '') {
        $prefix = 'img';
    }
    $name = $prefix . '-' . date('YmdHis') . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destination = xll_upload_dir() . '/' . $name;

    if (!move_uploaded_file($tmp, $destination)) {
        throw new RuntimeException('Não foi possível salvar a imagem enviada.');
    }
    @chmod($destination, 0644);

    if ($ext === 'svg') {
        $sanitized = xll_svg_sanitize((string) file_get_contents($destination));
        if ($sanitized === '') {
            @unlink($destination);
            throw new RuntimeException('SVG inválido.');
        }
        file_put_contents($destination, $sanitized);
    }

    return XLL_UPLOAD_URL . '/' . $name;
}

/**
 * Processa um campo de upload opcional. Se nenhum arquivo for enviado,
 * mantém o valor atual.
 */
function xll_handle_upload(string $field, string $prefix, string $current = ''): string
{
    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
        return $current;
    }
    $file = $_FILES[$field];
    if ((int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return $current;
    }

    return xll_save_upload($file, $prefix);
}

/**
 * Remove uma imagem enviada (somente dentro de data/uploads).
 */
function xll_delete_media(?string $path): void
{
    $path = trim((string) $path);
    if (!str_starts_with($path, XLL_UPLOAD_URL . '/')) {
        return;
    }
    $file = realpath(XLL_ROOT . '/' . $path);
    $uploads = realpath(XLL_UPLOAD_DIR);
    if ($file && $uploads && str_starts_with($file, $uploads) && is_file($file)) {
        @unlink($file);
    }
}

/**
 * Extrai um upload de um índice específico (arrays de múltiplos arquivos).
 *
 * @param array<string,mixed> $files Estrutura de $_FILES.
 * @return array<string,mixed>|null
 */
function xll_upload_item(array $files, int|string $index): ?array
{
    if (!isset($files['name'][$index]) || (int) ($files['error'][$index] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    return [
        'name'     => $files['name'][$index],
        'type'     => $files['type'][$index] ?? '',
        'tmp_name' => $files['tmp_name'][$index] ?? '',
        'error'    => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
        'size'     => $files['size'][$index] ?? 0,
    ];
}
