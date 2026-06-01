<?php
require_once 'db.php';
$user = requireAuth();
if (!$user['onboarding_done']) { header('Location: onboarding.php'); exit; }

$db      = getDB();
$success = '';
$errors  = [];

// Decode JSON fields
$user['interests']           = json_decode($user['interests'] ?? '[]', true) ?: [];
$user['hobbies']             = json_decode($user['hobbies'] ?? '[]', true) ?: [];
$user['languages']           = json_decode($user['languages'] ?? '[]', true) ?: [];
$user['photos']              = json_decode($user['photos'] ?? '[]', true) ?: [];
$user['prompts']             = json_decode($user['prompts'] ?? '[]', true) ?: [];
$user['partner_preferences'] = json_decode($user['partner_preferences'] ?? '[]', true) ?: [];

$interestOptions = ['Travel','Photography','Music','Movies','Reading','Cooking','Sports','Art','Technology','Fashion','Fitness','Gaming','Dancing','Writing','Nature','Adventure','Comedy','History','Science','Politics','Business','Volunteering','Pets','Cars','Motorcycles','Cycling','Running','Swimming'];
$hobbyOptions    = ['Hiking','Swimming','Cycling','Yoga','Painting','Guitar','Piano','Chess','Basketball','Tennis','Running','Meditation','Gardening','Crafting','Singing','Dancing','Drawing','Writing','Cooking','Baking','Photography','Videography','Skateboarding','Surfing','Rock Climbing','Camping','Fishing'];
$languageOptions = ['English','Spanish','French','German','Italian','Portuguese','Russian','Chinese (Mandarin)','Chinese (Cantonese)','Japanese','Korean','Arabic','Hindi','Bengali','Tamil','Telugu','Marathi','Gujarati','Punjabi','Urdu','Thai','Vietnamese','Indonesian','Malay','Filipino','Dutch','Swedish','Norwegian','Danish','Finnish','Polish','Czech','Hungarian','Romanian','Bulgarian','Croatian','Serbian','Greek','Turkish','Hebrew','Persian','Swahili','Amharic'];
$religionOptions = ['Christian','Muslim','Hindu','Buddhist','Jewish','Sikh','Atheist','Agnostic','Other','Prefer not to say'];

