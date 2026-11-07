<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('lists every label that still needs a translation and fails', function (): void {
    translations(['fields.Title' => 'عنوان']);

    $this->artisan('nova-translated-labels:missing', ['--path' => __DIR__.'/../Fixtures/Nova'])
        ->expectsOutputToContain('Author')
        ->expectsOutputToContain('Publish Post')
        ->doesntExpectOutputToContain('| fields | Title')
        ->assertFailed();
});

it('succeeds once everything is translated', function (): void {
    config(['nova-translated-labels.translated_pattern' => '/./u']);

    $this->artisan('nova-translated-labels:missing', ['--path' => __DIR__.'/../Fixtures/Nova'])
        ->expectsOutputToContain('0 labels have no translation.')
        ->assertSuccessful();
});

it('fails when a resource could not be walked, even with nothing reported missing', function (): void {
    config(['nova-translated-labels.translated_pattern' => '/./u']);

    $this->artisan('nova-translated-labels:missing', ['--path' => __DIR__.'/../Fixtures/Broken'])
        ->expectsOutputToContain('No signed-in user.')
        ->assertFailed();
});

it('prints only JSON keyed like the lang file, with empty values to fill in', function (): void {
    translations(['fields.Title' => 'عنوان']);

    Artisan::call('nova-translated-labels:missing', ['--path' => __DIR__.'/../Fixtures/Nova', '--json' => true]);
    $missing = json_decode(Artisan::output(), true, flags: JSON_THROW_ON_ERROR);

    expect($missing['fields'])->toHaveKey('Author', '')->not->toHaveKey('Title')
        ->and($missing['resources']['posts'])->toBe(['label' => '', 'singular' => '']);
});
