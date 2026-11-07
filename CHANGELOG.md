# Changelog

All notable changes to this package are documented here.

## v0.2.1 - 2026-11-07

- Fix: a grey line no longer stays behind when Nova rewrites a label with the same visible text but no real name, such as a computed `Name` column after switching resources. The script now counts its own writes instead of comparing text.

## v0.2.0 - 2026-11-07

- Changed: `nova-translated-labels:missing --json` prints only the JSON, nested like the lang file with empty values to fill in (`{"resources": {"posts": {"label": ""}}}`), instead of lists followed by a count line. `Translator::missing()` now maps each lang key to its English text.
- Fix: input placeholders, `title` and `aria-label` attributes no longer show the real name in brackets.
- Fix: a subtitle that contains a closing bracket, such as `Weight (kg)` in `original` mode, is drawn instead of left inline.
- Fix: `nova-translated-labels:missing` fails when a resource could not be walked, instead of passing with labels it never saw.
- Fix: a panel is translated once, not once per field. With `translated_pattern` set to `null`, its translated name was looked up again and reported as missing.
- Fix: a grey line no longer stays behind when Nova reuses a label for text without a real name, such as a computed field's column after switching resources.
- Fix: the `nova-translated-labels-lang` publish tag names the file after the `file` config option instead of always `nova-labels.php`.

## v0.1.0 - 2026-11-07

- Translate Nova menu groups, resources, fields, panels, filters, actions and Select/Badge/BooleanGroup options from `lang/{locale}/nova-labels.php`.
- Show each label's real database name (column, foreign key, related table, table, group or stored value) in grey under the translation.
- `nova-translated-labels:missing` command lists untranslated labels as a table or JSON.
