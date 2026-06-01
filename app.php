<?php
require_once 'db.php';
$user = requireAuth();
if (!$user['onboarding_done']) { header('Location: onboarding.php'); exit; }

$db   = getDB();
$view = $_GET['view'] ?? 'discover';

// Handle like action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['like_user_id'])) {
    $toId = (int)$_POST['like_user_id'];
    $fromId = (int)$user['id'];
    try {
        $db->prepare("INSERT IGNORE INTO likes (from_user_id, to_user_id) VALUES (?,?)")->execute([$fromId,$toId]);
        // Check mutual like
        $mutual = $db->prepare("SELECT id FROM likes WHERE from_user_id=? AND to_user_id=?");
        $mutual->execute([$toId,$fromId]);
        $isMatch = $mutual->fetch() !== false;
        if ($isMatch) {
            $chatId = 'chat_' . min($fromId,$toId) . '_' . max($fromId,$toId);
            $db->prepare("INSERT IGNORE INTO matches (user1_id,user2_id,chat_id) VALUES (?,?,?)")->execute([min($fromId,$toId),max($fromId,$toId),$chatId]);
            echo json_encode(['match'=>true,'chat_id'=>$chatId]);
        } else {
            echo json_encode(['match'=>false]);
        }
    } catch(Exception $e) {
        echo json_encode(['match'=>false]);
    }
    exit;
}

// Fetch users to discover (not already liked, not self, filtered by preference)
$lookingFor = $user['looking_for'] ?? 'both';
$genderWhere = '';
if ($lookingFor === 'male')   $genderWhere = "AND u.gender='male'";
if ($lookingFor === 'female') $genderWhere = "AND u.gender='female'";

$discoverSQL = "SELECT u.* FROM users u
    WHERE u.id != {$user['id']}
    AND u.onboarding_done = 1
    $genderWhere
    AND u.id NOT IN (SELECT to_user_id FROM likes WHERE from_user_id={$user['id']})
    ORDER BY RAND() LIMIT 20";
$discoverUsers = $db->query($discoverSQL)->fetchAll();

// Fetch matches
$matchesSQL = "SELECT m.*, 
    IF(m.user1_id={$user['id']}, m.user2_id, m.user1_id) AS other_user_id,
    m.chat_id
    FROM matches m
    WHERE m.user1_id={$user['id']} OR m.user2_id={$user['id']}
    ORDER BY m.created_at DESC";
$myMatches = $db->query($matchesSQL)->fetchAll();

// Get matched user details
$matchedUsers = [];
foreach ($myMatches as $m) {
    $u = $db->prepare("SELECT * FROM users WHERE id=?");
    $u->execute([$m['other_user_id']]);
    $matchedUsers[$m['chat_id']] = $u->fetch();
}

// Decode JSON fields
$user['interests'] = json_decode($user['interests'] ?? '[]', true) ?: [];
$user['photos']    = json_decode($user['photos'] ?? '[]', true) ?: [];

function decodeUser(array $u): array {
    $u['interests'] = json_decode($u['interests'] ?? '[]', true) ?: [];
    $u['hobbies']   = json_decode($u['hobbies'] ?? '[]', true) ?: [];
    $u['photos']    = json_decode($u['photos'] ?? '[]', true) ?: [];
    $u['prompts']   = json_decode($u['prompts'] ?? '[]', true) ?: [];
    return $u;
}

