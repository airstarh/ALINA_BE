ALTER TABLE `chat_message`
    ADD COLUMN `sender_emblem` VARCHAR(2048) NOT NULL DEFAULT '' AFTER `sender_name`;
