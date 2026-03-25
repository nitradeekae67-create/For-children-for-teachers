<?php
session_start();
include('connect.php');

if ($conn->connect_error) { die("Connection failed: " . $conn->connect_error); }
$conn->set_charset("utf8mb4");

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) { header('Location: all_news.php'); exit; }

// 1. ดึงรายละเอียดข่าว และ ข้อมูลกิจกรรมที่ผูกอยู่
$stmt = $conn->prepare("
    SELECT pr.*, e.event_name, e.event_date, e.Location, e.status AS event_status
    FROM news_update pr
    LEFT JOIN events e ON pr.event_id = e.event_id
    WHERE pr.news_id = ? AND pr.status = 1
");
$stmt->bind_param("i", $id);
$stmt->execute();
$news = $stmt->get_result()->fetch_assoc();
if (!$news) { header('Location: all_news.php'); exit; }

// 2. ดึงรูปภาพประกอบข่าว
$stmt2 = $conn->prepare("SELECT image_path FROM news_images WHERE news_id = ?");
$stmt2->bind_param("i", $id);
$stmt2->execute();
$res_imgs = $stmt2->get_result();
$images = [];
while ($r = $res_imgs->fetch_assoc()) { $images[] = 'img/' . $r['image_path']; }

// 3. จัดการสถานะและวันที่
$evstat  = strtolower(trim($news['event_status'] ?? 'active'));
$is_open = ($evstat !== 'closed');

$thai_months = ['','ม.ค.','ก.พ.','มี.ค.','เม.ย.','พ.ค.','มิ.ย.','ก.ค.','ส.ค.','ก.ย.','ต.ค.','พ.ย.','ธ.ค.'];
function fmt_thai($d,$m){
    if(!$d) return '';
    $ts=strtotime($d); if(!$ts) return '';
    return date('j',$ts).' '.$m[(int)date('n',$ts)].' '.date('Y',$ts);
}
$display_date = fmt_thai($news['event_date'] ?? '', $thai_months);

// 4. ดึงข่าวที่เกี่ยวข้อง
$related = [];
if (!empty($news['event_id'])) {
    $s3 = $conn->prepare("SELECT pr.news_id,pr.title,e.event_name FROM news_update pr LEFT JOIN events e ON pr.event_id=e.event_id WHERE pr.event_id=? AND pr.news_id!=? AND pr.status=1 LIMIT 4");
    $s3->bind_param("ii", $news['event_id'], $id);
    $s3->execute();
    $r3 = $s3->get_result();
    while ($row = $r3->fetch_assoc()) { $related[] = $row; }
}

// 5. แผนที่
$location_name = $news['Location'] ?? '';
$encoded_location = urlencode($location_name);
$map_url = "";
if (!empty($location_name)) {
    $map_url = "https://maps.google.com/maps?q=" . $encoded_location . "&t=&z=15&ie=UTF8&iwloc=&output=embed";
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($news['title']) ?></title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Mitr:wght@400;500;600;700&family=Sarabun:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
/* ════════ RESET ════════ */
*,*::before,*::after{box-sizing:border-box;margin:0;padding:0}

/* ════════ TOKENS ════════ */
:root{
  --b900:#0d2b5e; --b700:#1565c0; --b500:#2196f3; --b300:#90caf9;
  --b100:#e3f2fd; --b50:#f0f8ff;
  --o700:#f57c00; --o500:#ff9800; --o300:#ffb74d; --o100:#fff3e0; --o50:#fffbf5;
  --ink:#0a1628;  --ink2:#1e3a5f; --muted:#6b859e;
  --border:rgba(21,101,192,.1);   --border2:rgba(21,101,192,.18);
  --white:#fff;   --bg:#edf4ff;
  --green:#2e7d32; --green-bg:#e8f5e9;
  --gray:#757575;  --gray-bg:#f5f5f5;
  --r:12px; --r-lg:20px; --r-xl:28px;
  --sh-sm:0 2px 8px rgba(21,101,192,.08);
  --sh-md:0 6px 24px rgba(21,101,192,.12);
  --sh-lg:0 16px 56px rgba(21,101,192,.16);
  --sh-or:0 8px 28px rgba(245,124,0,.22);
}

html{scroll-behavior:smooth;-webkit-font-smoothing:antialiased}
body{
  background:var(--bg);
  font-family:'Sarabun',sans-serif;
  color:var(--ink2); line-height:1.9; font-size:15px;
  overflow-x:hidden;
}

/* Ambient gradient bg */
body::before{
  content:''; position:fixed; inset:0; z-index:0; pointer-events:none;
  background:
    radial-gradient(ellipse 60% 50% at 8% 12%, rgba(33,150,243,.08) 0%,transparent 68%),
    radial-gradient(ellipse 55% 45% at 92% 82%, rgba(255,152,0,.08) 0%,transparent 68%),
    radial-gradient(ellipse 40% 35% at 52% 48%, rgba(144,202,249,.04) 0%,transparent 78%);
}
/* Subtle dot grid */
body::after{
  content:''; position:fixed; inset:0; z-index:0; pointer-events:none;
  opacity:.3;
  background-image:
    radial-gradient(circle,rgba(33,150,243,.22) 1px,transparent 1px),
    radial-gradient(circle,rgba(255,152,0,.18) 1px,transparent 1px);
  background-size:44px 44px,66px 66px;
  background-position:0 0,22px 22px;
}

/* ════════ PROGRESS BAR ════════ */
#rprog{
  position:fixed;top:0;left:0;height:4px;width:0;z-index:9999;
  border-radius:0 3px 3px 0;
  background:linear-gradient(90deg,var(--b700),var(--b300),var(--o500),var(--o300));
  transition:width .06s linear;
}

/* ════════ FLOATING DOLL ════════ */
#doll-wrap{
  position:fixed; bottom:0; right:20px; width:92px;
  z-index:200; pointer-events:none;
  transform:translateY(160px); opacity:0;
  transition:transform .7s cubic-bezier(.34,1.56,.64,1), opacity .4s ease;
}
#doll-wrap.show{ transform:translateY(0); opacity:1; }
#doll-svg{ display:block; }
#doll-wrap.show #doll-svg{
  animation:dollBounce 3.2s ease-in-out infinite;
}
@keyframes dollBounce{
  0%,100%{ transform:translateY(0) rotate(0deg) }
  28%    { transform:translateY(-13px) rotate(2.5deg) }
  60%    { transform:translateY(-7px) rotate(-1.5deg) }
}

