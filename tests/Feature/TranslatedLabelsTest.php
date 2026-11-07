<?php

declare(strict_types=1);

use Adedsec\NovaTranslatedLabels\FieldDecorator;
use Adedsec\NovaTranslatedLabels\Label;
use Adedsec\NovaTranslatedLabels\Tests\Fixtures\Models\Post as PostModel;
use Adedsec\NovaTranslatedLabels\Tests\Fixtures\Nova\Actions\PublishPost;
use Adedsec\NovaTranslatedLabels\Tests\Fixtures\Nova\Filters\StatusFilter;
use Adedsec\NovaTranslatedLabels\Tests\Fixtures\Nova\Post;
use Adedsec\NovaTranslatedLabels\Translator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Lang;
use Illuminate\Validation\ValidationException;
use Laravel\Nova\Fields\BooleanGroup;
use Laravel\Nova\Fields\Field;
use Laravel\Nova\Fields\Select;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\ActionRequest;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;

/**
 * @return array<string, Field>
 */
function postFields(): array
{
    $fields = [];

    foreach ((new Post(new PostModel))->availableFields(NovaRequest::create('/nova-api/posts')) as $field) {
        $fields[$field->attribute] = $field;
    }

    return $fields;
}

it('shows a field in the admin language with its database column', function (): void {
    translations(['fields.Title' => 'عنوان']);

    expect(postFields()['title']->name)->toBe(Label::make('عنوان', 'title'));
});

it('names the foreign key of a belongs-to field and the table of a has-many field', function (): void {
    $fields = postFields();

    expect($fields['author']->name)->toBe(Label::make('Author', 'author_id'))
        ->and($fields['comments']->name)->toBe(Label::make('Comments', 'comments'));
});

it('gives a computed field no real name', function (): void {
    expect(postFields()['ComputedField']->name)->toBe('Word Count');
});

it('keeps the English label when no translation exists', function (): void {
    expect(postFields()['title']->name)->toBe(Label::make('Title', 'title'));
});

it('lets one resource override a shared translation', function (): void {
    translations(['fields.Title' => 'عنوان', 'overrides.posts.fields.Title' => 'تیتر مقاله']);

    expect(postFields()['title']->name)->toBe(Label::make('تیتر مقاله', 'title'));
});

it('shows the resource and its group with the table and the original group name', function (): void {
    translations([
        'groups.Content' => 'محتوا',
        'resources.posts.label' => 'مقاله‌ها',
        'resources.posts.singular' => 'مقاله',
    ]);

    expect(Post::group())->toBe(Label::make('محتوا', 'Content'))
        ->and(Post::label())->toBe(Label::make('مقاله‌ها', 'posts'))
        ->and(Post::singularLabel())->toBe('مقاله');
});

it('hides a kind of real name when its subtitle is turned off', function (): void {
    config(['nova-translated-labels.subtitles.fields' => false, 'nova-translated-labels.subtitles.resources' => false]);
    translations(['fields.Title' => 'عنوان', 'resources.posts.label' => 'مقاله‌ها']);

    expect(postFields()['title']->name)->toBe('عنوان')
        ->and(Post::label())->toBe('مقاله‌ها');
});

it('shows the original English label instead of the column when fields are set to original', function (): void {
    config(['nova-translated-labels.subtitles.fields' => 'original']);
    translations(['fields.Title' => 'عنوان']);

    expect(postFields()['title']->name)->toBe(Label::make('عنوان', 'Title'));
});

it('shows the original label and the database name together when set to both', function (): void {
    config(['nova-translated-labels.subtitles.fields' => 'both', 'nova-translated-labels.subtitles.resources' => 'both']);
    translations(['fields.Author' => 'نویسنده', 'resources.posts.label' => 'مقاله‌ها']);

    expect(postFields()['author']->name)->toBe(Label::make('نویسنده', 'Author · author_id'))
        ->and(Post::label())->toBe(Label::make('مقاله‌ها', 'Posts · posts'));
});

it('shows nothing extra in original mode while a label is still untranslated', function (): void {
    config(['nova-translated-labels.subtitles.fields' => 'original']);

    expect(postFields()['title']->name)->toBe('Title');
});

