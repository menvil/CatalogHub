# Architecture tests

`composer test:architecture` runs the architecture-named PHPUnit suite and the debt registry report. The baseline blocks:

- Central/Site Admin presentation imports crossing contexts;
- `Illuminate\Http\Request` inside `app/Data`;
- Filament, controllers, or Livewire imported into `app/Domains`;
- raw roles or `PermissionMatrix` used directly by presentation code.

Existing PHPStan presentation/database rules remain part of the same command. Each forbidden shape has a positive detection fixture.

The only exception registry is `tests/Architecture/allowlist.php`. Entries must name one existing `app/*.php` file and include `owner`, `reason`, and removal `task`. Directory, wildcard, and namespace exemptions are rejected; stale entries fail the suite.
