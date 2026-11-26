<?php

declare(strict_types=1);

namespace App\Documentation;

use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

/** Отображает проверяемые источники справочника по их публичным идентификаторам. */
final class ProjectHandbook
{
    /** @return array<string,string> идентификатор главы → заголовок */
    public function chapters(): array
    {
        $chapters = [];
        foreach ($this->sources() as $id => $path) {
            if (preg_match('/^#\s+(.+)$/m', (string) file_get_contents($path), $match)) {
                $chapters[$id] = trim($match[1]);
            }
        }

        return $chapters;
    }

    public function html(string $chapter): string
    {
        $sources = $this->sources();
        abort_unless(isset($sources[$chapter]), 404);
        $path = $sources[$chapter];
        $markdown = file_get_contents($path);
        abort_if($markdown === false, 404);

        $destinations = [];
        foreach ($sources as $id => $source) {
            $destinations[$source] = url('/admin/docs').'?document='.rawurlencode($id);
        }
        $destinations[dirname(base_path()).'/docs/contracts/openapi.json'] = url('/api/docs/openapi.json');
        $html = Str::markdown($markdown, ['html_input' => 'strip', 'allow_unsafe_links' => false]);

        return (string) preg_replace_callback('~href="([^"#]+\.(?:md|json))(#[^"]*)?"~',
            static function (array $link) use ($path, $destinations): string {
                $target = realpath(dirname($path).'/'.html_entity_decode($link[1]));
                if ($target === false || ! isset($destinations[$target])) {
                    return $link[0];
                }

                return 'href="'.e($destinations[$target].($link[2] ?? '')).'"';
            }, $html);
    }

    /** @return array<string,string> Разрешённые главы и их канонические пути. */
    private function sources(): array
    {
        $root = dirname(base_path());
        $sources = [];
        foreach (Finder::create()->files()->in($root.'/docs')->name('*.md')->sortByName() as $file) {
            if (! $file->isLink()) {
                $sources[$file->getRelativePathname()] = $file->getPathname();
            }
        }
        foreach (['README.md', 'ARCHITECTURE.md', 'plan/README.md', 'plan/FAST_STRICT_V1.md', 'plan/FAST_STRICT_V2.md'] as $name) {
            $sources['project/'.$name] = $root.'/'.$name;
        }
        foreach (glob($root.'/plan/*-fast-tdd/README.md') ?: [] as $readme) {
            $sources['project/'.Str::after($readme, $root.'/')] = $readme;
        }

        return $sources;
    }
}
