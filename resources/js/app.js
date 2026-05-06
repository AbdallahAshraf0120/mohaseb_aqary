import './bootstrap';
import 'bootstrap';
import 'admin-lte/dist/js/adminlte.min.js';
import { registerSwalConfirmForms } from './swal-confirm';

registerSwalConfirmForms();

function applyTheme(theme) {
  document.documentElement.setAttribute('data-bs-theme', theme);
  try {
    localStorage.setItem('ma_theme', theme);
  } catch (e) {}
}

function getTheme() {
  try {
    const t = localStorage.getItem('ma_theme');
    if (t === 'dark' || t === 'light') return t;
  } catch (e) {}
  return (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) ? 'dark' : 'light';
}

function syncThemeToggleIcon(themeToggle, theme) {
  const icon = themeToggle.querySelector('i');
  if (!icon) return;
  icon.classList.remove('fa-moon', 'fa-sun');
  icon.classList.add(theme === 'dark' ? 'fa-sun' : 'fa-moon');
  themeToggle.title = theme === 'dark' ? 'الوضع النهاري' : 'الوضع الليلي';
}

document.addEventListener('DOMContentLoaded', () => {
  const themeToggle = document.getElementById('themeToggle');
  if (!themeToggle) return;

  const initial = getTheme();
  applyTheme(initial);
  syncThemeToggleIcon(themeToggle, initial);

  themeToggle.addEventListener('click', () => {
    const next = (getTheme() === 'dark') ? 'light' : 'dark';
    applyTheme(next);
    syncThemeToggleIcon(themeToggle, next);
  });
});