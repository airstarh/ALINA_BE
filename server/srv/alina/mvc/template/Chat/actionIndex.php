<style>
.wrapper {
    height: 100vh;
    min-height: 0;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    flex-wrap: nowrap;
    justify-content: flex-end;
    align-content: stretch;
    align-items: stretch;

    & #messages {
        flex: 0 1 90vh;
        white-space: pre-wrap;
        word-break: break-word;
        border: 1px solid #ccc;
        padding: 10px;
        overflow-y: auto;
        background-color: #222222;
        color: #dddddd;
    }

    & .user-input {
        flex: 0 0 10vh;
        height: 10vh;
        display: flex;

        & textarea {
            flex: 1 0;
            padding: 3px;
        }

        & button {
            padding: 8px 16px;
            cursor: pointer;
        }
    }
}
</style>
<div class="wrapper">
    <div id="messages"></div>
    <div class="user-input">
        <textarea class="user-input-item" id="input" autocomplete="off" placeholder=""></textarea>
        <button class="user-input-item" id="send-btn"><?= ___('Send') ?></button>
    </div>
</div>
<script>
    <? require(__DIR__ . '/actionIndex.js') ?>
</script>
