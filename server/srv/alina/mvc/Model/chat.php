<?php

declare(strict_types=1);

namespace alina\mvc\Model;

use stdClass;

class chat extends _BaseAlinaModel
{
    public $table = 'chat';

    public function fields(): array
    {
        $now = self::nowMilliseconds();

        return [
            'id' => [],
            'origin' => [],
            'channel_name_pseudo_id' => [],
            'chat_name_human' => [],
            'is_anion_allowed' => ['default' => 1],
            'is_confirmation_required' => ['default' => 0],
            'created_at' => ['default' => $now],
            'updated_at' => ['default' => $now],
        ];
    }

    public function uniqueKeys(): array
    {
        return [
            ['origin', 'channel_name_pseudo_id'],
        ];
    }

    public function findByOriginAndChannel(string $origin, string $channel): ?stdClass
    {
        return $this->q()
            ->where('origin', '=', $origin)
            ->where('channel_name_pseudo_id', '=', $channel)
            ->first();
    }

    public function insertForChannel(string $origin, string $channel): stdClass
    {
        $now = self::nowMilliseconds();
        $id = $this->q(-1)->insertGetId([
            'origin' => $origin,
            'channel_name_pseudo_id' => $channel,
            'chat_name_human' => $channel,
            'is_anion_allowed' => 1,
            'is_confirmation_required' => 0,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->q()->where('id', '=', $id)->first();
    }

    public function rename(int $chatId, string $name, int $updatedAt): stdClass
    {
        $this->q(-1)
            ->where('id', '=', $chatId)
            ->update([
                'chat_name_human' => $name,
                'updated_at' => $updatedAt,
            ]);

        return $this->q()->where('id', '=', $chatId)->first();
    }

    public function setAnonymousAllowed(int $chatId, bool $allowed, int $updatedAt): stdClass
    {
        $this->q(-1)
            ->where('id', '=', $chatId)
            ->update([
                'is_anion_allowed' => $allowed ? 1 : 0,
                'updated_at' => $updatedAt,
            ]);

        return $this->q()->where('id', '=', $chatId)->first();
    }

    public function setConfirmationRequired(int $chatId, bool $required, int $updatedAt): stdClass
    {
        $this->q(-1)
            ->where('id', '=', $chatId)
            ->update([
                'is_confirmation_required' => $required ? 1 : 0,
                'updated_at' => $updatedAt,
            ]);

        return $this->q()->where('id', '=', $chatId)->first();
    }

    public static function nowMilliseconds(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
