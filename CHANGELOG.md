# Changelog

All notable changes to `advanced-roster-for-filament` will be documented in this file.

## v0.3.0 - 2026-07-04

### Added

- Extensible `RosterFilter` contract with filter registry
- Built-in assignee filter (toolbar funnel icon, modal, per-user persistence)
- Feature flag `features.filters` (default: enabled)
- Filter reset action and active-filter badge on the roster toolbar

### Changed

- README updated with marketing thumbnail and filter documentation

## v0.1.1 - 2026-06-22

### Changed

- Added Laravel 13 support (`illuminate/contracts` and `illuminate/support` `^13.0`)

## v0.1.0 - 2026-06-22

### Added

- Initial release of the Filament roster page plugin
- Configurable assignee model with optional model method overrides
- `roster_entries`, `roster_notes`, and `roster_user_preferences` tables
- Drag-and-drop entry move/copy with series support
- Assignee row reordering persisted per user and scope
- Recurring entries and day notes
- Overlap validation with extensible `RosterEntryValidator` registry
- Optional PDF export via Spatie Laravel PDF with print Blade fallback
- English and German translations
- Screenshots in README
