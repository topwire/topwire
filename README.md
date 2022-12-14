## Concepts

### Rendering Context

The rendering context is a piece of information, that defines,
which content element should be rendered standalone, without
anything else that is available on the page.
Most of the time it will be a content element containing an Extbase plugin.

The context requires the following technical information:

1. The record table name (e.g. `tt_content`)
2. The record uid
3. The rendering path, as defined in TypoScript (e.g. `tt_content.form_formframework.20`)
4. The page id

While the 90% use case is to define a rendering context for content elements
and/ or plugins, it is also possible to define a rendering context for
other tables as well. The only requirement is, that the record with the uid
exists in the table and that the TypoScript defined in the path is also available.

