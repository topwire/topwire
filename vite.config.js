import { defineConfig } from 'vite'

export default defineConfig({
    server: {
        // Needed for assets (e.g. fonts referenced in CSS) are loaded from the correct domain
        origin: 'http://localhost:5173',
    },
    // No public dir, when using Vite with a TYPO3 backend
    publicDir: false,
    // Must be empty so that referenced assets get relative URLs
    base: '',
    build: {
        manifest: 'manifest.json',
        assetsInlineLimit: 0,
        rollupOptions: {
            input: {
                'topwire/topwire': 'Resources/Private/JavaScript/topwire.js',
            },
            output: {
                dir: 'public/_assets/vite/',
                format: 'es'
            },
        }
    }
})
