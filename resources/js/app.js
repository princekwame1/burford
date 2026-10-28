import './bootstrap';
import './alerts';
import './searchable';
import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';

window.Alpine = Alpine;
window.Chart = Chart;

// Shared chart styling so every chart reads as one system, in light and dark.
const isDark = () => document.documentElement.classList.contains('dark');
Chart.defaults.font.family = "'Inter', ui-sans-serif, system-ui, sans-serif";
Chart.defaults.font.size = 12;
Chart.defaults.plugins.legend.labels.usePointStyle = true;
Chart.defaults.plugins.legend.labels.boxWidth = 8;
Chart.defaults.plugins.tooltip.padding = 10;
Chart.defaults.plugins.tooltip.cornerRadius = 10;
Chart.defaults.plugins.tooltip.titleFont = { weight: '600' };

window.chartTheme = () => ({
    grid: isDark() ? 'rgba(255,255,255,.06)' : 'rgba(16,24,40,.06)',
    text: isDark() ? '#8592a9' : '#66738b',
});

window.money = (v) => 'GH₵ ' + Number(v || 0).toLocaleString('en-GH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

window.makeChart = (el, config) => {
    const t = window.chartTheme();
    const scales = config.options?.scales;
    if (scales) {
        Object.values(scales).forEach((s) => {
            s.grid = { color: t.grid, drawTicks: false, ...(s.grid || {}) };
            s.border = { display: false };
            s.ticks = { color: t.text, padding: 8, ...(s.ticks || {}) };
        });
    }
    config.options = { responsive: true, maintainAspectRatio: false, ...config.options };
    config.options.plugins = { legend: { labels: { color: t.text } }, ...(config.options.plugins || {}) };
    return new Chart(el, config);
};

// Theme toggle (persisted per browser).
window.toggleTheme = () => {
    const dark = document.documentElement.classList.toggle('dark');
    try { localStorage.setItem('theme', dark ? 'dark' : 'light'); } catch (e) {}
};

Alpine.start();
