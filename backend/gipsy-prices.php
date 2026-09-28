<?php
// PHP 7.4+; this endpoint only serves Dunakeszi (2120).
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Content-Type-Options: nosniff');

const OTK_ZIP = 2120;
const OTK_KEYS = array('th2', 'th3', 'th4', 'cs2', 'cs3', 'cs4');
// Display keys remain compatible with earlier HTML; larger apartments share house prices.
const OTK_SOURCE_KEYS = array('th2' => 'th2', 'th3' => 'cs3', 'th4' => 'cs4', 'cs2' => 'cs2', 'cs3' => 'cs3', 'cs4' => 'cs4');
const OTK_SOURCE = 'https://otk.hu/prices/energetikai-tanusitvany-arak.php';

function otk_valid_prices($prices) {
    if (!is_array($prices)) return false;
    foreach (OTK_KEYS as $key) {
        if (!isset($prices[$key]) || !is_int($prices[$key]) || $prices[$key] <= 0 || $prices[$key] > 10000000) return false;
    }
    return true;
}

function otk_read_cache($path) {
    $data = is_file($path) ? json_decode((string) @file_get_contents($path), true) : null;
    if (!is_array($data) || !isset($data['zip'], $data['updated_at'], $data['prices'])
        || $data['zip'] !== OTK_ZIP || !is_int($data['updated_at'])
        || $data['updated_at'] > time() || $data['updated_at'] <= 0
        || !otk_valid_prices($data['prices'])) return null;
    // Normalize older saved data to the confirmed category mapping.
    $data['prices']['th3'] = $data['prices']['cs3'];
    $data['prices']['th4'] = $data['prices']['cs4'];
    return $data;
}

function otk_download() {
    if (function_exists('curl_init')) {
        $ch = curl_init(OTK_SOURCE);
        curl_setopt_array($ch, array(
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 7,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2
        ));
        $html = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return $status === 200 && is_string($html) ? $html : false;
    }
    if (!filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) return false;
    $context = stream_context_create(array(
        'http' => array('timeout' => 7, 'follow_location' => 0),
        'ssl' => array('verify_peer' => true, 'verify_peer_name' => true)
    ));
    $html = @file_get_contents(OTK_SOURCE, false, $context);
    return isset($http_response_header[0]) && preg_match('~^HTTP/\S+\s+200\b~', $http_response_header[0]) ? $html : false;
}

function otk_parse($html) {
    $prices = array();
    if (!is_string($html)) return $prices;
    foreach (OTK_KEYS as $key) {
        $id = preg_quote(OTK_SOURCE_KEYS[$key] . '-' . OTK_ZIP, '~');
        $pattern = '~<td\b[^>]*\s+id\s*=\s*(["\'])' . $id . '\1[^>]*>(.*?)</td\s*>~is';
        if (preg_match_all($pattern, $html, $matches) !== 1) continue;
        $text = html_entity_decode(strip_tags($matches[2][0]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[\s\x{00A0}\x{202F}]+/u', '', $text);
        if (is_string($text) && preg_match('/^([0-9]+)(?:Ft)?$/iu', $text, $number)) $prices[$key] = (int) $number[1];
    }
    return $prices;
}

function otk_save($path, $data) {
    $temporary = @tempnam(__DIR__, 'otk-');
    if ($temporary === false) return false;
    $written = @file_put_contents($temporary, json_encode($data), LOCK_EX);
    $saved = $written !== false && @rename($temporary, $path);
    if (is_file($temporary)) @unlink($temporary);
    return $saved;
}

// Refresh valid cells independently. Zero does not mean a free certificate.
$path = __DIR__ . '/gipsy-prices.txt';
$previous = otk_read_cache($path);
$html = otk_download();
$incoming = otk_parse($html);
$prices = $previous !== null ? $previous['prices'] : array(
    'th2' => 27000, 'th3' => 32000, 'th4' => 34000,
    'cs2' => 30000, 'cs3' => 32000, 'cs4' => 34000
);
$updatedKeys = array();
$retainedKeys = array();
$cellIssues = array();
foreach (OTK_KEYS as $key) {
    if (isset($incoming[$key]) && is_int($incoming[$key]) && $incoming[$key] > 0 && $incoming[$key] <= 10000000) {
        $prices[$key] = $incoming[$key];
        $updatedKeys[] = $key;
    } else {
        $retainedKeys[] = $key;
        $cellIssues[$key] = isset($incoming[$key]) ? 'zero_or_invalid_price' : 'missing_or_unreadable_cell';
    }
}
$saved = false;
$errorCode = null;
if (count($updatedKeys) > 0) {
    $saved = otk_save($path, array(
        'zip' => OTK_ZIP,
        'updated_at' => time(),
        'prices' => $prices,
        'refreshed_keys' => $updatedKeys,
        'retained_keys' => $retainedKeys
    ));
    if (!$saved) $errorCode = 'cache_write_failed';
} else {
    $errorCode = is_string($html) ? 'no_positive_prices_found' : 'upstream_download_failed';
}
$data = otk_read_cache($path);
$messages = array(
    'cache_write_failed' => 'Az OTK-árak megérkeztek, de a TXT mentése sikertelen. Ellenőrizze a backend mappa írási jogát.',
    'no_positive_prices_found' => 'Az OTK-oldal letöltődött, de nem található érvényes pozitív ár a 2120-as cellákban.',
    'upstream_download_failed' => 'Az OTK-oldal letöltése sikertelen. Ellenőrizze a szerver külső HTTPS-elérését.'
);
if ($data === null) {
    http_response_code(503);
    echo json_encode(array(
        'ok' => false, 'zip' => OTK_ZIP, 'version' => 'mapped-v4',
        'error_code' => $errorCode,
        'message' => isset($messages[$errorCode]) ? $messages[$errorCode] : 'A TXT nem olvasható.',
        'cell_issues' => $cellIssues,
        'backend_writable' => is_writable(__DIR__)
    ), JSON_UNESCAPED_UNICODE);
    exit;
}
echo json_encode(array(
    'ok' => true,
    'zip' => OTK_ZIP,
    'version' => 'mapped-v4',
    'status' => $saved ? (count($retainedKeys) ? 'partial' : 'fresh') : 'cached',
    'updated_at' => $data['updated_at'],
    'prices' => $data['prices'],
    'source_keys' => OTK_SOURCE_KEYS,
    'refreshed_keys' => $saved ? $updatedKeys : array(),
    'retained_keys' => $saved ? $retainedKeys : OTK_KEYS,
    'cell_issues' => $cellIssues,
    'error_code' => $errorCode,
    'message' => $errorCode !== null ? $messages[$errorCode] : (count($retainedKeys) ? 'A pozitív OTK-árak frissültek. A nullás vagy hiányzó cellákhoz a korábbi árak maradtak.' : 'Minden ár frissült.')
), JSON_UNESCAPED_UNICODE);
