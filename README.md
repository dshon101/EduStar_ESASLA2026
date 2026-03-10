# 🎓 EduStar AI — Initial Release v1.0.0

## Project Overview

**EduStar AI** is a full-stack web application designed to deliver curriculum-aligned, AI-powered education to students across Africa. It provides personalised lessons, adaptive quizzes, an AI tutor chat, and a school books library — all tailored to the student's country, grade level, and learning pace.

> "Smart Learning for Every African Student"

---

## 🌍 Supported Countries & Curricula

| Country | Curriculum System |
|---|---|
| 🇿🇲 Zambia | Grade 1–12 |
| 🇰🇪 Kenya | CBC (PP1–Grade 12) |
| 🇳🇬 Nigeria | Primary, JSS, SSS |
| 🇿🇦 South Africa | Foundation, Intermediate, FET |
| 🇹🇿 Tanzania | Primary Standards, Secondary Forms |
| 🇺🇬 Uganda | Primary P1–P7, O & A Level |
| 🇬🇭 Ghana | Basic, JHS, SHS |
| 🇿🇼 Zimbabwe | Primary Grades, Secondary Forms |
| 🇪🇹 Ethiopia | Grade 1–12 |
| 🇷🇼 Rwanda | Primary P1–P6, Secondary S1–S6 |
| + More | Mozambique, Malawi, Botswana, Senegal, Côte d'Ivoire |

---

## 🏗️ Tech Stack

| Layer | Technology |
|---|---|
| Frontend | HTML5, CSS3, Vanilla JavaScript |
| Backend | PHP 8.2 (REST API) |
| Database | MySQL 8.0 |
| Web Server | Apache (LAMPP) |
| AI Tutor | Anthropic Claude API (claude-sonnet-4-20250514) |
| Fonts | Google Fonts (Syne, DM Sans) |
| Auth | Bearer Token (session-based) |

---

## 📁 Project Structure

```
edustar/
├── index.html                  # Login & Sign Up page
├── dashboard.html              # Student dashboard + AI tutor chat
├── subjects.html               # Browse all subjects
├── lesson.html                 # Lesson viewer with sidebar navigation
├── quiz.html                   # Quiz arena
├── books.html                  # School books & PDF library
│
├── api/
│   ├── auth.php                # Register, Login, Logout, Me, Update
│   ├── books.php               # List, Get, Upload, Download books
│   ├── lessons.php             # Mark complete, Get progress
│   └── quiz.php                # Save scores, My scores, Leaderboard
│
├── config/
│   ├── db.php                  # PDO database connection + constants
│   └── helpers.php             # Shared functions (auth, JSON, sanitise)
│
├── scripts/
│   ├── data.js                 # All subjects, lessons, quiz questions, country data
│   └── api.js                  # API bridge (localStorage + backend sync)
│
├── styles/
│   ├── shared.css              # Global design system & component styles
│   └── normalize.css           # CSS reset
│
├── admin/
│   └── index.php               # Admin panel (users, books, upload PDFs)
│
├── uploads/
│   └── books/                  # Uploaded PDF storage
│
└── schema.sql                  # Full MySQL database schema
```

---

## ✨ Features

### 👤 Authentication System
- Secure user registration and login with bcrypt password hashing
- Bearer token session management (30-day expiry)
- Country and grade-level selection at signup with curriculum-specific grade lists
- Guest mode for instant access without an account
- Admin role with protected admin panel

### 📚 Subjects & Lessons
- 20+ subjects across categories: Core, Sciences, Commerce, Humanities, Languages, Technology, Practical
- Lessons are grade-level aware — content served matches the student's grade
- Lesson completion tracking with points awarded per lesson
- Progress sidebar with next/previous navigation
- "Ask AI Tutor" button on every lesson — passes context directly to the chat

### 🧠 Quiz Arena
- Subject selection with configurable question count (5, 10, 15, 20)
- Grade-appropriate questions generated per student profile
- Live timer and live correct-answer counter during quiz
- Instant feedback with explanations after each answer
- Points awarded per correct answer (+2 pts)
- Personal best scores leaderboard stored per user
- Quiz results saved to backend database

### 🤖 AI Tutor Chat
- Powered by Anthropic Claude (claude-sonnet-4-20250514)
- System prompt personalised with student's name, country, grade, and curriculum
- Full conversation history maintained per session
- Suggested starter questions based on student's country and grade
- Points rewarded for AI tutor engagement (every 5 messages = +5 pts)
- Typing indicator and smooth auto-scroll

### 📖 School Books Library
- Browse PDF textbooks filtered by country, subject, and grade range
- Download tracking logged to database per user
- Admin can upload PDFs directly through the admin panel
- Book metadata includes publisher, curriculum, year, chapters, and topics

