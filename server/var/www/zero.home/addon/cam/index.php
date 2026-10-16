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
    <link rel="stylesheet" href="./stuff/css/app.css?v=20261016-2">
</head>
<body>
<header class="app-header">
    <div>
        <p class="eyebrow">ALINA · local camera tools</p>
        <h1><button id="home-button" class="home-button" type="button">Borg Tech Service</button></h1>
    </div>
    <form id="camera-form" class="camera-picker">
        <select id="camera-select" name="camera" aria-describedby="connection-status"></select>
        <button id="connect-button" type="submit">Connect</button>
        <button id="reboot-button" class="danger-button" type="button" disabled>Reboot camera</button>
    </form>
</header>

<main>
    <p id="connection-status" class="connection-status" role="status" aria-live="polite">
        Loading camera list…
    </p>

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
</main>

<script type="module" src="./stuff/js/app.js?v=20261016-2"></script>
</body>
</html>
