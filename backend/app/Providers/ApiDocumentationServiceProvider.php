<?php

declare(strict_types=1);

namespace App\Providers;

use App\Admin\StaffPermission;
use App\Documentation\ConditionalRequestSchemas;
use App\Models\User;
use Dedoc\Scramble\Scramble;
use Dedoc\Scramble\Support\Generator\OpenApi;
use Dedoc\Scramble\Support\Generator\Operation;
use Dedoc\Scramble\Support\Generator\Response;
use Dedoc\Scramble\Support\Generator\SecurityRequirement;
use Dedoc\Scramble\Support\Generator\SecuritySchemes\ApiKeySecurityScheme;
use Dedoc\Scramble\Support\Generator\Server;
use Dedoc\Scramble\Support\RouteInfo;
use Filament\Facades\Filament;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

final class ApiDocumentationServiceProvider extends ServiceProvider
{
    private const array OPERATIONS = [
        'app/session' => ['miniAppSession', 'Войти в редактор через Telegram'],
        'app/calendars' => ['saveCalendarDraft', 'Сохранить новый черновик'],
        'app/calendars/{calendar}' => ['updateCalendar', 'Сохранить изменения календаря'],
        'app/calendars/{calendar}/publish' => ['publishCalendar', 'Опубликовать подготовленный календарь'],
        'telegram/webhook' => ['receiveTelegramUpdate', 'Обработать событие бота'],
    ];

    public function register(): void
    {
        Scramble::ignoreDefaultRoutes();
    }

    public function boot(): void
    {
        Gate::define('viewApiDocs', static function (User $user): bool {
            $panel = Filament::getPanel('admin');

            return $panel !== null && $user->canAccessPanel($panel) && $user->can(StaffPermission::Handbook->value);
        });
        $config = Scramble::configure();
        $config->routes(static fn (Route $route): bool => isset(self::OPERATIONS[$route->uri()]));
        $config->withDocumentTransformers([ConditionalRequestSchemas::class]);
        $config->withDocumentTransformers(static function (OpenApi $api): void {
            $api->servers = [new Server('/')];
            $api->components->addSecurityScheme('editorSession', new ApiKeySecurityScheme('cookie', Config::string('session.cookie')));
            $api->components->addSecurityScheme('csrf', new ApiKeySecurityScheme('header', 'X-CSRF-TOKEN'));
            $api->components->addSecurityScheme('telegramWebhook', new ApiKeySecurityScheme('header', 'X-Telegram-Bot-Api-Secret-Token'));
        })
            ->withOperationTransformers(static function (Operation $operation, RouteInfo $route): void {
                $uri = $route->route->uri();
                [$id,$summary] = self::OPERATIONS[$uri];
                $operation->setOperationId($id);
                $operation->summary = $summary;
                $operation->security = [new SecurityRequirement(match ($uri) {
                    'telegram/webhook' => ['telegramWebhook' => []],
                    'app/session' => ['csrf' => []],
                    default => ['editorSession' => [], 'csrf' => []],
                })];
                if ($uri !== 'telegram/webhook') {
                    $operation->addResponse(Response::make(429)->setDescription('Лимит частоты запросов исчерпан.'));
                    $operation->addResponse(Response::make(401)->setDescription('Требуется подтверждённая сессия Mini App или действительная подпись Telegram.'));
                    if (str_starts_with($uri, 'app/calendars/{calendar}')) {
                        $operation->addResponse(Response::make(409)->setDescription('Месяц получателя уже занят.'));
                    }
                } else {
                    $operation->addResponse(Response::make(403)->setDescription('Неверный секрет webhook.'));
                }
            })
            ->expose(ui: '/scalar', document: '/api/docs/openapi.json');
    }
}
