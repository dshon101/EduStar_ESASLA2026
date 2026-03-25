<?php session_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>EduStar — Admin Panel</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Syne:wght@400;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,300&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../styles/normalize.css">
  <link rel="stylesheet" href="../styles/shared.css">
  <style>
    .admin-grid{display:grid;grid-template-columns:230px 1fr;min-height:100vh}
    .admin-sidebar{background:rgba(13,13,26,0.98);border-right:1px solid var(--border);padding:1.5rem 1rem;position:sticky;top:0;height:100vh;overflow-y:auto}
    .admin-sidebar h2{font-family:'Syne',sans-serif;font-weight:800;font-size:1.05rem;margin-bottom:1.5rem;background:linear-gradient(135deg,var(--sun),var(--gold));-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
    .sidebar-link{display:block;padding:0.6rem 0.9rem;border-radius:var(--radius-sm);font-size:0.83rem;color:var(--muted);text-decoration:none;cursor:pointer;margin-bottom:0.2rem;border:1px solid transparent;transition:all 0.2s}
    .sidebar-link:hover,.sidebar-link.active{background:rgba(255,107,43,0.1);border-color:rgba(255,107,43,0.25);color:var(--sun)}
    .sidebar-section{font-size:0.63rem;color:var(--muted);text-transform:uppercase;letter-spacing:0.8px;padding:0.7rem 0.9rem 0.25rem;opacity:0.55}
    .admin-main{padding:2rem;overflow-x:auto}
    .admin-section{display:none}
    .admin-section.active{display:block}
    .admin-section>h2{font-family:'Syne',sans-serif;font-weight:700;font-size:1.4rem;margin-bottom:1.5rem}
    table{width:100%;border-collapse:collapse;font-size:0.83rem}
    th{text-align:left;padding:0.65rem 1rem;background:rgba(255,255,255,0.04);border-bottom:1px solid var(--border);font-weight:600;font-size:0.72rem;text-transform:uppercase;letter-spacing:0.4px;color:var(--muted)}
    td{padding:0.65rem 1rem;border-bottom:1px solid var(--border);vertical-align:middle}
    tr:hover td{background:rgba(255,255,255,0.02)}
    .badge{display:inline-block;padding:0.18rem 0.6rem;border-radius:50px;font-size:0.68rem;font-weight:600}
    .badge-admin{background:rgba(255,107,43,0.15);color:var(--sun);border:1px solid rgba(255,107,43,0.3)}
    .badge-active{background:rgba(0,201,167,0.12);color:var(--teal);border:1px solid rgba(0,201,167,0.28)}
    .badge-banned{background:rgba(255,71,87,0.1);color:#FF6B7A;border:1px solid rgba(255,71,87,0.3)}
    .form-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:1.6rem;margin-bottom:1.5rem;max-width:700px}
    .form-card h3{font-family:'Syne',sans-serif;font-weight:700;margin-bottom:1.2rem}
    .field{margin-bottom:1rem}
    .field label{display:block;font-size:0.78rem;color:var(--muted);margin-bottom:0.35rem;text-transform:uppercase;letter-spacing:0.4px}
    .field input,.field select,.field textarea{width:100%;background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:10px;padding:0.75rem 1rem;color:var(--text);font-family:'DM Sans',sans-serif;font-size:0.9rem;outline:none}
    .field input:focus,.field select:focus,.field textarea:focus{border-color:rgba(255,107,43,0.5)}
    .field textarea{min-height:80px;resize:vertical}
    .field select option{background:var(--sky)}
    .stat-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(135px,1fr));gap:1rem;margin-bottom:2rem}
    .stat-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:1.2rem;text-align:center}
    .stat-card .n{font-family:'Syne',sans-serif;font-size:1.8rem;font-weight:800;color:var(--gold)}
    .stat-card .l{font-size:0.72rem;color:var(--muted);margin-top:0.2rem}
    .stat-card.alert-card .n{color:#FF6B7A}
    .upload-zone{border:2px dashed var(--border);border-radius:var(--radius);padding:2rem;text-align:center;cursor:pointer;transition:all 0.2s}
    .upload-zone:hover,.upload-zone.drag{border-color:rgba(255,107,43,0.5);background:rgba(255,107,43,0.05)}
    #upload-file{display:none}
    .error-box{background:rgba(255,71,87,0.1);border:1px solid rgba(255,71,87,0.3);border-radius:10px;padding:0.8rem 1rem;font-size:0.83rem;color:#FF6B7A;margin-bottom:1rem;display:none}
    .error-box.show{display:block}
    .success-box{background:rgba(0,201,167,0.1);border:1px solid rgba(0,201,167,0.28);border-radius:10px;padding:0.8rem 1rem;font-size:0.83rem;color:var(--teal);margin-bottom:1rem;display:none}
    .success-box.show{display:block}
    .log-action{font-size:0.7rem;padding:0.15rem 0.5rem;border-radius:20px;background:rgba(123,104,238,0.12);color:#A78BFA;border:1px solid rgba(123,104,238,0.22);font-weight:600}
    .log-controls{display:flex;gap:0.7rem;flex-wrap:wrap;align-items:center;margin-bottom:1rem}
    .log-controls input,.log-controls select{background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:10px;padding:0.55rem 1rem;color:var(--text);font-family:'DM Sans',sans-serif;font-size:0.83rem;outline:none}
    .log-controls select option{background:#1A1A2E}
    .export-row{display:flex;gap:0.6rem;margin-bottom:1.2rem;flex-wrap:wrap}
    .status-open{background:rgba(255,107,43,0.12);color:var(--sun)}
    .status-in_progress{background:rgba(123,104,238,0.12);color:#A78BFA}
    .status-resolved{background:rgba(0,201,167,0.12);color:var(--teal)}
    .status-closed{background:rgba(255,255,255,0.06);color:var(--muted)}
    .modal-overlay{position:fixed;inset:0;background:rgba(0,0,0,0.72);z-index:600;display:none;align-items:center;justify-content:center;padding:1rem}
    .modal-overlay.open{display:flex}
    .modal-box{background:var(--sky);border:1px solid var(--border);border-radius:var(--radius);padding:2rem;max-width:580px;width:100%;max-height:90vh;overflow-y:auto}
    .modal-box h3{font-family:'Syne',sans-serif;font-weight:700;margin-bottom:1rem}
    .modal-row{display:flex;gap:0.7rem;justify-content:flex-end;margin-top:1rem}
    .action-btn{border-radius:50px;padding:0.28rem 0.7rem;font-size:0.72rem;cursor:pointer;font-family:'DM Sans',sans-serif;border:none;transition:all 0.18s}
    .ab-warn{background:rgba(255,107,43,0.1);color:var(--sun);border:1px solid rgba(255,107,43,0.3)}
    .ab-ban{background:rgba(255,71,87,0.1);color:#FF6B7A;border:1px solid rgba(255,71,87,0.3)}
    .ab-green{background:rgba(0,201,167,0.1);color:var(--teal);border:1px solid rgba(0,201,167,0.28)}
  </style>
</head>
<body>
<div class="app">

  <div id="auth-guard" style="display:flex;align-items:center;justify-content:center;min-height:100vh;flex-direction:column;gap:1rem">
    <div style="font-size:3rem">🔐</div>
    <h2 style="font-family:'Syne',sans-serif">Admin Login</h2>
    <div style="background:var(--card);border:1px solid var(--border);border-radius:var(--radius);padding:2rem;width:360px">
      <div class="field"><label>Email</label><input type="email" id="adm-email" placeholder="admin@example.com"></div>
      <div class="field"><label>Password</label><input type="password" id="adm-pw" placeholder="Password" onkeydown="if(event.key==='Enter')adminLogin()"></div>
      <div class="error-box" id="adm-err"></div>
      <button class="btn btn-primary" style="width:100%" onclick="adminLogin()">Login as Admin</button>
    </div>
  </div>

  <div id="admin-app" style="display:none">
    <div class="admin-grid">
      <div class="admin-sidebar">
        <h2>⚡ EduStar Admin</h2>
        <div class="sidebar-section">Overview</div>
        <a class="sidebar-link active" onclick="showSection('dashboard')">📊 Dashboard</a>
        <div class="sidebar-section">Users &amp; Content</div>
        <a class="sidebar-link" onclick="showSection('users')">👥 Users</a>
        <a class="sidebar-link" onclick="showSection('books')">📚 Books Analytics</a>
        <a class="sidebar-link" onclick="showSection('upload')">⬆️ Upload PDF</a>
        <a class="sidebar-link" onclick="showSection('add-book')">➕ Add Book</a>
        <div class="sidebar-section">Community &amp; Support</div>
        <a class="sidebar-link" onclick="showSection('tickets')">🎧 Support Tickets <span id="open-ticket-badge" style="display:none;background:rgba(255,107,43,0.15);color:var(--sun);border-radius:50px;padding:0.1rem 0.45rem;font-size:0.68rem;margin-left:0.2rem"></span></a>
        <a class="sidebar-link" onclick="showSection('community')">💬 Community Posts</a>
        <div class="sidebar-section">Logs</div>
        <a class="sidebar-link" onclick="showSection('logs')">📋 System Logs</a>
        <hr style="border-color:var(--border);margin:1rem 0">
        <a class="sidebar-link" href="../index.html">🏠 Back to App</a>
        <a class="sidebar-link" onclick="adminLogout()">🚪 Logout</a>
      </div>

      <div class="admin-main">

        <!-- DASHBOARD -->
        <div class="admin-section active" id="sec-dashboard">
          <h2>📊 Overview</h2>
          <div class="stat-cards">
            <div class="stat-card"><div class="n" id="stat-users">—</div><div class="l">Total Users</div></div>
            <div class="stat-card"><div class="n" id="stat-books">—</div><div class="l">Books in DB</div></div>
            <div class="stat-card"><div class="n" id="stat-quizzes">—</div><div class="l">Quizzes Taken</div></div>
            <div class="stat-card"><div class="n" id="stat-lessons">—</div><div class="l">Lessons Done</div></div>
            <div class="stat-card"><div class="n" id="stat-downloads">—</div><div class="l">Book Downloads</div></div>
            <div class="stat-card alert-card"><div class="n" id="stat-open-tickets">—</div><div class="l">Open Tickets</div></div>
            <div class="stat-card"><div class="n" id="stat-posts">—</div><div class="l">Community Posts</div></div>
          </div>
          <h3 style="font-family:'Syne',sans-serif;margin-bottom:1rem">🏆 Top Students</h3>
          <table id="lb-table"><thead><tr><th>#</th><th>Name</th><th>Country</th><th>Points</th><th>Level</th></tr></thead><tbody></tbody></table>
        </div>

        <!-- USERS -->
        <div class="admin-section" id="sec-users">
          <h2>👥 Registered Users</h2>
          <div style="margin-bottom:1rem">
            <input style="max-width:300px;background:rgba(255,255,255,0.05);border:1px solid var(--border);border-radius:10px;padding:0.55rem 1rem;color:var(--text);font-family:'DM Sans',sans-serif;outline:none" placeholder="🔍 Search users..." oninput="searchUsers(this.value)" id="user-search">
          </div>
          <table id="users-table">
            <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Country</th><th>Grade</th><th>Points</th><th>Joined</th><th>Status</th><th>Actions</th></tr></thead>
            <tbody></tbody>
          </table>
        </div>

        <!-- BOOKS -->
        <div class="admin-section" id="sec-books">
          <h2>📚 Book Downloads</h2>
          <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:2rem">
            <div class="stat-card"><div class="n" id="bs-total">—</div><div class="l">Total Downloads</div></div>
            <div class="stat-card"><div class="n" id="bs-top-country">—</div><div class="l">Top Country</div></div>
            <div class="stat-card"><div class="n" id="bs-top-subject">—</div><div class="l">Top Subject</div></div>
          </div>
          <h3 style="margin-bottom:1rem;font-family:'Syne',sans-serif">🏆 Most Downloaded</h3>
          <table><thead><tr><th>Title</th><th>Country</th><th>Subject</th><th>Downloads</th></tr></thead><tbody id="books-top-tbody"></tbody></table>
          <h3 style="margin:2rem 0 1rem;font-family:'Syne',sans-serif">🕐 Recent Downloads</h3>
          <table><thead><tr><th>Title</th><th>Country</th><th>Subject</th><th>User</th><th>Time</th></tr></thead><tbody id="books-recent-tbody"></tbody></table>
        </div>

        <!-- UPLOAD PDF -->
        <div class="admin-section" id="sec-upload">
          <h2>⬆️ Upload PDF</h2>
          <div class="form-card">
            <h3>Select Book & Upload File</h3>
            <div class="success-box" id="upload-success"></div>
            <div class="error-box"   id="upload-error"></div>
            <div class="field"><label>Book Key</label><select id="upload-bookkey"><option value="">Loading books...</option></select></div>
            <div class="upload-zone" onclick="document.getElementById('upload-file').click()" id="upload-zone">
              <div style="font-size:2.5rem">📄</div>
              <p>Click to select a PDF file</p>
              <p id="upload-filename" style="color:var(--sun);margin-top:0.5rem"></p>
            </div>
            <input type="file" id="upload-file" accept="application/pdf" onchange="onFileSelect(this)">
            <button class="btn btn-primary" style="margin-top:1rem;width:100%" onclick="doUpload()">⬆️ Upload PDF</button>
          </div>
        </div>

        <!-- ADD BOOK -->
        <div class="admin-section" id="sec-add-book">
          <h2>➕ Add New Book</h2>
          <div class="form-card">
            <h3>Book Metadata</h3>
            <div class="success-box" id="add-success"></div>
            <div class="error-box"   id="add-error"></div>
            <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
              <div class="field"><label>Book Key (unique)</label><input id="nb-key" placeholder="e.g. ke-math-g8"></div>
              <div class="field"><label>Title</label><input id="nb-title" placeholder="Mathematics Grade 8"></div>
              <div class="field"><label>Subject</label>
                <select id="nb-subject">
                  <option value="math">Mathematics</option><option value="english">English</option>
                  <option value="science">Science</option><option value="biology">Biology</option>
                  <option value="chemistry">Chemistry</option><option value="physics">Physics</option>
                  <option value="history">History</option><option value="geography">Geography</option>
                  <option value="economics">Economics</option><option value="computer">Computer Studies</option>
                </select>
              </div>
              <div class="field"><label>Country</label>
                <select id="nb-country">
                  <option value="continental">🌍 Pan-African</option>
                  <option value="KE">🇰🇪 Kenya</option><option value="NG">🇳🇬 Nigeria</option>
                  <option value="ZA">🇿🇦 South Africa</option><option value="ZW">🇿🇼 Zimbabwe</option>
                  <option value="ZM">🇿🇲 Zambia</option><option value="GH">🇬🇭 Ghana</option>
                </select>
              </div>
              <div class="field"><label>Grade Range</label>
                <select id="nb-grade">
                  <option value="lower_primary">Lower Primary</option>
                  <option value="upper_primary">Upper Primary</option>
                  <option value="lower_secondary">Lower Secondary</option>
                  <option value="upper_secondary">Upper Secondary</option>
                </select>
              </div>
              <div class="field"><label>Year</label><input id="nb-year" type="number" placeholder="2024"></div>
              <div class="field"><label>Publisher</label><input id="nb-publisher" placeholder="KICD"></div>
              <div class="field"><label>Curriculum</label><input id="nb-curriculum" placeholder="CBC"></div>
              <div class="field"><label>Icon (emoji)</label><input id="nb-icon" placeholder="📐"></div>
              <div class="field"><label>Colour</label><input id="nb-color" type="color" value="#FF6B2B"></div>
            </div>
            <div class="field"><label>Chapters (one per line: "Title | topic1, topic2")</label>
              <textarea id="nb-chapters" placeholder="Chapter 1: Numbers | Integers, Fractions"></textarea>
            </div>
            <button class="btn btn-primary" onclick="doAddBook()">➕ Create Book</button>
          </div>
        </div>

        <!-- TICKETS -->
        <div class="admin-section" id="sec-tickets">
          <h2>🎧 Support Tickets</h2>
          <div style="display:flex;gap:0.5rem;margin-bottom:1.2rem;flex-wrap:wrap">
            <button class="btn btn-sm btn-secondary" onclick="loadTickets('')">All</button>
            <button class="btn btn-sm btn-secondary" onclick="loadTickets('open')">🔴 Open</button>
            <button class="btn btn-sm btn-secondary" onclick="loadTickets('in_progress')">🟡 In Progress</button>
            <button class="btn btn-sm btn-secondary" onclick="loadTickets('resolved')">🟢 Resolved</button>
            <button class="btn btn-sm btn-secondary" onclick="loadTickets('closed')">⚫ Closed</button>
          </div>
          <table id="tickets-table">
            <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Subject</th><th>Category</th><th>Status</th><th>Submitted</th><th>Action</th></tr></thead>
            <tbody id="tickets-tbody"></tbody>
          </table>
        </div>

        <!-- COMMUNITY -->
        <div class="admin-section" id="sec-community">
          <h2>💬 Community Posts</h2>
          <table>
            <thead><tr><th>ID</th><th>Title</th><th>Author</th><th>Category</th><th>Likes</th><th>Replies</th><th>Posted</th><th>Action</th></tr></thead>
            <tbody id="comm-tbody"></tbody>
          </table>
        </div>

        <!-- SYSTEM LOGS -->
        <div class="admin-section" id="sec-logs">
          <h2>📋 System Logs</h2>
          <div class="export-row">
            <button class="btn btn-sm btn-teal" onclick="exportLogs('csv')">⬇️ Export CSV</button>
            <button class="btn btn-sm btn-teal" onclick="exportLogs('excel')">⬇️ Export Excel (.xls)</button>
            <button class="btn btn-sm btn-secondary" style="margin-left:auto" onclick="clearOldLogs()">🧹 Clear Old Logs (90d+)</button>
          </div>
          <div class="log-controls">
            <input id="log-search" type="text" placeholder="🔍 Search by actor, action, detail...">
            <select id="log-action-filter">
              <option value="">All Actions</option>
            </select>
            <span id="log-count" style="font-size:0.78rem;color:var(--muted)"></span>
          </div>
          <table>
            <thead><tr><th>Time</th><th>Actor</th><th>Action</th><th>Target</th><th>Detail</th></tr></thead>
            <tbody id="logs-tbody"></tbody>
          </table>
          <div id="logs-pagination" style="display:flex;gap:0.4rem;margin-top:1.2rem;flex-wrap:wrap"></div>
        </div>

      </div>
    </div>
  </div>
</div>

<!-- TICKET REPLY MODAL -->
<div class="modal-overlay" id="modal-ticket">
  <div class="modal-box">
    <h3>🎧 Reply to Ticket <span id="modal-ticket-id" style="color:var(--gold)"></span></h3>
    <div id="modal-ticket-detail" style="background:rgba(255,255,255,0.03);border-radius:10px;padding:1rem;margin-bottom:1rem;font-size:0.84rem;color:var(--muted);max-height:160px;overflow-y:auto"></div>
    <div class="field"><label>Update Status</label>
      <select id="reply-status">
        <option value="in_progress">🟡 In Progress</option>
        <option value="resolved">🟢 Resolved</option>
        <option value="closed">⚫ Closed</option>
      </select>
    </div>
    <div class="field"><label>Your Reply</label>
      <textarea id="reply-body" style="min-height:110px" placeholder="Write your support response here..."></textarea>
    </div>
    <div class="error-box" id="reply-err"></div>
    <div class="modal-row">
      <button class="btn btn-secondary" onclick="closeTicketModal()">Cancel</button>
      <button class="btn btn-primary" onclick="sendTicketReply()">Send Reply</button>
    </div>
  </div>
</div>

<div class="toast-container" id="toasts"></div>
<script></script>
<script src="/admin/admin.js"></script>
</body>
</html>
