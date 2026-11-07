<?php

declare(strict_types=1);

return [

    /*
    | The lang file that holds the translations: lang/{locale}/{file}.php.
    */
    'file' => 'nova-labels',

    /*
    | What to show in grey under each kind of translated label:
    |
    |   'database' the name behind it in your database: the table of a resource,
    |              the column of a field (the foreign key of a BelongsTo, the
    |              related table of a has-many field), the stored value of an
    |              option. Groups have no table, so they show their original name.
    |   'original' the English label your code uses, before translation.
    |   'both'     the original label and the database name: "Phone Number · phone_number".
    |   'none'     nothing (false works too).
    |
    | Nothing is shown when the subtitle would repeat the label itself.
    */
    'subtitles' => [
        'groups' => 'original',
        'resources' => 'database',
        'fields' => 'database',
        'options' => 'database',
    ],

    /*
    | A label that matches this pattern is treated as already translated, so it
    | is left alone and never reported as missing. The default matches any
    | letter outside the Latin script (Persian, Arabic, Hebrew, Cyrillic, CJK…).
    | Set it to null when your translations are in a Latin-script language.
    */
    'translated_pattern' => '/[^\p{Latin}\p{Common}\p{Inherited}]/u',

    /*
    | Option lists longer than this are treated as database rows rather than
    | fixed labels, and are left untranslated.
    */
    'max_static_options' => 40,

    /*
    | Load the script that draws the grey subtitle. Without it, labels show the
    | real name inline, in parentheses.
    */
    'script' => true,

];
