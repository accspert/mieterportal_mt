<?php

test('robots.txt keeps crawlers out of login, registration and the private areas', function () {
    $response = $this->get('http://localhost/robots.txt')
        ->assertOk()
        ->assertHeader('Content-Type', 'text/plain; charset=UTF-8');

    expect($response->getContent())
        ->toContain("User-agent: *\n")
        ->toContain("Disallow: /anmelden\n")
        ->toContain("Disallow: /registrieren\n")
        ->toContain("Disallow: /passwort-vergessen\n")
        ->toContain("Disallow: /admin\n")
        ->toContain("Disallow: /portal\n")
        ->toContain('Sitemap: '.rtrim(config('app.url'), '/').'/sitemap.xml')
        ->not->toContain("Disallow: /\n");

    expect(file_exists(public_path('robots.txt')))->toBeFalse();
});

test('the portal, admin panel and api are closed to search engines', function () {
    expect($this->get('/robots.txt')->getContent())
        ->toContain("Disallow: /portal\n")
        ->toContain("Disallow: /admin\n")
        ->toContain("Disallow: /api\n")
        ->toContain("Disallow: /login\n");
});

test('the sitemap lists only the public pages of the central domain', function () {
    $response = $this->get('http://localhost/sitemap.xml')
        ->assertOk()
        ->assertHeader('Content-Type', 'application/xml; charset=UTF-8');

    $urls = [];
    foreach (simplexml_load_string($response->getContent())->url as $url) {
        $urls[] = (string) $url->loc;
    }

    $base = rtrim(config('app.url'), '/');
    expect($urls)->toBe(["{$base}/", "{$base}/impressum", "{$base}/datenschutz", "{$base}/agb"]);
});

test('login and registration ask search engines not to index them', function (string $routeName) {
    $this->get(route($routeName))->assertSee('<meta name="robots" content="noindex, follow">', false);
})->with(['central.login', 'central.register', 'central.password.request']);

test('every page ships the favicon set', function (string $url) {
    $this->get($url)
        ->assertOk()
        ->assertSee('favicon.svg', false)
        ->assertSee('favicon.ico', false)
        ->assertSee('apple-touch-icon.png', false)
        ->assertSee('site.webmanifest', false);
})->with([
    'landing page' => 'http://localhost/',
    'login' => fn () => route('central.login'),
    'imprint' => fn () => route('legal.imprint'),
]);

test('the favicon files exist and the old blue logo is gone', function () {
    foreach (['favicon.ico', 'favicon.svg', 'apple-touch-icon.png', 'icon-192.png', 'icon-512.png', 'icon-maskable-512.png', 'site.webmanifest'] as $file) {
        expect(filesize(public_path($file)))->toBeGreaterThan(0);
    }

    expect(file_get_contents(public_path('favicon.svg')))->toContain('#e2231a')->not->toContain('#3b82f6');
    expect(json_decode(file_get_contents(public_path('site.webmanifest')), true)['name'])->toBe('Liegenschaftsportal');
});

test('the legal pages use the design of the website', function (string $routeName) {
    $this->get(route($routeName))
        ->assertOk()
        ->assertSee('css/landing.css', false)
        ->assertSee('<article class="legal-body">', false)
        ->assertSee('class="site-header"', false)
        ->assertSee('footer-mark', false);
})->with(['legal.imprint', 'legal.privacy', 'legal.terms']);
