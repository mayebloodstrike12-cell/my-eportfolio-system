-- EPROF Database Import Script
-- Note: Compatible with Web Hosting (phpMyAdmin) & Localhost
-- Select your database in phpMyAdmin before importing this file.

-- --------------------------------------------------------
-- Table structure for `activities`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `activities` (
    `id` VARCHAR(64) NOT NULL PRIMARY KEY,
    `title` VARCHAR(255) NOT NULL,
    `content` LONGTEXT NOT NULL,
    `date` VARCHAR(50) NOT NULL,
    `pinned` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `activity_images`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `activity_images` (
    `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `activity_id` VARCHAR(64) NOT NULL,
    `image_path` VARCHAR(255) NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_activity_images_activity_id` (`activity_id`),
    CONSTRAINT `fk_activity_images_activity`
        FOREIGN KEY (`activity_id`) REFERENCES `activities` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `posts`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `posts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `slug` VARCHAR(100) NOT NULL UNIQUE,
    `title` VARCHAR(255) NOT NULL,
    `date` VARCHAR(100) NOT NULL,
    `author` VARCHAR(150) NOT NULL,
    `content` LONGTEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Table structure for `comments`
-- --------------------------------------------------------

CREATE TABLE IF NOT EXISTS `comments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `post_id` INT NOT NULL,
    `author` VARCHAR(150) NOT NULL,
    `date` VARCHAR(100) NOT NULL,
    `comment` TEXT NOT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`post_id`) REFERENCES `posts`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------
-- Seed Data for `activities`
-- --------------------------------------------------------
INSERT INTO `activities` (`id`, `title`, `content`, `date`, `pinned`) VALUES ('act_1788229742_347', '# **REFLECTION PAPER ACTIVITY**', 'Becoming a Professional IT Practitioner

1. Understanding Professionalism

For me, professionalism in Information Technology means being responsible, respectful, ethical, and competent when using technology and performing IT-related tasks. It is not only about having technical knowledge or knowing how to program, troubleshoot computers, or manage databases. A good IT professional must also know how to communicate properly, work with others, follow rules, and make ethical decisions. Technical knowledge alone is not enough because IT professionals handle important information and systems. Without good values and responsibility, technical skills can be misused.

2. Personal Qualities

Among the qualities of a professional IT practitioner, I believe **responsibility and reliability** are my strongest qualities. I try my best to complete my assignments and responsibilities and take my school activities seriously. I also understand that I still need to improve my **communication skills and confidence**. Sometimes, I find it difficult to express my ideas clearly, especially when speaking in front of other people. Improving my communication skills will help me work better with classmates, teachers, clients, and future coworkers.

3. Ethical Responsibility

Ethical responsibility is very important in the IT profession because IT practitioners can have access to private information, computer systems, databases, and other sensitive resources. Protecting confidentiality and respecting people&#039;s privacy helps maintain trust. As an IT student, I should never access or share information without permission. I should also use information only for its proper purpose. In the future, I will make sure that I handle data responsibly and follow ethical standards when working with computer systems.

4. Professional Conduct

One situation where I can demonstrate professionalism is when working on a group project at school. I should complete my assigned task on time, cooperate with my group members, listen to their ideas, and respect their opinions. If I make a mistake, I should admit it instead of blaming others and should work on correcting it. In my future workplace, I will apply the same attitude by being punctual, respectful, responsible, and cooperative. Professional conduct is important because it builds trust and creates a positive working environment.

5. Cybersecurity Responsibility

If I discovered that a classmate or coworker had written their password beside their computer, I would inform them that this is a security risk. I would advise them to keep their password private and store it securely. I would never use their password or share it with other people because that would be unethical. As an IT professional, I should help protect accounts, systems, and information instead of taking advantage of someone&#039;s security mistake. Small security practices can help prevent unauthorized access.

6. Digital Professionalism

My social media posts, online communication, and digital behavior can affect my reputation as an IT student and future IT professional. Anything I post or share online can create an impression of my personality and responsibility. Therefore, I should think carefully before posting something and avoid offensive, inappropriate, or harmful content. I should also communicate respectfully with other people online. Maintaining good digital professionalism can help me build a positive reputation and demonstrate that I know how to use technology responsibly.

7. Continuous Learning

Technology changes rapidly, so an IT professional must continue learning even after graduation. New programming languages, software, cybersecurity methods, artificial intelligence tools, and other technologies are constantly developing. If I stop learning, my knowledge may become outdated. In the future, I would like to improve my skills in **web development, programming, database management, cybersecurity, and artificial intelligence**. Continuous learning will help me become more adaptable and prepared for different opportunities in the IT industry.

8. Personal Commitment

Based on the lecture, I need to make several changes to become a more professional IT student. First, I will improve my time management so I can complete my school responsibilities on time. Second, I will work on my communication and teamwork skills so I can collaborate better with others. Third, I will practice using technology responsibly by respecting privacy, protecting information, and following ethical rules. I will also continue learning new IT skills because I know that becoming a good IT professional requires continuous improvement.

Reflection

As a future IT professional, I commit myself to** continuously improving my technical and personal skills, practicing honesty and responsibility, and respecting the privacy and security of others. I commit myself to communicating respectfully, working well with others, and accepting my mistakes as opportunities to learn and improve. I will also continue studying new technologies and developing my skills even after graduation. Most importantly, I will use my knowledge and skills in a responsible and ethical way. I believe that being a successful IT professional is not only about being knowledgeable in technology but also about being trustworthy, respectful, responsible, and willing to learn. These commitments will guide me in becoming a competent and professional IT practitioner in the future.', '09/01', 1) ON DUPLICATE KEY UPDATE `title`='# **REFLECTION PAPER ACTIVITY**', `content`='Becoming a Professional IT Practitioner

1. Understanding Professionalism

For me, professionalism in Information Technology means being responsible, respectful, ethical, and competent when using technology and performing IT-related tasks. It is not only about having technical knowledge or knowing how to program, troubleshoot computers, or manage databases. A good IT professional must also know how to communicate properly, work with others, follow rules, and make ethical decisions. Technical knowledge alone is not enough because IT professionals handle important information and systems. Without good values and responsibility, technical skills can be misused.

2. Personal Qualities

Among the qualities of a professional IT practitioner, I believe **responsibility and reliability** are my strongest qualities. I try my best to complete my assignments and responsibilities and take my school activities seriously. I also understand that I still need to improve my **communication skills and confidence**. Sometimes, I find it difficult to express my ideas clearly, especially when speaking in front of other people. Improving my communication skills will help me work better with classmates, teachers, clients, and future coworkers.

3. Ethical Responsibility

Ethical responsibility is very important in the IT profession because IT practitioners can have access to private information, computer systems, databases, and other sensitive resources. Protecting confidentiality and respecting people&#039;s privacy helps maintain trust. As an IT student, I should never access or share information without permission. I should also use information only for its proper purpose. In the future, I will make sure that I handle data responsibly and follow ethical standards when working with computer systems.

4. Professional Conduct

One situation where I can demonstrate professionalism is when working on a group project at school. I should complete my assigned task on time, cooperate with my group members, listen to their ideas, and respect their opinions. If I make a mistake, I should admit it instead of blaming others and should work on correcting it. In my future workplace, I will apply the same attitude by being punctual, respectful, responsible, and cooperative. Professional conduct is important because it builds trust and creates a positive working environment.

5. Cybersecurity Responsibility

If I discovered that a classmate or coworker had written their password beside their computer, I would inform them that this is a security risk. I would advise them to keep their password private and store it securely. I would never use their password or share it with other people because that would be unethical. As an IT professional, I should help protect accounts, systems, and information instead of taking advantage of someone&#039;s security mistake. Small security practices can help prevent unauthorized access.

6. Digital Professionalism

My social media posts, online communication, and digital behavior can affect my reputation as an IT student and future IT professional. Anything I post or share online can create an impression of my personality and responsibility. Therefore, I should think carefully before posting something and avoid offensive, inappropriate, or harmful content. I should also communicate respectfully with other people online. Maintaining good digital professionalism can help me build a positive reputation and demonstrate that I know how to use technology responsibly.

7. Continuous Learning

Technology changes rapidly, so an IT professional must continue learning even after graduation. New programming languages, software, cybersecurity methods, artificial intelligence tools, and other technologies are constantly developing. If I stop learning, my knowledge may become outdated. In the future, I would like to improve my skills in **web development, programming, database management, cybersecurity, and artificial intelligence**. Continuous learning will help me become more adaptable and prepared for different opportunities in the IT industry.

8. Personal Commitment

Based on the lecture, I need to make several changes to become a more professional IT student. First, I will improve my time management so I can complete my school responsibilities on time. Second, I will work on my communication and teamwork skills so I can collaborate better with others. Third, I will practice using technology responsibly by respecting privacy, protecting information, and following ethical rules. I will also continue learning new IT skills because I know that becoming a good IT professional requires continuous improvement.

Reflection

As a future IT professional, I commit myself to** continuously improving my technical and personal skills, practicing honesty and responsibility, and respecting the privacy and security of others. I commit myself to communicating respectfully, working well with others, and accepting my mistakes as opportunities to learn and improve. I will also continue studying new technologies and developing my skills even after graduation. Most importantly, I will use my knowledge and skills in a responsible and ethical way. I believe that being a successful IT professional is not only about being knowledgeable in technology but also about being trustworthy, respectful, responsible, and willing to learn. These commitments will guide me in becoming a competent and professional IT practitioner in the future.', `date`='09/01', `pinned`=1;

-- --------------------------------------------------------
-- Seed Data for `posts`-- --------------------------------------------------------
INSERT INTO `posts` (`id`, `slug`, `title`, `date`, `author`, `content`) VALUES (1, 'about-me', 'About Me', 'AUGUST 31, 2026', 'ANTHONY MAYER P. GUANZON', '<div class="bio-layout"><div class="profile-img-wrap"><img src="assets/images/profile.jpg" alt="Anthony Mayer Guanzon" class="profile-portrait"></div><div class="bio-text-content"><p>Hello! I’m <strong>Anthony Mayer Guanzon</strong>, a Bachelor of Science in Information Technology (BSIT) student with a growing passion for technology and digital innovation. I am interested in web development, database management, system development, and exploring how technology can be used to solve real-world problems.</p><p>Throughout my studies, I have gained experience in programming, database design, web development, and creating information systems. I enjoy working on projects that allow me to apply what I have learned while developing my problem-solving and technical skills.</p><p>I consider myself a hardworking, curious, and dedicated learner. I believe that learning in Information Technology is a continuous process, and I am always willing to improve my skills, explore new technologies, and take on new challenges.</p><p>My goal is to become a skilled IT professional who can create useful, reliable, and innovative technology solutions.</p></div></div>') ON DUPLICATE KEY UPDATE `title`='About Me', `content`='<div class="bio-layout"><div class="profile-img-wrap"><img src="assets/images/profile.jpg" alt="Anthony Mayer Guanzon" class="profile-portrait"></div><div class="bio-text-content"><p>Hello! I’m <strong>Anthony Mayer Guanzon</strong>, a Bachelor of Science in Information Technology (BSIT) student with a growing passion for technology and digital innovation. I am interested in web development, database management, system development, and exploring how technology can be used to solve real-world problems.</p><p>Throughout my studies, I have gained experience in programming, database design, web development, and creating information systems. I enjoy working on projects that allow me to apply what I have learned while developing my problem-solving and technical skills.</p><p>I consider myself a hardworking, curious, and dedicated learner. I believe that learning in Information Technology is a continuous process, and I am always willing to improve my skills, explore new technologies, and take on new challenges.</p><p>My goal is to become a skilled IT professional who can create useful, reliable, and innovative technology solutions.</p></div></div>';

-- --------------------------------------------------------
-- Seed Data for `comments`-- --------------------------------------------------------
INSERT INTO `comments` (`id`, `post_id`, `author`, `date`, `comment`) VALUES (1, 1, 'Professor Smith', 'December 7, 2018 at 10:15 AM', 'Great introduction, Anthony! Looking forward to following your academic progress and reading your reflections throughout the semester.') ON DUPLICATE KEY UPDATE `comment`='Great introduction, Anthony! Looking forward to following your academic progress and reading your reflections throughout the semester.';
