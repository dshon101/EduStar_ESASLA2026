// ================================================================
// EduStar — API Bridge (api.js)
// Replaces localStorage-only auth/storage with real PHP backend.
// Drop this AFTER data.js in every HTML page.
// ================================================================

const EDUSTAR_API = '/edustar/api'; // Change to full URL if on different domain

// ── TOKEN MANAGEMENT ─────────────────────────────────────────────
function getToken()        { return localStorage.getItem('edustar_token'); }
function setToken(t)       { localStorage.setItem('edustar_token', t); }
function clearToken()      { localStorage.removeItem('edustar_token'); }

// ── LOW-LEVEL FETCH ──────────────────────────────────────────────
async function apiFetch(method, path, data, isForm) {
    const token = getToken();
    const headers = {};
    if (token) headers['Authorization'] = `Bearer ${token}`;

    const opts = { method, headers };
    if (data && !isForm) {
        headers['Content-Type'] = 'application/json';
        opts.body = JSON.stringify(data);
    } else if (data && isForm) {
        opts.body = data; // FormData
    }

    const res  = await fetch(EDUSTAR_API + path, opts);
    const json = await res.json();
    if (!json.ok) throw new Error(json.error || 'API error');
    return json;
}

// ── AUTH OVERRIDES ────────────────────────────────────────────────
/**
 * Register a new user via API. Returns student object.
 */
async function apiRegister(name, email, country, grade, password) {
    const data = await apiFetch('POST', '/auth.php?action=register',
        { name, email, country, grade, password });
    setToken(data.token);
    _cacheStudent(data.user);
    return data.user;
}

/**
 * Login via API. Returns student object.
 */
async function apiLogin(email, password) {
    const data = await apiFetch('POST', '/auth.php?action=login', { email, password });
    setToken(data.token);
    _cacheStudent(data.user);
    return data.user;
}

/**
 * Fetch current user from API and refresh cache.
 */
async function apiMe() {
    try {
        const data = await apiFetch('GET', '/auth.php?action=me');
        _cacheStudent(data.user);
        return data.user;
    } catch (e) {
        return null;
    }
}

/**
 * Persist student progress to the server.
 */
async function apiSaveStudent(student) {
    try {
        const data = await apiFetch('PUT', '/auth.php?action=update', {
            name:         student.name,
            country:      student.country,
            grade:        student.grade,
            points:       student.points   || 0,
            level:        student.level    || 1,
            quizzesTaken: student.quizzesTaken || 0,
            completed:    student.completed || [],
        });
        _cacheStudent(data.user);
        return data.user;
    } catch (e) {
        console.error('apiSaveStudent error:', e);
        return student;
    }
}

// ── OVERRIDE saveStudent TO ALSO HIT API ─────────────────────────
const _originalSaveStudent = typeof saveStudent === 'function' ? saveStudent : null;

saveStudent = function(s) {
    // Keep the localStorage cache in sync for offline/fast reads
    localStorage.setItem('edustar_current', JSON.stringify(s));
    const users = getUsers();
    const idx   = users.findIndex(u => u.email === s.email);
    if (idx !== -1) { users[idx] = s; saveUsers(users); }
    // Also persist to server asynchronously
    if (getToken()) apiSaveStudent(s).catch(() => {});
};

// ── OVERRIDE logout ───────────────────────────────────────────────
logout = function() {
    apiFetch('POST', '/auth.php?action=logout').catch(() => {});
    clearToken();
    localStorage.removeItem('edustar_current');
    window.location.href = '/edustar/index.html';
};

// ── MARK LESSON COMPLETE (also hits API) ─────────────────────────
async function apiCompleteLesson(lessonId, subjectId, points) {
    if (!getToken()) return;
    try {
        await apiFetch('POST', '/lessons.php?action=complete', { lessonId, subjectId, points });
    } catch (e) { /* Fail silently — localStorage already saved */ }
}

// ── SAVE QUIZ SCORE (also hits API) ──────────────────────────────
async function apiSaveQuizScore(subjectId, subjectName, pct, pts, correct, total, timeSecs) {
    if (!getToken()) return;
    try {
        await apiFetch('POST', '/quiz.php?action=save',
            { subjectId, subjectName, pct, pts, correct, total, timeSecs });
    } catch (e) { /* Fail silently */ }
}

// ── FETCH BOOKS FROM API (with fallback to hardcoded BOOKS) ──────
async function apiFetchBooks(filters = {}) {
    try {
        const qs = new URLSearchParams(
            Object.fromEntries(Object.entries(filters).filter(([,v]) => v))
        ).toString();
        const data = await apiFetch('GET', `/books.php?action=list&${qs}`);
        return data.books;
    } catch (e) {
        // Fall back to locally defined BOOKS if API is unavailable
        return typeof BOOKS !== 'undefined' ? BOOKS : [];
    }
}

// ── DOWNLOAD BOOK ─────────────────────────────────────────────────
async function apiDownloadBook(bookKey) {
    try {
        const data = await apiFetch('POST', `/books.php?action=download`, { id: bookKey });
        if (data.url) {
            const a    = document.createElement('a');
            a.href     = data.url;
            a.download = bookKey + '.pdf';
            a.target   = '_blank';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            return true;
        }
    } catch (e) {
        toast('⚠️ No PDF available for this book yet. Ask your teacher to upload it!', 'error');
    }
    return false;
}

// ── INTERNAL CACHE ────────────────────────────────────────────────
function _cacheStudent(user) {
    if (!user) return;
    // Merge API user shape into the shape the app expects
    const merged = {
        name:         user.name,
        email:        user.email,
        country:      user.country,
        grade:        user.grade,
        avatar:       user.avatar || null,
        points:       user.points || 0,
        level:        user.level  || 1,
        quizzesTaken: user.quizzesTaken || 0,
        completed:    user.completed || [],
        isAdmin:      user.isAdmin || false,
        joinDate:     user.joinDate || '',
    };
    localStorage.setItem('edustar_current', JSON.stringify(merged));
}

// ── AUTO-SYNC ON PAGE LOAD ────────────────────────────────────────
// Refresh user data from the server when a page loads, if a token exists.
window.addEventListener('load', async () => {
    if (getToken()) {
        const fresh = await apiMe();
        if (!fresh) {
            // Token is invalid — clear and redirect to login
            clearToken();
            if (!window.location.pathname.endsWith('index.html') &&
                !window.location.pathname.endsWith('/')) {
                window.location.href = '/edustar/index.html';
            }
        }
    }
});
