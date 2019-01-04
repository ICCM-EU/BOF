function create_user(username, password) {
  cy.visit("/register")
  cy.get('input[name=user_name]').clear().type(username)
  cy.get('input[name=password]').clear().type(password)
  cy.get('input[type=submit]').click()
}

function login_user(username, password) {
  cy.visit("/login")
  cy.get('input[name=user_name]').clear().type(username)
  cy.get('input[name=password]').clear().type(password)
  cy.get('input[type=submit]').click()
}

function logout_user() {
  cy.visit("/logout")
}

function create_topic(title) {
  cy.visit("/nomination")
  cy.get('input[name=title]').clear().type(title)
  cy.get('textarea[name=description]').clear().type("description for " + title)
  cy.get('input[type=submit]').click()
}

describe('test nomination stage', function() {
  it('create users', function() {
    var i
    for(i=1; i < 60; i++) {
      create_user("user" + i, "pwd" + i)
    }
  })

  it('nominate topics', function() {
    login_user("user1", "pwd1")
    var i
    for(i=1; i < 15; i++) {
      create_topic("topic" + i)
    }
    logout_user()
  })
})
