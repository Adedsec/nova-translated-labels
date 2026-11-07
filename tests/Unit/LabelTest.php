<?php

declare(strict_types=1);

use Adedsec\NovaTranslatedLabels\Label;

it('appends the real name between invisible markers', function (): void {
    expect(Label::make('شماره تلفن', 'phone_number'))->toBe("شماره تلفن\u{2063} (phone_number)\u{2063}");
});

it('leaves the label alone when there is no distinct real name', function (?string $realName): void {
    expect(Label::make('Phone', $realName))->toBe('Phone');
})->with([null, '', 'Phone']);

it('never marks a label twice', function (): void {
    $marked = Label::make('شماره تلفن', 'phone_number');

    expect(Label::make($marked, 'other'))->toBe($marked);
});

it('strips the real name back out', function (): void {
    expect(Label::strip('ایجاد '.Label::make('مقاله', 'posts')))->toBe('ایجاد مقاله');
});
