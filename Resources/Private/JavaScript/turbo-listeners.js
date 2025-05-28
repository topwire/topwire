import { morph } from '@alpinejs/morph'

document.addEventListener('turbo:before-fetch-request', async (event) => {
    event.preventDefault()
    const headers = event.detail.fetchOptions.headers
    if (event.target.tagName === 'HTML') {
        // We are not interested in Turbo drive requests for now
        event.detail.resume()
        return
    }
    // Link trigger or form submit?
    const turboFrame = event.target.tagName === 'turbo-frame' ? event.target : event.target.closest('turbo-frame')
    if (!headers['Turbo-Frame'] || !turboFrame?.dataset?.topwireContext) {
        event.detail.resume()
        return
    }
    headers['Topwire-Context'] = turboFrame.dataset.topwireContext
    event.detail.fetchOptions.headers = headers
    event.detail.resume()
})

document.addEventListener('turbo:before-frame-render', async (event) => {
    const originalRender = event.detail.render;
    event.detail.render = (currentElement, newElement) => {
        let render = originalRender;
        if (currentElement.dataset?.topwireMorph) {
            render = morph
            if (!newElement.dataset?.topwireMorph) {
                newElement.setAttribute('data-topwire-morph', currentElement.dataset?.topwireMorph)
            }
        }
        render(currentElement, newElement)
    }
})
