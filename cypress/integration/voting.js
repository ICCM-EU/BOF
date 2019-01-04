function login_user(username, password) {
  cy.visit("/login")
  cy.get('input[name=user_name]').clear().type(username)
  cy.get('input[name=password]').clear().type(password)
  cy.get('input[type=submit]').click()
}

function logout_user() {
  cy.visit("/logout")
}

function cast_vote(topic, vote) {
  cy.visit("/topics")
  // select the div class=topic
  cy.get('div[class=topic]:nth-child(' + topic+ ')').within(() => {

    // select the form to click
    if (vote == 0) {
      cy.get('form[class=facilitation] input[type=submit]').click()
    }
    else if (vote == 1) {
      cy.get('form[class=fullvote] input[type=submit]').click()
    }
    else if (vote == 2) {
      cy.get('form[class=quartervote] input[type=submit]').click()
    }
    else if (vote == -1) {
      cy.get('form[class=clearvote] input[type=submit]').click()
    }
  })
}

describe('test voting stage', function() {
  it('cast votes', function() {
    var i
    for(i=1; i < 2; i++) {
      login_user("user" + i, "pwd" + i)

      if (i == 1) {
        cast_vote(4, 0)
        cast_vote(5, 1)
        cast_vote(6, 1)
        cast_vote(7, 2)
      }
      logout_user()
    }
  })
})
