<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mieterportal') — Immobilienverwaltung</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='%232563eb'><path d='M11.47 3.84a.75.75 0 011.06 0l8.69 8.69a.75.75 0 101.06-1.06l-8.689-8.69a2.25 2.25 0 00-3.182 0l-8.69 8.69a.75.75 0 001.061 1.06l8.69-8.69z'/><path d='M12 5.432l8.159 8.159c.03.03.06.058.091.086v6.198c0 1.035-.84 1.875-1.875 1.875H15a.75.75 0 01-.75-.75v-4.5a.75.75 0 00-.75-.75h-3a.75.75 0 00-.75.75V21a.75.75 0 01-.75.75H5.625a1.875 1.875 0 01-1.875-1.875v-6.198a2.29 2.29 0 00.091-.086L12 5.432z'/></svg>">
    <script>
        (function() {
            const savedTheme = localStorage.getItem('theme') || 'system';
            const isDark = savedTheme === 'dark' || (savedTheme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', isDark);
            document.documentElement.classList.toggle('light', !isDark);
            document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
        })();
    </script>
    <style>
        .notification-dropdown {
            position: relative;
        }
        .notification-menu {
            position: absolute;
            right: 0;
            top: calc(100% + 10px);
            width: 340px;
            background: var(--bg-surface);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            z-index: 100;
            display: none;
            overflow: hidden;
        }
        .notification-menu.show {
            display: block;
        }
        .notification-header {
            padding: 12px 16px;
            background: var(--bg-surface-elevated);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 13px;
            font-weight: 700;
            color: var(--text-main);
        }
        .notification-item {
            padding: 12px 16px;
            border-bottom: 1px solid var(--border-color);
            display: block;
            font-size: 12px;
            color: var(--text-main);
            text-decoration: none;
            transition: background 0.15s ease;
        }
        .notification-item:hover {
            background: var(--bg-surface-elevated);
        }
        .badge-count {
            position: absolute;
            top: -4px;
            right: -4px;
            background: #ef4444;
            color: #fff;
            font-size: 10px;
            font-weight: 700;
            padding: 2px 6px;
            border-radius: 10px;
            border: 2px solid var(--bg-surface);
        }
    </style>
</head>
<body>
<div class="app-layout">
    <!-- Sidebar Navigation -->
    <aside class="sidebar" id="portal-sidebar">
        <div class="sidebar-header">
            <div class="logo-badge">MP</div>
            <div>
                <div class="logo-title">Mieterportal</div>
                <div class="logo-subtitle">Immobilien Service</div>
            </div>
        </div>

        <nav class="sidebar-nav">
            <span class="nav-label">{{ __('Übersicht') }}</span>
            <a href="{{ route('portal.dashboard') }}" class="nav-item {{ request()->routeIs('portal.dashboard') ? 'active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                </svg>
                <span>{{ __('Dashboard') }}</span>
            </a>

            @if(auth()->user()->hasRole('Eigentümer') || auth()->user()->hasAnyRole(['Administrator', 'Sachbearbeiter']))
                <a href="{{ route('portal.units.index') }}" class="nav-item {{ request()->routeIs('portal.units.*') ? 'active' : '' }}">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 21h19.5m-18-18v18m10.5-18v18m6-13.5V21M6.75 6.75h.75m-.75 3h.75m-.75 3h.75m3-6h.75m-.75 3h.75m-.75 3h.75M6.75 21v-3.75c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21M3 3h12m-.75 4.5H21m-3.75 3.75h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008zm0 3h.008v.008h-.008v-.008z" />
                    </svg>
                    <span>{{ __('Objekte & Einheiten') }}</span>
                </a>
            @endif

            <span class="nav-label">{{ __('Services') }}</span>
            <a href="{{ route('portal.tickets.index') }}" class="nav-item {{ request()->routeIs('portal.tickets.*') ? 'active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 010 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 010-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375z" />
                </svg>
                <span>{{ __('Schadenmeldungen & Tickets') }}</span>
            </a>

            <a href="{{ route('portal.documents.index') }}" class="nav-item {{ request()->routeIs('portal.documents.*') ? 'active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                </svg>
                <span>{{ __('Dokumentenablage') }}</span>
            </a>

            <a href="{{ route('portal.invoices.index') }}" class="nav-item {{ request()->routeIs('portal.invoices.*') ? 'active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" />
                </svg>
                <span>{{ __('Rechnungen') }}</span>
            </a>

            <a href="{{ route('portal.messages.index') }}" class="nav-item {{ request()->routeIs('portal.messages.*') ? 'active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H8.25m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0H12m4.125 0a.375.375 0 11-.75 0 .375.375 0 01.75 0zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 01-2.555-.337A5.972 5.972 0 015.41 20.97a.75.75 0 01-1.074-.707V18.5C2.477 16.89 1.5 14.582 1.5 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25z" />
                </svg>
                <span>{{ __('Nachrichten & Kontakt') }}</span>
            </a>

            <span class="nav-label">{{ __('Konto') }}</span>
            <a href="{{ route('portal.profile.show') }}" class="nav-item {{ request()->routeIs('portal.profile.*') ? 'active' : '' }}">
                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                </svg>
                <span>{{ __('Mein Profil & Sicherheit') }}</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content Area -->
    <div class="main-wrapper">
        <header class="topbar">
            <div style="display: flex; align-items: center; gap: 16px;">
                <button id="sidebar-toggle" class="btn btn-outline" style="display: none; padding: 6px 10px;" onclick="document.getElementById('portal-sidebar').classList.toggle('open')">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
                <div>
                    <h2 style="font-size: 20px; font-weight: 700;">@yield('page-title', 'Mieterportal')</h2>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 12px;">
                <!-- Notification Bell -->
                @php
                    $unreadNotifications = auth()->user()->unreadNotifications;
                    $unreadCount = $unreadNotifications->count();
                @endphp
                <div class="notification-dropdown">
                    <button type="button" class="btn btn-outline" style="position: relative; padding: 7px 10px;" onclick="document.getElementById('notification-menu').classList.toggle('show')">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 18px; height: 18px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />
                        </svg>
                        @if($unreadCount > 0)
                            <span class="badge-count">{{ $unreadCount }}</span>
                        @endif
                    </button>

                    <div id="notification-menu" class="notification-menu">
                        <div class="notification-header">
                            <span>Benachrichtigungen ({{ $unreadCount }})</span>
                            @if($unreadCount > 0)
                                <form method="POST" action="{{ route('portal.notifications.markRead') }}" style="margin:0;">
                                    @csrf
                                    <button type="submit" style="background:none; border:none; color: var(--color-primary-400); font-size: 11px; cursor: pointer; text-decoration: underline;">
                                        Alle als gelesen
                                    </button>
                                </form>
                            @endif
                        </div>
                        <div style="max-height: 280px; overflow-y: auto;">
                            @forelse($unreadNotifications->take(5) as $notification)
                                <div class="notification-item">
                                    <div style="font-weight: 600; margin-bottom: 2px;">{{ $notification->data['title'] ?? $notification->data['subject'] ?? 'Benachrichtigung' }}</div>
                                    <div style="color: var(--text-muted); font-size: 11px;">
                                        {{ $notification->data['message'] ?? $notification->data['preview'] ?? '' }}
                                    </div>
                                    <div style="font-size: 10px; color: var(--text-faint); margin-top: 4px;">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </div>
                                </div>
                            @empty
                                <div style="padding: 20px; text-align: center; color: var(--text-muted); font-size: 12px;">
                                    Keine neuen Benachrichtigungen
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Language Switcher -->
                <div style="display: flex; align-items: center; background: var(--bg-surface-elevated); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 2px;">
                    <a href="{{ route('locale.switch', 'de') }}" title="Deutsch" style="padding: 4px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; text-decoration: none; {{ app()->getLocale() === 'de' ? 'background: var(--color-primary-600); color: #fff;' : 'color: var(--text-muted);' }}">
                        🇩🇪 DE
                    </a>
                    <a href="{{ route('locale.switch', 'en') }}" title="English" style="padding: 4px 8px; font-size: 11px; font-weight: 700; border-radius: 6px; text-decoration: none; {{ app()->getLocale() === 'en' ? 'background: var(--color-primary-600); color: #fff;' : 'color: var(--text-muted);' }}">
                        🇬🇧 EN
                    </a>
                </div>

                <!-- Theme Switcher (Matches Admin / Filament Theme Switcher) -->
                <div class="theme-switch-group" role="group" aria-label="Theme switcher">
                    <button type="button" class="theme-switch-btn" data-theme-val="light" onclick="setPortalTheme('light')" title="{{ __('Hell (Light Mode)') }}">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386l-1.591 1.591M21 12h-2.25m-.386 6.364l-1.591-1.591M12 18.75V21m-4.773-4.227l-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0z" />
                        </svg>
                    </button>
                    <button type="button" class="theme-switch-btn" data-theme-val="dark" onclick="setPortalTheme('dark')" title="{{ __('Dunkel (Dark Mode)') }}">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.718 9.718 0 0118 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 003 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 009.002-5.998z" />
                        </svg>
                    </button>
                    <button type="button" class="theme-switch-btn" data-theme-val="system" onclick="setPortalTheme('system')" title="{{ __('System (Automatisch)') }}">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 17.25v1.007a3 3 0 01-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0115 18.257V17.25m6-12V15a2.25 2.25 0 01-2.25 2.25H5.25A2.25 2.25 0 013 15V5.25m18 0A2.25 2.25 0 0018.75 3H5.25A2.25 2.25 0 003 5.25m18 0H3" />
                        </svg>
                    </button>
                </div>

                @if(auth()->user()->hasAnyRole(['Administrator', 'Sachbearbeiter']))
                    <a href="/admin" class="btn btn-secondary" style="font-size: 13px;">
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                        </svg>
                        Admin-Bereich
                    </a>
                @endif

                <!-- User Profile & Logout Dropdown (Matching Admin Style) -->
                <div class="user-dropdown">
                    <button type="button" class="user-menu-btn" onclick="document.getElementById('user-profile-menu').classList.toggle('show')">
                        <div class="avatar">{{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 2)) }}</div>
                        <div class="user-details">
                            <span class="user-name">{{ auth()->user()->name }}</span>
                            <span class="user-role">
                                @if(auth()->user()->hasRole('Mieter')) {{ __('Mieter') }}
                                @elseif(auth()->user()->hasRole('Eigentümer')) {{ __('Eigentümer') }}
                                @elseif(auth()->user()->hasRole('Administrator')) {{ __('Admin') }}
                                @elseif(auth()->user()->hasRole('Sachbearbeiter')) {{ __('Sachbearbeiter') }}
                                @else {{ __('Benutzer') }}
                                @endif
                            </span>
                        </div>
                        <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 14px; height: 14px; margin-left: 4px; color: var(--text-muted);">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>

                    <div id="user-profile-menu" class="user-menu-dropdown">
                        <div class="user-dropdown-header">
                            <div class="name">{{ auth()->user()->name }}</div>
                            <div class="email">{{ auth()->user()->email }}</div>
                        </div>

                        <a href="{{ route('portal.profile.show') }}" class="user-dropdown-item">
                            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0012 15.75a7.488 7.488 0 00-5.982 2.975m11.963 0a9 9 0 10-11.963 0m11.963 0A8.966 8.966 0 0112 21a8.966 8.966 0 01-5.982-2.275M15 9.75a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                            <span>{{ __('Mein Profil & Sicherheit') }}</span>
                        </a>

                        @if(auth()->user()->hasAnyRole(['Administrator', 'Sachbearbeiter']))
                            <a href="/admin" class="user-dropdown-item">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75" />
                                </svg>
                                <span>{{ __('Admin-Bereich') }}</span>
                            </a>
                        @endif

                        <div class="user-dropdown-divider"></div>

                        <form method="POST" action="{{ route('portal.logout') }}" style="margin: 0;">
                            @csrf
                            <button type="submit" class="user-dropdown-item danger">
                                <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 16px; height: 16px;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15M12 9l-3 3m0 0l3 3m-3-3h12.75" />
                                </svg>
                                <span>{{ __('Abmelden') }}</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <main class="page-container">
            @if(session('success'))
                <div class="alert alert-success">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px; flex-shrink: 0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger">
                    <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px; flex-shrink: 0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            @yield('content')
        </main>
    </div>
