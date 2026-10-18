{{-- Balises communes aux deux mises en page (application et invité). --}}
@php($title = $title ?? null)

<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
@auth
    {{-- Ouvre la connexion temps réel (resources/js/echo.js) pour les membres seulement. --}}
    <meta name="africommit-user" content="{{ auth()->id() }}">
@endauth

{{--
    Ne transmet l'URL complète qu'aux pages du même site ; vers l'extérieur,
    seule l'origine part, et rien du tout en HTTP. Évite de divulguer le nom
    d'utilisateur ou le slug d'un projet privé aux sites liés depuis un profil.
--}}
<meta name="referrer" content="strict-origin-when-cross-origin">
<meta name="theme-color" content="#4F46E5" media="(prefers-color-scheme: light)">
<meta name="theme-color" content="#0B1120" media="(prefers-color-scheme: dark)">
<meta name="description" content="AfriCommit met en relation les développeurs, designers et porteurs de projets du continent africain.">

<title>{{ $title ? $title.' · '.config('app.name', 'AfriCommit') : config('app.name', 'AfriCommit') }}</title>

<link rel="icon" href="{{ asset('favicon.ico') }}" sizes="any">

<link rel="preconnect" href="https://fonts.bunny.net">
<link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

@include('layouts.partials.theme-script')

@vite(['resources/css/app.css', 'resources/js/app.js'])
