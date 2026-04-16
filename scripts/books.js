// books.js — Books library module
// WDD231 EduStar Project
import { showToast } from './nav.js';

const PREF_KEY = 'edustar_book_prefs';

let allBooks = [];

// ── Fetch books from local JSON ────────────────────────
export async function fetchBooks() {
  try {
    const response = await fetch('data/books.json');
    if (!response.ok) throw new Error(`HTTP error: ${response.status}`);
    const data = await response.json();
    allBooks = data;
    return data;
  } catch (err) {
    console.error('Failed to load books:', err);
    showToast('Could not load books. Please refresh.', '⚠️');
    return [];
  }
}

// ── Render a single book card ──────────────────────────
function renderCard(book) {
  return `
    <article class="book-card" data-id="${book.id}">
      <div class="book-card-top">
        <span class="book-icon" aria-hidden="true">${book.icon}</span>
        <div class="book-meta">
          <h3 class="book-title">${book.title}</h3>
          <div class="book-badges">
            <span class="badge badge-country">${book.country}</span>
            <span class="badge badge-grade">${book.grade}</span>
            <span class="badge badge-subject">${book.subject}</span>
          </div>
        </div>
      </div>
      <p class="book-desc">${book.description}</p>
      <div class="book-source">Source: <strong>${book.source}</strong></div>
      <div class="book-actions">
        <button class="btn btn-sm btn-primary" onclick="openModal(${book.id})" aria-label="View details for ${book.title}">
          View Details
        </button>
        <a href="${book.url}" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-secondary" aria-label="Open ${book.title} (opens in new tab)">
          Open Book ↗
        </a>
      </div>
    </article>`;
}

// ── Render all filtered books ──────────────────────────
export function renderBooks(books) {
  const grid  = document.getElementById('books-grid');
  const empty = document.getElementById('empty-state');
  const count = document.getElementById('results-count');

  if (!grid) return;

  if (books.length === 0) {
    grid.innerHTML = '';
    if (empty) empty.style.display = 'block';
    if (count) count.textContent = '';
    return;
  }

  if (empty) empty.style.display = 'none';
  if (count) count.textContent = `Showing ${books.length} book${books.length !== 1 ? 's' : ''}`;

  // map() array method to transform data into HTML
  grid.innerHTML = books.map(book => renderCard(book)).join('');
}

// ── Filter logic ───────────────────────────────────────
export function applyFilters() {
  const search  = document.getElementById('search-input')?.value.toLowerCase() || '';
  const country = document.getElementById('filter-country')?.value || '';
  const subject = document.getElementById('filter-subject')?.value || '';

  // Save preferences to localStorage
  localStorage.setItem(PREF_KEY, JSON.stringify({ country, subject }));

  // filter() array method
  const filtered = allBooks.filter(book => {
    const matchSearch  = !search || book.title.toLowerCase().includes(search) ||
                         book.subject.toLowerCase().includes(search) ||
                         book.description.toLowerCase().includes(search) ||
                         book.grade.toLowerCase().includes(search);
    const matchCountry = !country || book.country === country;
    const matchSubject = !subject || book.subject === subject;
    return matchSearch && matchCountry && matchSubject;
  });

  renderBooks(filtered);
}

export function clearFilters() {
  const search  = document.getElementById('search-input');
  const country = document.getElementById('filter-country');
  const subject = document.getElementById('filter-subject');
  if (search)  search.value  = '';
  if (country) country.value = '';
  if (subject) subject.value = '';
  localStorage.removeItem(PREF_KEY);
  renderBooks(allBooks);
}

// ── Restore saved preferences ──────────────────────────
export function restorePrefs() {
  try {
    const prefs = JSON.parse(localStorage.getItem(PREF_KEY) || '{}');
    if (prefs.country) {
      const el = document.getElementById('filter-country');
      if (el) el.value = prefs.country;
    }
    if (prefs.subject) {
      const el = document.getElementById('filter-subject');
      if (el) el.value = prefs.subject;
    }
    if (prefs.country || prefs.subject) applyFilters();
  } catch (e) {
    // ignore parse errors
  }
}

// ── Modal ──────────────────────────────────────────────
export function openBookModal(id) {
  const book = allBooks.find(b => b.id === id);
  if (!book) return;

  const modal   = document.getElementById('book-modal');
  const content = document.getElementById('modal-content');

  content.innerHTML = `
    <div class="modal-book-icon" aria-hidden="true">${book.icon}</div>
    <div class="modal-badge-row">
      <span class="badge badge-country">${book.country}</span>
      <span class="badge badge-grade">${book.grade}</span>
      <span class="badge badge-subject">${book.subject}</span>
    </div>
    <p class="modal-desc">${book.description}</p>
    <p class="modal-source">Source: <strong>${book.source}</strong></p>
    <a href="${book.url}" target="_blank" rel="noopener noreferrer" class="btn btn-primary" style="margin-top:1.2rem; width:100%; justify-content:center;">
      📖 Open Textbook ↗
    </a>`;

  document.getElementById('modal-title').textContent = book.title;
  modal.classList.add('open');
  document.getElementById('modal-close').focus();
}

export { allBooks };
