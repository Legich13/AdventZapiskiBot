<?php

declare(strict_types=1);

use App\Models\TelegramUser;
use App\Models\User;
use BezhanSalleh\FilamentShield\Resources\Roles\Pages\EditRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Livewire\Livewire;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Laravel\getJson;

use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('справочник проекта и контракт API открываются только сотруднику', function (): void {
    get('/admin/docs')->assertRedirect();
    getJson('/api/docs/openapi.json')->assertForbidden();
    $staff = User::factory()->create();
    actingAs($staff)->get('/admin/docs')->assertForbidden();
    $staff->assignRole(Role::findOrCreate('support', 'web'));
    get('/admin/docs')->assertOk()->assertSee('Документация')->assertSee('Доменные модели');
    get('/scalar')->assertOk()->assertSee('scalar', false);
    get('/admin/docs?document=../../.env')->assertNotFound();
    $result = getJson('/api/docs/openapi.json')->assertOk();
    $result->assertJsonPath('paths./app/calendars/{calendar}/publish.post.responses.422.$ref', '#/components/responses/ValidationException');
    foreach ([
        '/app/session' => ['post', 'miniAppSession'],
        '/app/calendars' => ['post', 'saveCalendarDraft'],
        '/app/calendars/{calendar}' => ['put', 'updateCalendar'],
        '/app/calendars/{calendar}/publish' => ['post', 'publishCalendar'],
        '/telegram/webhook' => ['post', 'receiveTelegramUpdate'],
    ] as $path => [$method, $operation]) {
        expect($result->json("paths.$path.$method.operationId"))->toBe($operation);
    }
});

test('администратор выдаёт и отзывает права на страницы через настоящую форму Shield', function (): void {
    $admin = User::factory()->create();
    $admin->assignRole(Role::findOrCreate('super_admin', 'web'));
    $role = Role::findOrCreate('morning_shift', 'web');
    $staff = User::factory()->create();
    $staff->assignRole(Role::findOrCreate('support', 'web'));
    $password = $staff->password;
    expect(Artisan::call('advent:staff-role', ['email' => $staff->email, 'role' => 'morning_shift']))->toBe(0);
    expect($staff->refresh()->password)->toBe($password);
    expect($staff->getRoleNames()->all())->toBe(['morning_shift']);
    actingAs($admin)->get('/admin/shield/roles/'.$role->id.'/edit')
        ->assertOk()->assertSee('View:Overview', false)->assertSee('View:Handbook', false);

    /** @param list<string> $permissions */
    $setPermissions = static function (array $permissions) use ($admin, $role): void {
        actingAs($admin);
        Livewire::test(EditRole::class, ['record' => $role->id])
            ->set('data.pages_tab', $permissions)->call('save')->assertHasNoErrors();
    };

    $setPermissions(['View:Overview']);
    actingAs($staff->refresh())->get('/admin')->assertOk();
    get('/admin/docs')->assertForbidden();
    get('/scalar')->assertForbidden();
    getJson('/api/docs/openapi.json')->assertForbidden();
    get('/admin/shield/roles')->assertForbidden();
    get('/admin/shield/roles/'.$role->id.'/edit')->assertForbidden();

    $setPermissions(['View:Handbook']);
    actingAs($staff->refresh())->get('/admin')->assertForbidden();
    get('/admin/docs')->assertOk();
    get('/scalar')->assertOk();
    getJson('/api/docs/openapi.json')->assertOk();
    get('/admin/shield/roles/create')->assertForbidden();

    $setPermissions([]);
    actingAs($staff->refresh())->get('/admin/docs')->assertForbidden();
    get('/scalar')->assertForbidden();
    getJson('/api/docs/openapi.json')->assertForbidden();
});

test('учётная запись предпросмотра не получает доступ к справке за пределами local', function (): void {
    $preview = User::factory()->create();
    $preview->assignRole(Role::findOrCreate('local_preview', 'web'));
    app()->detectEnvironment(fn (): string => 'local');
    actingAs($preview)->get('/admin')->assertOk();
    get('/admin/docs')->assertOk();
    get('/admin/shield/roles')->assertForbidden();
    app()->detectEnvironment(fn (): string => 'production');
    get('/admin')->assertForbidden();
    get('/admin/docs')->assertForbidden();
    get('/scalar')->assertForbidden();
    getJson('/api/docs/openapi.json')->assertForbidden();
});

