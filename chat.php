<?php
require_once 'db.php';
$user = requireAuth();
if (!$user['onboarding_done']) { header('Location: onboarding.php'); exit; }

$db     = getDB();
$chatId = $_GET['chat_id'] ?? null;

// Handle send message (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    $content = trim($_POST['content'] ?? '');
    $cid     = $_POST['chat_id'] ?? '';
    if ($content && $cid) {
        // Verify user belongs to this chat
        $check = $db->prepare("SELECT id FROM matches WHERE chat_id=? AND (user1_id=? OR user2_id=?)");
        $check->execute([$cid, $user['id'], $user['id']]);
        if ($check->fetch()) {
            $db->prepare("INSERT INTO messages (chat_id, sender_id, content) VALUES (?,?,?)")
               ->execute([$cid, $user['id'], $content]);
            echo json_encode(['ok'=>true,'time'=>date('H:i')]);
        } else {
            echo json_encode(['ok'=>false]);
        }
    }
    exit;
}

// Poll new messages (AJAX)
if (isset($_GET['poll'])) {
    $cid      = $_GET['chat_id'] ?? '';
    $afterId  = (int)($_GET['after_id'] ?? 0);
    $check    = $db->prepare("SELECT id FROM matches WHERE chat_id=? AND (user1_id=? OR user2_id=?)");
    $check->execute([$cid, $user['id'], $user['id']]);
    if ($check->fetch()) {
        $msgs = $db->prepare("SELECT * FROM messages WHERE chat_id=? AND id>? ORDER BY id ASC");
        $msgs->execute([$cid, $afterId]);
        echo json_encode($msgs->fetchAll());
    } else {
        echo json_encode([]);
    }
    exit;
}

// Fetch all chats/matches
$matchesSQL = "SELECT m.*,
    IF(m.user1_id=?, m.user2_id, m.user1_id) AS other_user_id
    FROM matches m
    WHERE m.user1_id=? OR m.user2_id=?
    ORDER BY m.created_at DESC";
$stmt = $db->prepare($matchesSQL);
$stmt->execute([$user['id'], $user['id'], $user['id']]);
$myMatches = $stmt->fetchAll();

// Get other user for each match + last message
$matchData = [];
foreach ($myMatches as $m) {
    $ou = $db->prepare("SELECT * FROM users WHERE id=?");
    $ou->execute([$m['other_user_id']]);
    $otherUser = $ou->fetch();
    if (!$otherUser) continue;
    $otherUser['photos'] = json_decode($otherUser['photos'] ?? '[]', true) ?: [];

    $lastMsg = $db->prepare("SELECT * FROM messages WHERE chat_id=? ORDER BY id DESC LIMIT 1");
    $lastMsg->execute([$m['chat_id']]);
    $last = $lastMsg->fetch();

    $matchData[] = [
        'chat_id'    => $m['chat_id'],
        'other_user' => $otherUser,
        'last_msg'   => $last,
    ];
}

// If a specific chat is open, load it
$activeChatUser = null;
$messages       = [];
$lastMsgId      = 0;

