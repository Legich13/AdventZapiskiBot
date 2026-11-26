<?php

declare(strict_types=1);

namespace App\Documentation;

use App\Http\Requests\Calendar\SaveCalendarRequest;
use App\Http\Requests\TelegramWebhookRequest;
use Dedoc\Scramble\Support\Generator\Combined\AnyOf;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Schema;
use Dedoc\Scramble\Support\Generator\Types\ObjectType;
use Dedoc\Scramble\Support\Generator\Types\StringType;
use LogicException;

/** Дополняет извлечённые из Form Request поля условиями exclude_unless и required_with. */
final class ConditionalRequestSchemas
{
    public function __invoke(OpenApi $api): void
    {
        $calendar = $api->components->schemas[SaveCalendarRequest::class] ?? null;
        $webhook = $api->components->schemas[TelegramWebhookRequest::class] ?? null;
        if (! $calendar instanceof Schema || ! $calendar->type instanceof ObjectType
            || ! $webhook instanceof Schema || ! $webhook->type instanceof ObjectType) {
            throw new LogicException('Схемы запросов календаря и Telegram должны быть объектами.');
        }

        $friend = $calendar->type;
        $self = $friend->clone();
        unset($self->properties['username']);
        $self->setRequired(array_values(array_diff($self->required, ['username'])));
        $self->addProperty('target', (new StringType)->const('self'));
        $friend->addProperty('target', (new StringType)->const('username'));
        $targets = new AnyOf;
        $targets->setItems([$self, $friend]);
        $calendar->type = $targets;

        $query = $webhook->type->properties['widget_query'] ?? null;
        $sender = $query instanceof ObjectType ? ($query->properties['from'] ?? null) : null;
        if (! $query instanceof ObjectType || ! $sender instanceof ObjectType) {
            throw new LogicException('Запрос виджета должен описывать отправителя.');
        }
        $query->setRequired(['id', 'from', 'data']);
        $sender->setRequired(['id']);
    }
}
