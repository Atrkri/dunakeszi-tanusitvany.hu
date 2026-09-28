<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Érvénytelen kérés.'], JSON_UNESCAPED_UNICODE);
    exit;
}
$to = 'gali.andras@dunakeszi-tanusitvany.hu';
function f(string $key): string { return trim((string)($_POST[$key] ?? '')); }
function safe_header(string $v): string { return trim(str_replace(["\r","\n"], '', $v)); }

$type=f('Űrlap_típusa');
$name=f('Név');
$phone=f('Telefonszám');
$email=f('E-mail');
$address=f('Ingatlan_címe');
$property=f('Ingatlan_típusa');
$reason=f('Megrendelés_oka');
$note=f('Megjegyzés');
$details=f('Ajánlat_részletei');

if ($name==='' || $email==='' || $address==='' || !filter_var($email,FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Kérjük, ellenőrizze a kötelező mezőket.'], JSON_UNESCAPED_UNICODE);
    exit;
}
if ($type==='') $type=($details!=='')?'Ajánlatkérés':'Megrendelés';

$subjectText='Dunakeszi tanúsítvány - '.$type.' - '.$name;
$subject='=?UTF-8?B?'.base64_encode($subjectText).'?=';
$lines=[
 'Weboldal: dunakeszi-tanusitvany.hu (ID:155)',
 '',
 'Űrlap típusa: '.$type,
 'Név: '.$name,
 'Telefonszám: '.($phone!==''?$phone:'—'),
 'E-mail: '.$email,
 'Ingatlan címe: '.$address,
 'Ingatlan típusa: '.($property!==''?$property:'—'),
 'Megrendelés oka: '.($reason!==''?$reason:'—')
];
if($details!=='')$lines[]='Ajánlat részletei: '.$details;
if($note!=='')$lines[]='Megjegyzés: '.$note;
$message=implode("\r\n",$lines);
$headers=[
 'MIME-Version: 1.0',
 'Content-Type: text/plain; charset=UTF-8',
 'From: Dunakeszi Tanúsítvány <gali.andras@dunakeszi-tanusitvany.hu>',
 'Reply-To: '.safe_header($email),
 'X-Mailer: PHP/'.phpversion()
];
if(!mail($to,$subject,$message,implode("\r\n",$headers))){
 http_response_code(500);
 echo json_encode(['success'=>false,'message'=>'Az e-mail küldése nem sikerült.'],JSON_UNESCAPED_UNICODE);
 exit;
}
echo json_encode(['success'=>true],JSON_UNESCAPED_UNICODE);
