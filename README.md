# EduStar — Full-Stack Setup Guide

## Stack
- **Frontend**: HTML5 + CSS3 + Vanilla JS (unchanged colour scheme)
- **Backend**: PHP 8.1+
- **Database**: MySQL 8.0+
- **File storage**: Local filesystem (PDF uploads)

---

## Project Structure

```
edustar/
├── index.html              ← Login / Register page
├── dashboard.html          ← Student dashboard + AI tutor
├── subjects.html           ← Subject browser
├── lesson.html             ← Lesson viewer
├── quiz.html               ← Quiz arena
├── books.html              ← School books + PDF downloads
├── .htaccess               ← Apache config
│
├── styles/
│   ├── normalize.css       ← CSS reset (normalize v8)
│   └── shared.css          ← EduStar design system
│
├── scripts/
│   ├── data.js             ← All subject/lesson/quiz data
│   └── api.js              ← API bridge (localStorage → MySQL)
│
├── api/
│   ├── auth.php            ← Register, login, logout, me, update
│   ├── books.php           ← List, get, upload, download, CRUD
│   ├── lessons.php         ← Mark complete, get progress
│   └── quiz.php            ← Save score, my scores, leaderboard
│
├── config/
│   ├── db.php              ← PDO connection + constants
│   ├── helpers.php         ← Auth middleware, JSON helpers
│   └── schema.sql          ← Full database schema
│
├── admin/
│   └── index.php           ← Admin panel (manage books, users)
│
└── uploads/
    ├── books/              ← Uploaded PDF files
    └── avatars/            ← User avatars (future)
```

---

## Step 1 — Database Setup

```sql
-- Run as MySQL root:
CREATE DATABASE edustar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'edustar_user'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON edustar.* TO 'edustar_user'@'localhost';
FLUSH PRIVILEGES;

-- Then import the schema:
mysql -u edustar_user -p edustar < config/schema.sql
```

---

## Step 2 — Configuration

Edit `config/db.php` or set environment variables:

```bash
export DB_HOST=localhost
export DB_NAME=edustar
export DB_USER=edustar_user
export DB_PASS=your_secure_password
export APP_SECRET=a_very_long_random_string_here
export CORS_ORIGIN=https://yourdomain.com
```

---

## Step 3 — File Permissions

```bash
chmod 755 uploads/
chmod 755 uploads/books/
chmod 755 uploads/avatars/
chown -R www-data:www-data uploads/
```

---

## Step 4 — Web Server

### Apache (recommended)
The `.htaccess` file handles routing. Enable `mod_rewrite`:
```bash
a2enmod rewrite headers
systemctl restart apache2
```

### Nginx
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/edustar;
    index index.html;

    location /api/ {
        try_files $uri $uri/ =404;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location /admin/ {
        try_files $uri $uri/ =404;
        fastcgi_pass unix:/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location / {
        try_files $uri $uri/ /index.html;
    }

    # Protect config files
    location ~ /config/ { deny all; }
    location ~ /uploads/.*\.php$ { deny all; }
}
```

---

## Step 5 — Create Admin Account

```sql
-- Insert an admin user (replace email/hash):
INSERT INTO users (name, email, password_hash, country, grade, is_admin)
VALUES (
  'Admin User',
  'admin@yourdomain.com',
  '$2y$12$YOUR_BCRYPT_HASH_HERE',
  'KE', 'Grade 12', 1
);
```

Generate a bcrypt hash with PHP:
```php
echo password_hash('your_password', PASSWORD_BCRYPT, ['cost' => 12]);
```

Then log in at `/admin/index.php`

---

## API Reference

### Auth
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/auth.php?action=register` | Create account |
| POST | `/api/auth.php?action=login` | Login |
| POST | `/api/auth.php?action=logout` | Logout |
| GET  | `/api/auth.php?action=me` | Get current user |
| PUT  | `/api/auth.php?action=update` | Update profile/progress |

### Books
| Method | Endpoint | Description |
|--------|----------|-------------|
| GET  | `/api/books.php?action=list` | List books (filter: country, subject, grade, search) |
| GET  | `/api/books.php?action=get&id=ke-m7` | Single book detail |
| POST | `/api/books.php?action=download` | Log & get PDF URL |
| POST | `/api/books.php?action=upload` | Upload PDF (admin) |
| POST | `/api/books.php?action=create` | Add book metadata (admin) |
| PUT  | `/api/books.php?action=update&id=X` | Update book (admin) |
| DELETE | `/api/books.php?action=delete&id=X` | Remove book (admin) |

### Lessons
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/lessons.php?action=complete` | Mark lesson done (+points) |
| GET  | `/api/lessons.php?action=progress` | Get completed lessons |

### Quiz
| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/quiz.php?action=save` | Save quiz result |
| GET  | `/api/quiz.php?action=scores` | My best scores |
| GET  | `/api/quiz.php?action=leaderboard` | Top 20 students |

---

## Uploading Books (Admin)

1. Log in at `/admin/index.php` with an admin account
2. Go to **Add Book** — fill in title, subject, country, grade, chapters
3. Go to **Upload PDF** — select the book and upload a PDF (max 50 MB)
4. Students will see a ⬇️ Download button on the books page

---

## Offline / Fallback

The app still works without a backend — it falls back to localStorage for:
- Auth (guest mode)
- Quiz scores
- Lesson progress

The `api.js` bridge handles this transparently.
