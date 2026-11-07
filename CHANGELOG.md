# Changelog

All notable changes to this package are documented here.

## Unreleased

- Fix: input placeholders, `title` and `aria-label` attributes no longer show the real name in brackets.
- Fix: a subtitle that contains a closing bracket, such as `Weight (kg)` in `original` mode, is drawn instead of left inline.
- Fix: `nova-translated-labels:missing` fails when a resource could not be walked, instead of passing with labels it never saw.

## v0.1.0 - 2026-11-07

- Translate Nova menu groups, resources, fields, panels, filters, actions and Select/Badge/BooleanGroup options from `lang/{locale}/nova-labels.php`.
- Show each label's real database name (column, foreign key, related table, table, group or stored value) in grey under the translation.
- `nova-translated-labels:missing` command lists untranslated labels as a table or JSON.
