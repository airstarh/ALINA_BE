<?php

declare(strict_types=1);

namespace alina\mvc\Model;

use Illuminate\Database\Capsule\Manager as Dal;
use Illuminate\Database\Query\Builder;
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

    public function directoryRows(
        string $origin,
        string $senderType,
        string $senderId,
        bool $participated,
        int $limit,
        int $offset,
    ): array {
        $aggregate = Dal::table('chat_message')
            ->select('chat_id')
            ->selectRaw("COUNT(DISTINCT CASE WHEN sender_type != 'bot' THEN sender_id END) AS participant_count")
            ->selectRaw('MAX(created_at) AS latest_message_at')
            ->groupBy('chat_id');

        return $this->directoryQuery($origin, $senderType, $senderId, $participated)
            ->leftJoinSub($aggregate, 'message_aggregate', 'message_aggregate.chat_id', '=', 'chat.id')
            ->select([
                'chat.id',
                'chat.channel_name_pseudo_id',
                'chat.chat_name_human',
                'message_aggregate.participant_count',
                'message_aggregate.latest_message_at',
            ])
            ->orderByRaw('message_aggregate.latest_message_at IS NULL ASC')
            ->orderBy('message_aggregate.latest_message_at', 'DESC')
            ->orderBy('chat.id', 'DESC')
            ->limit($limit)
            ->offset($offset)
            ->get()
            ->all();
    }

    public function directoryCount(string $origin, string $senderType, string $senderId, bool $participated): int
    {
        return $this->directoryQuery($origin, $senderType, $senderId, $participated)->count();
    }

    private function directoryQuery(string $origin, string $senderType, string $senderId, bool $participated): Builder
    {
        $query = Dal::table('chat')->where('chat.origin', '=', $origin);
        $method = $participated ? 'whereExists' : 'whereNotExists';

        return $query->{$method}(static function (Builder $messages) use ($senderType, $senderId): void {
            $messages
                ->selectRaw('1')
                ->from('chat_message AS identity_message')
                ->whereColumn('identity_message.chat_id', 'chat.id')
                ->whereIn('identity_message.sender_type', $senderType === 'user' ? ['user', 'user_service'] : ['guest', 'user_service'])
                ->where('identity_message.sender_id', '=', $senderId);
        });
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
