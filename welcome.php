<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user'])) {
    header('Location: auth.php');
    exit;
}

if (isset($_GET['done'])) {
    $_SESSION['video_watched'] = true;
    header('Location: app.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>LoveConnect – Security Awareness</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  html, body {
    width: 100%;
    height: 100%;
    background: #000;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    font-family: 'Segoe UI', sans-serif;
    overflow: hidden;
  }
  video {
    width: 80%;
    max-width: 1000px;
    max-height: 80vh;
    border-radius: 1rem;
    box-shadow: 0 0 60px rgba(219,39,119,.25);
    display: block;
    background: #000;
  }
  /* Hide all video controls except play/pause */
  video::-webkit-media-controls-timeline { display: none !important; }
  video::-webkit-media-controls-fullscreen-button { display: none !important; }
  video::-webkit-media-controls-mute-button { display: none !important; }
  video::-webkit-media-controls-volume-slider { display: none !important; }
  video::-webkit-media-controls-overflow-button { display: none !important; }
</style>
</head>
<body>
<video
  id="v"
  autoplay
  playsinline
  controlsList="nodownload nofullscreen noremoteplayback"
  disablePictureInPicture
  oncontextmenu="return false"
>
  <source src="videos/demo_video.mp4" type="video/mp4">
</video>

<script>
  const v = document.getElementById('v');

  // Autoplay
  v.play().catch(() => console.log('Autoplay blocked'));

  // Block seeking forward — user cannot skip ahead
  let lastTime = 0;
  v.addEventListener('timeupdate', () => {
    if (v.currentTime > lastTime + 2) {
      v.currentTime = lastTime; // force back
    } else {
      lastTime = v.currentTime;
    }
  });

  // Block right click
  v.addEventListener('contextmenu', e => e.preventDefault());

  // When video ends → go to app
  v.addEventListener('ended', () => {
    window.location.href = 'welcome.php?done=1';
  });
</script>
</body>
</html>
