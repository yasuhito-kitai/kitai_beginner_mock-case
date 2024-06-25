<!DOCTYPE html>
<html lang="ja">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="{{ asset('css/sanitize.css') }}">
    <link rel="stylesheet" href="{{ asset('css/common.css') }}">
    @yield('css')
</head>

<body>
    <div class="container">
        <header>
            <div class="logo">
                <p class="logo__text">Atte</p>
            </div>

            @yield('header_content')


        </header>

        <main>
            @yield('main_content')
        </main>

        <footer>
            <div class="copyright">
                <p class=copyright__text>Atte,inc.</p>
            </div>
        </footer>
    </div>
</body>

</html>