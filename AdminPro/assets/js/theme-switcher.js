// Theme Switcher Logic
const themeKey = 'adminpro_theme';

function setTheme(themeName) {
    localStorage.setItem(themeKey, themeName);
    loadThemeCSS(themeName);
}

function loadThemeCSS(themeName) {
    let link = document.getElementById('theme-css');
    if (!link) {
        link = document.createElement('link');
        link.id = 'theme-css';
        link.rel = 'stylesheet';
        document.head.appendChild(link);
    }
    link.href = `../assets/css/themes/theme-${themeName}.css`;
}

function initTheme() {
    const savedTheme = localStorage.getItem(themeKey);
    if (savedTheme) {
        loadThemeCSS(savedTheme);
    } else {
        // Default theme
        setTheme('blue');
    }
}

// Expose to window for inline calls
window.setTheme = setTheme;

document.addEventListener('DOMContentLoaded', initTheme);
