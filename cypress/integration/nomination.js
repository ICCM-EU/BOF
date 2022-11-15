function create_topic(title) {
  cy.visit("/nomination")
  cy.get('input[name=title]').clear().type(title)
  cy.get('textarea[name=description]').clear().type("description for " + title)
  cy.get('input[type=submit]').click()
}

describe('test nomination stage', function() {
  before(function() {
    require('../support/reset_database.js').reset()
  })

  it('create users', function() {
    var i
    for(i=1; i < 60; i++) {
      cy.createUser({username: "user" + i, password: "Test123!pwd" + i, email: "user" + i + "@example.org"})
    }
  })

  it('nominate topics', function() {
    cy.typeLogin({username: "user1", password: "pwd1"})
    var i
    for(i=1; i < 15; i++) {
      create_topic("topic" + i)
    }
    cy.logout()
  })
})