/* Speech bubble */
#doll-bubble{
  position:absolute; bottom:136px; right:0;
  background:var(--white);
  border:2.5px solid var(--b300);
  border-radius:14px 14px 4px 14px;
  padding:7px 13px;
  font-family:'Mitr',sans-serif; font-size:12px; font-weight:600;
  color:var(--b700); white-space:nowrap;
  box-shadow:var(--sh-md);
  opacity:0; transform:scale(.65) translateY(8px);
  transform-origin:bottom right;
  transition:opacity .3s, transform .38s cubic-bezier(.34,1.56,.64,1);
  pointer-events:none;
}
#doll-bubble.show{ opacity:1; transform:scale(1) translateY(0); }

/* Sparkle particles */
.sparkle{
  position:fixed; z-index:201; pointer-events:none; font-size:18px;
  animation:sparkleFly 2.4s ease-out forwards;
}
@keyframes sparkleFly{
  0%  { opacity:0; transform:translateY(0) scale(.3) rotate(0deg) }
  18% { opacity:1; transform:translateY(-25px) scale(1) rotate(12deg) }
  78% { opacity:.55; transform:translateY(-140px) scale(1.05) rotate(28deg) }
  100%{ opacity:0; transform:translateY(-185px) scale(.6) rotate(45deg) }
}

/* ════════ PAGE SHELL ════════ */
.page{ max-width:1120px; margin:0 auto; padding:36px 22px 100px; position:relative; z-index:1; }

