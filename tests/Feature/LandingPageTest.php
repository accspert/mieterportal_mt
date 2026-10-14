<?php

use Illuminate\Support\Facades\Route;

test('the landing page presents the liegenschaftsportal', function () {
    $this->get('http://localhost/')
        ->assertOk()
        ->assertSee('<title>Liegenschaftsportal', false)
        ->assertSee('Software für Liegenschaftsverwaltungen</p>', false)
        ->assertSee('Weniger Telefon.')
        ->assertSee('<span class="serif">Mehr</span> Zeit.', false)
        ->assertDontSee('Mehr Verwaltung')
        ->assertSee('Schäden melden')
        ->assertSee('Sicherheit &amp; Datenschutz', false)
        ->assertSee('Sichere Server mit täglichen Backups.')
        ->assertSee('css/landing.css', false)
        ->assertDontSee('Mieterportal —', false);
});

test('the landing page makes no claims or offers that do not exist', function () {
    config(['legal.email' => 'info@accspert.ch', 'legal.hosting_location' => 'Schweiz']);

    $this->get('http://localhost/')
        ->assertOk()
        ->assertDontSee('Demo vereinbaren')
        ->assertDontSee('Serverstandort')
        ->assertDontSee('Basel</p>', false)
        ->assertDontSee('Anrufbeantworter')
        ->assertDontSee('#anrufbeantworter', false);
});

test('the landing page offers no access to individual property managements', function () {
    $this->get('http://localhost/')
        ->assertOk()
        ->assertDontSee('/portal/login', false)
        ->assertDontSee('Adresse Ihrer Verwaltung');

    expect(Route::has('central.portal-finder'))->toBeFalse();
});

test('the contact section shows the operator address and email', function () {
    $this->get('http://localhost/')
        ->assertSee('05 — Kontakt')
        ->assertSee('Breisacherstrasse 83')
        ->assertSee('mailto:info@liegenschaftsportal.ch', false);
});

test('the imprint lists the contact email of the operator', function () {
    $this->get(route('legal.imprint'))
        ->assertOk()
        ->assertSee('info@liegenschaftsportal.ch');
});

test('a blurred city silhouette sits behind the landing page', function () {
    $this->get('http://localhost/')
        ->assertSee('<div class="backdrop" aria-hidden="true"></div>', false);

    expect(file_get_contents(public_path('images/skyline.svg')))->toContain('<svg')
        ->and(file_get_contents(public_path('css/landing.css')))->toContain("url('../images/skyline.svg')");
});

test('birds circle above the silhouette unless reduced motion is requested', function () {
    $response = $this->get('http://localhost/')->assertOk();

    expect(substr_count($response->getContent(), '<span class="bird bird-'))->toBe(7);

    $css = file_get_contents(public_path('css/landing.css'));
    expect($css)->toContain('animation: bird-flight')
        ->and($css)->toMatch('/prefers-reduced-motion: reduce\)[\s\S]*\.birds \{\s*display: none;/');
});

test('the call to action sits next to the hero headline', function () {
    $this->get('http://localhost/')
        ->assertSeeInOrder(['<div class="hero-head">', 'Weniger Telefon.', 'class="btn btn-large" href="'.route('central.register').'"'], false);
});
