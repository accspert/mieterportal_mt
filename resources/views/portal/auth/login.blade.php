<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anmeldung — Mieterportal</title>
    <link rel="stylesheet" href="{{ asset('css/portal.css') }}">
    <style>
        .login-page {
            display: flex;
            min-height: 100vh;
            align-items: center;
            justify-content: center;
            padding: 24px;
        }
        .login-box {
            width: 100%;
            max-width: 440px;
            background: var(--bg-surface-glass);
            backdrop-filter: blur(20px);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-xl);
            padding: 40px 36px;
            box-shadow: var(--shadow-xl), var(--shadow-glow);
        }
        .demo-pills {
            display: flex;
            gap: 8px;
            margin-top: 16px;
            flex-wrap: wrap;
        }
        .demo-pill {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid var(--border-color);
            border-radius: 20px;
            padding: 6px 12px;
            font-size: 12px;
            color: var(--text-muted);
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .demo-pill:hover {
            background: rgba(59, 130, 246, 0.2);
            border-color: var(--color-primary-500);
            color: #fff;
        }
    </style>
</head>
<body class="login-page">
<div class="login-box" style="position: relative;">
    <div style="position: absolute; top: 20px; right: 20px; display: flex; align-items: center; background: rgba(255,255,255,0.06); border: 1px solid var(--border-color); border-radius: var(--radius-sm); padding: 2px;">
        <a href="{{ route('locale.switch', 'de') }}" title="Deutsch" style="padding: 3px 6px; font-size: 11px; font-weight: 600; border-radius: 4px; text-decoration: none; {{ app()->getLocale() === 'de' ? 'background: var(--color-primary-600); color: #fff;' : 'color: var(--text-muted);' }}">
            🇩🇪 DE
        </a>
        <a href="{{ route('locale.switch', 'en') }}" title="English" style="padding: 3px 6px; font-size: 11px; font-weight: 600; border-radius: 4px; text-decoration: none; {{ app()->getLocale() === 'en' ? 'background: var(--color-primary-600); color: #fff;' : 'color: var(--text-muted);' }}">
            🇬🇧 EN
        </a>
    </div>

    <div style="text-align: center; margin-bottom: 32px;">
        <div class="logo-badge" style="margin: 0 auto 16px; width: 56px; height: 56px; font-size: 26px;">MP</div>
        <h1 style="font-size: 24px; margin-bottom: 6px;">{{ app()->getLocale() === 'en' ? 'Tenant Portal' : 'Mieterportal' }}</h1>
        <p style="font-size: 13px; color: var(--text-muted);">{{ app()->getLocale() === 'en' ? 'Welcome to the tenant portal' : 'Willkommen im Mieterportal' }}</p>
    </div>

    @if ($errors->any())
        <div class="alert alert-danger" style="margin-bottom: 20px;">
            <svg fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width: 20px; height: 20px; flex-shrink: 0;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
            </svg>
            <div>
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        </div>
    @endif

    @if (session('success'))
        <div class="alert alert-success" style="margin-bottom: 20px;">
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('portal.login.post') }}">
        @csrf
        <div class="form-group">
            <label class="form-label" for="email">E-Mail-Adresse</label>
            <input type="email" id="email" name="email" class="form-control" value="{{ old('email') }}" required autofocus placeholder="name@beispiel.de">
        </div>

        <div class="form-group">
            <label class="form-label" for="password">Passwort</label>
            <input type="password" id="password" name="password" class="form-control" required placeholder="••••••••">
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; font-size: 13px;">
            <label style="display: flex; align-items: center; gap: 8px; color: var(--text-muted); cursor: pointer;">
                <input type="checkbox" name="remember" style="accent-color: var(--color-primary-600); border-radius: 4px;">
                Angemeldet bleiben
            </label>
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%; padding: 13px; font-size: 15px;">
            Anmelden
        </button>
    </form>

    <div style="margin-top: 28px; padding-top: 20px; border-top: 1px solid var(--border-color);">
        <div style="font-size: 11px; font-weight: 700; text-transform: uppercase; color: var(--text-faint); margin-bottom: 8px;">
            Demo-Zugänge (Passwort: password)
        </div>
        <div class="demo-pills">
            <button type="button" class="demo-pill" onclick="fillCreds('tenant@kundenportal.local')">
                🏠 {{ __('Mieter') }}
            </button>
            <button type="button" class="demo-pill" onclick="fillCreds('owner@kundenportal.local')">
                🏢 {{ __('Eigentümer') }}
            </button>
            <button type="button" class="demo-pill" onclick="fillCreds('staff@kundenportal.local')">
                📋 {{ __('Sachbearbeiter') }}
            </button>
            <button type="button" class="demo-pill" onclick="fillCreds('admin@kundenportal.local')">
                ⚙️ {{ __('Admin') }}
            </button>
        </div>
    </div>
</div>

<script>
    function fillCreds(email) {
        document.getElementById('email').value = email;
        document.getElementById('password').value = 'password';
    }
</script>
</body>
</html>
