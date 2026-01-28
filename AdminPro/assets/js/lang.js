// Language & RTL Logic
const langKey = 'adminpro_lang';
const supportedLangs = ['en', 'hi', 'ar'];

async function setLanguage(lang) {
    if (!supportedLangs.includes(lang)) return;

    localStorage.setItem(langKey, lang);

    // Handle RTL
    if (lang === 'ar') {
        document.documentElement.setAttribute('dir', 'rtl');
        document.documentElement.setAttribute('lang', 'ar');
        loadRTLBootstrap();
    } else {
        document.documentElement.setAttribute('dir', 'ltr');
        document.documentElement.setAttribute('lang', lang);
        loadLTRBootstrap();
    }

    // Load Translations
    try {
        const response = await fetch(`../languages/${lang}.json`);
        const translations = await response.json();
        applyTranslations(translations);
    } catch (e) {
        console.error('Failed to load language file:', e);
    }
}

function loadRTLBootstrap() {
    const bootstrapLink = document.getElementById('bootstrap-css');
    if (bootstrapLink) {
        bootstrapLink.href = '../assets/css/bootstrap.rtl.min.css';
    }
}

function loadLTRBootstrap() {
    const bootstrapLink = document.getElementById('bootstrap-css');
    if (bootstrapLink) {
        bootstrapLink.href = '../assets/css/bootstrap.min.css';
    }
}

function applyTranslations(translations) {
    document.querySelectorAll('[data-i18n]').forEach(el => {
        const key = el.getAttribute('data-i18n');
        if (translations[key]) {
            el.textContent = translations[key];
        }
    });
}

function initLanguage() {
    const savedLang = localStorage.getItem(langKey) || 'en';
    setLanguage(savedLang);
}

// Expose
window.setLanguage = setLanguage;

document.addEventListener('DOMContentLoaded', initLanguage);