if ($chatId) {
    // Verify this chat belongs to current user
    $check = $db->prepare("SELECT *, IF(user1_id=?, user2_id, user1_id) AS other_user_id FROM matches WHERE chat_id=? AND (user1_id=? OR user2_id=?)");
    $check->execute([$user['id'], $chatId, $user['id'], $user['id']]);
    $chatMatch = $check->fetch();
    if ($chatMatch) {
        $ouStmt = $db->prepare("SELECT * FROM users WHERE id=?");
        $ouStmt->execute([$chatMatch['other_user_id']]);
        $activeChatUser = $ouStmt->fetch();
        if ($activeChatUser) {
            $activeChatUser['photos']  = json_decode($activeChatUser['photos'] ?? '[]', true) ?: [];
            $activeChatUser['prompts'] = json_decode($activeChatUser['prompts'] ?? '[]', true) ?: [];
            $activeChatUser['interests'] = json_decode($activeChatUser['interests'] ?? '[]', true) ?: [];
            $activeChatUser['hobbies']   = json_decode($activeChatUser['hobbies'] ?? '[]', true) ?: [];
        }
        $msgStmt = $db->prepare("SELECT * FROM messages WHERE chat_id=? ORDER BY id ASC");
        $msgStmt->execute([$chatId]);
        $messages  = $msgStmt->fetchAll();
        $lastMsgId = !empty($messages) ? (int)end($messages)['id'] : 0;
    } else {
        // Invalid chat id for this user
        $chatId = null;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LoveConnect – Messages</title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php if ($chatId && $activeChatUser): ?>
<!-- ===== ACTIVE CHAT VIEW ===== -->
<div class="chat-wrap">
  <!-- Header -->
  <div class="chat-header">
    <a href="chat.php" class="chat-back">←</a>
    <div style="display:flex;align-items:center;gap:.75rem;flex:1">
      <div style="position:relative">
        <?php $ouPhoto = $activeChatUser['photos'][0] ?? 'https://images.pexels.com/photos/3310695/pexels-photo-3310695.jpeg'; ?>
        <img src="<?= htmlspecialchars($ouPhoto) ?>" class="chat-avatar" alt="<?= htmlspecialchars($activeChatUser['name']) ?>" onclick="openProfileModal()">
        <?php if ($activeChatUser['is_online']): ?>
        <div style="position:absolute;bottom:-2px;right:-2px;width:.875rem;height:.875rem;background:#4ade80;border-radius:9999px;border:2px solid #fff"></div>
        <?php endif; ?>
      </div>
      <div>
        <div style="font-weight:600;font-size:1rem"><?= htmlspecialchars($activeChatUser['name']) ?> <?= $activeChatUser['is_verified']==='verified'?'✅':($activeChatUser['is_verified']==='partially-verified'?'🔵':'') ?></div>
        <div class="text-xs text-gray"><?= $activeChatUser['is_online'] ? '🟢 Online' : 'Offline' ?></div>
      </div>
    </div>
    <button onclick="openProfileModal()" style="background:none;border:none;font-size:1.2rem;cursor:pointer;color:#6b7280" title="View profile">⋮</button>
  </div>

  <!-- Messages -->
  <div class="chat-messages" id="chatMessages">
    <?php if (empty($messages)): ?>
    <div style="text-align:center;padding:3rem 1rem;color:#9ca3af">
      <div style="font-size:3rem;margin-bottom:.75rem">💗</div>
      <p style="font-weight:600;color:#6b7280;margin-bottom:.4rem">You matched with <?= htmlspecialchars($activeChatUser['name']) ?>!</p>
      <p class="text-sm">Start the conversation and get to know each other.</p>
    </div>
    <?php else: ?>
    <?php foreach ($messages as $msg): ?>
    <div class="msg <?= $msg['sender_id'] == $user['id'] ? 'mine' : 'theirs' ?>">
      <div class="bubble <?= $msg['sender_id'] == $user['id'] ? 'bubble-mine' : 'bubble-theirs' ?>">
        <?= htmlspecialchars($msg['content']) ?>
        <div class="bubble-time"><?= date('H:i', strtotime($msg['created_at'])) ?></div>
      </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <!-- Input -->
  <div class="chat-input-bar">
    <input type="text" id="msgInput" class="chat-input"
      placeholder="Message <?= htmlspecialchars($activeChatUser['name']) ?>..."
      onkeydown="if(event.key==='Enter'&&!event.shiftKey){sendMsg();event.preventDefault();}">
    <button class="send-btn" id="sendBtn" onclick="sendMsg()" disabled>➤</button>
  </div>
</div>

<!-- Profile Modal -->
<div id="profileModal" class="modal-overlay" style="display:none" onclick="if(event.target===this)closeModal()">
  <div class="modal-box">
    <div class="modal-photo-wrap">
      <?php
        $photos = $activeChatUser['photos'];
        $pCount = count($photos);
        $firstPhoto = $photos[0] ?? 'https://images.pexels.com/photos/3310695/pexels-photo-3310695.jpeg';
      ?>
      <img src="<?= htmlspecialchars($firstPhoto) ?>" class="modal-photo" id="modalImg">
      <?php if ($pCount > 1): ?>
      <button class="photo-nav prev" onclick="changePhoto(-1)">‹</button>
      <button class="photo-nav next" onclick="changePhoto(1)">›</button>
      <div class="photo-dots">
        <?php for ($i=0;$i<$pCount;$i++): ?>
        <div class="photo-dot <?= $i===0?'active':'' ?>" id="dot<?= $i ?>"></div>
        <?php endfor; ?>
      </div>
      <?php endif; ?>
      <button class="modal-close" onclick="closeModal()">×</button>
      <?php if ($activeChatUser['is_online']): ?>
      <div style="position:absolute;top:.75rem;left:.75rem;width:.875rem;height:.875rem;background:#4ade80;border-radius:9999px;border:2px solid #fff"></div>
      <?php endif; ?>
    </div>
    <div class="modal-info">
      <h3 style="font-size:1.5rem;font-weight:700;margin-bottom:.5rem">
        <?= htmlspecialchars($activeChatUser['name']) ?>, <?= (int)$activeChatUser['age'] ?>
        <?= $activeChatUser['is_verified']==='verified'?'✅':($activeChatUser['is_verified']==='partially-verified'?'🔵':'') ?>
      </h3>
      <p class="text-gray text-sm mb-1">💼 <?= htmlspecialchars($activeChatUser['profession'] ?? '') ?></p>
      <p class="text-gray text-sm mb-1">📍 <?= htmlspecialchars($activeChatUser['current_location'] ?? '') ?></p>
      <p class="text-gray text-sm mb-1">📏 <?= htmlspecialchars($activeChatUser['height'] ?? '') ?> · <?= htmlspecialchars($activeChatUser['religion'] ?? '') ?></p>
      <p class="text-gray text-sm mb-4">💑 Looking for <?= htmlspecialchars($activeChatUser['relationship_goal'] ?? '') ?></p>
      <p class="text-gray mb-4"><?= htmlspecialchars($activeChatUser['bio'] ?? '') ?></p>

      <?php if (!empty($activeChatUser['prompts'])): ?>
      <div class="mb-4">
        <?php foreach ($activeChatUser['prompts'] as $p): ?>
        <div class="prompt-box mb-2">
          <p class="prompt-q"><?= htmlspecialchars($p['question']) ?></p>
          <p class="prompt-a"><?= htmlspecialchars($p['answer']) ?></p>
        </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($activeChatUser['interests'])): ?>
      <h4 class="section-title">Interests</h4>
      <div class="tags-row mb-4">
        <?php foreach ($activeChatUser['interests'] as $int): ?>
        <span class="tag tag-pink"><?= htmlspecialchars($int) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <?php if (!empty($activeChatUser['hobbies'])): ?>
      <h4 class="section-title">Hobbies</h4>
      <div class="tags-row">
        <?php foreach ($activeChatUser['hobbies'] as $h): ?>
        <span class="tag tag-purple"><?= htmlspecialchars($h) ?></span>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
// Photo gallery
const photos = <?= json_encode($photos) ?>;
let photoIdx = 0;
function changePhoto(dir) {
  photoIdx = (photoIdx + dir + photos.length) % photos.length;
  document.getElementById('modalImg').src = photos[photoIdx];
  document.querySelectorAll('.photo-dot').forEach((d,i)=>d.classList.toggle('active',i===photoIdx));
}
function openProfileModal() { document.getElementById('profileModal').style.display='flex'; }
function closeModal()       { document.getElementById('profileModal').style.display='none'; }

// Messaging
const chatId     = <?= json_encode($chatId) ?>;
const myUserId   = <?= (int)$user['id'] ?>;
let   lastMsgId  = <?= $lastMsgId ?>;

const input   = document.getElementById('msgInput');
const sendBtn = document.getElementById('sendBtn');
const msgArea = document.getElementById('chatMessages');

input.addEventListener('input', () => sendBtn.disabled = !input.value.trim());

function sendMsg() {
  const content = input.value.trim();
  if (!content) return;
  input.value = '';
  sendBtn.disabled = true;

  // Optimistic render
  appendMsg({sender_id: myUserId, content, created_at: new Date().toISOString()});

  fetch('chat.php', {
    method: 'POST',
    headers: {'Content-Type':'application/x-www-form-urlencoded'},
    body: `chat_id=${encodeURIComponent(chatId)}&content=${encodeURIComponent(content)}`
  }).then(r=>r.json()).then(d=>{
    if (d.ok) pollMessages();
  });
}

function appendMsg(msg) {
  const isMine = msg.sender_id == myUserId;
  const time   = new Date(msg.created_at).toLocaleTimeString([],{hour:'2-digit',minute:'2-digit'});
  const div    = document.createElement('div');
  div.className= 'msg ' + (isMine?'mine':'theirs');
  div.innerHTML= `<div class="bubble ${isMine?'bubble-mine':'bubble-theirs'}">${esc(msg.content)}<div class="bubble-time">${time}</div></div>`;
  msgArea.appendChild(div);
  msgArea.scrollTop = msgArea.scrollHeight;
}

function pollMessages() {
  fetch(`chat.php?poll=1&chat_id=${encodeURIComponent(chatId)}&after_id=${lastMsgId}`)
    .then(r=>r.json())
    .then(msgs => {
      msgs.forEach(m => {
        if (m.sender_id != myUserId) appendMsg(m); // don't re-add own
        lastMsgId = Math.max(lastMsgId, m.id);
      });
    });
}

function esc(s) {
  const d = document.createElement('div');
  d.textContent = s;
  return d.innerHTML;
}

// Auto-scroll to bottom
msgArea.scrollTop = msgArea.scrollHeight;

// Poll every 3 seconds
setInterval(pollMessages, 3000);
</script>

<?php else: ?>
<!-- ===== CHAT LIST VIEW ===== -->
<div class="app-header">
  <div class="app-header-row">
    <div class="logo"><span class="logo-heart">💗</span><span class="text-grad">LoveConnect</span></div>
    <div style="display:flex;gap:.5rem">
      <a href="app.php" style="padding:.5rem 1rem;border-radius:.5rem;color:#6b7280;font-size:.95rem">← Discover</a>
      <a href="api/logout.php" style="font-size:.85rem;color:#9ca3af;padding:.4rem .875rem;border:1px solid #e5e7eb;border-radius:.5rem">Sign Out</a>
    </div>
  </div>
</div>

<div style="max-width:640px;margin:0 auto;padding:2rem 1.5rem 5rem">
  <h2 class="section-title">Your Conversations</h2>
  <?php if (empty($matchData)): ?>
  <div class="empty-state">
    <div class="icon">💗</div>
    <h3>No conversations yet</h3>
    <p>Start matching to begin conversations!</p>
  </div>
  <?php else: ?>
  <div class="match-list">
    <?php foreach ($matchData as $md):
      $ou     = $md['other_user'];
      $ouPhoto = $ou['photos'][0] ?? 'https://images.pexels.com/photos/3310695/pexels-photo-3310695.jpeg';
      $last   = $md['last_msg'];
    ?>
    <a class="match-item" href="chat.php?chat_id=<?= urlencode($md['chat_id']) ?>">
      <div class="avatar-wrap">
        <img class="match-avatar" src="<?= htmlspecialchars($ouPhoto) ?>" alt="<?= htmlspecialchars($ou['name']) ?>">
        <?php if ($ou['is_online']): ?><div class="online-indicator"></div><?php endif; ?>
      </div>
      <div class="match-info">
        <div class="match-name"><?= htmlspecialchars($ou['name']) ?> <?= $ou['is_verified']==='verified'?'✅':($ou['is_verified']==='partially-verified'?'🔵':'') ?></div>
        <div class="match-last-msg"><?= $last ? htmlspecialchars($last['content']) : 'Start a conversation...' ?></div>
      </div>
      <div class="text-xs text-gray"><?= $last ? date('H:i', strtotime($last['created_at'])) : '' ?></div>
    </a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php endif; ?>

</body>
</html>
