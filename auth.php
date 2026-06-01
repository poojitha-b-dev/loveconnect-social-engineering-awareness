<?php
require_once 'db.php';
sessionStart();
if (currentUser()) { header('Location: app.php'); exit; }

$tab    = $_GET['tab'] ?? 'signup'; // 'login' or 'signup'
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $db     = getDB();

    // ===== LOGIN =====
    if ($action === 'login') {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$email) $errors['email'] = 'Email is required';
        if (!$password) $errors['password'] = 'Password is required';

        if (empty($errors)) {
            $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            // For demo accounts inserted via SQL, allow any password
            $demoEmails = ['emma@example.com','michael@example.com','sarah@example.com','david@example.com','jessica@example.com'];
            $validPass  = $user && (
                password_verify($password, $user['password_hash']) ||
                in_array($email, $demoEmails) // demo shortcut
            );
            if ($validPass) {
                $_SESSION['user'] = $user;
                // Mark online
                $db->prepare("UPDATE users SET is_online=1 WHERE id=?")->execute([$user['id']]);
                header('Location: ' . ($user['onboarding_done'] ? 'app.php' : 'onboarding.php'));
                exit;
            } else {
                $errors['general'] = 'Invalid email or password.';
            }
        }
        $tab = 'login';
    }

    // ===== SIGNUP =====
    if ($action === 'signup') {
        $name     = trim($_POST['name'] ?? '');
        $email    = trim($_POST['email'] ?? '');
        $phone    = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';

        if (!$name)    $errors['name']    = 'Name is required';
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required';
        if (!$phone)   $errors['phone']   = 'Phone number is required';
        if (strlen($password) < 6) $errors['password'] = 'Password must be at least 6 characters';
        if ($password !== $confirm) $errors['confirm_password'] = 'Passwords do not match';

        if (empty($errors)) {
            // Check duplicate email
            $stmt = $db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $errors['email'] = 'Email already registered. Please sign in.';
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $ins  = $db->prepare("INSERT INTO users (name,email,phone,password_hash,is_online) VALUES (?,?,?,?,1)");
                $ins->execute([$name, $email, $phone, $hash]);
                $userId = $db->lastInsertId();
                $user   = $db->prepare("SELECT * FROM users WHERE id=?")->execute([$userId]) && true;
                $user   = $db->query("SELECT * FROM users WHERE id=$userId")->fetch();
                $_SESSION['user'] = $user;
                header('Location: onboarding.php');
                exit;
            }
        }
        $tab = 'signup';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LoveConnect – <?= $tab === 'login' ? 'Sign In' : 'Create Account' ?></title>
