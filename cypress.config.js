const { defineConfig } = require('cypress')

module.exports = defineConfig({
  defaultCommandTimeout: 10000,
  video: false,
  supportFolder: 'cypress/support',
  e2e: {
    defaultCommandTimeout: 10000,
    redirectionLimit: 10000,
    supportFile: 'cypress/support/commands.js',
    specPattern: 'cypress/integration/**/*.js'
  },
})