test('назначение неизвестной роли не меняет права и пароль сотрудника', function (): void {
    $staff = User::factory()->create();
    $staff->assignRole(Role::findOrCreate('support', 'web'));
    $password = $staff->password;
    expect(Artisan::call('advent:staff-role', ['email' => $staff->email, 'role' => 'absent-role']))->toBe(1);
    expect($staff->refresh()->password)->toBe($password);
    expect($staff->getRoleNames()->all())->toBe(['support']);
    expect(Role::where('name', 'absent-role')->exists())->toBeFalse();
});

test('локальная учётная запись и её роль зарезервированы за мастерской', function (): void {
    $staff = User::factory()->create()->assignRole('support');
    $preview = User::factory()->create()->assignRole('local_preview');
    expect(Artisan::call('advent:staff-role', ['email' => $preview->email, 'role' => 'super_admin']))->toBe(1);
    expect(Artisan::call('advent:staff-role', ['email' => $staff->email, 'role' => 'local_preview']))->toBe(1);
    expect($preview->refresh()->getRoleNames()->all())->toBe(['local_preview']);
    expect($staff->refresh()->getRoleNames()->all())->toBe(['support']);
});

test('документация открывает опубликованные ссылки на архитектуру, руководство и защищённый контракт', function (): void {
    $staff = User::factory()->create();
    $staff->assignRole(Role::findByName('support', 'web'));
    actingAs($staff);
    foreach ([
        ['backend/ARCHITECTURE.md', 'корневая архитектура', 'ARCH-CALENDAR-001'],
        ['COMMANDS.md', 'README', 'Локальный запуск'],
        ['backend/API_CONTRACT.md', 'снимке OpenAPI', 'saveCalendarDraft'],
    ] as [$chapter, $linkText, $expected]) {
        $response = get('/admin/docs?document='.rawurlencode($chapter))->assertOk();
        $html = (string) $response->getContent();
        if (! preg_match('~<a href="([^"]+)">'.preg_quote($linkText, '~').'</a>~u', $html, $match)) {
            throw new RuntimeException('Ссылка в тексте главы отсутствует: '.$linkText);
        }
        $href = html_entity_decode($match[1]);
        $target = str_starts_with($href, 'http') ? $href : url('/admin/'.$href);
        get($target)->assertOk()->assertSee($expected);
    }
    get('/admin/docs?document=project%2F.env')->assertNotFound();
});

test('документация отражает условные обязательные поля редактора и запроса виджета', function (): void {
    TelegramUser::factory()->create(['id' => 101]);
    Pest\Laravel\withSession(['telegram_id' => 101])->postJson('/app/calendars', ['month' => '2027-01', 'target' => 'self', 'days' => []])->assertCreated();
    Pest\Laravel\postJson('/app/calendars', ['month' => '2027-01', 'target' => 'username', 'days' => []])->assertUnprocessable()->assertJsonValidationErrors('username');
    config(['services.telegram.webhook_secret' => 'doc-test-secret']);
    Pest\Laravel\withHeader('X-Telegram-Bot-Api-Secret-Token', 'doc-test-secret')->postJson('/telegram/webhook', ['update_id' => 1])->assertNoContent();
    Pest\Laravel\postJson('/telegram/webhook', ['update_id' => 2, 'widget_query' => ['id' => 'q']])->assertUnprocessable()->assertJsonValidationErrors(['widget_query.from.id', 'widget_query.data']);
    Pest\Laravel\postJson('/telegram/webhook', ['update_id' => 3, 'widget_query' => []])->assertUnprocessable();

    $staff = User::factory()->create();
    $staff->assignRole(Role::findByName('support', 'web'));
    actingAs($staff);
    $spec = getJson('/api/docs/openapi.json')->assertOk();
    $spec->assertJsonPath('components.schemas.SaveCalendarRequest.anyOf.0.properties.target.const', 'self');
    $spec->assertJsonPath('components.schemas.SaveCalendarRequest.anyOf.1.properties.target.const', 'username');
    expect($spec->json('components.schemas.SaveCalendarRequest.anyOf.0.required'))->toBe(['month', 'target', 'days']);
    expect($spec->json('components.schemas.SaveCalendarRequest.anyOf.1.required'))->toContain('username');
    expect($spec->json('components.schemas.TelegramWebhookRequest.properties.widget_query.required'))->toBe(['id', 'from', 'data']);
    expect($spec->json('components.schemas.TelegramWebhookRequest.properties.widget_query.properties.from.required'))->toBe(['id']);
});
