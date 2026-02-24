# 🌍 EduStar AI – Smart Learning Assistant

## What's Included
- `index.php` — The complete website (PHP + JavaScript + HTML/CSS in one file)

## How to Run It

### Option 1: Local PHP Server (easiest for testing)
1. Make sure you have PHP installed (most computers do)
2. Open a terminal in this folder
3. Run: `php -S localhost:8080`
4. Open your browser and go to: `http://localhost:8080`

### Option 2: XAMPP / WAMP / LAMP (recommended)
1. Install XAMPP (free): https://www.apachefriends.org
2. Copy the `smart_learning_assistant` folder into the `htdocs` directory
3. Start Apache in XAMPP
4. Visit: `http://localhost/smart_learning_assistant/`

### Option 3: Any PHP Web Hosting
- Upload `index.php` to your hosting provider (Hostinger, cPanel, etc.)
- Access via your domain

## Features Built
- ✅ **Student Profiles** — Name, level, XP points (stored in PHP sessions)
- ✅ **6 Subjects** — Math, Science, English, History, Geography, Computer Studies
- ✅ **Lessons** — Rich lesson content in popup panels with "Mark Complete" (+50 pts)
- ✅ **Daily Quiz** — 5 randomized questions, auto-scored, +2 pts per correct answer
- ✅ **AI Tutor Chat** — Powered by Claude AI for instant question answering
- ✅ **Progress Tracking** — XP bar, level system, stats dashboard
- ✅ **Gamification** — Points, levels, badges, completion tracking

## AI Chat Setup
The AI chat uses the Anthropic Claude API. The API call happens client-side.
To enable it fully, you may need to configure CORS on the Anthropic API or proxy it through PHP.

For a simple PHP proxy, add this to your server:
```php
// api_proxy.php
header('Content-Type: application/json');
$data = json_decode(file_get_contents('php://input'), true);
$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($data),
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'x-api-key: YOUR_API_KEY_HERE',
        'anthropic-version: 2023-06-01'
    ]
]);
echo curl_exec($ch);
```

## Technologies Used
- **PHP** — Session management, progress tracking, quiz scoring, backend logic
- **JavaScript** — UI interactions, quiz engine, AI chat, subject rendering
- **HTML/CSS** — Beautiful responsive design with African-inspired warm color theme
- **Claude AI API** — Powers the intelligent tutor chat feature
