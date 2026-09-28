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
$address=f('Ingatlan címe');
if ($address==='') $address=f('Ingatlan_címe');
if ($address==='') $address=f('Ingatlan_címe');
$property=f('Ingatlan jellege');
if ($property==='') $property=f('Ingatlan típusa');
if ($property==='') $property=f('Ingatlan_jellege');
if ($property==='') $property=f('Ingatlan_típusa');
$area=f('Ingatlan hasznos alapterülete');
if ($area==='') $area=f('Ingatlan alapterülete');
if ($area==='') $area=f('Ingatlan_hasznos_alapterülete');
if ($area==='') $area=f('Ingatlan_alapterülete');
$reason=f('Tanúsítás célja');
if ($reason==='') $reason=f('Megrendelés oka');
if ($reason==='') $reason=f('Megrendelés_oka');
if ($reason==='') $reason=f('Ajánlatkérés oka');
if ($reason==='') $reason=f('Tanúsítás_célja');
if ($reason==='') $reason=f('Ajánlatkérés_oka');
$allowedAreas=['0-50 m²','51-90 m²','91-120 m²','121-160 m²','161-200 m²','201 m² felett'];
$allowedReasons=['Adásvételhez','Bérbeadáshoz','Banki hitelhez','Pályázathoz','Egyéb célra'];
$note=f('Megjegyzés');
$details=f('Ajánlat_részletei');

if ($type==='') $type=($details!=='')?'Ajánlatkérés':'Megrendelés';
$missingOrderFields = $type==='Megrendelés' && ($phone==='' || $property==='' || $note==='');

if ($missingOrderFields || $name==='' || $email==='' || $address==='' || !in_array($area,$allowedAreas,true) || !in_array($reason,$allowedReasons,true) || !filter_var($email,FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success'=>false,'message'=>'Kérjük, ellenőrizze a kötelező mezőket.'], JSON_UNESCAPED_UNICODE);
    exit;
}


if ($type === 'Ajánlatkérés') {
    $subjectText = 'Tanúsítvány ajánlatkérés - Dunakeszi - dunakeszi-tanusitvany.hu';
} else {
    $reason = preg_replace('/\s+/', ' ', trim($reason));
    $subjectAddress = preg_replace('/\s+/', ' ', trim($address));
    $subjectText = 'Tanúsítvány megrendelés - ' . $subjectAddress;
}
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
 'Ingatlan alapterülete: '.$area,
 ($type==='Ajánlatkérés'?'Ajánlatkérés oka: ':'Megrendelés oka: ').$reason
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
