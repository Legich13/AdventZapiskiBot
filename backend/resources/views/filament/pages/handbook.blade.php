<x-filament-panels::page>
    <x-filament::section heading="Справочник проекта">
        <nav aria-label="Разделы документации">
            @foreach($chapters as $path => $title)
                <p><a href="{{ url('/admin/docs').'?document='.rawurlencode($path) }}" @if($path === $document)aria-current="page"@endif>{{ $title }}</a></p>
            @endforeach
            <p><a href="{{ url('/scalar') }}">Справочник API · Scalar</a></p>
            <p><a href="{{ url('/api/docs/openapi.json') }}">Схема OpenAPI JSON</a></p>
        </nav>
    </x-filament::section>
    <x-filament::section><article class="fi-prose">{!! $html !!}</article></x-filament::section>
</x-filament-panels::page>
