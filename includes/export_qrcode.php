<?php
/**
 * QR code as data URI (PNG) for embedding in HTML/PDF exports.
 * Prefers Endroid (composer); falls back to a public QR API if allowed.
 */

if (!defined('BASE_PATH')) {
    exit;
}

/**
 * @return string data:image/png;base64,... or empty string on failure
 */
function exportQrCodeDataUri(string $text, int $size = 120): string {
    $text = trim($text);
    if ($text === '') {
        return '';
    }
    $size = max(80, min(400, $size));

    if (class_exists(\Endroid\QrCode\Builder\Builder::class)
        && class_exists(\Endroid\QrCode\Writer\PngWriter::class)) {
        try {
            $result = \Endroid\QrCode\Builder\Builder::create()
                ->writer(new \Endroid\QrCode\Writer\PngWriter())
                ->data($text)
                ->size($size)
                ->margin(8)
                ->build();

            return $result->getDataUri();
        } catch (Throwable $e) {
            error_log('exportQrCodeDataUri Endroid: ' . $e->getMessage());
        }
    }

    $api = 'https://api.qrserver.com/v1/create-qr-code/?size=' . $size . 'x' . $size . '&ecc=M&data=' . rawurlencode($text);
    $png = exportQrCodeHttpGet($api);
    if ($png !== null && strlen($png) > 24 && strncmp($png, "\x89PNG\r\n\x1a\n", 8) === 0) {
        return 'data:image/png;base64,' . base64_encode($png);
    }

    return '';
}

function exportQrCodeHttpGet(string $url): ?string {
    if (function_exists('curl_init')) {
        $ch = curl_init($url);
        if ($ch === false) {
            return null;
        }
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 12,
            CURLOPT_USERAGENT => 'LSU-JobPortal/1.0',
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($code >= 200 && $code < 300 && is_string($body)) {
            return $body;
        }

        return null;
    }
    $ctx = stream_context_create([
        'http' => [
            'timeout' => 12,
            'header' => "User-Agent: LSU-JobPortal/1.0\r\n",
        ],
    ]);
    $body = @file_get_contents($url, false, $ctx);

    return is_string($body) ? $body : null;
}
