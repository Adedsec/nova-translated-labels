# Changelog

All notable changes to this package are documented here.

## Unreleased

- Fix: input placeholders, `title` and `aria-label` attributes no longer show the real name in brackets.

## v0.1.0 - 2026-11-07

- Translate Nova menu groups, resources, fields, panels, filters, actions and Select/Badge/BooleanGroup options from `lang/{locale}/nova-labels.php`.
- Show each label's real database name (column, foreign key, related table, table, group or stored value) in grey under the translation.
- `nova-translated-labels:missing` command lists untranslated labels as a table or JSON.
