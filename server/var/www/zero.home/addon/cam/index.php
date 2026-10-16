<?php

declare(strict_types=1);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="color-scheme" content="dark">
    <title>Borg Tech Service</title>
    <link rel="stylesheet" href="./stuff/css/app.css?v=20261016-6">
</head>
<body>
<header class="app-header">
    <h1><button id="home-button" class="home-button" type="button">Borg Tech Service</button></h1>
    <form id="camera-form" class="camera-picker">
        <select id="camera-select" name="camera" aria-describedby="connection-status"></select>
        <button id="connect-button" type="submit" aria-label="Connect selected camera" title="Connect selected camera">✔</button>
        <button id="reboot-button" class="danger-button" type="button" aria-label="Reboot selected camera" title="Reboot selected camera" disabled>🗘</button>
    </form>
</header>

<main>
    <section id="dashboard" class="dashboard" hidden>
        <button
            id="sidebar-toggle"
            class="sidebar-toggle"
            type="button"
            aria-controls="camera-sidebar"
            aria-expanded="false"
            hidden
        >Show menu</button>

        <aside id="camera-sidebar" class="sidebar">
            <section class="panel identity-panel" aria-labelledby="identity-heading">
                <h2 id="identity-heading">Device</h2>
                <div id="camera-identity"></div>
            </section>

            <nav class="panel" aria-labelledby="sections-heading">
                <div class="panel-heading">
                    <h2 id="sections-heading">Configuration</h2>
                    <button id="load-all" class="quiet-button" type="button">Load all</button>
                </div>
                <div id="section-list" class="section-list"></div>
            </nav>
        </aside>

        <section class="panel content-panel" aria-labelledby="section-title">
            <div class="panel-heading">
                <h2 id="section-title">Choose a section</h2>
                <button id="refresh-section" class="quiet-button" type="button" disabled>Refresh</button>
            </div>
            <div id="section-content" class="section-content">
                Choose a configuration section from the left.
            </div>
        </section>
    </section>

    <section class="app-status-panel" aria-label="Application status">
        <p id="connection-status" class="connection-status" role="status" aria-live="polite">
            App: Loading camera list…
        </p>
        <div id="operation-statuses" class="operation-statuses" aria-live="polite"></div>
    </section>
</main>

<script type="module" src="./stuff/js/app.js?v=20261016-5"></script>
</body>
</html>
