<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>{{ $title ?? config('app.name') }}</title>

<link rel="icon" href="/favicon.svg" type="image/svg+xml">

<link rel="preconnect" href="https://fonts.bunny.net">
<link rel="preload" href="https://fonts.bunny.net/css?family=nunito:400,500,600,700,800&display=swap" as="style" />
<link href="https://fonts.bunny.net/css?family=nunito:400,500,600,700,800&display=swap" rel="stylesheet" />
<link rel="preconnect" href="https://i.imgur.com">

@vite(['resources/css/app.css', 'resources/js/app.js'])
@fluxAppearance
