<?php 
session_start();
include('connect.php'); // เชื่อมต่อฐานข้อมูล (เปลี่ยนชื่อไฟล์ตามที่คุณใช้จริง)

// ดึงข้อมูลผู้ใช้ถ้ามีการ Login ไว้
$user_data = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $query = "SELECT * FROM users WHERE id = '$user_id'";
    $result = mysqli_query($conn, $query);
    $user_data = mysqli_fetch_assoc($result);
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โครงการเพื่อเด็กบนภู เพื่อนครูบนดอย - สิ่งของบริจาค</title>
    <link href="https://fonts.googleapis.com/css2?family=Anuphan:wght@200;300;400;500;600;700&family=Kanit:wght@300;400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="style-menu.css">
    <link rel="stylesheet" href="index.css">
    <style>
        /* Consolidated Styles from donation.html.php */
        :root {
          --primary: #3a5a40;
          --primary-light: #588157;
          --primary-soft: #f1f5f2;
          --accent-warm: #bc8a5f;
          --secondary: #bc8a5f;
          --white: #ffffff;
          --bg-soft: #f4f7f6;
          --text-dark: #1e272e;
          --text-muted: #576574;
          --shadow-premium: 0 20px 50px -12px rgba(26, 58, 58, 0.08);
          --shadow-hover: 0 40px 80px -15px rgba(26, 58, 58, 0.15);
          --transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
          --radius-lg: 40px;
          --radius-md: 24px;
          --primary-gradient: linear-gradient(135deg, #274a83 0%, #1a1b66 100%);
          --dark-blue: #1e293b;
        }

        body {
          font-family: 'Anuphan', -apple-system, BlinkMacSystemFont, sans-serif;
          background-color: var(--bg-soft);
          color: var(--text-dark);
          margin: 0;
          padding: 0;
          line-height: 1.6;
        }

        .donation-content {
          max-width: 1240px;
          margin: 0 auto;
          padding: 60px 24px;
        }

        .donation-hero {
          text-align: center;
          padding: 100px 40px;
          background: radial-gradient(circle at top right, rgba(188,138,95,0.1), transparent), 
                      radial-gradient(circle at bottom left, rgba(45,90,67,0.15), transparent),
                      var(--white);
          border-radius: var(--radius-lg);
          margin-bottom: 100px;
          box-shadow: var(--shadow-premium);
          border: 1px solid rgba(255,255,255,0.8);
        }

        .hero-sub {
          color: var(--secondary);
          text-transform: uppercase;
          letter-spacing: 0.3em;
          font-weight: 700;
          font-size: 0.85rem;
          margin-bottom: 24px;
          display: block;
        }

        .donation-hero h1 {
          font-size: clamp(2.2rem, 5.5vw, 3.5rem);
          color: var(--primary);
          line-height: 1.3;
          font-weight: 800;
          margin-bottom: 30px;
        }

        .hero-accent {
          font-size: 1.1rem;
          color: var(--text-muted);
          max-width: 600px;
          margin: 0 auto;
          line-height: 1.7;
        }

        .section-header {
          margin-bottom: 50px;
          border-left: 6px solid var(--secondary);
          padding-left: 24px;
        }

        .section-header h2 {
          font-size: 2rem;
          color: var(--primary);
          font-weight: 800;
          margin-bottom: 8px;
          line-height: 1.4;
        }

        .section-desc {
          color: var(--text-muted);
          font-size: 1.05rem;
          line-height: 1.6;
        }

        .donation-section { 
          margin-bottom: 80px; 
        }

        .card-grid {
          display: grid;
          grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
          gap: 25px;
          max-width: 1200px;
          margin: 0 auto;
        }

        .card-grid.grid-center {
          justify-content: center;
          gap: 60px;
          grid-template-columns: repeat(auto-fit, minmax(320px, 420px));
          max-width: 1000px;
        }

        .donation-card {
          background: var(--white);
          padding: 40px;
          border-radius: var(--radius-md);
          border: 1px solid rgba(58, 90, 64, 0.05);
          box-shadow: 0 10px 30px rgba(0, 0, 0, 0.02);
          transition: var(--transition);
          display: flex;
          flex-direction: column;
        }

        .donation-card:hover {
          transform: translateY(-8px);
          box-shadow: 0 20px 40px rgba(58, 90, 64, 0.08);
          border-color: var(--primary-light);
        }

        .donation-card h3 {
          font-size: 1.4rem;
          color: var(--primary);
          margin-bottom: 12px;
          font-weight: 800;
          text-align: center;
        }

        .donation-card h3::after {
          content: "";
          display: block;
          width: 40px;
          height: 3px;
          background: var(--accent-warm);
          margin: 8px auto 0;
          border-radius: 10px;
        }

        .card-desc {
          font-size: 0.95rem;
          color: var(--text-muted);
          margin-bottom: 25px;
          line-height: 1.6;
        }

        .donation-card ul {
          list-style: none;
          padding: 0;
          margin-bottom: 30px;
        }

        .donation-card ul li {
          padding: 10px 0;
          border-bottom: 1px solid rgba(58, 90, 64, 0.05);
          display: flex;
          align-items: center;
          font-weight: 600;
          font-size: 0.9rem;
          color: var(--text-dark);
        }

        .donation-card ul li::before {
          content: "";
          width: 6px;
          height: 6px;
          background: var(--accent-warm);
          border-radius: 50%;
          margin-right: 12px;
          flex-shrink: 0;
        }

        .card-note {
          font-size: 0.85rem;
          color: var(--accent-warm);
          font-weight: 500;
          margin-top: auto;
          display: flex;
          align-items: center;
          gap: 6px;
        }

        .donation-note {
          background: #fff;
          border-radius: 20px;
          padding: 40px;
          margin: 40px auto;
          max-width: 1100px;
          border: 1px solid rgba(235, 77, 75, 0.1);
          text-align: center;
          box-shadow: 0 10px 30px rgba(0, 0, 0, 0.03);
        }

        .donation-note h3 {
          color: var(--text-dark);
          margin-bottom: 15px;
        }

        .donation-note ul {
          list-style: none;
          padding: 0;
          display: flex;
          flex-wrap: wrap;
          justify-content: center;
          gap: 15px;
        }

        .donation-note ul li {
          background: #fdf2f2;
          padding: 10px 20px;
          border-radius: 12px;
          color: #e03131;
          font-weight: 600;
          font-size: 0.9rem;
        }

        .donation-cta {
          padding: 60px 30px;
          background: linear-gradient(to bottom, var(--primary-soft), var(--white));
          border-radius: var(--radius-md);
          text-align: center;
          margin-top: 60px;
        }

        .btn-primary {
          background: var(--primary);
          color: white !important;
          padding: 16px 40px;
          border-radius: 12px;
          text-decoration: none;
          font-weight: 600;
          display: inline-block;
          transition: var(--transition);
        }

        .btn-primary:hover {
          background: var(--primary-light);
          transform: translateY(-3px);
        }

        /* Footer Styles */
        .modern-footer {
            background: #fff;
            position: relative;
            padding-top: 80px;
            font-family: 'Anuphan', sans-serif;
            border-top: 1px solid #eee;
        }

        .container-balanced {
            max-width: 1200px; 
            margin: 0 auto;
            padding: 0 40px 40px;
        }

        .footer-header-center {
            text-align: center;
            margin-bottom: 40px;
        }

        .brand-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
        }

        .brand-title span {
            display: block;
            font-size: 1.1rem;
            font-weight: 300;
            color: var(--text-muted);
        }

        .footer-content-spread {
            display: flex;
            justify-content: space-between;
            border-top: 1px solid #eee;
            padding-top: 40px;
        }

        .horizontal-links {
            list-style: none;
            padding: 0;
            display: flex;
            gap: 20px;
        }

        .horizontal-links a {
            text-decoration: none;
            color: var(--text-muted);
        }

        .contact-spread {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-end;
        }
    </style>
