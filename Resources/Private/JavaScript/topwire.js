import './turbo-listeners.js'
// Listeners MUST be imported first to be able to react to custom elements
// already firing events on init
import * as Turbo from '@hotwired/turbo'

Turbo.session.drive = false

export default Turbo
