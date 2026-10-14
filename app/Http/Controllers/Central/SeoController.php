<?php

declare(strict_types=1);

namespace App\Http\Controllers\Central;

use App\Http\Controllers\Controller;
use App\Services\CentralLoginService;
use Illuminate\Http\Response;

/**
 * robots.txt and sitemap.xml: only the public website is indexed, never login, registration,
 * the portal or the admin panel.
 */
class SeoController extends Controller
{
    /**
     * Public pages listed in the sitemap.
     *
     * @var list<string>
     */
    private const PUBLIC_PATHS = ['/', '/impressum', '/datenschutz', '/agb'];

    /**
     * Areas crawlers must not visit.
     *
     * @var list<string>
     */
    private const DISALLOWED_PATHS = [
        '/anmelden',
        '/passwort-vergessen',
        '/registrieren',
        '/login',
        '/admin',
        '/portal',
        '/api',
        '/livewire',
    ];

    public function robots(): Response
    {
        $lines = ['User-agent: *'];

        foreach (self::DISALLOWED_PATHS as $path) {
            $lines[] = "Disallow: {$path}";
        }

        $lines[] = '';
        $lines[] = 'Sitemap: '.CentralLoginService::centralUrl('/sitemap.xml');

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }

    public function sitemap(): Response
    {
        $urls = collect(self::PUBLIC_PATHS)
            ->map(fn (string $path): string => '  <url><loc>'.e(CentralLoginService::centralUrl($path)).'</loc></url>')
            ->implode("\n");

        $xml = <<<XML
            <?xml version="1.0" encoding="UTF-8"?>
            <urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
            {$urls}
            </urlset>

            XML;

        return response($xml, 200, ['Content-Type' => 'application/xml; charset=UTF-8']);
    }
}
