-- One child discussion chat per source message.
-- Intentionally no foreign key: ALINA chat relations are managed by application logic.
ALTER TABLE `chat`
    ADD COLUMN `discussion_of_message_id` BIGINT UNSIGNED NULL
        COMMENT 'Source chat_message.id when this chat is a message discussion',
    ADD UNIQUE KEY `uq_chat_discussion_message` (`discussion_of_message_id`);