$currentDiscoverUser = !empty($discoverUsers) ? decodeUser($discoverUsers[0]) : null;
$remainingUsers      = array_slice($discoverUsers, 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LoveConnect – Discover</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<!-- Header -->
<div class="app-header">
  <div class="app-header-row">
    <div class="logo"><span class="logo-heart">💗</span><span class="text-grad">LoveConnect</span></div>
    <nav class="app-desk-nav">
      <button onclick="setView('discover')" class="<?= $view==='discover'?'active':'' ?>">Discover</button>
      <button onclick="setView('matches')"  class="<?= $view==='matches'?'active':'' ?>">Matches (<?= count($myMatches) ?>)</button>
      <button onclick="setView('profile')"  class="<?= $view==='profile'?'active':'' ?>">Profile</button>
      <button onclick="setView('help')"     class="<?= $view==='help'?'active':'' ?>">Help</button>
    </nav>
    <div style="display:flex;align-items:center;gap:.75rem">
      <span class="text-sm text-gray" style="display:none" id="welcomeMsg">Welcome, <?= htmlspecialchars($user['name']) ?>!</span>
      <a href="api/logout.php" style="font-size:.85rem;color:#9ca3af;padding:.4rem .875rem;border:1px solid #e5e7eb;border-radius:.5rem">Sign Out</a>
    </div>
  </div>
  <nav class="app-mob-nav">
    <button class="mob-nav-btn <?= $view==='discover'?'active':'' ?>" onclick="setView('discover')">👥<span>Discover</span></button>
    <button class="mob-nav-btn <?= $view==='matches'?'active':'' ?>"  onclick="setView('matches')">💗<span>Matches</span></button>
    <button class="mob-nav-btn <?= $view==='profile'?'active':'' ?>"  onclick="setView('profile')">👤<span>Profile</span></button>
    <button class="mob-nav-btn <?= $view==='help'?'active':'' ?>"     onclick="setView('help')">⚙️<span>Help</span></button>
  </nav>
</div>

<div class="app-content">

  <!-- ===== DISCOVER VIEW ===== -->
  <div id="view-discover" class="view-panel" style="<?= $view!=='discover'?'display:none':'' ?>">
    <?php if (!$currentDiscoverUser): ?>
    <div class="empty-state">
      <div class="icon">💗</div>
      <h3>No more profiles</h3>
      <p>Check back later for new matches!</p>
    </div>
    <?php else:
      $photo = $currentDiscoverUser['photos'][0] ?? 'https://images.pexels.com/photos/3310695/pexels-photo-3310695.jpeg';
      $firstPrompt = $currentDiscoverUser['prompts'][0] ?? null;
    ?>
    <div class="discover-wrap">
      <div class="card" id="discoverCard" data-user-id="<?= $currentDiscoverUser['id'] ?>">
        <div style="position:relative">
          <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($currentDiscoverUser['name']) ?>" class="profile-img-card" onclick="openProfileModal(<?= $currentDiscoverUser['id'] ?>)" style="border-radius:1.25rem 1.25rem 0 0">
          <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.55) 0%,transparent 55%);border-radius:1.25rem 1.25rem 0 0;pointer-events:none"></div>
          <?php if ($currentDiscoverUser['is_online']): ?>
          <div style="position:absolute;top:.875rem;right:.875rem;width:.875rem;height:.875rem;background:#4ade80;border-radius:9999px;border:2px solid #fff"></div>
          <?php endif; ?>
          <?php if ($currentDiscoverUser['is_verified'] !== 'unverified'): ?>
          <div style="position:absolute;top:.875rem;left:.875rem;font-size:1.1rem"><?= $currentDiscoverUser['is_verified']==='verified'?'✅':'🔵' ?></div>
          <?php endif; ?>
          <button onclick="openProfileModal(<?= $currentDiscoverUser['id'] ?>)" style="position:absolute;top:.875rem;right:2.5rem;background:rgba(255,255,255,.2);border:none;border-radius:9999px;width:2rem;height:2rem;color:#fff;cursor:pointer;font-size:.9rem">👁</button>
        </div>
        <div class="profile-card-info">
          <div class="profile-card-name-row">
            <span class="profile-card-name"><?= htmlspecialchars($currentDiscoverUser['name']) ?>, <?= (int)$currentDiscoverUser['age'] ?></span>
            <div class="rating"><span class="star">⭐</span> 4.8</div>
          </div>
          <div class="info-row">📍 <?= htmlspecialchars($currentDiscoverUser['current_location'] ?? '') ?></div>
          <div class="info-row">💼 <?= htmlspecialchars($currentDiscoverUser['profession'] ?? '') ?></div>
          <div class="info-row">📏 <?= htmlspecialchars($currentDiscoverUser['height'] ?? '') ?> · <?= htmlspecialchars($currentDiscoverUser['religion'] ?? '') ?></div>
          <div class="info-row">💑 Looking for <?= htmlspecialchars($currentDiscoverUser['relationship_goal'] ?? '') ?></div>

          <?php if ($firstPrompt): ?>
          <div class="prompt-box">
            <p class="prompt-q"><?= htmlspecialchars($firstPrompt['question']) ?></p>
            <p class="prompt-a"><?= htmlspecialchars($firstPrompt['answer']) ?></p>
          </div>
          <?php endif; ?>

          <div class="tags-row mt-2">
            <?php foreach (array_slice($currentDiscoverUser['interests'],0,3) as $int): ?>
            <span class="tag tag-pink"><?= htmlspecialchars($int) ?></span>
            <?php endforeach; ?>
          </div>

          <div class="action-btns">
            <button class="action-btn action-dislike" onclick="handleDislike()" title="Skip">✕</button>
            <button class="action-btn action-like"    onclick="handleLike()"    title="Like">💗</button>
          </div>
        </div>
      </div>
    </div>

    <!-- Queue of remaining discover users (hidden, used by JS) -->
    <script>
    const discoverQueue = <?= json_encode(array_map(function($u){ return decodeUser($u); }, $remainingUsers)) ?>;
    let queueIdx = 0;

    function handleLike() {
      const card = document.getElementById('discoverCard');
      const uid  = card.dataset.userId;
      fetch('app.php', {method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'like_user_id='+uid})
        .then(r=>r.json()).then(data => {
          if (data.match) showMatchModal(data.chat_id);
          nextCard();
        }).catch(()=>nextCard());
    }

    function handleDislike() { nextCard(); }

    function nextCard() {
      if (queueIdx >= discoverQueue.length) {
        document.getElementById('view-discover').innerHTML = `<div class="empty-state"><div class="icon">💗</div><h3>No more profiles</h3><p>Check back later!</p></div>`;
        return;
      }
      const u = discoverQueue[queueIdx++];
      const photo = (u.photos && u.photos[0]) ? u.photos[0] : 'https://images.pexels.com/photos/3310695/pexels-photo-3310695.jpeg';
      const prompt = u.prompts && u.prompts[0] ? `<div class="prompt-box"><p class="prompt-q">${e(u.prompts[0].question)}</p><p class="prompt-a">${e(u.prompts[0].answer)}</p></div>` : '';
      const tags = (u.interests||[]).slice(0,3).map(i=>`<span class="tag tag-pink">${e(i)}</span>`).join('');
      document.getElementById('discoverCard').dataset.userId = u.id;
      document.getElementById('discoverCard').innerHTML = `
        <div style="position:relative">
          <img src="${e(photo)}" class="profile-img-card" style="border-radius:1.25rem 1.25rem 0 0;cursor:pointer" onclick="openProfileModal(${u.id})">
          <div style="position:absolute;inset:0;background:linear-gradient(to top,rgba(0,0,0,.55),transparent 55%);border-radius:1.25rem 1.25rem 0 0;pointer-events:none"></div>
          ${u.is_online ? '<div style="position:absolute;top:.875rem;right:.875rem;width:.875rem;height:.875rem;background:#4ade80;border-radius:9999px;border:2px solid #fff"></div>':'' }
        </div>
        <div class="profile-card-info">
          <div class="profile-card-name-row"><span class="profile-card-name">${e(u.name)}, ${u.age}</span><div class="rating"><span class="star">⭐</span> 4.8</div></div>
          <div class="info-row">📍 ${e(u.current_location||'')}</div>
          <div class="info-row">💼 ${e(u.profession||'')}</div>
          ${prompt}
          <div class="tags-row mt-2">${tags}</div>
          <div class="action-btns">
            <button class="action-btn action-dislike" onclick="handleDislike()">✕</button>
            <button class="action-btn action-like"    onclick="handleLike()">💗</button>
          </div>
        </div>`;
    }
    function e(s){ const d=document.createElement('div');d.textContent=s||'';return d.innerHTML; }
    </script>
    <?php endif; ?>
  </div>

  <!-- ===== MATCHES VIEW ===== -->
  <div id="view-matches" class="view-panel" style="<?= $view!=='matches'?'display:none':'' ?>">
    <h2 class="section-title">Your Matches</h2>
    <?php if (empty($myMatches)): ?>
    <div class="empty-state"><div class="icon">💗</div><h3>No matches yet</h3><p>Start swiping to find your perfect match!</p></div>
    <?php else: ?>
    <div class="match-list">
      <?php foreach ($myMatches as $m):
        $mu = isset($matchedUsers[$m['chat_id']]) ? decodeUser($matchedUsers[$m['chat_id']]) : null;
        if (!$mu) continue;
        $mPhoto = $mu['photos'][0] ?? 'https://images.pexels.com/photos/3310695/pexels-photo-3310695.jpeg';
      ?>
      <a class="match-item" href="chat.php?chat_id=<?= urlencode($m['chat_id']) ?>">
        <div class="avatar-wrap">
          <img class="match-avatar" src="<?= htmlspecialchars($mPhoto) ?>" alt="<?= htmlspecialchars($mu['name']) ?>">
          <?php if ($mu['is_online']): ?><div class="online-indicator"></div><?php endif; ?>
        </div>
        <div class="match-info">
          <div class="match-name"><?= htmlspecialchars($mu['name']) ?> <?= $mu['is_verified']==='verified'?'✅':($mu['is_verified']==='partially-verified'?'🔵':'') ?></div>
          <div class="match-sub"><?= (int)$mu['age'] ?> · <?= htmlspecialchars($mu['profession'] ?? '') ?></div>
          <div class="match-last-msg" style="color:#9ca3af;font-size:.8rem"><?= htmlspecialchars($mu['current_location'] ?? '') ?></div>
        </div>
        <span style="color:#db2777;font-size:1.1rem">💬</span>
      </a>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- ===== PROFILE VIEW ===== -->
  <div id="view-profile" class="view-panel" style="<?= $view!=='profile'?'display:none':'' ?>">
    <div class="section-card">
      <div class="text-center mb-4">
        <div class="profile-photo-wrap">
          <?php $userPhoto = $user['photos'][0] ?? null; ?>
          <?php if ($userPhoto): ?>
          <img src="<?= htmlspecialchars($userPhoto) ?>" class="profile-photo" alt="<?= htmlspecialchars($user['name']) ?>">
          <?php else: ?>
          <div class="profile-photo-placeholder"><?= strtoupper(substr($user['name'],0,1)) ?></div>
          <?php endif; ?>
        </div>
        <h2 style="font-size:1.3rem;font-weight:700"><?= htmlspecialchars($user['name']) ?>, <?= (int)$user['age'] ?></h2>
        <p class="text-gray text-sm mt-1"><?= htmlspecialchars($user['profession'] ?? '') ?></p>
        <p class="text-gray text-sm"><?= htmlspecialchars($user['current_location'] ?? '') ?></p>
      </div>
      <div class="profile-stats">
        <div><div class="profile-stat-val text-pink"><?= count($myMatches) ?></div><div class="profile-stat-lbl">Matches</div></div>
        <div><div class="profile-stat-val text-purple">8</div><div class="profile-stat-lbl">Chats</div></div>
        <div><div class="profile-stat-val" style="color:#4f46e5">4.8</div><div class="profile-stat-lbl">Rating</div></div>
      </div>
    </div>
    <div class="section-card">
      <p class="section-title">About Me</p>
      <p class="text-gray"><?= htmlspecialchars($user['bio'] ?? 'No bio yet') ?></p>
    </div>
    <?php if (!empty($user['interests'])): ?>
    <div class="section-card">
      <p class="section-title">Interests</p>
      <div class="tags-row">
        <?php foreach ($user['interests'] as $int): ?>
        <span class="tag tag-pink"><?= htmlspecialchars($int) ?></span>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>
    <div class="section-card">
      <a href="profile.php" class="btn-grad" style="display:block;text-align:center">Edit Full Profile</a>
    </div>
  </div>

  <!-- ===== HELP VIEW ===== -->
  <div id="view-help" class="view-panel" style="<?= $view!=='help'?'display:none':'' ?>">
    <div class="section-card">
      <h3 style="font-size:1.2rem;font-weight:700;margin-bottom:1.25rem">Help & Support</h3>
      <?php foreach ([
        ['How to get more matches?','Complete your profile, add multiple photos, and be active. Verified profiles get 3× more matches!'],
        ['How does matching work?','When you like someone and they like you back, it\'s a match! You can then start chatting.'],
        ['Safety tips','Always meet in public places, trust your instincts, and report any suspicious behavior.'],
        ['Contact Support','Need help? Email us at support@loveconnect.com or use the in-app chat.'],
      ] as [$q,$a]): ?>
      <div style="border-bottom:1px solid #f3f4f6;padding:.875rem 0;margin-bottom:.25rem">
        <p style="font-weight:600;color:#374151;margin-bottom:.4rem"><?= $q ?></p>
        <p class="text-gray text-sm"><?= $a ?></p>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

