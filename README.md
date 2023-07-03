## Installation

```bash
composer require topwire/topwire
```

## Configuration

Add the following TypoScript to your site:

```
page.includeJSLibs.topwire = EXT:topwire/Resources/Public/JavaScript/topwire.js
page.includeJSLibs.topwire.type = module
page.includeJSLibs.topwire.async = 1
page.includeJSLibs.topwire.defer = 1
page.includeJSLibs.topwire.disableCompression = 1
page.includeJSLibs.topwire.excludeFromConcatenation = 1
```

Alternatively you can import this module in your frontend build chain.

## Examples

### Generating links for partial rendering

#### `lib.typoscript`
```
lib.tsExample = TEXT
lib.tsExample.typolink.htmlSpecialChars = 1
lib.tsExample.typolink.parameter.data = page:uid
lib.tsExample.typolink.topwire.type = plugin
lib.tsExample.typolink.topwire.extensionName = TopwireExamples
lib.tsExample.typolink.topwire.pluginName = Json
lib.tsExample.typolink.returnLast = url
```

#### `Fluid.html`
```html
<html
    xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
    xmlns:topwire="http://typo3.org/ns/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

<topwire:context.plugin>
    <f:link.action
        class="btn btn-primary"
        additionalParams="{topwire: {type: 'context'}}"
        action="json"
    >
        Link that renders plugin action
    </f:link.action>
</topwire:context.plugin>

<f:link.page
    class="btn btn-primary"
    pageUid="42" 
    additionalParams="{topwire: {type: 'typoScript', typoScriptPath: 'lib.tsPluginExample', recordUid: '42', tableName: 'tt_content'}}"
>
    Show rendered TypoScript path
</f:link.page>

</html>
```


### Wrap parts of a Fluid template of an Extbase plugin in a Turbo Frame 

#### `Default.html`

```html
<html
    xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
    xmlns:topwire="http://typo3.org/ns/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

<topwire:turbo.frame id="my_plugin">
    <h2>Default action</h2>
    <f:link.action action="my">Show my action result</f:link.action>
</topwire:turbo.frame>    

</html>
```

#### `My.html`

```html
<html
    xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
    xmlns:topwire="http://typo3.org/ns/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

<topwire:turbo.frame id="my_plugin">
    <h2>My action</h2>
    <f:link.action action="default">Show default action result</f:link.action>
</topwire:turbo.frame>    

</html>
```

### Render a plugin

```html
<html
    xmlns:topwire="http://typo3.org/ns/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

<topwire:context.plugin extensionName="FeLogin" pluginName="Login">
    <topwire:context.render />
</topwire:context.plugin>

</html>
```

### Render a specific (non default) action of a plugin

```html
<html
    xmlns:topwire="http://typo3.org/ns/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

<topwire:context.plugin 
    extensionName="MyExtension" 
    pluginName="MyPlugin" 
    action="list"
>
    <topwire:context.render />
</topwire:context.plugin>

</html>
```

### Render a specific (non default) action of a plugin and limit output to a given Fluid section

```html
<html
    xmlns:topwire="http://typo3.org/ns/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

<topwire:context.plugin 
    extensionName="MyExtension" 
    pluginName="MyPlugin" 
    action="list" 
    section="MySection"
>
    <topwire:context.render />
</topwire:context.plugin>

</html>
```

### Render a plugin, wrapped in a Turbo Frame

```html
<html
    xmlns:topwire="http://typo3.org/ns/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

<topwire:context.plugin extensionName="FeLogin" pluginName="Login">
    <topwire:turbo.frame id="other_plugin" wrapResponse="true">
        <topwire:context.render />
    </topwire:turbo.frame>
</topwire:context.plugin>

</html>
```

### Render a plugin asynchronously, wrapped in a Turbo Frame

```html
<html
    xmlns:topwire="http://typo3.org/ns/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

<topwire:context.plugin extensionName="FeLogin" pluginName="Login">
    <topwire:turbo.frame id="other_plugin_async" src="async" wrapResponse="true">
        Loading...
    </topwire:turbo.frame>
</topwire:context.plugin>

</html>
```

### Render content element, wrapped in a Turbo Frame

```html
<html
    xmlns:topwire="http://typo3.org/ns/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

<topwire:context.contentElement uid="148">
    <topwire:turbo.frame id="content_element" wrapResponse="true">
        <topwire:context.render />
    </topwire:turbo.frame>
</topwire:context.contentElement>

</html>
```

### Render content element asynchronously, wrapped in a Turbo Frame

```html
<html
    xmlns:topwire="http://typo3.org/ns/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

<topwire:context.contentElement uid="148">
    <topwire:turbo.frame id="content_element_async" src="async" wrapResponse="true">
        Loading...
    </topwire:turbo.frame>
</topwire:context.contentElement>

</html>
```

### Render any TypoScript, wrapped in a Turbo Frame

```html
<html
    xmlns:topwire="http://typo3.org/ns/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

<topwire:context.typoScript typoScriptPath="lib.tsExample">
    <topwire:turbo.frame id="typo_script" wrapResponse="true">
        <topwire:context.render />
    </topwire:turbo.frame>
</topwire:context.typoScript>

</html>
```

### Render any TypoScript asynchronously, wrapped in a Turbo Frame

```html
<html
    xmlns:topwire="http://typo3.org/ns/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

<topwire:context.typoScript typoScriptPath="lib.tsExample">
    <topwire:turbo.frame id="typo_script_async" src="async" wrapResponse="true">
        Loading...
    </topwire:turbo.frame>
</topwire:context.typoScript>

</html>
```


## Concepts

### Topwire Context

The topwire context is a piece of information, that defines,
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


## TODO

* [x] Maybe optionally allow wrapping server response in turbo frame to 
      not require changing the plugin itself
* [ ] Implement other features of frames or make it possible to use arbitrary ones
* [x] Register content object also as service for TYPO3 12 compatibility
* [x] Add a way to address frames with the dynamically generated ids
* [x] Implement URI generation for addressing plugin rendering via URLs
* [x] Re-evaluate responsibilities of Frame and TopwireContext
      (Frame is currently used to unserialize TopwireContext for URLs, 
      Frame also used to represent a frame during rendering. Introduce a third entity?)
* [ ] Evaluate routing enhancers for nice URLs and a clean way to add page arguments
* [x] Context VHs should propagate the context to their children, 
      maybe get rid of the argument then altogether. With that it would be
      possible to get rid of the additional withContext VH and to easily render
      multiple frames within one context without duplicating the code for that.
* [ ] Evaluate more use cases for rendering a plugin inside a plugin template
      and adapt view helpers accordingly
* [ ] Evaluate and most likely tweak usages of the view helpers in standalone view context
* [ ] Implement turbo streams helpers
* [ ] Performance evaluations and optimisations
* [ ] Evaluate static file caching options
* [ ] Implement tagging an Extbase controller via DI to inject a view resolver, 
      that returns the TopwireTemplateView. Allow defining the resulting view class,
      to be able to override the place where partials for frame rendering are located
* [ ] Triage scroll restoration issues with anchors
* [ ] Implement cleaner solution for propagating context to PageLinkBuilder
* [ ] Allow rendering *some* header values in a frame response, e.g. `<title>` tag
      or `<meta name="turbo-visit-control" content="reload">` tag
* [ ] Fix `action` argument of context view helper, when the action is uncached

