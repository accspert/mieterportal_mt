<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Mieterportal Multi-Tenant Platform</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700&display=swap" rel="stylesheet" />
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Figtree', sans-serif; }
        body { background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #f8fafc; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 24px; }
        .card { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.1); border-radius: 16px; padding: 36px; max-width: 640px; width: 100%; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.3); }
        .badge { display: inline-block; padding: 4px 12px; background: rgba(37, 99, 235, 0.2); border: 1px solid rgba(59, 130, 246, 0.4); color: #60a5fa; border-radius: 9999px; font-size: 13px; font-weight: 600; margin-bottom: 16px; }
        h1 { font-size: 26px; font-weight: 700; margin-bottom: 8px; color: #ffffff; }
        p { color: #94a3b8; font-size: 15px; line-height: 1.6; margin-bottom: 24px; }
        .tenant-list { display: flex; flex-direction: column; gap: 12px; margin-bottom: 24px; }
        .tenant-item { display: flex; align-items: center; justify-content: space-between; padding: 14px 18px; background: rgba(15, 23, 42, 0.6); border: 1px solid rgba(255, 255, 255, 0.08); border-radius: 10px; text-decoration: none; color: inherit; transition: all 0.2s ease; }
        .tenant-item:hover { border-color: #3b82f6; background: rgba(37, 99, 235, 0.15); transform: translateY(-2px); }
        .tenant-name { font-weight: 600; color: #f1f5f9; font-size: 15px; }
        .tenant-domain { font-size: 13px; color: #60a5fa; }
        .btn-group { display: flex; gap: 8px; }
        .btn { padding: 6px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; text-decoration: none; transition: 0.2s; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-outline { background: transparent; border: 1px solid rgba(255, 255, 255, 0.2); color: #e2e8f0; }
        .btn-outline:hover { background: rgba(255, 255, 255, 0.1); }
        .footer { font-size: 13px; color: #64748b; border-top: 1px solid rgba(255, 255, 255, 0.08); padding-top: 16px; margin-top: 20px; text-align: center; }
    </style>
</head>
<body>
    <div class="card">
        <div class="badge">Multi-Tenant Platform</div>
        <h1>Mieterportal Mandanten-Zugang</h1>
        <p>Jede Hausverwaltung und jeder Kunde verfügt über eine eigene, isolierte Datenbank. Wählen Sie Ihren Mandanten aus oder nutzen Sie Ihre individuelle Subdomain.</p>

        @if($tenants->isNotEmpty())
            <div class="tenant-list">
                @foreach($tenants as $tenant)
                    @php
                        $domain = $tenant->domains->first()?->domain ?? $tenant->id;
                        $port = request()->getPort() && !in_array(request()->getPort(), [80, 443]) ? ':'.request()->getPort() : '';
                        $scheme = request()->getScheme();
                        $tenantHost = str_contains($domain, '.') ? $domain : $domain . '.' . request()->getHost();
                        $portalUrl = "{$scheme}://{$tenantHost}{$port}/portal/login";
                        $adminUrl = "{$scheme}://{$tenantHost}{$port}/admin";
                    @endphp
                    <div class="tenant-item">
                        <div>
                            <div class="tenant-name">{{ $tenant->company_name ?? $tenant->id }}</div>
                            <div class="tenant-domain">{{ $tenantHost }}</div>
                        </div>
                        <div class="btn-group">
                            <a href="{{ $adminUrl }}" class="btn btn-outline">Admin Panel</a>
                            <a href="{{ $portalUrl }}" class="btn btn-primary">Mieterportal</a>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div style="padding: 16px; background: rgba(234, 179, 8, 0.1); border: 1px solid rgba(234, 179, 8, 0.3); border-radius: 8px; color: #fde047; font-size: 14px; margin-bottom: 20px;">
                Noch keine Mandanten angelegt. Erstellen Sie einen Mandanten mit: <br>
                <code style="display: block; margin-top: 8px; background: rgba(0,0,0,0.3); padding: 6px 10px; border-radius: 4px; color: #fff;">php artisan tenant:create demo demo "Demo Verwaltung AG"</code>
            </div>
        @endif

        <div class="footer">
            Mieterportal Multi-Database Tenancy &copy; {{ date('Y') }}
        </div>
    </div>
</body>
</html>
