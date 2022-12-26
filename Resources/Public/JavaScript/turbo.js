import * as Turbo from '../JavaScript/vendor/@hotwired_turbo@v7.2.4.js';
Turbo.session.drive = 0

document.addEventListener('turbo:before-fetch-request', async (event) => {
    console.log(event)
    event.preventDefault()
    const headers = event.detail.fetchOptions.headers
    const turboFrame = event.target.tagName === 'turbo-frame' ? event.target : event.target.closest('turbo-frame')
    if (!headers['Turbo-Frame']
        || !turboFrame.dataset
        || !turboFrame.dataset.topwireContext
    ) {
        event.detail.resume()
        return
    }
    headers['Topwire-Context'] = turboFrame.dataset.topwireContext
    event.detail.fetchOptions.headers = headers
    event.detail.resume()
})

document.addEventListener('turbo:before-fetch-response', async (event) => {
    console.log(event)
})

document.addEventListener('turbo:submit-start', async (event) => {
    console.log(event)
})

document.addEventListener('turbo:submit-end', async (event) => {
    console.log(event)
})
