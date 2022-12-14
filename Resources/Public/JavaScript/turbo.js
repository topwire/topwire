import * as Turbo from '../JavaScript/vendor/@hotwired_turbo@v7.2.4.js';

Turbo.session.drive = 0

document.addEventListener('turbo:before-fetch-request', async (event) => {
    // console.log(event)
    event.preventDefault()
    const headers = event.detail.fetchOptions.headers
    const turboFrame = event.target.tagName === 'turbo-frame' ? event.target : event.target.closest('turbo-frame')
    if (!headers['Turbo-Frame']
        || !turboFrame.dataset
        || !turboFrame.dataset.renderingContext
    ) {
        return
    }
    headers['Telegraph-Rendering-Context'] = turboFrame.dataset.renderingContext
    event.detail.fetchOptions.headers = headers
    event.detail.resume()
})

document.addEventListener('turbo:before-fetch-response', async (event) => {
    // console.log(event)
})

document.addEventListener('turbo:submit-start', async (event) => {
    // event.detail.formSubmission.mustRedirect = true
    // console.log(event)
})

document.addEventListener('turbo:submit-end', async (event) => {
    // console.log(event)
})
