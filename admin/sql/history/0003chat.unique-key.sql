ALTER TABLE chat_message
    DROP INDEX uq_chat_message_client_id,
    ADD UNIQUE KEY uq_chat_message_group_sender (
        chat_id,
        client_message_id,
        sender_type,
        sender_id
    );
