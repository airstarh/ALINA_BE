<style>
    <? require __DIR__ . '/actionIndex.css' ?>
</style>

<div class="wrapper">
    <header class="chat-header">
        <div><a href="/" class="home">🏚</a></div>
        <h1><?= AlinaCfg('title') ?></h1>
        <div><a href="" class="reload">⟳</a></div>
    </header>

    <div class="main-content">
        <div id="messages"></div>

        <div class="user-input">
            <textarea class="user-input-item" id="input" autocomplete="off" placeholder="Type a message..."></textarea>
            <button class="user-input-item" id="send-btn"><?= ___('Send') ?></button>
        </div>
    </div>
</div>

<script>
<? require __DIR__ . '/actionIndex.js' ?>
</script>