</div>

<!-- Profile Modal -->
<div id="profileModal" class="modal-overlay" style="display:none">
  <div class="modal-box" id="profileModalContent"></div>
</div>

<!-- Match Modal -->
<div id="matchModal" class="modal-overlay" style="display:none">
  <div class="modal-box">
    <div class="match-modal">
      <div style="font-size:4rem">🎉</div>
      <h3>It's a Match!</h3>
      <p class="text-gray" id="matchModalText">You liked each other!</p>
      <div class="match-modal-btns">
        <button class="btn-keep" onclick="closeMatchModal()">Keep Swiping</button>
        <button class="btn-msg"  onclick="goToChat()">Send Message</button>
      </div>
    </div>
  </div>
</div>

<script>
let currentChatId = null;

function setView(v) {
  document.querySelectorAll('.view-panel').forEach(el=>el.style.display='none');
  document.getElementById('view-'+v).style.display='';
  document.querySelectorAll('.app-desk-nav button,.mob-nav-btn').forEach(btn=>btn.classList.remove('active'));
  document.querySelectorAll(`[onclick="setView('${v}')"]`).forEach(b=>b.classList.add('active'));
}

function showMatchModal(chatId) {
  currentChatId = chatId;
  document.getElementById('matchModal').style.display='flex';
}
function closeMatchModal() { document.getElementById('matchModal').style.display='none'; }
function goToChat() { if(currentChatId) location.href='chat.php?chat_id='+encodeURIComponent(currentChatId); }

