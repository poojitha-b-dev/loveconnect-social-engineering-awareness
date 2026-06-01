<?php
require_once 'db.php';
sessionStart();
if (currentUser()) { header('Location: app.php'); exit; }

$db = getDB();

// Showcase: Priya (woman) + Kavya (woman) + Arjun (man)
$showcaseUsers = $db->query("
    SELECT * FROM users
    WHERE email IN ('priya@example.com','kavya@example.com','arjun@example.com')
    AND onboarding_done=1
    ORDER BY FIELD(email,'priya@example.com','kavya@example.com','arjun@example.com')
")->fetchAll();

// Popup rotates between the remaining 2: Ananya + Rohan
$popupUsers = $db->query("
    SELECT * FROM users
    WHERE email IN ('ananya@example.com','rohan@example.com')
    AND onboarding_done=1
    ORDER BY FIELD(email,'ananya@example.com','rohan@example.com')
")->fetchAll();

// Build popup data for JS
$popupData = [];
foreach ($popupUsers as $pu) {
    $photos = json_decode($pu['photos'] ?? '[]', true);
    $popupData[] = [
        'name'   => $pu['name'],
        'age'    => (int)$pu['age'],
        'photo'  => $photos[0] ?? '',
        'online' => (bool)$pu['is_online'],
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LoveConnect – Find Your Perfect Match</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Header -->
<header class="header">
  <div class="logo">
    <span class="logo-heart">💗</span>
    <span class="text-grad">LoveConnect</span>
  </div>
  <nav class="header-nav">
    <a href="auth.php?tab=login">Sign In</a>
    <a href="auth.php?tab=signup" class="nav-btn-pink">Get Started</a>
  </nav>
</header>

<!-- Hero -->
<section class="hero">
  <h2>Find Your<span class="text-grad"> Perfect Match</span></h2>
  <p>Connect with amazing people near you. Start meaningful conversations and build lasting relationships.</p>
  <a href="auth.php?tab=signup" class="btn btn-primary" style="border-radius:9999px;padding:.875rem 2.25rem;font-size:1.1rem;">
    Get Started →
  </a>
</section>

<!-- Features -->
<div class="features">
  <div class="card card-p feature-card">
    <div class="feature-icon pink">👥</div>
    <h3>Smart Matching</h3>
    <p>Our advanced algorithm finds compatible matches based on your preferences, interests, and lifestyle.</p>
  </div>
  <div class="card card-p feature-card">
    <div class="feature-icon purple">💬</div>
    <h3>Safe Messaging</h3>
    <p>Connect safely with built-in privacy controls and secure messaging features.</p>
  </div>
  <div class="card card-p feature-card">
    <div class="feature-icon indigo">✨</div>
    <h3>Real Connections</h3>
    <p>Move beyond superficial swipes to build meaningful relationships that last.</p>
  </div>
</div>

<!-- Showcase -->
<div class="showcase">
  <h3>Meet Amazing People</h3>
  <div class="showcase-grid">
    <?php foreach ($showcaseUsers as $u):
      $photos = json_decode($u['photos'] ?? '[]', true);
      $photo  = $photos[0] ?? '';
    ?>
    <div class="showcase-card">
      <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($u['name']) ?>">
      <div class="showcase-overlay">
        <div class="showcase-info">
          <h4>
            <?= htmlspecialchars($u['name']) ?>, <?= (int)$u['age'] ?>
            <?php if ($u['is_verified'] === 'verified'): ?>
              <span style="background:#3b82f6;border-radius:9999px;padding:.1rem .3rem;font-size:.7rem;color:#fff;">✓</span>
            <?php endif; ?>
          </h4>
          <p class="sub"><?= htmlspecialchars($u['profession'] ?? '') ?></p>
          <p class="loc">📍 <?= htmlspecialchars($u['current_location'] ?? '') ?></p>
        </div>
      </div>
      <?php if ($u['is_online']): ?><div class="online-dot-abs"></div><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- Stats -->
<div class="stats-section">
  <div class="card card-p">
    <div class="stats-inner">
      <div>
        <div class="stat-num text-grad">10,000+</div>
        <div class="stat-label">Active Members</div>
      </div>
      <div>
        <div class="stat-num text-grad">5,000+</div>
        <div class="stat-label">Successful Matches</div>
      </div>
      <div>
        <div class="stat-num text-grad">98%</div>
        <div class="stat-label">Satisfaction Rate</div>
      </div>
    </div>
  </div>
</div>

<!-- Popup — rotates between Ananya & Rohan every 5 seconds -->
<?php if (!empty($popupData)): ?>
<div class="side-popup" id="sidePopup" style="display:none">
  <div class="side-popup-inner">
    <button class="side-popup-close" onclick="document.getElementById('sidePopup').style.display='none'">×</button>
    <div style="font-size:1.5rem;margin-bottom:.25rem">💕</div>
    <p class="popup-title" id="popupTitle">Someone is waiting for you!</p>
    <img class="popup-avatar" id="popupAvatar" src="" alt="">
    <p id="popupName" style="font-size:.8rem;font-weight:600;margin:.3rem 0;color:#fff"></p>
    <button class="popup-btn" onclick="location.href='auth.php?tab=signup'">Join Now</button>
  </div>
</div>

<script>
const popupProfiles = <?= json_encode($popupData) ?>;
let popupIdx = 0;

function updatePopup() {
  const p = popupProfiles[popupIdx];
  document.getElementById('popupAvatar').src = p.photo;
  document.getElementById('popupName').textContent  = p.name + ', ' + p.age;
  popupIdx = (popupIdx + 1) % popupProfiles.length;
}

// Show popup after 3 seconds
setTimeout(() => {
  updatePopup(); // set first profile
  document.getElementById('sidePopup').style.display = 'block';

  // Rotate every 5 seconds
  setInterval(updatePopup, 5000);
}, 3000);
</script>
<?php endif; ?>

</body>
</html>