/* ════════ BACK BUTTON ════════ */
.back-pill{
  display:inline-flex; align-items:center; gap:9px;
  margin-bottom:28px;
  background:var(--white); color:var(--b700);
  font-family:'Mitr',sans-serif; font-size:13px; font-weight:600;
  text-decoration:none;
  padding:9px 20px 9px 12px;
  border-radius:99px;
  border:2px solid var(--b100);
  box-shadow:var(--sh-sm);
  transition:.2s;
}
.back-pill .arr-circle{
  width:28px; height:28px; border-radius:50%;
  background:var(--b100); color:var(--b700);
  display:flex; align-items:center; justify-content:center;
  font-size:10px; flex-shrink:0; transition:.2s;
}
.back-pill:hover{ background:var(--b700); color:#fff; border-color:var(--b700); transform:translateX(-3px); }
.back-pill:hover .arr-circle{ background:rgba(255,255,255,.2); color:#fff; }

/* ════════ HERO LAYOUT ════════ */
.hero-layout{
  display:grid; grid-template-columns:1.1fr 1fr;
  background:var(--white);
  border-radius:var(--r-xl);
  overflow:hidden;
  border:2px solid var(--border2);
  margin-bottom:28px;
  min-height:480px;
  box-shadow:var(--sh-lg);
  position:relative;
}
/* Rainbow stripe */
.hero-layout::before{
  content:''; position:absolute; top:0; left:0; right:0; height:5px; z-index:2;
  background:linear-gradient(90deg,var(--b700) 0%,var(--b300) 32%,var(--o500) 65%,var(--o300) 100%);
}

/* Image pane */
.img-pane{
  position:relative; overflow:hidden;
  background:linear-gradient(135deg,var(--b900),var(--b700));
  min-height:480px;
}
.img-pane img{ width:100%; height:100%; object-fit:cover; display:block; transition:transform 8s ease; }
.img-pane:hover img{ transform:scale(1.05); }
.img-grad1{
  position:absolute; inset:0; pointer-events:none;
  background:linear-gradient(to top, rgba(9,21,45,.72) 0%, rgba(21,101,192,.14) 52%, transparent 100%);
}
.img-grad2{
  position:absolute; inset:0; pointer-events:none;
  background:linear-gradient(to right, rgba(9,21,45,.12) 0%, transparent 50%);
}
.img-empty{
  width:100%; height:100%;
  display:flex; align-items:center; justify-content:center;
  background:linear-gradient(135deg,var(--b900) 0%,var(--b700) 100%);
}
.img-empty i{ font-size:4rem; color:rgba(255,255,255,.1); }

/* Status badge */
.img-badge{
  position:absolute; top:20px; left:20px; z-index:3;
  display:inline-flex; align-items:center; gap:6px;
  padding:6px 14px; border-radius:99px;
  font-family:'Mitr',sans-serif; font-size:11px; font-weight:700;
  backdrop-filter:blur(10px); -webkit-backdrop-filter:blur(10px);
  background:<?= $is_open ? 'rgba(255,152,0,.22)' : 'rgba(0,0,0,.32)' ?>;
  color:<?= $is_open ? '#fff' : 'rgba(255,255,255,.7)' ?>;
  border:1.5px solid <?= $is_open ? 'rgba(255,183,77,.55)' : 'rgba(255,255,255,.2)' ?>;
  box-shadow:0 2px 10px rgba(0,0,0,.2);
}
.bdot{
  width:7px; height:7px; border-radius:50%;
  background:<?= $is_open ? 'var(--o300)' : '#9e9e9e' ?>;
  <?= $is_open ? 'animation:bdotPulse 1.8s ease-in-out infinite' : '' ?>
}
@keyframes bdotPulse{ 0%,100%{opacity:1;transform:scale(1)} 50%{opacity:.3;transform:scale(.6)} }

/* Thumbnails */
.img-thumbs{
  position:absolute; bottom:14px; left:0; right:0; z-index:3;
  display:flex; justify-content:center; gap:7px; padding:0 16px; flex-wrap:wrap;
}
.img-thumbs img{
  width:54px; height:42px; object-fit:cover;
  border-radius:8px; cursor:pointer;
  opacity:.44; border:2.5px solid transparent;
  flex-shrink:0; transition:.18s;
}
.img-thumbs img.on,.img-thumbs img:hover{ opacity:1; border-color:var(--o500); }

/* Info pane */
.info-pane{
  display:flex; flex-direction:column; justify-content:space-between;
  padding:40px 38px 32px; position:relative;
}
/* cross pattern bg */
.info-pane::before{
  content:''; position:absolute; inset:0; pointer-events:none; opacity:.035;
  background-image:url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='%231565c0'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/svg%3E");
}

.event-chip{
  display:inline-flex; align-items:center; gap:7px;
  background:linear-gradient(135deg,var(--o500),var(--o300));
  color:#fff;
  padding:6px 16px; border-radius:99px;
  font-family:'Mitr',sans-serif; font-size:12px; font-weight:700;
  margin-bottom:18px; align-self:flex-start;
  box-shadow:var(--sh-or); position:relative;
}
.news-title{
  font-family:'Mitr',sans-serif;
  font-size:clamp(1.4rem,2.8vw,2rem);
  font-weight:700; color:var(--ink);
  line-height:1.4; margin-bottom:22px; letter-spacing:-.01em;
}

/* Meta */
.meta-list{ display:flex; flex-direction:column; gap:12px; margin-bottom:24px; }
.meta-item{ display:flex; align-items:flex-start; gap:12px; }
.meta-ico{
  width:36px; height:36px; border-radius:10px;
  display:flex; align-items:center; justify-content:center;
  font-size:13px; flex-shrink:0; border:1.5px solid transparent;
}
.meta-ico.blue{ background:var(--b100); color:var(--b700); border-color:rgba(33,150,243,.15); }
.meta-ico.orange{ background:var(--o100); color:var(--o700); border-color:rgba(255,152,0,.15); }
.meta-ico.green{ background:var(--green-bg); color:var(--green); }
.meta-ico.gray{ background:var(--gray-bg); color:var(--gray); }
.meta-lbl{ font-size:10px; color:var(--muted); font-weight:700; letter-spacing:.07em; text-transform:uppercase; margin-bottom:3px; font-family:'Mitr',sans-serif; }
.meta-val{ font-size:13px; font-weight:600; color:var(--ink); line-height:1.35; }

.pill-open{
  display:inline-flex; align-items:center; gap:5px;
  padding:4px 12px; border-radius:99px; font-size:11px; font-weight:700;
  font-family:'Mitr',sans-serif;
  background:var(--green-bg); color:var(--green);
  border:1.5px solid rgba(46,125,50,.2);
}
.pill-closed{
  display:inline-flex; align-items:center; gap:5px;
  padding:4px 12px; border-radius:99px; font-size:11px; font-weight:700;
  font-family:'Mitr',sans-serif;
  background:var(--gray-bg); color:var(--gray);
}
.pill-dot{ width:6px; height:6px; border-radius:50%; background:currentColor; }
.pill-dot.live{ animation:bdotPulse 1.8s ease-in-out infinite; }

/* Share row */
.pane-share{
  display:flex; align-items:center; gap:8px;
  padding-top:20px; border-top:2px dashed var(--border); margin-top:auto;
}
.share-lbl{ font-size:12px; font-weight:700; color:var(--muted); font-family:'Mitr',sans-serif; margin-right:4px; }
.s-btn{
  width:36px; height:36px; border-radius:10px;
  display:inline-flex; align-items:center; justify-content:center;
  font-size:14px; border:1.5px solid var(--border2);
  background:var(--b50); color:var(--b700);
  text-decoration:none; transition:.18s; cursor:pointer;
}
.s-btn:hover{
  background:var(--o500); color:#fff; border-color:var(--o500);
  transform:translateY(-3px) rotate(-6deg);
  box-shadow:var(--sh-or);
}

/* ════════ MAP ════════ */
.map-container{
  display:grid; grid-template-columns:1.55fr 1fr; gap:20px; margin-bottom:28px;
}
@media(max-width:900px){ .map-container{ grid-template-columns:1fr; } }

.map-card{
  border-radius:var(--r-xl); overflow:hidden;
  border:2px solid var(--border2); box-shadow:var(--sh-md);
  background:var(--white); line-height:0;
}
.map-card iframe{ width:100%; height:340px; border:none; display:block; }

.map-info{
  background:var(--white); padding:32px 28px;
  border-radius:var(--r-xl); border:2px solid var(--border2);
  box-shadow:var(--sh-md);
  display:flex; flex-direction:column; justify-content:center;
  position:relative; overflow:hidden;
}
.map-info::before{
  content:''; position:absolute; top:-30px; right:-30px;
  width:120px; height:120px; border-radius:50%;
  background:var(--o100); opacity:.7;
}
.map-info::after{
  content:''; position:absolute; bottom:-20px; left:-20px;
  width:80px; height:80px; border-radius:50%;
  background:var(--b100); opacity:.6;
}
.map-info-ttl{
  font-family:'Mitr',sans-serif; font-size:17px; font-weight:700;
  color:var(--o700); margin-bottom:10px;
  display:flex; align-items:center; gap:8px;
  position:relative; z-index:1;
}
.map-info-addr{
  font-size:14px; color:var(--ink2); line-height:1.75;
  margin-bottom:22px; position:relative; z-index:1;
}
.map-btn{
  display:inline-flex; align-items:center; justify-content:center; gap:8px;
  background:linear-gradient(135deg,var(--o700),var(--o500));
  color:#fff; text-decoration:none;
  padding:13px 26px; border-radius:99px;
  font-family:'Mitr',sans-serif; font-weight:700; font-size:14px;
  box-shadow:var(--sh-or); transition:.22s;
  position:relative; z-index:1; align-self:flex-start;
}
.map-btn:hover{ transform:translateY(-2px); box-shadow:0 12px 36px rgba(245,124,0,.32); }

/* ════════ BODY CARD ════════ */
.body-card{
  background:var(--white); border:2px solid var(--border2);
  border-radius:var(--r-xl); padding:40px 44px;
  margin-bottom:28px; box-shadow:var(--sh-md);
  position:relative; overflow:hidden;
}
.body-card::before{
  content:''; position:absolute; top:0; left:0; right:0; height:5px;
  background:linear-gradient(90deg,var(--b700),var(--b300),var(--o500),var(--o300));
}
.body-card-lbl{
  font-family:'Mitr',sans-serif; font-size:11px; font-weight:700;
  letter-spacing:.1em; text-transform:uppercase;
  color:var(--b700); margin-bottom:20px;
  display:flex; align-items:center; gap:8px;
}
.body-card-lbl i{ color:var(--o500); font-size:14px; }
.body-card-lbl::after{ content:''; flex:1; height:2px; border-radius:99px; background:linear-gradient(90deg,var(--b100),transparent); }
.body-text{
  font-size:1rem; color:var(--ink2); line-height:2.1; white-space:pre-line;
}
.body-text::first-letter{
  font-family:'Mitr',sans-serif; font-size:4rem; font-weight:700;
  color:var(--o700); float:left; line-height:.8; margin:6px 14px 0 0;
}

/* ════════ RELATED ════════ */
.rel-head{
  font-family:'Mitr',sans-serif; font-size:15px; font-weight:700;
  color:var(--b700); margin-bottom:16px;
  display:flex; align-items:center; gap:12px;
}
.rel-head i{ color:var(--o500); }
.rel-head::after{ content:''; flex:1; height:2px; border-radius:99px; background:linear-gradient(90deg,var(--b100),transparent); }

.rel-grid{ display:grid; grid-template-columns:repeat(4,1fr); gap:15px; }
.rel-card{
  background:var(--white); border:2px solid var(--border);
  border-radius:var(--r-lg); overflow:hidden;
  text-decoration:none; color:inherit; display:block;
  transition:.22s; box-shadow:var(--sh-sm);
}
.rel-card:hover{
  border-color:var(--o300);
  transform:translateY(-5px) rotate(.4deg);
  box-shadow:0 12px 36px rgba(255,152,0,.15);
}
.rel-thumb{
  aspect-ratio:16/9; background:var(--b100); overflow:hidden; position:relative;
}
.rel-thumb img{ width:100%; height:100%; object-fit:cover; display:block; transition:transform .4s ease; }
.rel-card:hover .rel-thumb img{ transform:scale(1.07); }
.rel-thumb-empty{
  width:100%; height:100%;
  display:flex; align-items:center; justify-content:center;
  color:var(--b300); font-size:1.8rem;
  background:linear-gradient(135deg,var(--b50),var(--b100));
}
.rel-body{ padding:12px 14px 15px; }
.rel-ev{ font-size:10px; color:var(--o700); font-weight:700; font-family:'Mitr',sans-serif; letter-spacing:.04em; text-transform:uppercase; margin-bottom:5px; }
.rel-title{ font-size:12.5px; font-weight:600; line-height:1.55; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; color:var(--ink); }
.rel-arrow{
  margin-top:9px; width:26px; height:26px; border-radius:50%;
  border:2px solid var(--b100); background:var(--b50);
  display:flex; align-items:center; justify-content:center;
  font-size:10px; color:var(--b700); transition:.18s;
}
.rel-card:hover .rel-arrow{ background:var(--o500); border-color:var(--o500); color:#fff; transform:rotate(-35deg); }

/* ════════ SCROLL REVEAL ════════ */
.reveal{ opacity:0; transform:translateY(28px); transition:opacity .55s ease, transform .55s ease; }
.reveal.in{ opacity:1; transform:none; }

/* ════════ RESPONSIVE ════════ */
@media(max-width:820px){
  .hero-layout{ grid-template-columns:1fr; min-height:unset; }
  .img-pane{ min-height:280px; }
  .info-pane{ padding:26px 22px; }
  .news-title{ font-size:1.35rem; }
}
@media(max-width:700px){
  .body-card{ padding:26px 22px; }
  .rel-grid{ grid-template-columns:1fr 1fr; }
}
@media(max-width:440px){ .rel-grid{ grid-template-columns:1fr; } }
</style>
</head>
<body>

<div id="rprog"></div>

<!-- ═══════════ DOLL CHARACTER ═══════════ -->
<div id="doll-wrap">
  <div id="doll-bubble"></div>
  <svg id="doll-svg" viewBox="0 0 92 150" xmlns="http://www.w3.org/2000/svg" width="92" height="150" aria-hidden="true">
    <!-- Shoes -->
    <ellipse cx="30" cy="143" rx="13" ry="7" fill="#0d47a1"/>
    <ellipse cx="62" cy="143" rx="13" ry="7" fill="#0d47a1"/>
    <!-- Legs -->
    <rect x="22" y="110" width="14" height="36" rx="7" fill="#90caf9"/>
    <rect x="56" y="110" width="14" height="36" rx="7" fill="#90caf9"/>
    <!-- Body -->
    <rect x="16" y="62" width="60" height="55" rx="22" fill="#ff9800"/>
    <!-- Shirt stripe -->
    <rect x="16" y="82" width="60" height="8" rx="0" fill="rgba(255,255,255,.18)"/>
    <!-- Collar -->
    <path d="M33 62 Q46 78 59 62" stroke="rgba(255,255,255,.45)" stroke-width="3" fill="none" stroke-linecap="round"/>
    <!-- Star on chest -->
    <text x="46" y="78" text-anchor="middle" font-size="14" fill="rgba(255,255,255,.85)">★</text>
    <!-- Left arm (holds book) -->
    <rect x="0" y="68" width="20" height="13" rx="6.5" fill="#ff9800" transform="rotate(-12,10,74)"/>
    <!-- Right arm (waving up) -->
    <rect x="72" y="52" width="20" height="13" rx="6.5" fill="#ff9800" transform="rotate(-42,82,58)"/>
    <!-- Hands -->
    <circle cx="4"  cy="79" r="8.5" fill="#ffcc80"/>
    <circle cx="88" cy="49" r="8.5" fill="#ffcc80"/>
    <!-- Book in left hand -->
    <rect x="-9" y="71" width="18" height="23" rx="3" fill="#1565c0" transform="rotate(-12,0,82)"/>
    <line x1="-9" y1="76" x2="8" y2="74" stroke="#fff" stroke-width="1.2" opacity=".5" transform="rotate(-12,0,82)"/>
    <line x1="-9" y1="81" x2="8" y2="79" stroke="#fff" stroke-width="1.2" opacity=".5" transform="rotate(-12,0,82)"/>
    <line x1="-9" y1="86" x2="8" y2="84" stroke="#fff" stroke-width="1.2" opacity=".5" transform="rotate(-12,0,82)"/>
    <!-- Neck -->
    <rect x="36" y="52" width="20" height="14" rx="8" fill="#ffcc80"/>
    <!-- Head -->
    <ellipse cx="46" cy="37" rx="30" ry="32" fill="#ffcc80"/>
    <!-- Hair -->
    <path d="M16 30 Q17 3 46 5 Q75 3 76 30" fill="#1565c0"/>
    <ellipse cx="17" cy="31" rx="10" ry="13" fill="#1565c0"/>
    <ellipse cx="75" cy="31" rx="10" ry="13" fill="#1565c0"/>
    <!-- Hair highlight -->
    <path d="M16 26 Q22 13 34 10" stroke="#42a5f5" stroke-width="2.5" fill="none" stroke-linecap="round" opacity=".7"/>
    <!-- Eyes white -->
    <ellipse cx="35" cy="35" rx="6.5" ry="7.5" fill="white"/>
    <ellipse cx="57" cy="35" rx="6.5" ry="7.5" fill="white"/>
    <!-- Pupils -->
    <circle cx="36" cy="36" r="4"   fill="#0d47a1"/>
    <circle cx="58" cy="36" r="4"   fill="#0d47a1"/>
    <!-- Eye shine -->
    <circle cx="37.5" cy="34.5" r="1.5" fill="white"/>
    <circle cx="59.5" cy="34.5" r="1.5" fill="white"/>
    <!-- Eyelashes -->
    <line x1="29" y1="28" x2="31" y2="26" stroke="#555" stroke-width="1.2" stroke-linecap="round"/>
    <line x1="33" y1="27" x2="35" y2="25" stroke="#555" stroke-width="1.2" stroke-linecap="round"/>
    <!-- Nose -->
    <ellipse cx="46" cy="43" rx="2.8" ry="2" fill="#ffb74d"/>
    <!-- Smile -->
    <path d="M36 50 Q46 59 56 50" stroke="#e65100" stroke-width="2.5" fill="none" stroke-linecap="round"/>
    <!-- Rosy cheeks -->
    <ellipse cx="27" cy="46" rx="6.5" ry="4.5" fill="#ffab91" opacity=".6"/>
    <ellipse cx="65" cy="46" rx="6.5" ry="4.5" fill="#ffab91" opacity=".6"/>
  </svg>
</div>

<?php include 'menu_volunteer.php'; ?>

<div class="page">

  <!-- Back -->
  <a href="all_news.php" class="back-pill">
    <span class="arr-circle"><i class="fas fa-arrow-left"></i></span>
    ข่าวประชาสัมพันธ์ทั้งหมด
  </a>

  <!-- ══ HERO ══ -->
  <div class="hero-layout reveal">

    <!-- Image pane -->
    <div class="img-pane">
      <?php if (!empty($images)): ?>
        <img src="<?= $images[0] ?>" id="heroImg" alt="">
        <div class="img-grad1"></div>
        <div class="img-grad2"></div>
        <span class="img-badge"><span class="bdot"></span><?= $is_open ? 'กำลังดำเนินการ' : 'สิ้นสุดแล้ว' ?></span>
        <?php if (count($images) > 1): ?>
        <div class="img-thumbs" id="trow">
          <?php foreach ($images as $i => $src): ?>
          <img src="<?= $src ?>" class="<?= $i===0?'on':'' ?>" onclick="setHero('<?= $src ?>',this)" alt="">
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="img-empty"><i class="fas fa-image"></i></div>
        <span class="img-badge"><span class="bdot"></span><?= $is_open ? 'กำลังดำเนินการ' : 'สิ้นสุดแล้ว' ?></span>
      <?php endif; ?>
    </div>

    <!-- Info pane -->
    <div class="info-pane">
      <div>
        <?php if (!empty($news['event_name'])): ?>
        <span class="event-chip"><i class="fas fa-star" style="font-size:10px"></i><?= htmlspecialchars($news['event_name']) ?></span>
        <?php endif; ?>

        <h1 class="news-title"><?= htmlspecialchars($news['title']) ?></h1>

        <div class="meta-list">
          <?php if ($display_date): ?>
          <div class="meta-item">
            <div class="meta-ico blue"><i class="fas fa-calendar-alt"></i></div>
            <div><div class="meta-lbl">วันที่จัดกิจกรรม</div><div class="meta-val"><?= $display_date ?></div></div>
          </div>
          <?php endif; ?>
          <?php if (!empty($news['Location'])): ?>
          <div class="meta-item">
            <div class="meta-ico orange"><i class="fas fa-map-marker-alt"></i></div>
            <div><div class="meta-lbl">สถานที่</div><div class="meta-val"><?= htmlspecialchars($news['Location']) ?></div></div>
          </div>
          <?php endif; ?>
          <div class="meta-item">
            <div class="meta-ico <?= $is_open ? 'green' : 'gray' ?>"><i class="fas fa-circle-dot"></i></div>
            <div>
              <div class="meta-lbl">สถานะ</div>
              <div style="margin-top:5px">
                <?php if ($is_open): ?>
                <span class="pill-open"><span class="pill-dot live"></span>กำลังดำเนินการ</span>
                <?php else: ?>
                <span class="pill-closed"><span class="pill-dot"></span>สิ้นสุดแล้ว</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="pane-share">
        <span class="share-lbl">แชร์</span>
        <a class="s-btn" href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>" target="_blank" title="Facebook"><i class="fab fa-facebook-f"></i></a>
        <a class="s-btn" href="https://line.me/R/msg/text/?<?= urlencode($news['title'].' http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>" target="_blank" title="LINE"><i class="fab fa-line"></i></a>
        <a class="s-btn" href="https://twitter.com/intent/tweet?url=<?= urlencode('http://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']) ?>" target="_blank" title="X"><i class="fab fa-x-twitter"></i></a>
        <a class="s-btn" href="#" onclick="copyLink(this);return false" title="คัดลอกลิงก์"><i class="fas fa-link"></i></a>
      </div>
    </div>
  </div>

  <!-- ══ MAP ══ -->
  <?php if (!empty($location_name)): ?>
  <div class="map-container reveal" style="transition-delay:.1s">
    <div class="map-card">
      <iframe src="<?= $map_url ?>" allowfullscreen loading="lazy"></iframe>
    </div>
    <div class="map-info">
      <div class="map-info-ttl"><i class="fa-solid fa-location-dot"></i>พิกัดสถานที่</div>
      <div class="map-info-addr"><?= htmlspecialchars($location_name) ?></div>
      <a href="https://www.google.com/maps/search/?api=1&query=<?= $encoded_location ?>" target="_blank" class="map-btn">
        เปิดใน Google Maps <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:11px"></i>
      </a>
    </div>
  </div>
  <?php endif; ?>

  <!-- ══ BODY ══ -->
  <div class="body-card reveal" style="transition-delay:.18s">
    <div class="body-card-lbl"><i class="fas fa-newspaper"></i>รายละเอียด</div>
    <div class="body-text"><?= htmlspecialchars($news['detail']) ?></div>
  </div>

  <!-- ══ RELATED ══ -->
  <?php if (!empty($related)): ?>
  <div class="reveal" style="transition-delay:.25s">
    <p class="rel-head"><i class="fas fa-layer-group"></i>ข่าวอื่นในกิจกรรมนี้</p>
    <div class="rel-grid">
      <?php foreach ($related as $rel):
        $rs = $conn->prepare("SELECT image_path FROM news_images WHERE news_id=? LIMIT 1");
        $rs->bind_param("i",$rel['news_id']); $rs->execute();
        $ri = $rs->get_result()->fetch_assoc();
        $rsrc = $ri ? 'img/'.$ri['image_path'] : null;
      ?>
      <a href="news_detail.php?id=<?= $rel['news_id'] ?>" class="rel-card">
        <div class="rel-thumb">
          <?php if ($rsrc): ?>
          <img src="<?= $rsrc ?>" alt="">
          <?php else: ?>
          <div class="rel-thumb-empty"><i class="fas fa-image"></i></div>
          <?php endif; ?>
        </div>
        <div class="rel-body">
          <div class="rel-ev"><?= htmlspecialchars($rel['event_name']??'') ?></div>
          <div class="rel-title"><?= htmlspecialchars($rel['title']) ?></div>
          <div class="rel-arrow"><i class="fas fa-arrow-right"></i></div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
/* ─── Image switch ─── */
function setHero(src, el){
  const img = document.getElementById('heroImg');
  img.style.transition = 'opacity .2s';
  img.style.opacity = '0';
  setTimeout(()=>{ img.src = src; img.style.opacity='1'; }, 200);
  document.querySelectorAll('#trow img').forEach(i=>i.classList.remove('on'));
  el.classList.add('on');
  burst(window.innerWidth - 60, window.innerHeight - 200, 4);
}

/* ─── Copy link ─── */
function copyLink(el){
  navigator.clipboard.writeText(location.href);
  el.innerHTML = '<i class="fas fa-check"></i>';
  Object.assign(el.style, {background:'#e8f5e9',color:'#2e7d32',borderColor:'#a5d6a7'});
  setTimeout(()=>{
    el.innerHTML='<i class="fas fa-link"></i>';
    el.style.background=el.style.color=el.style.borderColor='';
  }, 1800);
}

/* ─── Sparkle burst ─── */
const EMOJIS = ['⭐','🎉','💙','🧡','🌟','✨','🎊','💫','🎈','🎀','🌈','🤩'];
function burst(x, y, n){
  n = n || 5;
  for(let i=0;i<n;i++){
    setTimeout(()=>{
      const el = document.createElement('div');
      el.className = 'sparkle';
      el.textContent = EMOJIS[Math.floor(Math.random()*EMOJIS.length)];
      el.style.left = (x + (Math.random()-.5)*70) + 'px';
      el.style.top  = y + 'px';
      el.style.animationDelay = (Math.random()*.25)+'s';
      document.body.appendChild(el);
      setTimeout(()=>el.remove(), 2600);
    }, i*75);
  }
}

/* ─── Doll ─── */
const doll   = document.getElementById('doll-wrap');
const bubble = document.getElementById('doll-bubble');
let dollShown = false, lastMilestone = 0, msgIdx = 0;

const MSGS = ['สวัสดีจ้า! 👋','อ่านสนุกมั้ย? 📚','เก่งมากเลย! ⭐','ใกล้จบแล้ว! 🎉','ขอบคุณที่อ่านนะ 💙'];

function showBubble(txt){
  bubble.textContent = txt;
  bubble.classList.add('show');
  setTimeout(()=>bubble.classList.remove('show'), 2800);
}

function dollBurst(){
  const r = doll.getBoundingClientRect();
  burst(r.left + 46, r.top + 55, 6);
}

window.addEventListener('scroll', ()=>{
  const d = document.documentElement;
  const pct = d.scrollHeight-d.clientHeight > 0
    ? (d.scrollTop/(d.scrollHeight-d.clientHeight))*100 : 0;

  /* Progress */
  document.getElementById('rprog').style.width = pct + '%';

  /* Show doll */
  if(!dollShown && pct > 12){
    dollShown = true;
    doll.classList.add('show');
    setTimeout(()=>showBubble(MSGS[0]), 700);
  }

  /* Milestone burst every 20% */
  const ms = Math.floor(pct/20)*20;
  if(ms > lastMilestone && ms >= 20){
    lastMilestone = ms;
    dollBurst();
    msgIdx = Math.min(msgIdx+1, MSGS.length-1);
    showBubble(MSGS[msgIdx]);
  }
}, { passive:true });

/* ─── Scroll reveal ─── */
const obs = new IntersectionObserver(entries=>{
  entries.forEach(e=>{ if(e.isIntersecting) e.target.classList.add('in'); });
},{ threshold:.07 });
document.querySelectorAll('.reveal').forEach(el=>obs.observe(el));

/* ─── Idle sparkle every 8s ─── */
setInterval(()=>{ if(dollShown) dollBurst(); }, 8000);
</script>

<?php include 'footer.php'; ?>
</body>
</html>