</head>
<body>
<?php include 'menu_volunteer.php'; ?>
<main class="donation-content">
  <section class="donation-hero">
    <span class="hero-sub">Small Acts, Big Impacts</span>
    <h1>สิ่งของเล็กๆ ของคุณ<br>คือโอกาสที่ยิ่งใหญ่</h1>
    <p class="hero-accent">
      ร่วมส่งต่อคุณภาพชีวิตที่ดีขึ้น ผ่านการแบ่งปันสิ่งของจำเป็นให้กับชุมชนบนดอย
    </p>
  </section>

  <section class="donation-section">
    <header class="section-header">
      <h2>หมวดบริโภค</h2>
      <p class="section-desc">
        "ส่งต่อความอิ่มท้องและโภชนาการที่ดี เพื่อคุณภาพชีวิตที่สมบูรณ์ของครอบครัวในพื้นที่ขาดแคลน"
      </p>
    </header>

   <div class="card-grid grid-center">
      <article class="donation-card">
        <h3>ข้าวสาร & อาหารแห้ง</h3>
        <p class="card-desc">
          อาหารหลักที่สามารถเก็บรักษาได้นานและนำไปประกอบอาหารได้หลากหลาย
        </p>
        <ul>
          <li>ข้าวสาร / เส้นหมี่แห้ง</li>
          <li>ปลากระป๋อง / บะหมี่กึ่งสำเร็จรูป</li>
          <li>เครื่องปรุงรส / น้ำมันพืช</li>
        </ul>
      </article>

      <article class="donation-card">
        <h3>ขนม & นมสำหรับเด็ก</h3>
        <p class="card-desc">
          เติมพลังงานและสารอาหารที่จำเป็นให้เด็กๆ ในช่วงวัยเรียน
        </p>
        <ul>
          <li>นมกล่อง</li>
          <li>ขนมซอง / เยลลี่</li>
          <li>ธัญพืช / นมถั่วเหลือง</li>
        </ul>
        <p class="card-note">*โปรดตรวจสอบวันหมดอายุก่อนส่งมอบ*</p>
      </article>
    </div>
  </section>

  <section class="donation-section">
    <header class="section-header">
      <h2>หมวดอุปโภค</h2>
      <p class="section-desc">
