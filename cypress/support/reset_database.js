var reset_database_php = Cypress.config('supportFolder') + '/reset_database.php'

exports.reset = function(stage, admin) {
  cy.exec('php ' + reset_database_php).its('code').should('eq', 0)
}

exports.resetFinished = function(stage, admin) {
  cy.exec('php ' + reset_database_php + ' finished').its('code').should('eq', 0)
}

exports.resetVoting = function(stage, admin) {
  cy.exec('php ' + reset_database_php + ' voting').its('code').should('eq', 0)
}

