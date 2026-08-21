<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />

        <!-- Custom static CSS -->
        <link rel="stylesheet" href="{{ asset('css/style.css') }}">

        <!-- App JS (optional) -->
        @if (file_exists(public_path('build/assets/app-DUr89oQr.js')))
            <script type="module" src="{{ asset('build/assets/app-DUr89oQr.js') }}"></script>
        @endif
    </head>
    <body>
        <header>
            @if (Route::has('login'))
                <nav>
                    @auth
                        <a href="{{ url('/dashboard') }}" class="nav-link">Dashboard</a>
                    @else
                        <a href="{{ route('login') }}" class="nav-link">Log in</a>

                        @if (Route::has('register'))
                            <a href="{{ route('register') }}" class="nav-link">Register</a>
                        @endif
                    @endauth
                </nav>
            @endif
        </header>
        <div class="container">
            <main>
                <div class="card">
                    <h1>Let's get started</h1>
                    <p>Laravel has an incredibly rich ecosystem. <br>We suggest starting with the following.</p>
                    <ul class="list">
                        <li>
                            <span class="dot"><span class="dot-inner"></span></span>
                            <span>
                                Read the
                                <a href="https://laravel.com/docs" target="_blank" class="nav-link">Documentation
                                    <svg
                                        width="10"
                                        height="11"
                                        viewBox="0 0 10 11"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="icon"
                                    >
                                        <path
                                            d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001"
                                            stroke="currentColor"
                                            stroke-linecap="square"
                                        />
                                    </svg>
                                </a>
                            </span>
                        </li>
                        <li>
                            <span class="dot"><span class="dot-inner"></span></span>
                            <span>
                                Watch video tutorials at
                                <a href="https://laracasts.com" target="_blank" class="nav-link">Laracasts
                                    <svg
                                        width="10"
                                        height="11"
                                        viewBox="0 0 10 11"
                                        fill="none"
                                        xmlns="http://www.w3.org/2000/svg"
                                        class="icon"
                                    >
                                        <path
                                            d="M7.70833 6.95834V2.79167H3.54167M2.5 8L7.5 3.00001"
                                            stroke="currentColor"
                                            stroke-linecap="square"
                                        />
                                    </svg>
                                </a>
                            </span>
                        </li>
                    </ul>
                    <ul class="list">
                        <li>
                            <a href="https://cloud.laravel.com" target="_blank" class="cta">Deploy now</a>
                        </li>
                    </ul>
                </div>
                <div class="aside">
                    {{-- Logo and illustrations omitted for brevity --}}
                    <div class="overlay"></div>
                </div>
            </main>
        </div>

        @if (Route::has('login'))
            <div class="spacer"></div>
        @endif
    </body>
</html>
