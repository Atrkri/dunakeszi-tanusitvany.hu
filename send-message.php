<?php
declare(strict_types=1);
header('Content-Type: text/html; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: index.html');
    exit;
}

$to = 'gali.andras@dunakeszi-tanusitvany.hu';

function f(string $key): string {
    return trim((string)($_POST[$key] ?? ''));
}
function safe_header(string $v): string {
    return trim(str_replace(["\r", "\n"], '', $v));
}

$type     = f('Űrlap_típusa');
$name     = f('Név');
$phone    = f('Telefonszám');
$email    = f('E-mail');
$address  = f('Ingatlan_címe');
$property = f('Ingatlan_típusa');
$reason   = f('Megrendelés_oka');
$note     = f('Megjegyzés');
$details  = f('Ajánlat_részletei');

if ($name === '' || $email === '' || $address === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo '<!doctype html><html lang="hu"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Hiba</title></head>
    <body style="font-family:Arial,sans-serif;padding:40px;max-width:700px;margin:auto;line-height:1.6">
    <h2>Az üzenet nem küldhető el.</h2>
    <p>Kérjük, ellenőrizze a név, e-mail cím és ingatlan címe mezőket.</p>
    <p><a href="javascript:history.back()">Vissza az űrlaphoz</a></p></body></html>';
    exit;
}

if ($type === '') {
    $type = ($details !== '') ? 'Ajánlatkérés' : 'Megrendelés';
}

$subjectText = 'Dunakeszi tanúsítvány - ' . $type . ' - ' . $name;
$subject = '=?UTF-8?B?' . base64_encode($subjectText) . '?=';

$lines = [
    'Weboldal: dunakeszi-tanusitvany.hu (ID:155)',
    '',
    'Űrlap típusa: ' . $type,
    'Név: ' . $name,
    'Telefonszám: ' . ($phone !== '' ? $phone : '—'),
    'E-mail: ' . $email,
    'Ingatlan címe: ' . $address,
    'Ingatlan típusa: ' . ($property !== '' ? $property : '—'),
    'Megrendelés oka: ' . ($reason !== '' ? $reason : '—')
];
if ($details !== '') $lines[] = 'Ajánlat részletei: ' . $details;
if ($note !== '') $lines[] = 'Megjegyzés: ' . $note;

$message = implode("\r\n", $lines);

$headers = [
    'MIME-Version: 1.0',
    'Content-Type: text/plain; charset=UTF-8',
    'From: Dunakeszi Tanúsítvány <gali.andras@dunakeszi-tanusitvany.hu>',
    'Reply-To: ' . safe_header($email),
    'X-Mailer: PHP/' . phpversion()
];

if (!mail($to, $subject, $message, implode("\r\n", $headers))) {
    http_response_code(500);
    echo '<!doctype html><html lang="hu"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Küldési hiba</title></head>
    <body style="font-family:Arial,sans-serif;padding:40px;max-width:700px;margin:auto;line-height:1.6">
    <h2>Az üzenet küldése most nem sikerült.</h2>
    <p>Kérjük, próbálja meg később.</p>
    <p><a href="javascript:history.back()">Vissza az űrlaphoz</a></p></body></html>';
    exit;
}
?>
<!doctype html>
<html lang="hu">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sikeres elküldés</title>
<style>
html,body { height:100%; margin:0; }
body {
    font-family:Arial,sans-serif;
    background:#f4f6f8;
    display:flex;
    align-items:center;
    justify-content:center;
}
.popup {
    width:420px;
    max-width:calc(100% - 40px);
    box-sizing:border-box;
    background:#fff;
    padding:32px;
    border-radius:14px;
    box-shadow:0 10px 35px rgba(0,0,0,.18);
    text-align:center;
}
.popup h2 { margin:0 0 14px; font-size:23px; }
.popup p { margin:0 0 22px; line-height:1.5; }
.back {
    color:#1677e8;
    font-weight:bold;
    text-decoration:none;
    font-size:16px;
}
.back:hover { text-decoration:underline; }
.arrow {
    color:#1677e8;
    font-size:25px;
    vertical-align:-2px;
    margin-right:7px;
}
</style>
</head>
<body>
<div class="popup">
    <h2>Köszönjük! Az üzenetet sikeresen elküldtük.</h2>
    <p>Hamarosan felvesszük Önnel a kapcsolatot.</p>
    <a class="back" href="index.html"><span class="arrow">&#8629;</span>Vissza a főoldalra</a>
</div>
</body>
</html>