<link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-box">
    <!-- Header -->
    <div class="auth-header">
      <a href="index.php" class="auth-back">←</a>
      <div style="font-size:3rem;margin-bottom:.25rem">💗</div>
      <h1>LoveConnect</h1>
      <p><?= $tab === 'login' ? 'Welcome back!' : 'Join the community' ?></p>
    </div>

    <!-- Body -->
    <div class="auth-body">
      <div class="auth-tabs">
        <button class="auth-tab <?= $tab==='signup'?'active':'' ?>" onclick="switchTab('signup')">Sign Up</button>
        <button class="auth-tab <?= $tab==='login'?'active':'' ?>" onclick="switchTab('login')">Sign In</button>
      </div>

      <?php if (!empty($errors['general'])): ?>
        <div class="alert alert-error"><?= htmlspecialchars($errors['general']) ?></div>
      <?php endif; ?>

      <!-- SIGNUP FORM -->
      <form id="signupForm" method="POST" style="<?= $tab==='signup'?'':'display:none' ?>">
        <input type="hidden" name="action" value="signup">

        <div class="form-group">
          <label class="form-label">Full Name <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">👤</span>
            <input type="text" name="name" class="form-input <?= isset($errors['name'])?'error':'' ?>"
              placeholder="Enter your full name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
          </div>
          <?php if (isset($errors['name'])): ?><p class="err-msg"><?= $errors['name'] ?></p><?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Email Address <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">✉️</span>
            <input type="email" name="email" class="form-input <?= isset($errors['email'])?'error':'' ?>"
              placeholder="Enter your email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <?php if (isset($errors['email'])): ?><p class="err-msg"><?= $errors['email'] ?></p><?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Phone Number <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">📞</span>
            <input type="tel" name="phone" class="form-input <?= isset($errors['phone'])?'error':'' ?>"
              placeholder="Enter your phone number" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>">
          </div>
          <?php if (isset($errors['phone'])): ?><p class="err-msg"><?= $errors['phone'] ?></p><?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Password <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" name="password" id="pw1" class="form-input has-right <?= isset($errors['password'])?'error':'' ?>"
              placeholder="Enter your password">
            <button type="button" class="toggle-pw" onclick="togglePw('pw1',this)">👁️</button>
          </div>
          <?php if (isset($errors['password'])): ?><p class="err-msg"><?= $errors['password'] ?></p><?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm Password <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" name="confirm_password" id="pw2" class="form-input has-right <?= isset($errors['confirm_password'])?'error':'' ?>"
              placeholder="Confirm your password">
            <button type="button" class="toggle-pw" onclick="togglePw('pw2',this)">👁️</button>
          </div>
          <?php if (isset($errors['confirm_password'])): ?><p class="err-msg"><?= $errors['confirm_password'] ?></p><?php endif; ?>
        </div>

        <button type="submit" class="btn-grad">Create Account</button>
      </form>

      <!-- LOGIN FORM -->
      <form id="loginForm" method="POST" style="<?= $tab==='login'?'':'display:none' ?>">
        <input type="hidden" name="action" value="login">

        <div class="form-group">
          <label class="form-label">Email Address <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">✉️</span>
            <input type="email" name="email" class="form-input <?= isset($errors['email'])?'error':'' ?>"
              placeholder="Enter your email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
          </div>
          <?php if (isset($errors['email'])): ?><p class="err-msg"><?= $errors['email'] ?></p><?php endif; ?>
        </div>

        <div class="form-group">
          <label class="form-label">Password <span class="req">*</span></label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input type="password" name="password" id="lpw" class="form-input has-right <?= isset($errors['password'])?'error':'' ?>"
              placeholder="Enter your password">
            <button type="button" class="toggle-pw" onclick="togglePw('lpw',this)">👁️</button>
          </div>
          <?php if (isset($errors['password'])): ?><p class="err-msg"><?= $errors['password'] ?></p><?php endif; ?>
        </div>

        <button type="submit" class="btn-grad">Sign In</button>
        <p class="form-hint text-center mt-2">
          <small>Demo: use any email from the showcase above with any password</small>
        </p>
      </form>

      <div class="auth-switch" id="switchHint">
        <?php if ($tab === 'signup'): ?>
          Already have an account? <a href="#" onclick="switchTab('login');return false">Sign In</a>
        <?php else: ?>
          Don't have an account? <a href="#" onclick="switchTab('signup');return false">Sign Up</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
function switchTab(tab) {
  document.getElementById('signupForm').style.display = tab === 'signup' ? '' : 'none';
  document.getElementById('loginForm').style.display  = tab === 'login'  ? '' : 'none';
  document.querySelectorAll('.auth-tab').forEach((el,i) => {
    el.classList.toggle('active', (i===0 && tab==='signup') || (i===1 && tab==='login'));
  });
  document.getElementById('switchHint').innerHTML = tab === 'signup'
    ? 'Already have an account? <a href="#" onclick="switchTab(\'login\');return false">Sign In</a>'
    : 'Don\'t have an account? <a href="#" onclick="switchTab(\'signup\');return false">Sign Up</a>';
}
function togglePw(id, btn) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? '👁️' : '🙈';
}
</script>
</body>
</html>
