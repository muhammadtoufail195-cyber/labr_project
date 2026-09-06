<?php
require __DIR__.'/config.php';
require_login();
require __DIR__.'/app/helpers.php';

$id        = (int)($_GET['id'] ?? 0);
$copyParam = $_GET['copy'] ?? '';      // '', 'patient', 'lab'
$chain     = isset($_GET['chain']);    // when present, chain to next copy

/* -------- Lab info (safe defaults) -------- */
$lab = $conn->query('SELECT name,address,phone,IFNULL(whatsapp,"") AS whatsapp,IFNULL(email,"") AS email FROM lab_profile LIMIT 1')->fetch_assoc();
if (!$lab){
  $lab = [
    'name'     => 'New DAWN MEDICAL LABORATORY',
    'address'  => 'Afridi Medical Complex; 2nd Floor; (SF-42) Tehkal Peshawar.',
    'phone'    => '091-1234567',
    'whatsapp' => '',
    'email'    => ''
  ];
}

/* -------- Receipt + Patient -------- */
$r = $conn->query(
  'SELECT r.*,
          p.name AS pname, p.gender, p.age, p.address, p.mr_no,
          IFNULL(p.mobile,"") AS pmobile
   FROM receipts r
   JOIN patients p ON p.id = r.patient_id
   WHERE r.id = '.$id
)->fetch_assoc();
if(!$r){ die('Invoice not found'); }

$items = $conn->query('SELECT test_name,rate,qty,amount FROM receipt_items WHERE receipt_id='.$id.' ORDER BY id ASC')->fetch_all(MYSQLI_ASSOC);

$subtotal   = (float)$r['subtotal'];
$discount   = (float)$r['discount'];
$grand      = (float)$r['grand_total'];
$paid       = (float)$r['paid_amount'];
$remaining  = max(0.0, $grand - $paid);

$isPaid     = ($paid >= $grand - 0.009);
$showDisc   = $discount  > 0.0001;
$showPaid   = $paid      > 0.0001;
$showRemain = $remaining > 0.0001;
$showPhone  = !empty($r['pmobile']);
$showAddr   = !empty($r['address']);

/* -------- WhatsApp QR helpers -------- */
function normalize_phone_e164(string $raw, string $default_cc = '+92'): string {
  $raw = trim($raw);
  if ($raw === '') return '';
  $s = preg_replace('/[^\d+]/', '', $raw);
  if ($s === '') return '';
  if ($s[0] === '+') return $s;
  if ($s[0] === '0') return $default_cc . substr($s, 1);
  return '+' . $s;
}

$wa_phone = trim($lab['whatsapp'] ?? '');
$wa_e164  = $wa_phone ? normalize_phone_e164($wa_phone, '+92') : '';
$wa_link  = $wa_e164 ? ('https://wa.me/' . ltrim($wa_e164, '+')) : '';
$qr_url   = $wa_link ? ('https://api.qrserver.com/v1/create-qr-code/?size=120x120&data=' . rawurlencode($wa_link)) : '';
