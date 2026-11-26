<!doctype html>
<html lang="ru"><head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,viewport-fit=cover"><meta name="csrf-token" content="{{ csrf_token() }}"><meta name="theme-color" content="#64192d">
<title>@yield('title', 'Декабрь чудес')</title>
<link rel="stylesheet" href="/studio.css">@stack('head')
</head><body><div class="shell">
<header class="mast"><span class="brand"><span class="brand-star" aria-hidden="true">✴</span> Декабрь чудес</span>@yield('nav')</header>
<main>@yield('content')</main>
</div>@stack('scripts')</body></html>
