function cast_vote(topic, vote) {
  cy.visit("/topics")
  // select the div class=topic
  // the first child is the warning div. so start to count at 2
  cy.get("div[class=topic]:nth-child(" + (topic + 2) + ")").within(() => {

    // select the form to click
    if (vote == 0) {
      cy.get("form[class=facilitation] input[type=submit]").click()
    }
    else if (vote == 1) {
      cy.get("form[class=fullvote] input[type=submit]").click()
    }
    else if (vote == 2) {
      cy.get("form[class=quartervote] input[type=submit]").click()
    }
    else if (vote == -1) {
      cy.get("form[class=clearvote] input[type=submit]").click()
    }
  })
}

var votes_facilitator = [
  // topic 1
  [3, 12 ],
  // topic 2
  [2, 10  ],
  // topic 3
  [8 ],
  // topic 4
  [8, 12 ],
  // topic 5
  [11 ],
  // topic 6
  [2  ],
  // topic 7
  [1 ],
  // topic 8
  [1 ],
  // topic 9
  [1 ],
  // topic 10
  [2 ],
  // topic 11
  [9 ],
  // topic 12
  [12],
  // topic 13
  [17 ],
  // topic 14
  [5 ],
  // topic 15
  [3 ],
]

var votes_full = [
  // topic 1
  [3, 12, 15, 17, 24, 25, 28, 30, 32, 33, 36, 41, 51, 58, 59  ],
  // topic 2
  [2, 10, 18, 24, 27, 28, 30, 34, 38, 44, 56  ],
  // topic 3
  [0, 8, 9, 10, 11, 26, 31, 35, 36, 42, 44  ],
  // topic 4
  [8, 12, 14, 38, 40, 41, 43, 45, 47, 49, 52, 54  ],
  // topic 5
  [11, 19, 20, 21, 23, 36, 42, 45, 46, 51, 53  ],
  // topic 6
  [2, 4, 5, 7, 13, 14, 15, 25, 34, 42, 58  ],
  // topic 7
  [0, 1, 4, 8, 16, 22, 24, 27, 30, 31, 35, 39, 55  ],
  // topic 8
  [0, 1, 3, 4, 10, 16, 26, 43, 48, 50, 53, 55, 59  ],
  // topic 9
  [1, 6, 13, 14, 15, 21, 26, 29, 33, 38, 41, 50, 52, 56, 57, 58  ],
  // topic 10
  [2, 6, 11, 20, 27, 29, 31, 37, 39, 43, 51, 54, 59  ],
  // topic 11
  [9, 13, 17, 20, 21, 23, 28, 29, 33, 37, 40, 44, 46, 47, 49, 55  ],
  // topic 12
  [12, 18, 25, 39, 48, 50, 54  ],
  // topic 13
  [17, 19, 32, 34, 37, 40, 45, 46, 47, 49, 52, 57  ],
  // topic 14
  [5, 7, 9, 16, 18, 22, 48, 53, 56, 57  ],
  // topic 15
  [3, 5, 6, 7, 19, 22, 23, 32, 35  ],
]
var votes_quarter = [
  // topic 1
  [2, 8, 9, 10, 11, 13, 19, 20, 26, 27, 34, 37, 40, 42, 45, 46, 47, 49, 50, 54, 57  ],
  // topic 2
  [1, 4, 15, 17, 19, 21, 22, 32, 36, 39, 45, 46, 48, 50, 52, 53, 54, 55, 57, 59  ],
  // topic 3
  [1, 14, 16, 19, 21, 23, 25, 29, 32, 34, 39, 43, 45, 46, 48, 51, 52, 56, 57, 58  ],
  // topic 4
  [1, 6, 10, 13, 16, 19, 23, 26, 29, 31, 32, 33, 34, 35, 42, 44, 53  ],
  // topic 5
  [2, 3, 4, 6, 12, 15, 17, 22, 24, 25, 27, 28, 31, 32, 38, 47, 55, 57  ],
  // topic 6
  [3, 6, 10, 16, 18, 21, 22, 24, 33, 35, 36, 40, 48, 49, 51, 54, 55, 56, 59  ],
  // topic 7
  [5, 7, 10, 11, 14, 15, 18, 26, 29, 33, 36, 38, 43, 44, 45, 47, 51, 52, 53, 58  ],
  // topic 8
  [6, 7, 8, 9, 12, 14, 15, 17, 18, 20, 21, 28, 30, 33, 36, 37, 38, 39, 40, 41, 47, 49, 51, 56, 58  ],
  // topic 9
  [0, 2, 4, 5, 7, 9, 11, 12, 20, 23, 24, 28, 36, 37, 49, 59  ],
  // topic 10
  [4, 5, 8, 12, 23, 30, 44, 46, 50, 52, 58  ],
  // topic 11
  [1, 3, 5, 11, 12, 14, 22, 25, 26, 27, 30, 31, 32, 35, 38, 41, 43, 48, 50, 51, 52, 53, 57  ],
  // topic 12
  [0, 2, 5, 6, 7, 9, 16, 17, 20, 22, 23, 27, 28, 29, 30, 35, 37, 41, 56  ],
  // topic 13
  [0, 2, 3, 7, 13, 16, 18, 20, 24, 25, 26, 27, 28, 30, 31, 35, 39, 42, 43, 44, 48, 53, 55  ],
  // topic 14
  [0, 3, 8, 13, 17, 19, 21, 24, 31, 33, 34, 37, 38, 40, 41, 42, 44, 45, 47, 49, 50, 54, 59  ],
  // topic 15
  [0, 1, 4, 8, 9, 10, 11, 13, 14, 15, 18, 25, 29, 34, 39, 40, 41, 42, 43, 46, 54, 55, 56, 58, 59  ],
]

describe("test voting stage", function() {
  before(function() {
    var resetDB = require("../support/reset_database.js")
    resetDB.resetVoting()
    for(var i=1; i < 60; i++) {
      cy.createUser({username: "user" + i, password: "Test123!pwd" + i, email: "user" + i + "@example.org"})
    }
  })

  it("cast votes", function() {
    var i
    for(i=1; i < 60; i++) {
      cy.typeLogin({username: "user" + i, password: "Test123!pwd" + i})

      var topic
      for (topic=0; topic < 14; topic++)
      {
        if (votes_facilitator[topic].indexOf(i) > -1)
          cast_vote(topic, 0)
        else if (votes_full[topic].indexOf(i) > -1)
          cast_vote(topic, 1)
        else if (votes_quarter[topic].indexOf(i) > -1)
          cast_vote(topic, 2)
      }
      cy.logout()
    }
  })
})
