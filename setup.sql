-- ===== LoveConnect Database Schema =====
-- Run this SQL in your MySQL/MariaDB to set up the database

CREATE DATABASE IF NOT EXISTS loveconnect CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE loveconnect;

CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    name          VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    phone         VARCHAR(30),
    password_hash VARCHAR(255) NOT NULL,
    date_of_birth DATE,
    age           INT DEFAULT 18,
    profession    VARCHAR(100),
    gender        ENUM('male','female','other') DEFAULT 'other',
    looking_for   ENUM('male','female','both') DEFAULT 'both',
    native_place  VARCHAR(150),
    current_location VARCHAR(150),
    height        VARCHAR(20),
    religion      VARCHAR(50),
    relationship_goal ENUM('long-term','short-term','casual','fun') DEFAULT 'long-term',
    drinking_habits   ENUM('never','socially','regularly','prefer-not-to-say') DEFAULT 'never',
    smoking_habits    ENUM('never','socially','regularly','prefer-not-to-say') DEFAULT 'never',
    languages     TEXT COMMENT 'JSON array',
    bio           TEXT,
    interests     TEXT COMMENT 'JSON array',
    hobbies       TEXT COMMENT 'JSON array',
    partner_preferences TEXT COMMENT 'JSON array',
    photos        TEXT COMMENT 'JSON array of file paths',
    is_online     TINYINT(1) DEFAULT 0,
    is_verified   ENUM('verified','partially-verified','unverified') DEFAULT 'unverified',
    prompts       TEXT COMMENT 'JSON array [{question, answer}]',
    onboarding_done TINYINT(1) DEFAULT 0,
    created_at    TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS likes (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    from_user_id INT NOT NULL,
    to_user_id   INT NOT NULL,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_like (from_user_id, to_user_id),
    FOREIGN KEY (from_user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (to_user_id)   REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS matches (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    user1_id     INT NOT NULL,
    user2_id     INT NOT NULL,
    chat_id      VARCHAR(50) NOT NULL UNIQUE,
    created_at   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user1_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (user2_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS messages (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    chat_id    VARCHAR(50) NOT NULL,
    sender_id  INT NOT NULL,
    content    TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Demo data – Indian profiles (local photos)
INSERT IGNORE INTO users (name, email, phone, password_hash, date_of_birth, age, profession, gender, looking_for, native_place, current_location, height, religion, relationship_goal, drinking_habits, smoking_habits, languages, bio, interests, hobbies, partner_preferences, photos, is_online, is_verified, prompts, onboarding_done) VALUES
('Priya Sharma','priya@example.com','+919876543210','$2y$12$demohashdemohashdemoha01','1998-03-15',26,'Fashion Designer','female','male','Jaipur, Rajasthan','Mumbai, Maharashtra','5\'4"','Hindu','long-term','never','never','["English","Hindi","Rajasthani"]','A creative soul who loves traditional art, chai, and exploring old city markets. Looking for someone genuine who appreciates culture and simplicity.','["Art","Fashion","Travel","Photography","Music","Dancing"]','["Painting","Reading","Classical Dance","Yoga","Cooking"]','["Kind","Creative","Ambitious","Respectful","Honest"]','["uploads_photos/demo1.jpg"]',1,'verified','[{"question":"My ideal Sunday","answer":"Visiting a local art exhibition followed by chai at a roadside stall"}]',1),

('Ananya Iyer','ananya@example.com','+919876543211','$2y$12$demohashdemohashdemoha02','2000-06-20',24,'Software Engineer','female','male','Chennai, Tamil Nadu','Bengaluru, Karnataka','5\'3"','Hindu','long-term','socially','never','["English","Tamil","Kannada"]','Tech girl by day, plant parent by evening. Love nature, quiet cafes, and deep conversations. Looking for someone who values both ambition and peace.','["Technology","Nature","Photography","Reading","Travel","Music"]','["Gardening","Yoga","Cycling","Photography","Cooking"]','["Intelligent","Caring","Funny","Loyal","Supportive"]','["uploads_photos/demo2.jpg"]',0,'partially-verified','[{"question":"My love language","answer":"Acts of service and long evening walks"}]',1),

('Kavya Reddy','kavya@example.com','+919876543212','$2y$12$demohashdemohashdemoha03','1999-11-05',25,'Marketing Manager','female','male','Hyderabad, Telangana','Hyderabad, Telangana','5\'5"','Hindu','casual','never','never','["English","Telugu","Hindi"]','Passionate about storytelling, open fields, and traditional celebrations. I believe life is better with colour and laughter. Looking for someone warm and adventurous.','["Travel","Fashion","Art","Music","Fitness","Dancing"]','["Classical Dance","Meditation","Swimming","Photography","Painting"]','["Warm","Adventurous","Honest","Creative","Positive"]','["uploads_photos/demo3.jpg"]',1,'verified','[{"question":"You should message me if","answer":"You love road trips, good food, and even better conversations"}]',1),

('Arjun Mehta','arjun@example.com','+919876543213','$2y$12$demohashdemohashdemoha04','1996-08-14',28,'Product Manager','male','female','Ahmedabad, Gujarat','Pune, Maharashtra','5\'11"','Hindu','long-term','socially','never','["English","Hindi","Gujarati"]','Product manager who thinks in systems but lives in the moment. Love cricket, street food, and spontaneous weekend trips. Looking for someone real, not perfect.','["Technology","Sports","Travel","Music","Gaming","Cooking"]','["Cricket","Rock Climbing","Cooking","Photography","Cycling"]','["Intelligent","Independent","Genuine","Ambitious","Caring"]','["uploads_photos/demo4.jpg"]',1,'verified','[{"question":"My perfect date","answer":"Exploring a new city on foot and finding a hidden rooftop restaurant"}]',1),

('Rohan Nair','rohan@example.com','+919876543214','$2y$12$demohashdemohashdemoha05','1997-04-22',27,'Graphic Designer','male','female','Kochi, Kerala','Bengaluru, Karnataka','5\'10"','Christian','fun','never','never','["English","Malayalam","Kannada"]','Designer by profession, storyteller at heart. I find beauty in everyday things – a good book, a rainy evening, a perfectly brewed filter coffee. Let\'s explore life together.','["Art","Music","Nature","Photography","Reading","Travel"]','["Sketching","Guitar","Hiking","Cycling","Cooking"]','["Creative","Empathetic","Open-minded","Funny","Kind"]','["uploads_photos/demo5.jpeg"]',0,'partially-verified','[{"question":"Fun fact about me","answer":"I have sketched portraits of over 50 strangers in coffee shops across India"}]',1);
