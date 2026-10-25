<?php

declare(strict_types=1);

namespace alina\mvc\Model;

class chat_like extends _BaseAlinaModel
{
    public $table = 'chat_like';

    public function fields(): array
    {
        return [
            'id' => [], 'chat_message_id' => [], 'chat_user_id' => [], 'reaction' => [],
            'created_at' => [], 'updated_at' => [],
        ];
    }

    public function uniqueKeys(): array
    {
        return [['chat_message_id', 'chat_user_id']];
    }
}