### 📊 Gamification & Progress
- XP points system: earn points from lessons, quizzes, and AI chat
- Level system: level up every 200 XP
- Visual XP progress bar on dashboard
- Stats displayed: Total Points, Level, Lessons Done, Quizzes Taken

### 🔐 Admin Panel
- Secure admin-only login (role checked server-side)
- Dashboard with platform stats and global leaderboard
- Full books database management (add, update, delete, upload PDF)
- User management table

---

## 🗄️ Database Schema

| Table | Purpose |
|---|---|
| `users` | Student accounts, points, level, grade, country |
| `sessions` | Bearer token auth sessions with expiry |
| `completed_lessons` | Tracks which lessons each user has finished |
| `quiz_scores` | Stores every quiz result per user per subject |
| `books` | Book metadata (title, subject, country, grade, file) |
| `book_chapters` | Chapter list per book with topic arrays |
| `book_downloads` | Download log per book per user |

---

## 🔌 REST API Endpoints

### Auth — `/api/auth.php`
| Method | Action | Description |
|---|---|---|
| POST | `?action=register` | Create new student account |
| POST | `?action=login` | Login, returns bearer token |
| POST | `?action=logout` | Invalidate session token |
| GET | `?action=me` | Get current authenticated user |
| PUT | `?action=update` | Update profile and sync progress |

### Lessons — `/api/lessons.php`
| Method | Action | Description |
|---|---|---|
| POST | `?action=complete` | Mark a lesson as completed, award points |
| GET | `?action=progress` | Get all completed lessons for user |

### Quiz — `/api/quiz.php`
| Method | Action | Description |
|---|---|---|
| POST | `?action=save` | Save quiz result and award points |
| GET | `?action=scores` | Get personal best scores per subject |
| GET | `?action=leaderboard` | Get global top 20 leaderboard |

### Books — `/api/books.php`
| Method | Action | Description |
|---|---|---|
| GET | `?action=list` | List books with country/subject/grade filters |
| GET | `?action=get&id=KEY` | Get single book with chapters |
| POST | `?action=download` | Log download and return file URL |
| POST | `?action=upload` | Admin: upload PDF for a book |
| POST | `?action=create` | Admin: create book metadata |
| PUT | `?action=update&id=KEY` | Admin: update book metadata |
| DELETE | `?action=delete&id=KEY` | Admin: soft-delete a book |

---

## 🐛 Bugs Fixed in This Release

- Removed all trailing `?>` closing tags from PHP API files that were causing `Unexpected token '<', '<?xml vers...' is not valid JSON` errors in the browser
- Wrapped all helper functions in `function_exists()` guards in `helpers.php` to prevent `Cannot redeclare intVal()` fatal errors caused by double inclusion
- Fixed `index.php` admin panel PHP opening tag to prevent whitespace leaking before HTML output
- Corrected all API base paths from `/api` to `/edustar/api` to match the subdirectory deployment structure
- Fixed all internal page redirect URLs to include the `/edustar/` base path (dashboard, subjects, lesson, quiz, books, index)

---

## ⚙️ Local Setup Instructions

### Prerequisites
- LAMPP / XAMPP with PHP 8.2+ and MySQL 8.0+

### Steps

```bash
# 1. Clone the repository
git clone https://github.com/YOURUSERNAME/edustar.git /opt/lampp/htdocs/edustar

# 2. Import the database schema
/opt/lampp/bin/mysql -u root edustar < schema.sql

# 3. Create the database if it doesn't exist
/opt/lampp/bin/mysql -u root -e "CREATE DATABASE IF NOT EXISTS edustar;"

# 4. Configure database credentials
nano /opt/lampp/htdocs/edustar/config/db.php
# Update DB_HOST, DB_NAME, DB_USER, DB_PASS as needed

# 5. Set correct permissions
sudo chown -R $USER:daemon /opt/lampp/htdocs/edustar/
sudo chmod -R 775 /opt/lampp/htdocs/edustar/

# 6. Start LAMPP
sudo /opt/lampp/lampp start

# 7. Create your admin account
curl -X POST "http://localhost/edustar/api/auth.php?action=register" \
  -H "Content-Type: application/json" \
  -d '{"name":"Your Name","email":"you@email.com","country":"ZM","grade":"Grade 12","password":"yourpassword"}'

# 8. Grant admin privileges
/opt/lampp/bin/mysql -u root edustar \
  -e "UPDATE users SET is_admin=1 WHERE email='you@email.com';"

# 9. Open in browser
# http://localhost/edustar/
```

---

## 👨‍💻 Author

Developed as part of an academic project on AI-powered education platforms for African students.

**Academic Year:** 2025/2026
**Institution:** University of Lusaka
**Department:** Faculty of Computing and Information Technology

---

*EduStar AI — Quality Education Powered by Artificial Intelligence 🌍*
