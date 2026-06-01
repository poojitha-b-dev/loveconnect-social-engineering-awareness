<?php
// ===== config.php =====
// Database configuration (SQLite for simplicity, swap to MySQL as needed)
define('DB_PATH', __DIR__ . '/../db/loveconnect.db');
define('SITE_NAME', 'LoveConnect');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', '/uploads/');

// Session start
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ===== Helper functions =====

function db(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dir = dirname(DB_PATH);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        initDb($pdo);
    }
    return $pdo;
}

function initDb(PDO $pdo): void {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT UNIQUE NOT NULL,
            phone TEXT,
            password_hash TEXT NOT NULL,
            date_of_birth TEXT,
            age INTEGER DEFAULT 0,
            profession TEXT,
            gender TEXT DEFAULT 'other',
            looking_for TEXT DEFAULT 'both',
            native_place TEXT,
            current_location TEXT,
            height TEXT,
            religion TEXT,
            relationship_goal TEXT DEFAULT 'long-term',
            drinking_habits TEXT DEFAULT 'never',
            smoking_habits TEXT DEFAULT 'never',
            languages TEXT DEFAULT '[]',
            bio TEXT,
            interests TEXT DEFAULT '[]',
            hobbies TEXT DEFAULT '[]',
            partner_preferences TEXT DEFAULT '[]',
            photos TEXT DEFAULT '[]',
            is_online INTEGER DEFAULT 0,
            is_verified TEXT DEFAULT 'unverified',
            prompts TEXT DEFAULT '[]',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS matches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            matched_user_id INTEGER NOT NULL,
            chat_id TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, matched_user_id)
        );

        CREATE TABLE IF NOT EXISTS messages (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            chat_id TEXT NOT NULL,
            sender_id INTEGER NOT NULL,
            content TEXT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        );

        CREATE TABLE IF NOT EXISTS likes (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            liker_id INTEGER NOT NULL,
            liked_id INTEGER NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(liker_id, liked_id)
        );
    ");

    // Seed demo users if none exist
    $count = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
    if ($count == 0) {
        seedDemoUsers($pdo);
    }
}

