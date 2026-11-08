<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Monitor de servidor</title>
    @vite(['resources/js/app.js'])
    <style>
        :root { color-scheme: dark; --bg: #0f172a; --panel: #1e293b; --text: #e2e8f0; --accent: #38bdf8; --muted: #94a3b8; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; background: var(--bg); color: var(--text); font-family: system-ui, sans-serif; }
        main { width: min(1100px, 92vw); margin: 0 auto; padding: 32px 0; }
        header { display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap; }
        .status { padding: 6px 12px; border-radius: 999px; background: #14532d; }
        .status.error { background: #7f1d1d; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-top: 24px; }
        article { background: var(--panel); border-radius: 14px; padding: 18px; min-height: 155px; }
        h1, h2, p { margin-top: 0; }
        h2 { font-size: 1rem; color: var(--muted); }
        .value { font-size: 2rem; font-weight: 700; }
        .bar { height: 10px; overflow: hidden; border-radius: 999px; background: #334155; margin-top: 14px; }
        .bar > span { display: block; height: 100%; width: 0; background: var(--accent); transition: width .4s; }
        dl { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin: 12px 0 0; }
        dt { color: var(--muted); } dd { margin: 0; text-align: right; }
    </style>
</head>
<body>
<main>
    <header>
        <h1>Monitor de servidor</h1>
        <span id="status" class="status">Conectado</span>
    </header>
    <nav><a href="/servidor">Monitor</a> · <a href="/graficos">Gráficos</a></nav>
    <section>
        <button data-section="inicio">Inicio</button>
        <button data-section="reportes">Reportes</button>
        <button data-section="configuracion">Configuración</button>
        <button data-section="usuarios">Usuarios</button>
        <span id="activity-status"></span>
    </section>
    <section class="grid" aria-live="polite">
        <article>
            <h2>CPU</h2>
            <p class="value" id="cpu">No disponible</p>
            <div class="bar"><span id="cpu-bar"></span></div>
        </article>
        <article>
            <h2>Memoria RAM</h2>
            <p class="value" id="memory">No disponible</p>
            <div class="bar"><span id="memory-bar"></span></div>
            <dl><dt>Total</dt><dd id="memory-total">No disponible</dd><dt>Libre</dt><dd id="memory-free">No disponible</dd></dl>
        </article>
        <article>
            <h2>Disco</h2>
            <p class="value" id="disk">No disponible</p>
            <div class="bar"><span id="disk-bar"></span></div>
            <dl><dt>Total</dt><dd id="disk-total">No disponible</dd><dt>Libre</dt><dd id="disk-free">No disponible</dd></dl>
        </article>
        <article>
            <h2>Tiempo de actividad</h2>
            <p class="value" id="uptime">No disponible</p>
        </article>
        <article>
            <h2>Información general</h2>
            <dl>
                <dt>Equipo</dt><dd id="hostname">No disponible</dd>
                <dt>Sistema</dt><dd id="os">No disponible</dd>
                <dt>PHP</dt><dd id="php">No disponible</dd>
                <dt>Actualizado</dt><dd id="updated">No disponible</dd>
            </dl>
        </article>
    </section>
</main>
<script>
const initialMetrics = @json($metrics);

function validNumber(value) {
    return typeof value === 'number' && Number.isFinite(value);
}

function textNumber(value, suffix = '') {
    return validNumber(value) ? `${value}${suffix}` : 'No disponible';
}

function setBar(id, value) {
    document.getElementById(id).style.width = `${validNumber(value) ? Math.max(0, Math.min(100, value)) : 0}%`;
}

function formatUptime(seconds) {
    if (!validNumber(seconds) || seconds < 0) return 'No disponible';
    const days = Math.floor(seconds / 86400);
    const hours = Math.floor((seconds % 86400) / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return `${days} d ${hours} h ${minutes} min`;
}

function render(data) {
    if (!data || typeof data !== 'object') return;
    document.getElementById('cpu').textContent = textNumber(data.cpu_pct, '%');
    setBar('cpu-bar', data.cpu_pct);

    const memory = data.memory && typeof data.memory === 'object' ? data.memory : {};
    document.getElementById('memory').textContent = textNumber(memory.used_pct, '%');
    document.getElementById('memory-total').textContent = textNumber(memory.total_gb, ' GB');
    document.getElementById('memory-free').textContent = textNumber(memory.free_gb, ' GB');
    setBar('memory-bar', memory.used_pct);

    const disk = data.disk && typeof data.disk === 'object' ? data.disk : {};
    document.getElementById('disk').textContent = textNumber(disk.used_pct, '%');
    document.getElementById('disk-total').textContent = textNumber(disk.total_gb, ' GB');
    document.getElementById('disk-free').textContent = textNumber(disk.free_gb, ' GB');
    setBar('disk-bar', disk.used_pct);

    document.getElementById('uptime').textContent = formatUptime(data.uptime_s);
    document.getElementById('hostname').textContent = typeof data.hostname === 'string' && data.hostname ? data.hostname : 'No disponible';
    document.getElementById('os').textContent = typeof data.os === 'string' && data.os ? data.os : 'No disponible';
    document.getElementById('php').textContent = typeof data.php_version === 'string' && data.php_version ? data.php_version : 'No disponible';
    document.getElementById('updated').textContent = typeof data.at === 'string' && data.at ? new Date(data.at).toLocaleString() : 'No disponible';
}

async function refreshMetrics() {
    try {
        const response = await fetch('/api/metrics', { headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        render(data);
        const status = document.getElementById('status');
        status.textContent = 'Conectado';
        status.classList.remove('error');
    } catch (error) {
        const status = document.getElementById('status');
        status.textContent = 'Error de actualización';
        status.classList.add('error');
    }
}

render(initialMetrics);
let pollingId = window.setInterval(refreshMetrics, 5000);

function setRealtimeStatus(message, error = false) {
    const status = document.getElementById('status');
    status.textContent = message;
    status.classList.toggle('error', error);
}

function startPolling() {
    if (!pollingId) pollingId = window.setInterval(refreshMetrics, 5000);
}

function stopPolling() {
    if (pollingId) window.clearInterval(pollingId);
    pollingId = null;
}

if (window.Echo) {
    window.Echo.channel('server.metrics').listen('.metrics.updated', (event) => {
        render(event);
        setRealtimeStatus('Tiempo real conectado');
    });
    window.Echo.connector.pusher.connection.bind('connected', () => {
        stopPolling();
        setRealtimeStatus('Tiempo real conectado');
    });
    window.Echo.connector.pusher.connection.bind('disconnected', () => {
        startPolling();
        setRealtimeStatus('Tiempo real desconectado; usando respaldo', true);
    });
    window.Echo.connector.pusher.connection.bind('unavailable', () => {
        startPolling();
        setRealtimeStatus('Tiempo real desconectado; usando respaldo', true);
    });
}

document.querySelectorAll('[data-section]').forEach((button) => {
    button.addEventListener('click', async () => {
        const result = document.getElementById('activity-status');
        try {
            const response = await fetch('/demo/open', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ section: button.dataset.section }),
            });
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            result.textContent = 'Actividad registrada';
        } catch {
            result.textContent = 'No se pudo registrar la actividad';
        }
    });
});
</script>
</body>
</html>