it('shows the original option label when options are set to original', function (): void {
    config(['nova-translated-labels.subtitles.options' => 'original']);
    translations(['options.Draft' => 'پیش‌نویس']);

    $options = collect(postFields()['status']->jsonSerialize()['options']);

    expect($options->firstWhere('value', 'draft')['label'])->toBe(Label::make('پیش‌نویس', 'Draft'));
});

it('shows select options in the admin language with their stored value', function (): void {
    translations(['options.Draft' => 'پیش‌نویس']);

    $options = collect(postFields()['status']->jsonSerialize()['options']);

    expect($options->firstWhere('value', 'draft')['label'])->toBe(Label::make('پیش‌نویس', 'draft'));
});

it('translates badge labels', function (): void {
    translations(['options.draft' => 'پیش‌نویس']);

    expect(postFields()['stage']->labels['draft'])->toBe('پیش‌نویس');
});

it('leaves database-driven option lists untouched', function (): void {
    $options = collect(range(1, 50))->mapWithKeys(fn (int $id): array => ['code-'.$id => 'Class '.$id])->all();
    $field = Select::make('Class')->options($options);

    resolve(FieldDecorator::class)->decorate([$field], null, null);

    expect(collect($field->jsonSerialize()['options'])->pluck('label')->all())->toBe(array_values($options));
});

it('never relabels a list-shaped boolean group, whose labels are what gets saved', function (): void {
    translations(['options.Admin' => 'مدیر']);
    $field = BooleanGroup::make('Roles')->options(['Admin', 'Editor']);

    resolve(FieldDecorator::class)->decorate([$field], null, null);

    expect(collect($field->jsonSerialize()['options'])->pluck('name')->all())->toBe(['Admin', 'Editor']);
});

it('translates a panel shared by several fields once, even when translations are in the Latin script', function (): void {
    config(['nova-translated-labels.translated_pattern' => null]);
    translations(['panels.Details' => 'Info', 'panels.Info' => 'Information']);
    $panel = Panel::make('Details', [Text::make('Title'), Text::make('Body')]);

    resolve(FieldDecorator::class)->decorate($panel->data, null, null);

    expect($panel->name)->toBe('Info')
        ->and(resolve(Translator::class)->missing())->not->toHaveKey('panels');
});

it('treats labels already in a non-Latin script as translated', function (): void {
    $field = Select::make('وضعیت')->options(['a' => 'فعال']);

    resolve(FieldDecorator::class)->decorate([$field], null, null);

    expect($field->name)->toBe('وضعیت');
});

it('translates an action name without changing the key Nova runs it by', function (): void {
    translations(['actions.Publish Post' => 'انتشار مقاله', 'fields.Reason' => 'دلیل']);

    $action = (new Post(new PostModel))->resolveActions(NovaRequest::create('/nova-api/posts'))->sole();

    expect($action->name())->toBe('انتشار مقاله')
        ->and($action->uriKey())->toBe('publish-post')
        ->and($action->jsonSerialize()['fields'][0]->name)->toBe('دلیل');
});

it('translates a filter name and its options', function (): void {
    translations(['filters.Status' => 'وضعیت', 'options.Draft' => 'پیش‌نویس']);

    $filter = (new StatusFilter)->jsonSerialize();

    expect($filter['name'])->toBe('وضعیت')
        ->and(collect($filter['options'])->firstWhere('value', 'draft')['label'])->toBe(Label::make('پیش‌نویس', 'draft'));
});

it('reads translations from the file named in the config', function (): void {
    config(['nova-translated-labels.file' => 'admin']);
    Lang::addLines(['admin.fields.Title' => 'عنوان'], 'fa');

    expect(postFields()['title']->name)->toBe(Label::make('عنوان', 'title'));
});

it('names the translated field in an action validation message', function (): void {
    translations(['fields.Reason' => 'دلیل']);
    $request = ActionRequest::createFrom(Request::create('/nova-api/posts/action', 'POST'));

    $errors = rescue(
        fn (): array => (new PublishPost)->validateFields($request),
        fn (ValidationException $exception): array => $exception->errors(),
        report: false,
    );

    expect($errors['reason'][0])->toContain('دلیل')->not->toContain('Reason');
});
