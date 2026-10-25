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
            'discussion_of_message_id' => ['default' => null],
            'created_at' => ['default' => $now],
            'updated_at' => ['default' => $now],
        ];
    }

    public function uniqueKeys(): array
    {
        return [
            ['origin', 'channel_name_pseudo_id'],
            ['discussion_of_message_id'],
        ];
    }

    public function findByDiscussionMessageId(int $messageId): ?stdClass
    {
        return $this->q()->where('discussion_of_message_id', '=', $messageId)->first();
    }

    public function insertDiscussion(string $origin, string $channel, string $name, int $messageId): stdClass
    {
        $now = self::nowMilliseconds();
        $id = $this->q(-1)->insertGetId([
            'origin' => $origin,
            'channel_name_pseudo_id' => $channel,
            'chat_name_human' => $name,
            'is_anion_allowed' => 1,
            'is_confirmation_required' => 0,
            'discussion_of_message_id' => $messageId,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $this->q()->where('id', '=', $id)->first();
    }

    public function findByOriginAndChannel(string $origin, string $channel): ?stdClass
    {
        return $this->q()
            ->where('origin', '=', $origin)
            ->where('channel_name_pseudo_id', '=', $channel)
            ->first();
    }

    public function insertForChannel(string $origin, string $channel, string $name, bool $anonymousAllowed = true, bool $confirmationRequired = false): stdClass
    {
        $now = self::nowMilliseconds();
        $id = $this->q(-1)->insertGetId([
            'origin' => $origin,
            'channel_name_pseudo_id' => $channel,
            'chat_name_human' => $name,
            'is_anion_allowed' => (int) $anonymousAllowed,
            'is_confirmation_required' => (int) $confirmationRequired,
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
            ->join('chat_user', 'chat_user.id', '=', 'chat_message.chat_user_id')
            ->select('chat_message.chat_id')
            ->selectRaw("COUNT(DISTINCT CASE WHEN chat_user.user_type != 'bot' THEN chat_message.chat_user_id END) AS participant_count")
            ->selectRaw('MAX(chat_message.created_at) AS latest_message_at')
            ->groupBy('chat_message.chat_id');

        return $this->directoryQuery($origin, $senderType, $senderId, $participated)
            ->leftJoinSub($aggregate, 'message_aggregate', 'message_aggregate.chat_id', '=', 'chat.id')
            ->select([
                'chat.id',
                'chat.channel_name_pseudo_id',
                'chat.chat_name_human',
                'message_aggregate.participant_count',
                'message_aggregate.latest_message_at',
            ])
            ->selectRaw("EXISTS (SELECT 1 FROM chat_message AS mention_message JOIN chat_user AS mention_user ON mention_user.id = mention_message.chat_user_id WHERE mention_message.chat_id = chat.id AND mention_message.sender_type = 'user_service' AND mention_user.user_type = ? AND mention_user.user_id = ? AND mention_message.message_text LIKE 'MENTION:%') AS is_mentioned", [$senderType, $senderId])
            ->selectRaw("(SELECT MAX(mention_message.created_at) FROM chat_message AS mention_message JOIN chat_user AS mention_user ON mention_user.id = mention_message.chat_user_id WHERE mention_message.chat_id = chat.id AND mention_message.sender_type = 'user_service' AND mention_user.user_type = ? AND mention_user.user_id = ? AND mention_message.message_text LIKE 'MENTION:%') AS latest_mention_at", [$senderType, $senderId])
            ->selectRaw("EXISTS (SELECT 1 FROM chat_pinned JOIN chat_user AS pin_user ON pin_user.id = chat_pinned.chat_user_id WHERE chat_pinned.chat_id = chat.id AND pin_user.user_type = 'user' AND pin_user.user_id = ?) AS is_pinned", [$senderId])
            ->orderBy('is_pinned', 'DESC')
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

    public function searchRows(string $origin, string $senderId, string $query, int $limit): array
    {
        $pattern = '%' . addcslashes($query, '%_\\') . '%';
        $aggregate = Dal::table('chat_message')
            ->join('chat_user', 'chat_user.id', '=', 'chat_message.chat_user_id')
            ->select('chat_message.chat_id')
            ->selectRaw("COUNT(DISTINCT CASE WHEN chat_user.user_type != 'bot' THEN chat_message.chat_user_id END) AS participant_count")
            ->selectRaw('MAX(chat_message.created_at) AS latest_message_at')
            ->groupBy('chat_message.chat_id');

        return Dal::table('chat')
            ->leftJoinSub($aggregate, 'message_aggregate', 'message_aggregate.chat_id', '=', 'chat.id')
            ->where('chat.origin', '=', $origin)
            ->whereRaw("chat.chat_name_human LIKE ? ESCAPE '\\\\'", [$pattern])
            ->select([
                'chat.id',
                'chat.channel_name_pseudo_id',
                'chat.chat_name_human',
                'message_aggregate.participant_count',
                'message_aggregate.latest_message_at',
            ])
            ->selectRaw("EXISTS (SELECT 1 FROM chat_pinned JOIN chat_user AS pin_user ON pin_user.id = chat_pinned.chat_user_id WHERE chat_pinned.chat_id = chat.id AND pin_user.user_type = 'user' AND pin_user.user_id = ?) AS is_pinned", [$senderId])
            ->orderBy('is_pinned', 'DESC')
            ->orderByRaw('message_aggregate.latest_message_at IS NULL ASC')
            ->orderBy('message_aggregate.latest_message_at', 'DESC')
            ->orderBy('chat.id', 'DESC')
            ->limit($limit)
            ->get()
            ->all();
    }

    private function directoryQuery(string $origin, string $senderType, string $senderId, bool $participated): Builder
    {
        $query = Dal::table('chat')->where('chat.origin', '=', $origin);
        $participation = static function (Builder $messages) use ($senderType, $senderId): void {
            $messages
                ->selectRaw('1')
                ->from('chat_message AS identity_message')
                ->join('chat_user AS identity_user', 'identity_user.id', '=', 'identity_message.chat_user_id')
                ->whereColumn('identity_message.chat_id', 'chat.id')
                ->whereIn('identity_message.sender_type', $senderType === 'user' ? ['user', 'user_service'] : ['guest', 'user_service'])
                ->where('identity_user.user_type', '=', $senderType)
                ->where('identity_user.user_id', '=', $senderId);
        };
        $pin = static function (Builder $pins) use ($senderType, $senderId): void {
            $pins->selectRaw('1')->from('chat_pinned')
                ->join('chat_user AS pinned_user', 'pinned_user.id', '=', 'chat_pinned.chat_user_id')
                ->whereColumn('chat_pinned.chat_id', 'chat.id')
                ->where('pinned_user.user_type', '=', $senderType)
                ->where('pinned_user.user_id', '=', $senderId);
        };
        if ($senderType === 'user') {
            return $participated
                ? $query->where(fn (Builder $mine) => $mine->whereExists($participation)->orWhereExists($pin))
                : $query->whereNotExists($participation)->whereNotExists($pin);
        }
        return $participated ? $query->whereExists($participation) : $query->whereNotExists($participation);
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