// ---- Handle POST ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bio              = trim($_POST['bio'] ?? '');
    $profession       = trim($_POST['profession'] ?? '');
    $currentLocation  = trim($_POST['current_location'] ?? '');
    $nativePlace      = trim($_POST['native_place'] ?? '');
    $height           = trim($_POST['height'] ?? '');
    $religion         = trim($_POST['religion'] ?? '');
    $relationshipGoal = $_POST['relationship_goal'] ?? '';
    $drinkingHabits   = $_POST['drinking_habits'] ?? '';
    $smokingHabits    = $_POST['smoking_habits'] ?? '';
    $interests        = $_POST['interests'] ?? [];
    $hobbies          = $_POST['hobbies'] ?? [];
    $languages        = $_POST['languages'] ?? [];

    // Photo upload
    $photos = $user['photos'];
    if (!empty($_FILES['new_photo']['tmp_name'])) {
        $tmp  = $_FILES['new_photo']['tmp_name'];
        $ext  = strtolower(pathinfo($_FILES['new_photo']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg','jpeg','png','gif','webp'];
        if (in_array($ext, $allowed) && $_FILES['new_photo']['error'] === UPLOAD_ERR_OK) {
            $fname = 'uploads_photos/' . uniqid('u' . $user['id'] . '_') . '.' . $ext;
            if (move_uploaded_file($tmp, __DIR__ . '/' . $fname)) {
                array_unshift($photos, $fname); // Put new photo first
                $photos = array_slice($photos, 0, 6); // Max 6
            }
        }
    }

    $stmt = $db->prepare("UPDATE users SET
        bio=?, profession=?, current_location=?, native_place=?, height=?, religion=?,
        relationship_goal=?, drinking_habits=?, smoking_habits=?,
        interests=?, hobbies=?, languages=?, photos=?
        WHERE id=?");
    $stmt->execute([
        $bio, $profession, $currentLocation, $nativePlace, $height, $religion,
        $relationshipGoal, $drinkingHabits, $smokingHabits,
        json_encode($interests), json_encode($hobbies), json_encode($languages), json_encode($photos),
        $user['id']
    ]);

    // Refresh session
    $_SESSION['user'] = $db->query("SELECT * FROM users WHERE id={$user['id']}")->fetch();
    $user = $_SESSION['user'];
    $user['interests']           = json_decode($user['interests'] ?? '[]', true) ?: [];
    $user['hobbies']             = json_decode($user['hobbies'] ?? '[]', true) ?: [];
    $user['languages']           = json_decode($user['languages'] ?? '[]', true) ?: [];
    $user['photos']              = json_decode($user['photos'] ?? '[]', true) ?: [];
    $user['prompts']             = json_decode($user['prompts'] ?? '[]', true) ?: [];
    $user['partner_preferences'] = json_decode($user['partner_preferences'] ?? '[]', true) ?: [];
    $success = 'Profile updated successfully!';
}

// Count matches for stats
$matchCount = $db->query("SELECT COUNT(*) FROM matches WHERE user1_id={$user['id']} OR user2_id={$user['id']}")->fetchColumn();
$isEditing  = isset($_GET['edit']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LoveConnect – My Profile</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Header -->
<div class="app-header">
  <div class="app-header-row">
    <div class="logo"><span class="logo-heart">💗</span><span class="text-grad">LoveConnect</span></div>
    <div style="display:flex;align-items:center;gap:.5rem">
      <?php if ($isEditing): ?>
      <a href="profile.php" style="display:flex;align-items:center;gap:.35rem;padding:.5rem 1rem;color:#6b7280;font-size:.9rem">✕ Cancel</a>
      <?php else: ?>
      <a href="profile.php?edit=1" style="display:flex;align-items:center;gap:.35rem;padding:.5rem 1rem;color:#db2777;font-weight:500;font-size:.9rem">✏️ Edit Profile</a>
      <?php endif; ?>
      <a href="app.php" style="padding:.5rem 1rem;color:#6b7280;font-size:.9rem">← Back</a>
    </div>
  </div>
</div>

<div style="max-width:680px;margin:0 auto;padding:2rem 1.5rem 5rem">

  <?php if ($success): ?>
  <div class="alert alert-success mb-4"><?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" enctype="multipart/form-data">

    <!-- Profile Header Card -->
    <div class="section-card text-center mb-4">
      <div class="profile-photo-wrap">
        <?php $mainPhoto = $user['photos'][0] ?? null; ?>
        <?php if ($mainPhoto): ?>
        <img src="<?= htmlspecialchars($mainPhoto) ?>" class="profile-photo" alt="<?= htmlspecialchars($user['name']) ?>">
        <?php else: ?>
        <div class="profile-photo-placeholder"><?= strtoupper(substr($user['name'],0,1)) ?></div>
        <?php endif; ?>
        <?php if ($isEditing): ?>
        <label for="photoUpload" class="photo-edit-btn" title="Change photo">📷</label>
        <input type="file" name="new_photo" id="photoUpload" accept="image/*" style="display:none" onchange="previewPhoto(this)">
        <?php endif; ?>
        <?php if ($user['is_online']): ?>
        <div style="position:absolute;top:.25rem;right:.25rem;width:1.25rem;height:1.25rem;background:#4ade80;border-radius:9999px;border:3px solid #fff"></div>
        <?php endif; ?>
      </div>

      <h2 style="font-size:1.5rem;font-weight:700;margin-bottom:.35rem">
        <?= htmlspecialchars($user['name']) ?>, <?= (int)$user['age'] ?>
      </h2>

      <div style="margin-bottom:.5rem">
        <?php
        $badgeClass = match($user['is_verified']) {
          'verified'           => 'badge-verified',
          'partially-verified' => 'badge-partial',
          default              => 'badge-unverified',
        };
        $badgeIcon = match($user['is_verified']) {
          'verified'           => '✅',
          'partially-verified' => '🔵',
          default              => '○',
        };
        $badgeText = match($user['is_verified']) {
          'verified'           => 'Verified Profile',
          'partially-verified' => 'Partially Verified',
          default              => 'Unverified',
        };
        ?>
        <span class="badge <?= $badgeClass ?>"><?= $badgeIcon ?> <?= $badgeText ?></span>
      </div>

      <p class="text-gray"><?= htmlspecialchars($user['profession'] ?? '') ?></p>
      <p class="text-gray text-sm">📍 <?= htmlspecialchars($user['current_location'] ?? '') ?></p>

      <div class="profile-stats">
        <div><div class="profile-stat-val text-pink"><?= (int)$matchCount ?></div><div class="profile-stat-lbl">Matches</div></div>
        <div><div class="profile-stat-val text-purple">8</div><div class="profile-stat-lbl">Chats</div></div>
        <div><div class="profile-stat-val" style="color:#4f46e5">4.8</div><div class="profile-stat-lbl">Rating</div></div>
      </div>
    </div>

    <!-- About Me -->
    <div class="section-card mb-4">
      <h3 class="section-title">About Me</h3>
      <?php if ($isEditing): ?>
      <textarea name="bio" class="form-textarea" rows="5" maxlength="500" placeholder="Tell others about yourself..." id="bioArea" oninput="document.getElementById('bioCount').textContent=this.value.length+'/500'"><?= htmlspecialchars($user['bio'] ?? '') ?></textarea>
      <p class="char-count" id="bioCount"><?= strlen($user['bio'] ?? '') ?>/500</p>
      <?php else: ?>
      <p class="text-gray"><?= nl2br(htmlspecialchars($user['bio'] ?? 'No bio yet')) ?></p>
      <?php endif; ?>
    </div>

    <!-- Basic Info -->
    <div class="section-card mb-4">
      <h3 class="section-title">Basic Info</h3>
      <?php if ($isEditing): ?>
      <div class="two-col">
        <div class="form-group">
          <label class="form-label">Profession</label>
          <input type="text" name="profession" class="form-input no-icon" value="<?= htmlspecialchars($user['profession'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Height</label>
          <input type="text" name="height" class="form-input no-icon" placeholder='5\'8"' value="<?= htmlspecialchars($user['height'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Current Location</label>
          <input type="text" name="current_location" class="form-input no-icon" value="<?= htmlspecialchars($user['current_location'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Native Place</label>
          <input type="text" name="native_place" class="form-input no-icon" value="<?= htmlspecialchars($user['native_place'] ?? '') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Religion</label>
          <select name="religion" class="form-select">
            <option value="">Select...</option>
            <?php foreach ($religionOptions as $r): ?>
            <option value="<?= $r ?>" <?= ($user['religion'] ?? '') === $r ? 'selected' : '' ?>><?= $r ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Relationship Goal</label>
          <select name="relationship_goal" class="form-select">
            <?php foreach (['long-term'=>'Long-term','short-term'=>'Short-term','casual'=>'Casual','fun'=>'Fun'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($user['relationship_goal'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Drinking</label>
          <select name="drinking_habits" class="form-select">
            <?php foreach (['never'=>'Never','socially'=>'Socially','regularly'=>'Regularly','prefer-not-to-say'=>'Prefer not to say'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($user['drinking_habits'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Smoking</label>
          <select name="smoking_habits" class="form-select">
            <?php foreach (['never'=>'Never','socially'=>'Socially','regularly'=>'Regularly','prefer-not-to-say'=>'Prefer not to say'] as $v=>$l): ?>
            <option value="<?= $v ?>" <?= ($user['smoking_habits'] ?? '') === $v ? 'selected' : '' ?>><?= $l ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <?php else: ?>
      <div class="two-col">
        <?php foreach ([
          '💼 Profession'       => $user['profession'] ?? '',
          '📏 Height'          => $user['height'] ?? '',
          '📍 Location'        => $user['current_location'] ?? '',
          '🏠 Native Place'    => $user['native_place'] ?? '',
          '🛐 Religion'        => $user['religion'] ?? '',
          '💑 Looking for'     => $user['relationship_goal'] ?? '',
          '🍺 Drinking'        => $user['drinking_habits'] ?? '',
          '🚬 Smoking'         => $user['smoking_habits'] ?? '',
        ] as $label => $val): ?>
        <?php if ($val): ?>
        <div>
          <p class="text-xs text-gray"><?= $label ?></p>
          <p class="text-sm font-semibold"><?= htmlspecialchars(ucfirst($val)) ?></p>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Languages -->
    <div class="section-card mb-4">
      <h3 class="section-title">Languages</h3>
      <?php if ($isEditing): ?>
      <div class="tag-grid">
        <?php foreach ($languageOptions as $lang): ?>
        <label class="tag-btn <?= in_array($lang,$user['languages'])?'sel-indigo':'' ?>" style="cursor:pointer">
          <input type="checkbox" name="languages[]" value="<?= $lang ?>" <?= in_array($lang,$user['languages'])?'checked':'' ?> style="display:none" onchange="this.closest('.tag-btn').classList.toggle('sel-indigo',this.checked)">
          <?= htmlspecialchars($lang) ?>
        </label>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="tags-row">
        <?php foreach ($user['languages'] as $l): ?>
        <span class="tag tag-indigo" style="background:#e0e7ff;color:#3730a3"><?= htmlspecialchars($l) ?></span>
        <?php endforeach; ?>
        <?php if (empty($user['languages'])): ?><p class="text-gray text-sm">None specified</p><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Prompts -->
    <?php if (!empty($user['prompts'])): ?>
    <div class="section-card mb-4">
      <h3 class="section-title">My Prompts</h3>
      <?php foreach ($user['prompts'] as $p): ?>
      <div class="prompt-box mb-2">
        <p class="prompt-q"><?= htmlspecialchars($p['question']) ?></p>
        <p class="prompt-a"><?= htmlspecialchars($p['answer']) ?></p>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- Interests -->
    <div class="section-card mb-4">
      <h3 class="section-title">Interests</h3>
      <?php if ($isEditing): ?>
      <div class="tag-grid">
        <?php foreach ($interestOptions as $item): ?>
        <label class="tag-btn <?= in_array($item,$user['interests'])?'sel-pink':'' ?>" style="cursor:pointer">
          <input type="checkbox" name="interests[]" value="<?= $item ?>" <?= in_array($item,$user['interests'])?'checked':'' ?> style="display:none" onchange="this.closest('.tag-btn').classList.toggle('sel-pink',this.checked)">
          <?= htmlspecialchars($item) ?>
        </label>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="tags-row">
        <?php foreach ($user['interests'] as $int): ?>
        <span class="tag tag-pink"><?= htmlspecialchars($int) ?></span>
        <?php endforeach; ?>
        <?php if (empty($user['interests'])): ?><p class="text-gray text-sm">None selected</p><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Hobbies -->
    <div class="section-card mb-4">
      <h3 class="section-title">Hobbies</h3>
      <?php if ($isEditing): ?>
      <div class="tag-grid">
        <?php foreach ($hobbyOptions as $item): ?>
        <label class="tag-btn <?= in_array($item,$user['hobbies'])?'sel-purple':'' ?>" style="cursor:pointer">
          <input type="checkbox" name="hobbies[]" value="<?= $item ?>" <?= in_array($item,$user['hobbies'])?'checked':'' ?> style="display:none" onchange="this.closest('.tag-btn').classList.toggle('sel-purple',this.checked)">
          <?= htmlspecialchars($item) ?>
        </label>
        <?php endforeach; ?>
      </div>
      <?php else: ?>
      <div class="tags-row">
        <?php foreach ($user['hobbies'] as $h): ?>
        <span class="tag tag-purple"><?= htmlspecialchars($h) ?></span>
        <?php endforeach; ?>
        <?php if (empty($user['hobbies'])): ?><p class="text-gray text-sm">None selected</p><?php endif; ?>
      </div>
      <?php endif; ?>
    </div>

    <!-- Partner Preferences -->
    <div class="section-card mb-4">
      <h3 class="section-title">Looking For</h3>
      <div class="tags-row">
        <?php foreach ($user['partner_preferences'] as $pref): ?>
        <span class="tag" style="background:#e0e7ff;color:#3730a3"><?= htmlspecialchars($pref) ?></span>
        <?php endforeach; ?>
        <?php if (empty($user['partner_preferences'])): ?><p class="text-gray text-sm">None specified</p><?php endif; ?>
      </div>
    </div>

    <!-- Save Button -->
    <?php if ($isEditing): ?>
    <div class="section-card mb-4">
      <button type="submit" class="btn-grad">💾 Save Profile</button>
    </div>
    <?php endif; ?>

  </form>

  <!-- Settings (non-edit mode) -->
  <?php if (!$isEditing): ?>
  <div class="section-card mb-4">
    <h3 class="section-title">Settings</h3>
    <div class="settings-list">
      <a href="profile.php?edit=1" class="settings-item">
        <div class="settings-item-left">⚙️ <span>Account Settings</span></div>
        <span class="settings-arrow">›</span>
      </a>
      <a href="#" class="settings-item">
        <div class="settings-item-left">🔒 <span>Privacy Settings</span></div>
        <span class="settings-arrow">›</span>
      </a>
      <a href="api/logout.php" class="settings-item danger" onclick="return confirm('Sign out?')">
        <div class="settings-item-left">🚪 <span>Sign Out</span></div>
        <span class="settings-arrow" style="color:#fca5a5">›</span>
      </a>
    </div>
  </div>
  <?php endif; ?>

</div>

<script>
function previewPhoto(input) {
  if (input.files && input.files[0]) {
    const reader = new FileReader();
    reader.onload = e => {
      const photos = document.querySelectorAll('.profile-photo, .profile-photo-placeholder');
      photos.forEach(p => {
        if (p.tagName === 'IMG') p.src = e.target.result;
      });
    };
    reader.readAsDataURL(input.files[0]);
  }
}
</script>

</body>
</html>
