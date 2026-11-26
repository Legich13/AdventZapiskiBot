<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;

/** @return list<string> */
function adventApplicationFiles(): array
{
    $files = [];
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(dirname(__DIR__, 3).'/app', FilesystemIterator::SKIP_DOTS));
    foreach ($iterator as $file) {
        if ($file instanceof SplFileInfo && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }
    sort($files);

    return $files;
}

test('код приложения использует строгие типы PHP', function (): void {
    $violations = [];
    foreach (adventApplicationFiles() as $file) {
        if (preg_match('/\bdeclare\(strict_types=1\);/', (string) file_get_contents($file)) !== 1) {
            $violations[] = basename($file);
        }
    }

    expect($violations)->toBe([]);
});

test('прикладные и транспортные слои обращаются к данным через репозитории', function (): void {
    $parser = (new ParserFactory)->createForHostVersion();
    $traverser = new NodeTraverser(new NameResolver);
    $finder = new NodeFinder;
    $violations = [];

    foreach (adventApplicationFiles() as $file) {
        // Исключения ARCH-BE-011: модели задают связи, эти две служебные команды готовят локальную среду и доступ сотрудника.
        if (str_contains($file, '/Infrastructure/Persistence/') || str_contains($file, '/app/Models/')
            || in_array(basename($file), ['CreateStaff.php', 'SeedDemo.php'], true)) {
            continue;
        }
        $nodes = $traverser->traverse($parser->parse((string) file_get_contents($file)) ?? []);
        foreach ($finder->findInstanceOf($nodes, Node\Expr\StaticCall::class) as $call) {
            if (! $call->class instanceof Node\Name || ! $call->name instanceof Node\Identifier) {
                continue;
            }
            $type = $call->class->toString();
            $method = $call->name->toString();
            if ($type === DB::class && str_contains($file, '/Actions/') && in_array($method, ['transaction', 'afterCommit'], true)) {
                continue;
            }
            if ($type === DB::class || (str_starts_with($type, 'App\\Models\\') && is_subclass_of($type, Model::class))) {
                $violations[] = basename($file).':'.$call->getStartLine().' '.$type.'::'.$method;
            }
        }
    }

    expect($violations)->toBe([]);
});

test('DTO модуля неизменяемы, типизированы и названы по контракту Jobgram', function (): void {
    $classes = [];
    foreach (adventApplicationFiles() as $file) {
        if (! str_contains($file, '/DTO/')) {
            continue;
        }
        $class = 'App\\'.str_replace('/', '\\', substr($file, strlen(dirname(__DIR__, 3).'/app/'), -4));
        if (! class_exists($class)) {
            throw new LogicException("Класс DTO не найден: $class");
        }
        $dto = new ReflectionClass($class);
        expect($dto->getShortName())->toEndWith('DTO')
            ->and($dto->isFinal())->toBeTrue()
            ->and($dto->isReadOnly())->toBeTrue();
        foreach ($dto->getProperties() as $property) {
            expect($property->hasType())->toBeTrue();
            $type = (string) $property->getType();
            expect($type)->not->toContain('Illuminate\\Database\\Eloquent', 'Illuminate\\Http', Collection::class, 'App\\Models');
        }
        $classes[] = $class;
    }
    expect($classes)->not->toBeEmpty();
});

test('у моделей есть русское описание, типизированные поля и фабрики', function (): void {
    foreach (adventApplicationFiles() as $file) {
        if (! str_contains($file, '/app/Models/')) {
            continue;
        }
        $class = 'App\\Models\\'.basename($file, '.php');
        if (! is_subclass_of($class, Model::class)) {
            throw new LogicException("Некорректная модель: $class");
        }
        $reflection = new ReflectionClass($class);
        $model = $reflection->newInstanceWithoutConstructor();
        $sections = ['Название модели', 'Данная Model предназначена', 'Бизнес-процесс', 'Источник проверки', 'Жизненный цикл', 'Таблица  ===> '.$model->getTable(), 'Ниже описаны ключи', 'Связи модели', '@property'];
        foreach ($sections as $section) {
            expect($reflection->getDocComment())->toContain($section);
        }
        expect(class_exists('Database\\Factories\\'.$reflection->getShortName().'Factory'))->toBeTrue();
    }
});

test('документация связывает реализацию календаря с источниками и тестами', function (): void {
    $docs = dirname(__DIR__, 4).'/docs/backend';
    foreach (['README', 'ARCHITECTURE', 'API_CONTRACT', 'TESTING', 'domain-models', 'model-business-evidence', 'repositories', 'seeders-and-factories'] as $document) {
        expect(is_readable($docs.'/'.$document.'.md'))->toBeTrue();
    }
    $guide = (string) file_get_contents($docs.'/README.md');
    expect($guide)->toContain('domain-models.md', 'model-business-evidence.md', 'repositories.md', 'seeders-and-factories.md');
});

test('сценарии ссылаются на существующие операции и канонические документы', function (): void {
    $docs = dirname(__DIR__, 4).'/docs';
    /** @var array{paths: array<string,array<string,array{operationId?:string}>>} $schema */
    $schema = json_decode((string) file_get_contents($docs.'/contracts/openapi.json'), true, flags: JSON_THROW_ON_ERROR);
    $operations = [];
    foreach ($schema['paths'] as $path) {
        foreach ($path as $operation) {
            if (isset($operation['operationId'])) {
                $operations[] = $operation['operationId'];
            }
        }
    }

    $flows = glob($docs.'/flows/*.md') ?: [];
    expect($flows)->not->toBeEmpty();
    foreach ($flows as $file) {
        $text = (string) file_get_contents($file);
        foreach (['Брифинг', 'Для пользователя', 'Основной сценарий', 'Альтернативные сценарии', 'Бизнес-правила', 'Чеклист тестирования', 'Зона риска', 'Связанные API', 'Логи и следы', 'Ошибки', 'Готово, когда'] as $heading) {
            expect($text)->toContain('## '.$heading);
        }
        preg_match('/^## Связанные API\R+(.*?)(?=^## )/ms', $text, $api);
        preg_match_all('/\b[a-z]+(?:[A-Z][A-Za-z]+)+\b/', $api[1] ?? '', $ids);
        foreach (array_diff($ids[0], ['answerWidgetQuery']) as $id) {
            expect($operations)->toContain($id);
        }
    }

    foreach (array_merge($flows, glob($docs.'/backend/*.md') ?: []) as $file) {
        preg_match_all('/\]\(([^)#]+)(?:#[^)]*)?\)/', (string) file_get_contents($file), $links);
        foreach ($links[1] as $href) {
            if (! str_contains($href, '://')) {
                expect(is_file(dirname($file).'/'.$href))->toBeTrue();
            }
        }
    }
});
