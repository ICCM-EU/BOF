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
  cy.get('div[class=topic]:nth-child(' + (topic + 1) + ')').within(() => {

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
    var votes_full = [
       // topic 1
       [1,2,3,4,5,6,7,8,9],
       // topic 2
       [1,2,3,4,5,6,7,8,9,10,11,12,13],
       // topic 3
       [1,2,3,4,5,6,7],
       // topic 4
       [10,11,12,13,14,15]
    ]
    var votes_facilitator = [
       // topic 1
       [21],
       // topic 2
       [22],
       // topic 3
       [22],
       // topic 4
       [23]
    ]
    var votes_quarter = [
       // topic 1
       [30,31,32,33,34,35,36,37],
       // topic 2
       [30,31,32],
       // topic 3
       [33,34,35,36],
       // topic 4
       [35,36,37,38,39]
    ]
    var i
    for(i=1; i < 60; i++) {
      login_user("user" + i, "pwd" + i)

      var topic
      for (topic=1; topic < 4; topic++)
      {
        if (votes_facilitator[topic-1].indexOf(i) > -1)
          cast_vote(topic, 0)
        if (votes_full[topic-1].indexOf(i) > -1)
          cast_vote(topic, 1)
        if (votes_quarter[topic-1].indexOf(i) > -1)
          cast_vote(topic, 2)
      }
      logout_user()
    }
  })
})
