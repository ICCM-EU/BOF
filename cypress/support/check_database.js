var check_database_php = Cypress.config('supportFolder') + '/check_database.php'

exports.checkPrepBoF = function(ownSlot, round, location) {
  cy.exec('php ' + check_database_php + ' prepBoF ' + ownSlot + ' ' + round + ' ' + location).its('code').should('eq', 0)
}