</div>

<script>
    if (window.innerWidth <= 900) {
        const toggleBtn = document.getElementById('sidebar-toggle');
        if (toggleBtn) toggleBtn.style.display = 'inline-flex';
    }

    // Theme Switcher Logic
    function updateThemeButtons(theme) {
        document.querySelectorAll('.theme-switch-btn').forEach(btn => {
            btn.classList.toggle('active', btn.getAttribute('data-theme-val') === theme);
        });
    }

    function setPortalTheme(theme) {
        localStorage.setItem('theme', theme);
        const isDark = theme === 'dark' || (theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
        document.documentElement.classList.toggle('dark', isDark);
        document.documentElement.classList.toggle('light', !isDark);
        document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
        updateThemeButtons(theme);
    }

    // Initialize active theme button state on load
    const currentTheme = localStorage.getItem('theme') || 'system';
    updateThemeButtons(currentTheme);

    // Listen for OS color scheme changes if set to system
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
        if ((localStorage.getItem('theme') || 'system') === 'system') {
            setPortalTheme('system');
        }
    });

    // Close dropdown menus on outer click
    document.addEventListener('click', function(e) {
        const notifMenu = document.getElementById('notification-menu');
        const notifDropdown = document.querySelector('.notification-dropdown');
        if (notifMenu && notifDropdown && !notifDropdown.contains(e.target)) {
            notifMenu.classList.remove('show');
        }

        const userMenu = document.getElementById('user-profile-menu');
        const userDropdown = document.querySelector('.user-dropdown');
        if (userMenu && userDropdown && !userDropdown.contains(e.target)) {
            userMenu.classList.remove('show');
        }
    });
</script>
</body>
</html>