function seedDemoUsers(PDO $pdo): void {
    $demos = [
        [
            'name' => 'Emma Johnson', 'email' => 'emma@example.com', 'phone' => '+1234567890',
            'password_hash' => password_hash('demo123', PASSWORD_DEFAULT),
            'date_of_birth' => '1998-03-15', 'age' => 26, 'profession' => 'Graphic Designer',
            'gender' => 'female', 'looking_for' => 'male', 'native_place' => 'Boston, MA',
            'current_location' => 'New York, NY', 'height' => "5'6\"", 'religion' => 'Christian',
            'relationship_goal' => 'long-term', 'drinking_habits' => 'socially', 'smoking_habits' => 'never',
            'languages' => json_encode(['English', 'Spanish']),
            'bio' => 'Creative soul who loves art, coffee, and long walks in the city. Looking for someone to share adventures with!',
            'interests' => json_encode(['Art', 'Photography', 'Travel', 'Coffee', 'Music', 'Movies']),
            'hobbies' => json_encode(['Painting', 'Reading', 'Hiking', 'Yoga', 'Cooking']),
            'partner_preferences' => json_encode(['Kind', 'Creative', 'Ambitious', 'Funny', 'Honest']),
            'photos' => json_encode(['https://images.pexels.com/photos/3310695/pexels-photo-3310695.jpeg?auto=compress&cs=tinysrgb&w=600']),
            'is_online' => 1, 'is_verified' => 'verified',
            'prompts' => json_encode([
                ['question' => 'My ideal Sunday', 'answer' => 'Brunch with friends and a museum visit'],
                ['question' => "I'm looking for", 'answer' => 'Someone who shares my love for creativity and adventure']
            ])
        ],
        [
            'name' => 'Michael Chen', 'email' => 'michael@example.com', 'phone' => '+1234567891',
            'password_hash' => password_hash('demo123', PASSWORD_DEFAULT),
            'date_of_birth' => '1995-07-22', 'age' => 29, 'profession' => 'Software Engineer',
            'gender' => 'male', 'looking_for' => 'female', 'native_place' => 'San Jose, CA',
            'current_location' => 'San Francisco, CA', 'height' => "5'10\"", 'religion' => 'Buddhist',
            'relationship_goal' => 'long-term', 'drinking_habits' => 'regularly', 'smoking_habits' => 'never',
            'languages' => json_encode(['English', 'Mandarin', 'Japanese']),
            'bio' => 'Tech enthusiast by day, chef by night. Love trying new recipes and exploring the outdoors.',
            'interests' => json_encode(['Technology', 'Cooking', 'Hiking', 'Gaming', 'Travel', 'Music']),
            'hobbies' => json_encode(['Coding', 'Rock Climbing', 'Cooking', 'Gaming', 'Photography']),
            'partner_preferences' => json_encode(['Intelligent', 'Adventurous', 'Loyal', 'Independent', 'Caring']),
            'photos' => json_encode(['https://images.pexels.com/photos/2379004/pexels-photo-2379004.jpeg?auto=compress&cs=tinysrgb&w=600']),
            'is_online' => 0, 'is_verified' => 'partially-verified',
            'prompts' => json_encode([
                ['question' => 'My perfect date', 'answer' => 'Cooking dinner together and stargazing'],
                ['question' => 'Fun fact about me', 'answer' => "I can solve a Rubik's cube in under 2 minutes"]
            ])
        ],
        [
            'name' => 'Sarah Williams', 'email' => 'sarah@example.com', 'phone' => '+1234567892',
            'password_hash' => password_hash('demo123', PASSWORD_DEFAULT),
            'date_of_birth' => '2000-11-08', 'age' => 24, 'profession' => 'Marketing Manager',
            'gender' => 'female', 'looking_for' => 'male', 'native_place' => 'Austin, TX',
            'current_location' => 'Los Angeles, CA', 'height' => "5'4\"", 'religion' => 'Hindu',
            'relationship_goal' => 'casual', 'drinking_habits' => 'socially', 'smoking_habits' => 'never',
            'languages' => json_encode(['English', 'Hindi']),
            'bio' => 'Yoga instructor and marketing professional. Passionate about wellness, travel, and making meaningful connections.',
            'interests' => json_encode(['Yoga', 'Wellness', 'Travel', 'Music', 'Fashion', 'Art']),
            'hobbies' => json_encode(['Yoga', 'Dancing', 'Photography', 'Meditation', 'Swimming']),
            'partner_preferences' => json_encode(['Kind', 'Fit', 'Spiritual', 'Honest', 'Supportive']),
            'photos' => json_encode(['https://images.pexels.com/photos/3671083/pexels-photo-3671083.jpeg?auto=compress&cs=tinysrgb&w=600']),
            'is_online' => 1, 'is_verified' => 'verified',
            'prompts' => json_encode([
                ['question' => 'My love language', 'answer' => 'Quality time and acts of service'],
                ['question' => 'You should message me if', 'answer' => 'You love trying new restaurants and outdoor adventures']
            ])
        ],
        [
            'name' => 'David Rodriguez', 'email' => 'david@example.com', 'phone' => '+1234567893',
            'password_hash' => password_hash('demo123', PASSWORD_DEFAULT),
            'date_of_birth' => '1996-05-12', 'age' => 28, 'profession' => 'Fitness Trainer',
            'gender' => 'male', 'looking_for' => 'female', 'native_place' => 'Miami, FL',
            'current_location' => 'Miami, FL', 'height' => "6'0\"", 'religion' => 'Catholic',
            'relationship_goal' => 'short-term', 'drinking_habits' => 'never', 'smoking_habits' => 'never',
            'languages' => json_encode(['English', 'Spanish']),
            'bio' => 'Fitness enthusiast who believes in living life to the fullest. Love the beach, working out, and good vibes.',
            'interests' => json_encode(['Fitness', 'Beach', 'Music', 'Dancing', 'Sports', 'Travel']),
            'hobbies' => json_encode(['Weightlifting', 'Surfing', 'Salsa Dancing', 'Basketball', 'Running']),
            'partner_preferences' => json_encode(['Active', 'Positive', 'Fun-loving', 'Confident', 'Honest']),
            'photos' => json_encode(['https://images.pexels.com/photos/1043471/pexels-photo-1043471.jpeg?auto=compress&cs=tinysrgb&w=600']),
            'is_online' => 1, 'is_verified' => 'verified',
            'prompts' => json_encode([
                ['question' => 'My workout playlist', 'answer' => 'Latin beats and high-energy pop'],
                ['question' => 'Best travel story', 'answer' => 'Surfing in Costa Rica during a thunderstorm']
            ])
        ],
        [
            'name' => 'Jessica Park', 'email' => 'jessica@example.com', 'phone' => '+1234567894',
            'password_hash' => password_hash('demo123', PASSWORD_DEFAULT),
            'date_of_birth' => '1999-09-30', 'age' => 25, 'profession' => 'Nurse',
            'gender' => 'female', 'looking_for' => 'both', 'native_place' => 'Seattle, WA',
            'current_location' => 'Portland, OR', 'height' => "5'5\"", 'religion' => 'Agnostic',
            'relationship_goal' => 'fun', 'drinking_habits' => 'socially', 'smoking_habits' => 'socially',
            'languages' => json_encode(['English', 'Korean']),
            'bio' => 'Healthcare worker with a passion for helping others. Love indie music, coffee shops, and weekend adventures.',
            'interests' => json_encode(['Healthcare', 'Music', 'Coffee', 'Nature', 'Reading', 'Art']),
            'hobbies' => json_encode(['Guitar', 'Hiking', 'Volunteering', 'Painting', 'Cycling']),
            'partner_preferences' => json_encode(['Compassionate', 'Genuine', 'Open-minded', 'Creative', 'Kind']),
            'photos' => json_encode(['https://images.pexels.com/photos/3785079/pexels-photo-3785079.jpeg?auto=compress&cs=tinysrgb&w=600']),
            'is_online' => 0, 'is_verified' => 'partially-verified',
            'prompts' => json_encode([
                ['question' => 'My hidden talent', 'answer' => 'I can play 5 different instruments'],
                ['question' => 'Life motto', 'answer' => 'Kindness is always the right choice']
            ])
        ]
    ];

    $sql = "INSERT INTO users (name,email,phone,password_hash,date_of_birth,age,profession,gender,looking_for,native_place,current_location,height,religion,relationship_goal,drinking_habits,smoking_habits,languages,bio,interests,hobbies,partner_preferences,photos,is_online,is_verified,prompts) VALUES (:name,:email,:phone,:password_hash,:date_of_birth,:age,:profession,:gender,:looking_for,:native_place,:current_location,:height,:religion,:relationship_goal,:drinking_habits,:smoking_habits,:languages,:bio,:interests,:hobbies,:partner_preferences,:photos,:is_online,:is_verified,:prompts)";
    $stmt = $pdo->prepare($sql);
    foreach ($demos as $d) {
        $stmt->execute($d);
    }
}

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function currentUser(): ?array {
    if (!isLoggedIn()) return null;
    $stmt = db()->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $u = $stmt->fetch();
    if ($u) {
        $u['photos'] = json_decode($u['photos'], true) ?: [];
        $u['interests'] = json_decode($u['interests'], true) ?: [];
        $u['hobbies'] = json_decode($u['hobbies'], true) ?: [];
        $u['partner_preferences'] = json_decode($u['partner_preferences'], true) ?: [];
        $u['languages'] = json_decode($u['languages'], true) ?: [];
        $u['prompts'] = json_decode($u['prompts'], true) ?: [];
    }
    return $u ?: null;
}

function h(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function verifiedBadge(string $status, int $size = 16): string {
    if ($status === 'verified') {
        return "<span title='Verified' style='color:#3b82f6;font-size:{$size}px;'>✔</span>";
    } elseif ($status === 'partially-verified') {
        return "<span title='Partially Verified' style='color:#60a5fa;font-size:{$size}px;'>🛡</span>";
    }
    return '';
}

function firstPhoto(array $user): string {
    return !empty($user['photos']) ? $user['photos'][0] : 'https://images.pexels.com/photos/3310695/pexels-photo-3310695.jpeg?auto=compress&cs=tinysrgb&w=600';
}

function calculateAge(string $dob): int {
    $birth = new DateTime($dob);
    $today = new DateTime();
    return (int)$birth->diff($today)->y;
}
