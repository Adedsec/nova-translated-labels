# Nova Translated Labels

Translate your whole [Laravel Nova](https://nova.laravel.com) admin panel from one lang file (menu groups, resources, fields, panels, filters, actions and option labels) and show each label's **real database name in small grey text under it**.

<!-- screenshot: docs/screenshot.png -->

Admins read the panel in their own language and still know which table and column they are looking at, so they can find it in Metabase, a SQL client or the code.

```
شماره تلفن          Title          Author
phone_number        title          author_id
```

- Your code keeps its English labels. Translations live in `lang/{locale}/nova-labels.php`.
- By default the grey name is the column (`phone_number`), the foreign key of a `BelongsTo` (`author_id`), the related table of a has-many field (`comments`), the table of a resource (`posts`), the original name of a group (`Content`), or the stored value of an option (`draft`).
- Prefer the English label your code uses, or both? Set it per kind of label (see [Configuration](#configuration)).
- Works for any language. Right-to-left panels (`Nova::enableRTL()`) align correctly.
- Labels that are already in a non-Latin script are left alone, so you can translate gradually.
- An artisan command lists every label that is still untranslated, ready to paste.

## Requirements

- PHP 8.2+
- Laravel 11, 12 or 13
- Laravel Nova 5

## Installation

```bash
composer require adedsec/nova-translated-labels
```

Add the trait to your base Nova resource (`app/Nova/Resource.php`). Every resource that extends it is then translated:

```php
use Adedsec\NovaTranslatedLabels\Concerns\HasTranslatedLabels;
use Laravel\Nova\Resource as NovaResource;

abstract class Resource extends NovaResource
{
    use HasTranslatedLabels;
}
```

To translate the fields inside an action's modal, or a filter's name and options, add a trait to those classes too. Action **names** are translated by the resource trait already.

```php
use Adedsec\NovaTranslatedLabels\Concerns\TranslatesActionFields;

class PublishPost extends Action
{
    use TranslatesActionFields;
}
```

```php
use Adedsec\NovaTranslatedLabels\Concerns\TranslatesFilterLabels;

class StatusFilter extends Filter
{
    use TranslatesFilterLabels;
}
```

Publish the lang file for your app locale, and optionally the config:

```bash
php artisan vendor:publish --tag=nova-translated-labels-lang
php artisan vendor:publish --tag=nova-translated-labels-config
```

## Writing translations

`lang/fa/nova-labels.php` (use your own locale):

```php
return [
    'groups' => [
        'Content' => 'محتوا',
    ],

    'resources' => [
        // keyed by the resource's uriKey
        'posts' => ['label' => 'مقاله‌ها', 'singular' => 'مقاله'],
    ],

    'fields' => [
        // keyed by the name you pass to make()
        'Title' => 'عنوان',
        'Created At' => 'تاریخ ایجاد',
    ],

    'panels' => ['Details' => 'جزئیات'],
    'filters' => ['Status' => 'وضعیت'],
    'actions' => ['Publish Post' => 'انتشار مقاله'],

    'options' => [
        // Select, Badge, BooleanGroup and filter option labels
        'Draft' => 'پیش‌نویس',
    ],

    'overrides' => [
        // when one resource needs a different word, keyed by uriKey
        'orders' => ['fields' => ['Status' => 'وضعیت سفارش']],
    ],
];
```

Anything without a translation keeps its English text and still gets its grey real name.

### Finding what is left

```bash
php artisan nova-translated-labels:missing          # table
php artisan nova-translated-labels:missing --json   # paste-ready
```

The command walks every resource in `app/Nova` (change it with `--path`), including fields, actions, filters and options. It exits with a failure code while anything is missing, so you can run it in CI. If some fields look at the current user, pass `--user=1` to walk the resources as that user.

## Configuration

`config/nova-translated-labels.php`:

| Key | Default | What it does |
| --- | --- | --- |
| `file` | `nova-labels` | Lang file name: `lang/{locale}/{file}.php` |
| `subtitles.groups` | `original` | What to show in grey under menu groups: `original`, `none` |
| `subtitles.resources` / `fields` / `options` | `database` | What to show in grey: `database` (table, column, foreign key or stored value), `original` (the English label from your code), `both`, or `none` |
| `translated_pattern` | any non-Latin letter | Labels matching it count as already translated. Set it to `null` when your admin language uses the Latin script. |
| `max_static_options` | `40` | Option lists longer than this are treated as database rows and left untranslated |
| `script` | `true` | Load the script that draws the grey line |

## How it works

The resource trait hooks into Nova's field, action and group resolution and replaces each label with its translation. It then appends the real name between two invisible separator characters (U+2063). A small script that Nova loads on every page turns that marked text into the grey line in the sidebar, the index headers, the detail and form labels and the filter menu. Everywhere else (buttons, toasts, breadcrumbs, the browser tab title), the script removes the real name.

A few things to know:

- **Native dropdowns** (`<option>`) cannot hold styled text, so their options show the value inline: `پیش‌نویس (draft)`.
- **Action keys do not change.** Nova builds an action's URL key from its name, so the package pins the original key before renaming the action. Existing action URLs and export filenames keep working.
- **CSV exports** are untouched. Their headers come from your export code, not from field labels.
- **Validation messages** use the translated label, with the real name inline.
- Relation fields are only resolved to a foreign key or table when Nova itself treats them as relations. A plain field that happens to share a name with a model method is never called.

## Testing

Nova is not on Packagist, so installing this package's dev dependencies needs a Nova licence:

```bash
composer config http-basic.nova.laravel.com your-email@example.com your-license-key
composer install
composer test
composer analyse
```

## Contributing

Issues and pull requests are welcome. Please add a test for any behaviour you change.

## License

The MIT License (MIT). See [LICENSE](LICENSE.md).
