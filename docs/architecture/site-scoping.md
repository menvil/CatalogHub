# Site-scoped queries

Site-local reads receive a `Site` explicitly. They use a named local scope or a query object such as `SiteContentQuery`; the current request, session, authenticated user, and static state must not silently choose a site.

`SiteContentQuery` is the foundation reference:

```php
$content = $siteContentQuery->find($site, $contentItemId);
```

The `ContentItem::forSite($site)` scope is an equally valid lower-level form when the query remains simple. Callers authorize access to the given site before running a site query; scoping does not replace authorization.

Cross-site reads are intentionally unscoped, explicitly named by the calling action/query, and authorized at that action boundary. Queue jobs receive a site ID or `Site` deliberately and must not recover a site from request state.
