// ***********************************************
// This example commands.js shows you how to
// create various custom commands and overwrite
// existing commands.
//
// For more comprehensive examples of custom
// commands please read more here:
// https://on.cypress.io/custom-commands
// ***********************************************
//
//
// -- This is a parent command --
// Cypress.Commands.add("login", (email, password) => { ... })
//
//
// -- This is a child command --
// Cypress.Commands.add("drag", { prevSubject: 'element'}, (subject, options) => { ... })
//
//
// -- This is a dual command --
// Cypress.Commands.add("dismiss", { prevSubject: 'optional'}, (subject, options) => { ... })
//
//
// -- This will overwrite an existing command --
// Cypress.Commands.overwrite("visit", (originalFn, url, options) => { ... })
//

Cypress.Commands.add('typeLogin', (user) => {
  cy.request('POST', "/authenticate", { user_name: user.username, password: user.password })
})

Cypress.Commands.add('logout', () => {
  cy.request("/logout")
})

Cypress.Commands.add('createUser', (user) => {
  cy.request('POST', "/new_user", { user_name: user.username, password: user.password, email: user.email }).its('body').should('include', 'All topics')
})