function openProfileModal(userId) {
  fetch('api/get_user.php?id='+userId)
    .then(r=>r.json())
    .then(u=>{
      const photos = u.photos||[];
      const photo  = photos[0]||'https://images.pexels.com/photos/3310695/pexels-photo-3310695.jpeg';
      const interests = (u.interests||[]).map(i=>`<span class="tag tag-pink">${e(i)}</span>`).join('');
      const hobbies   = (u.hobbies||[]).map(h=>`<span class="tag tag-purple">${e(h)}</span>`).join('');
      const prompts   = (u.prompts||[]).map(p=>`<div class="prompt-box"><p class="prompt-q">${e(p.question)}</p><p class="prompt-a">${e(p.answer)}</p></div>`).join('');
      document.getElementById('profileModalContent').innerHTML = `
        <div class="modal-photo-wrap">
          <img src="${e(photo)}" class="modal-photo" id="modalPhoto">
          <button class="modal-close" onclick="closeProfileModal()">×</button>
          ${u.is_online?'<div style="position:absolute;top:.75rem;left:.75rem;width:.875rem;height:.875rem;background:#4ade80;border-radius:9999px;border:2px solid #fff"></div>':''}
        </div>
        <div class="modal-info">
          <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:.5rem">${e(u.name)}, ${u.age}</h3>
          <p class="text-gray text-sm mb-1">${e(u.profession||'')}</p>
          <p class="text-gray text-sm mb-1">📍 ${e(u.current_location||'')}</p>
          <p class="text-gray text-sm mb-1">${e(u.height||'')} · ${e(u.religion||'')}</p>
          <p class="text-gray text-sm mb-4">Looking for ${e(u.relationship_goal||'')}</p>
          <p class="text-gray mb-4">${e(u.bio||'')}</p>
          ${prompts}
          ${interests?`<h4 class="section-title mt-4">Interests</h4><div class="tags-row mb-3">${interests}</div>`:''}
          ${hobbies?`<h4 class="section-title">Hobbies</h4><div class="tags-row">${hobbies}</div>`:''}
        </div>`;
      document.getElementById('profileModal').style.display='flex';
    });
}
function closeProfileModal() { document.getElementById('profileModal').style.display='none'; }
document.getElementById('profileModal').addEventListener('click', function(e){if(e.target===this)closeProfileModal();});
document.getElementById('matchModal').addEventListener('click', function(e){if(e.target===this)closeMatchModal();});
function e(s){const d=document.createElement('div');d.textContent=s||'';return d.innerHTML;}
</script>
</body>
</html>
