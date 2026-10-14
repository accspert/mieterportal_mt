<?php

test('legal pages show the operator details', function (string $routeName, string $title) {
    config(['legal.email' => 'info@accspert.ch']);

    $this->get(route($routeName))
        ->assertOk()
        ->assertSee($title)
        ->assertSee('Accspert Software GmbH i.Gr.')
        ->assertSee(route('legal.imprint'), false)
        ->assertSee(route('legal.privacy'), false)
        ->assertSee(route('legal.terms'), false);
})->with([
    'imprint' => ['legal.imprint', 'Impressum'],
    'privacy policy' => ['legal.privacy', 'Datenschutzerklärung'],
    'terms' => ['legal.terms', 'Allgemeine Geschäftsbedingungen'],
]);

test('imprint names the managing director, the address and the contact address', function () {
    config(['legal.email' => 'info@accspert.ch', 'legal.uid' => null]);

    $this->get(route('legal.imprint'))
        ->assertOk()
        ->assertSee('Eren Gürkaynar')
        ->assertSee('Breisacherstrasse 83')
        ->assertSee('4057 Basel')
        ->assertSee('info@accspert.ch')
        ->assertSee('befindet sich in Gründung');
});

test('imprint shows the company identification number once it is configured', function () {
    config(['legal.uid' => 'CHE-123.456.789']);

    $this->get(route('legal.imprint'))
        ->assertSee('CHE-123.456.789')
        ->assertDontSee('befindet sich in Gründung');
});

test('central landing page and portal login link to the legal pages', function (string $url) {
    $this->get($url)
        ->assertOk()
        ->assertSee(route('legal.imprint'), false)
        ->assertSee(route('legal.privacy'), false);
})->with([
    'central landing page' => fn () => centralLandingPageUrl(),
    'central login' => fn () => route('central.login'),
]);

test('no page loads fonts from third-party servers', function (string $url) {
    $this->get($url)
        ->assertOk()
        ->assertDontSee('fonts.googleapis.com')
        ->assertDontSee('fonts.bunny.net');
})->with([
    'central landing page' => fn () => centralLandingPageUrl(),
    'central login' => fn () => route('central.login'),
    'admin password reset' => fn () => '/admin/password-reset/request',
]);

function centralLandingPageUrl(): string
{
    return 'http://localhost/';
}

test('self-hosted stylesheets do not import fonts from third-party servers', function (string $stylesheet) {
    expect(file_get_contents(public_path($stylesheet)))
        ->not->toContain('googleapis')
        ->not->toContain('bunny.net');
})->with(['css/portal.css', 'css/admin.css', 'css/fonts.css']);
