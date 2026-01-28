// Dark Mode Logic
const darkModeToggle = document.getElementById('darkModeToggle');
const body = document.body;
const darkModeKey = 'adminpro_dark_mode';

// Initialize Dark Mode
function initDarkMode() {
    const isDark = localStorage.getItem(darkModeKey) === 'true';
    if (isDark) {
        body.classList.add('dark-mode');
        loadDarkCSS();
        if(darkModeToggle) darkModeToggle.checked = true;
    }
}

// Toggle Dark Mode
function toggleDarkMode() {
    body.classList.toggle('dark-mode');
    const isDark = body.classList.contains('dark-mode');
    localStorage.setItem(darkModeKey, isDark);

    if (isDark) {
        loadDarkCSS();
    } else {
        removeDarkCSS();
    }
}

function loadDarkCSS() {
    if (!document.getElementById('dark-css')) {
        const link = document.createElement('link');
        link.id = 'dark-css';
        link.rel = 'stylesheet';
        link.href = '../assets/css/dark.css';
        document.head.appendChild(link);
    }
}

function removeDarkCSS() {
    const link = document.getElementById('dark-css');
    if (link) {
        link.remove();
    }
}

if (darkModeToggle) {
    darkModeToggle.addEventListener('change', toggleDarkMode);
}

document.addEventListener('DOMContentLoaded', initDarkMode);
