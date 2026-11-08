import './bootstrap.js';
import Chart from 'chart.js/auto';

const initialMetrics = window.initialServerMetrics;
const labels = [];
const values = { cpu: [], memory: [], disk: [] };
const colors = { cpu: '#38bdf8', memory: '#a78bfa', disk: '#fbbf24' };

function safe(value) {
    return typeof value === 'number' && Number.isFinite(value) ? Math.max(0, Math.min(100, value)) : null;
}

function makeChart(element, label, color, dataKey) {
    return new Chart(document.getElementById(element), {
        type: 'line',
        data: {
            labels,
            datasets: [{ label, data: values[dataKey], borderColor: color, backgroundColor: color, tension: 0.3, spanGaps: true }],
        },
        options: { responsive: true, maintainAspectRatio: false, animation: false, scales: { y: { min: 0, max: 100 } } },
    });
}

const charts = [
    makeChart('cpu-chart', 'CPU (%)', colors.cpu, 'cpu'),
    makeChart('memory-chart', 'RAM usada (%)', colors.memory, 'memory'),
    makeChart('disk-chart', 'Disco usado (%)', colors.disk, 'disk'),
];

function addPoint(data) {
    const time = typeof data?.at === 'string' ? new Date(data.at) : new Date();
    labels.push(Number.isNaN(time.getTime()) ? '--:--' : time.toLocaleTimeString());
    values.cpu.push(safe(data?.cpu_pct));
    values.memory.push(safe(data?.memory?.used_pct));
    values.disk.push(safe(data?.disk?.used_pct));
    if (labels.length > 20) {
        labels.shift();
        Object.values(values).forEach((series) => series.shift());
    }
    charts.forEach((chart) => chart.update());
}

function status(message, error = false) {
    const element = document.getElementById('status');
    element.textContent = message;
    element.classList.toggle('error', error);
}

addPoint(initialMetrics);
window.Echo.channel('server.metrics').listen('.metrics.updated', (event) => addPoint(event));
window.Echo.connector.pusher.connection.bind('connected', () => status('Tiempo real conectado'));
window.Echo.connector.pusher.connection.bind('disconnected', () => status('Tiempo real desconectado', true));
window.Echo.connector.pusher.connection.bind('unavailable', () => status('Tiempo real desconectado', true));
