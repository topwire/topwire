import * as Turbo from '../JavaScript/vendor/@hotwired_turbo@v7.2.4.js';
Turbo.session.drive = 0

document.addEventListener('turbo:before-fetch-request', async (event) => {
    console.log(event)
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
