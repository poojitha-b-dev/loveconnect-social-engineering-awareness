<?php
// ============================================================
//  LoveConnect — Phishing Awareness Demonstration
//  File: onboarding.php
//  Author: [Your Name]
//  Purpose: Educational / Cybersecurity Awareness Only
// ============================================================
//
//  ⚠️ DISCLAIMER:
//  This file is part of a controlled cybersecurity awareness
//  demonstration. It simulates a realistic 10-step dating app
//  onboarding flow that secretly requests and exploits browser
//  permissions (camera, microphone, location).
//
//  This code is intentionally sanitized for public viewing.
//  The full working implementation is demonstrated in the
//  video proof of concept linked in the README.
//
// ============================================================
//
//  WHAT THIS FILE DEMONSTRATES:
//
//  STEP 0 — Location Access
//    Requests browser Geolocation permission.
//    Appears as a normal "find matches near you" feature.
//    Behind the scenes: exact GPS coordinates, IP address,
//    and device fingerprint are sent to save_video.php
//    and permanently logged to location.txt
//
//  STEP 1 — Basic Information
//    Collects: Date of Birth, Profession, Current Location.
//    Standard form fields that feel completely normal to
//    any dating app user.
//
//  STEP 2 — Personal Details
//    Collects: Gender, Looking For, Relationship Goal,
//    Religion. Builds a detailed personal profile.
//
//  STEP 3 — More About You
//    Collects: Drinking habits, Smoking habits, Languages.
//    Further deepens the personal data profile.
//
//  STEP 4 — Bio
//    User writes a personal bio (min 50 characters).
//    Collects personal thoughts and self-description.
//
//  STEP 5 — Interests
//    User selects 5+ interests from a grid.
//    Builds a psychological/behavioral profile.
//
//  STEP 6 — Hobbies
//    User selects 5+ hobbies.
//    Further behavioral profiling.
//
//  STEP 7 — Partner Preferences
//    User selects 5+ partner traits they desire.
//    Reveals personal values and relationship expectations.
//
//  STEP 8 — Prompts (Optional)
//    User answers personal questions like:
//    "My love language", "My ideal Sunday", etc.
//    Reveals intimate personal details voluntarily.
//
//  STEP 9 — Photos & Verification (KEY STEP)
//    Requests CAMERA permission for "profile verification".
//    Behind the scenes:
//      - getUserMedia API activates webcam silently
//      - Snapshots captured every few seconds
//      - Images base64 encoded and sent to save_video.php
//      - Saved permanently to snapshots/ folder on server
//
//    Requests MICROPHONE permission for "voice verification".
//    Behind the scenes:
//      - MediaRecorder API begins recording ambient audio
//      - Audio blob uploaded silently to save_video.php
//      - Saved permanently to uploads/audio/ on server
//
// ============================================================
//
//  SOCIAL ENGINEERING TECHNIQUE USED:
//
//  Each permission request is framed as a legitimate
//  dating app feature:
//    📍 Location → "Find matches near you"
//    📷 Camera   → "Verify your profile photo"
//    🎤 Mic      → "Voice verify your identity"
//
//  Users willingly grant all permissions because the
//  interface looks professional and trustworthy.
//  This is the core of social engineering —
//  exploiting TRUST, not technical vulnerabilities.
//
// ============================================================
//
//  KEY CYBERSECURITY LESSONS:
//
//  ✅ Always check the URL before granting permissions
//  ✅ Legitimate apps explain WHY they need each permission
//  ✅ A beautiful UI does NOT mean a safe website
//  ✅ Once you click Allow — data capture is instant
//  ✅ Camera/mic can be active even when not visible
//
// ============================================================
//
//  📹 Full working demo: https://www.youtube.com/xyz
//  📁 GitHub Repo: https://github.com/yourname/loveconnect
//
// ============================================================

// [Full 10-step onboarding implementation with permission
//  capture hooks redacted for responsible public disclosure]
?>
