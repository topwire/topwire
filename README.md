## Examples

### Generating links for partial rendering

```
lib.tsExample = TEXT
lib.tsExample.typolink.htmlSpecialChars = 1
lib.tsExample.typolink.parameter.data = page:uid
lib.tsExample.typolink.additionalParams = &tx_topwire[type]=plugin&tx_topwire[extensionName]=TopwireExamples&tx_topwire[pluginName]=Json
lib.tsExample.typolink.returnLast = url
```

```html
<html
    xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
    xmlns:topwire="http://typo3.org/ns/Helhum/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">
    
    <f:link.page 
        pageUid="42" 
        additionalParams="{tx_topwire: {type: 'plugin', extensionName: 'TopwireExamples', pluginName: 'Json'}}" class="btn btn-primary"
    >
        Download
    </f:link.page>

</html>
```


### Wrapping parts of a Fluid template of an Extbase plugin in a Turbo Frame 

#### `Default.html`

```html
<html
    xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
    xmlns:topwire="http://typo3.org/ns/Helhum/Topwire/ViewHelpers"
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
    xmlns:topwire="http://typo3.org/ns/Helhum/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

    <topwire:turbo.frame id="my_plugin">
        <h2>My action</h2>
        <f:link.action action="default">Show default action result</f:link.action>
    </topwire:turbo.frame>    

</html>
```

### Render a plugin, wrapped in a Turbo Frame

```html
<html
    xmlns:topwire="http://typo3.org/ns/Helhum/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

    <topwire:turbo.frame.withContext 
        id="other_plugin" 
        context="{topwire:context.plugin(extensionName: 'FeLogin', pluginName: 'Login')}" 
    />

</html>
```

### Render a plugin asynchronously, wrapped in a Turbo Frame

```html
<html
    xmlns:topwire="http://typo3.org/ns/Helhum/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

    <topwire:turbo.frame.withContext 
        id="other_plugin_async" async="true" 
        context="{topwire:context.plugin(extensionName: 'FeLogin', pluginName: 'Login')}"
    >
        Loading...
    </topwire:turbo.frame.withContext>

</html>
```

### Render content element, wrapped in a Turbo Frame

```html
<html
    xmlns:topwire="http://typo3.org/ns/Helhum/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

    <topwire:turbo.frame.withContext 
        id="content_element" 
        context="{topwire:context.contentElement(contentElementUid: '148')}"
    />

</html>
```

### Render content element asynchronously, wrapped in a Turbo Frame

```html
<html
    xmlns:topwire="http://typo3.org/ns/Helhum/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

    <topwire:turbo.frame.withContext 
        id="content_element_async" 
        async="true" 
        context="{topwire:context.contentElement(contentElementUid: '148')}"
    >
        Loading...
    </topwire:turbo.frame.withContext>

</html>
```

### Render any TypoScript, wrapped in a Turbo Frame

```html
<html
    xmlns:topwire="http://typo3.org/ns/Helhum/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

    <topwire:turbo.frame.withContext 
        id="typo_script" 
        context="{topwire:context.typoScript(typoScriptPath: 'lib.tsExample')}"
    />

</html>
```

### Render any TypoScript asynchronously, wrapped in a Turbo Frame

```html
<html
    xmlns:topwire="http://typo3.org/ns/Helhum/Topwire/ViewHelpers"
    data-namespace-typo3-fluid="true">

    <topwire:turbo.frame.withContext 
        id="typo_script_async" 
        async="true" 
        context="{topwire:context.typoScript(typoScriptPath: 'lib.tsExample')}"
    >
        Loading...
    </topwire:turbo.frame.withContext>

</html>
```


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


## TODO

* [x] Maybe optionally allow wrapping server response in turbo frame to 
      not require changing the plugin itself
* [ ] Implement other features of frames or make it possible to arbitrary ones
* [ ] Add a way to address frames with the dynamically generated ids
* [x] Implement URI generation for addressing plugin rendering via URLs
* [ ] Re-evaluate responsibilities of Frame and TopwireContext
      (Frame is currently used to unserialize TopwireContext for URLs, 
      Frame also used to represent a frame during rendering. Introduce a third entity?)
* [ ] Evaluate routing enhancers for nice URLs and a clean way to add page arguments
* [ ] Implement turbo streams