"ดูแลสุขภาวะคนในชุมชนด้วยสิ่งของอุปโภคพื้นฐานที่สะอาด ปลอดภัย และจำเป็นต่อการดำเนินชีวิต"      </p>
    </header>

    <div class="card-grid">
      <article class="donation-card">
        <h3>ยาสามัญประจำบ้าน</h3>
        <p class="card-desc">
          สำหรับการดูแลสุขภาพเบื้องต้นในพื้นที่ห่างไกลและการเข้าถึงสถานพยาบาล
        </p>
        <ul>
          <li>พาราเซตามอล / ยาแก้แพ้</li>
          <li>ยาแก้ท้องเสีย / ยาถ่ายพยาธิ</li>
          <li>ยาลดกรด / ยาแก้ไอ</li>
          <li>ชุดทำแผล</li>
        </ul>
      </article>

      <article class="donation-card">
        <h3>เสื้อผ้า & รองเท้า</h3>
        <p class="card-desc">
          เสื้อผ้าที่ช่วยสร้างความอบอุ่นและลดภาระค่าใช้จ่ายของครอบครัว
        </p>
        <ul>
          <li>ชุดนักเรียน / รองเท้านักเรียน</li>
          <li>เสื้อกันหนาว / กางเกงขายาว</li>
          <li>ผ้าห่ม / ถุงเท้า</li>
        </ul>
        <p class="card-note">*ขอเป็นสภาพดี สะอาด และพร้อมใช้งาน</p>
      </article>

      <article class="donation-card">
        <h3>อุปกรณ์การเรียน & กีฬา</h3>
        <p class="card-desc">
          ส่งเสริมการเรียนรู้ พัฒนาทักษะ และเสริมสร้างสุขภาพให้กับเด็กๆ
        </p>
        <ul>
          <li>สมุด ดินสอ ปากกา และเครื่องเขียน</li>
          <li>หนังสืออ่านเสริม / หนังสือนิทาน</li>
          <li>ลูกฟุตบอล และอุปกรณ์กีฬา</li>
        </ul>
      </article>

      <article class="donation-card">
        <h3>ของเล่น & ตุ๊กตา</h3>
        <p class="card-desc">
          ของเล่นที่ช่วยเสริมพัฒนาการและสร้างรอยยิ้มให้เด็กๆ
        </p>
        <ul>
          <li>ของเล่นเสริมพัฒนาการ</li>
          <li>เกมฝึกทักษะ / ตัวต่อ</li>
          <li>ตุ๊กตามือสองสภาพดี</li>
        </ul>
      </article>

      <article class="donation-card">
        <h3>ของใช้ส่วนตัว</h3>
        <p class="card-desc">
          สิ่งของจำเป็นที่ช่วยดูแลสุขอนามัยและคุณภาพชีวิตของทุกคนในครอบครัว
        </p>
        <ul>
          <li>ผ้าอนามัย</li>
          <li>ผงซักฟอก / น้ำยาล้างจาน</li>
          <li>สบู่ ยาสีฟัน และแปรงสีฟัน</li>
        </ul>
      </article>

      <article class="donation-card">
        <h3>อุปกรณ์ทำความสะอาด</h3>
        <p class="card-desc">
          เพื่อดูแลพื้นที่ส่วนรวม โรงเรียน และที่อยู่อาศัยให้สะอาดปลอดภัย
        </p>
        <ul>
          <li>ไม้กวาด / ไม้ถูพื้น</li>
          <li>แปรงขัดพื้น / ถังน้ำ</li>
          <li>น้ำยาทำความสะอาดและฆ่าเชื้อ</li>
        </ul>
      </article>
    </div>
  </section>

  <aside class="donation-note">
    <h3>สิ่งของที่ขอความกรุณางดรับบริจาค</h3>
    <p>เพื่อความเหมาะสมและความปลอดภัยของผู้รับ โครงการขอสงวนสิทธิ์ไม่รับสิ่งของดังต่อไปนี้</p>
    <ul>
      <li>ของใช้อยู่ในสภาพชำรุดเสียหาย</li>
      <li>อาหารที่หมดอายุ หรือใกล้หมดอายุ</li>
      <li>เครื่องนุ่งห่มที่ชำรุดหรือเผยให้เห็นสรีระเกินสมควร</li>
    </ul>
  </aside>

  <section class="donation-cta">
    <p class="cta-text">
  ร่วมสร้างสังคมแห่งการแบ่งปันและส่งต่อความดีงามไปด้วยกัน<br>
  <strong>ทุกการส่งต่อจากคุณ คือก้าวสำคัญสู่การเปลี่ยนแปลงที่มีความหมาย</strong><br>
  <?php if (!isset($_SESSION['user_id'])): ?>
  <span style="display: block; margin-top: 10px; font-size: 0.9em; color: #555;">
    กรุณาสมัครสมาชิกเพื่อร่วมบริจาค
  </span>
