<?php

declare(strict_types=1);

namespace alina\mvc\Model;

use stdClass;

class chat_user extends _BaseAlinaModel
{
    public $table = 'chat_user';

    public function fields(): array
    {
        return [
            'id' => [],
            'user_type' => [],
            'user_id' => [],
            'user_name' => [],
            'user_emblem' => [],
            'created_at' => ['default' => self::nowMilliseconds()],
            'updated_at' => ['default' => self::nowMilliseconds()],
        ];
    }

    public function upsertIdentity(string $type, string $id, string $name, string $emblem, int $updatedAt): stdClass
    {
        $identity = ['user_type' => $type, 'user_id' => $id];
        $this->q(-1)->insertOrIgnore($identity + [
            'user_name' => $name,
            'user_emblem' => $emblem,
            'created_at' => $updatedAt,
            'updated_at' => $updatedAt,
        ]);
        $this->q(-1)->where($identity)->update([
            'user_name' => $name,
            'user_emblem' => $emblem,
            'updated_at' => $updatedAt,
        ]);

        return $this->q()
            ->where('user_type', '=', $type)
            ->where('user_id', '=', $id)
            ->first();
    }

    public function rename(string $type, string $id, string $name, int $updatedAt): int
    {
        return $this->q(-1)
            ->where('user_type', '=', $type)
            ->where('user_id', '=', $id)
            ->update(['user_name' => $name, 'updated_at' => $updatedAt]);
    }

    public static function nowMilliseconds(): int
    {
        return (int) floor(microtime(true) * 1000);
    }
}
