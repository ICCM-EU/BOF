describe("After the Voting stage", function() {
  const navfooter = require("../support/navfooter.js")
  const resetDB = require("../support/reset_database.js")
  const topics = require("../support/topics.js")
  const login = (user = {}) => {
    cy.session(user, () => {
      cy.typeLogin(user)
    })
  }

  describe("the All topics page for a non-admin user", function() {
    before(() => {
      resetDB.resetFinished()
      cy.createUser({username: "user1", password: "Test123!pwd1", email: "user1@example.org"})
    })

    beforeEach(() => {
      login({username: "user1", password: "Test123!pwd1"})
      cy.visit("/topics")
    })

    it("loads successfully", function() {
      cy.visit("/topics")
    })

    it("has a logo", function() {
      cy.get("img.logo")
    })

    it("displays the correct topic information and notice", function() {
      topics.checkTopics(false, [], [], ["", "", "", "", "", "", "", "", "", "", "", "", "", "", ""])
    })

    navfooter.check("", false)
  })

  describe("the All topics page for the admin user", function() {
    before(() => {
      resetDB.resetFinished()
    })

    beforeEach(() => {
      login({username: "admin", password: "secret"})
      cy.visit("/topics")
    })

    it("loads successfully", function() {
      cy.visit("/topics")
    })

    it("has a logo", function() {
      cy.get("img.logo")
    })

    it("displays the correct topic information and notice", function() {
      topics.checkTopics(false, [], [], ["", "", "", "", "", "", "", "", "", "", "", "", "", "", ""])
    })

    navfooter.check("", true)
  })
})