<?php endif; ?>

<div class="cta-actions" style="margin-top: 30px;">
<?php if (isset($_SESSION['user_id'])): ?>
    <a href="donate.php" class="btn-primary">ร่วมบริจาคตอนนี้</a>
<?php else: ?>
    <a href="register.php" class="btn-primary">สมัครสมาชิกเพื่อร่วมบริจาค</a>
<?php endif; ?>
</div>

</section>
</main>

<footer class="modern-footer">
    <div class="container-balanced">
        <div class="footer-header-center">
            <h2 class="brand-title">เพื่อเด็กบนภู<span>เพื่อนครูบนดอย</span></h2>
            <p class="brand-subtitle">ร่วมเป็นส่วนหนึ่งในการสร้างโอกาสทางการศึกษา และพัฒนาคุณภาพชีวิตเด็กในพื้นที่ห่างไกล</p>
        </div>

        <div class="footer-content-spread">
            <div class="footer-col">
                <ul class="horizontal-links">
                    <li><a href="index.php">หน้าแรก</a></li>
                    <li><a href="donation.html.php">บริจาคสิ่งของ</a></li>
                    <li><a href="events.php">กิจกรรมอาสา</a></li>
                </ul>
            </div>

            <div class="footer-col">
                <div class="contact-spread">
                    <span><i class="fas fa-phone-alt"></i> 094 997 8287</span>
                    <span><i class="fas fa-map-marker-alt"></i> 75/91 หมู่ 11 ต. คลองหนึ่ง อ.คลองหลวง จ.ปทุมธานี 12120</span>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 40px; color: #94a3b8; font-size: 0.8rem;">
            <p>&copy; 2026 เพื่อเด็กบนภู เพื่อนครูบนดอย. All Rights Reserved.</p>
        </div>
    </div>
</footer>

</body>
</html>