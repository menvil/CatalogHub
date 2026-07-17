# HTTP Boundaries

CatalogHub controllers are transport adapters. They coordinate validated input,
authorization, application actions or queries, and the HTTP response. They do
not own validation, permissions, business rules, transactions, or complex data
access.

## Controllers

A controller may:

- receive a dedicated Form Request and route-bound models;
- authorize a resource through a policy or Gate;
- invoke an Action, Service, or Query Object;
- return a response, view, or redirect.

A controller must not:

- call `Request::validate()`, `Request::validateWithBag()`, `Validator::make()`,
  or the `validator()` helper;
- read request payload or query values through `input()`, `query()`, `string()`,
  array access, magic properties, or equivalent unvalidated accessors;
- call `hasCatalogHubPermission()` directly;
- construct low-level or raw database queries;
- persist Eloquent models or relations directly from Controllers, Form Requests,
  Livewire components, or Filament resources/pages;
- manage database transactions;
- contain reusable domain rules.

## Form Requests

Every HTTP use case with input has a dedicated class under `app/Http/Requests`.
Form Requests own normalization, validation rules, messages, and validated input
access. Controllers consume typed accessors or data objects exposed by that Form
Request; they do not interpret the array returned by `validated()` themselves.
Context-only request metadata such as host, authenticated user, session, IP, and
URL remains available to transport code. Resource authorization remains explicit through policies and Gate; Form
Requests return `true` from `authorize()` unless a use case deliberately adopts
a documented alternative convention.

Livewire and Filament component validation is outside this controller rule
because those frameworks own a different request lifecycle. Reusable validation
rules should still be extracted when they are shared or non-trivial.

## Authorization

Policies adapt Laravel authorization abilities to CatalogHub's permission
matrix. Controllers must not inspect roles or permission keys directly.

- model/resource rules belong in policies;
- non-resource rules belong in named Gate abilities;
- permission-key changes are product decisions and are not inferred during a
  transport refactor.

## Application layer

State changes and transactions belong in Actions or domain Services. Framework
save hooks may delegate to an Action, but application-owned presentation code
must not call model, builder, or relation mutation methods directly. Read-side
composition belongs in Eloquent scopes or Query Objects. A controller should be
readable as a short sequence of authorize, invoke, respond.
