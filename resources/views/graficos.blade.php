<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Gráficos del servidor</title>
    @vite(['resources/js/charts.js'])
    <style>
        :root { color-scheme: dark; background: #0f172a; color: #e2e8f0; }
        body { margin: 0; font-family: system-ui, sans-serif; }
        main { width: min(1100px, 92vw); margin: auto; padding: 32px 0; }
        .charts { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 16px; }
        article { height: 330px; padding: 16px; border-radius: 14px; background: #1e293b; }
        #status { display: inline-block; padding: 6px 12px; border-radius: 999px; background: #14532d; }
        #status.error { background: #7f1d1d; }
    </style>
</head>
<body>
<main>
    <header>
        <h1>Gráficos en tiempo real</h1>
        <a href="/servidor">Monitor</a>
        <span id="status">Conectando</span>
    </header>
    <section class="charts">
        <article><canvas id="cpu-chart"></canvas></article>
        <article><canvas id="memory-chart"></canvas></article>
        <article><canvas id="disk-chart"></canvas></article>
    </section>
</main>
<script>
window.initialServerMetrics = @json($metrics);
</script>
</body>
</html>
