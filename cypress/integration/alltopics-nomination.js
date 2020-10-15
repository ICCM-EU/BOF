describe('In the Nomination stage', function() {
  const navfooter = require('../support/navfooter.js')
  const resetDB = require('../support/reset_database.js')
  const topics = require('../support/topics.js')
  describe('the All topics page for the admin user', function() {
    before(() => {
      resetDB.reset()
      cy.typeLogin({username: 'admin', password: 'secret'})
    })

    it('loads successfully', function() {
      cy.visit('/topics')
    })

    it('has a logo', function() {
      cy.get('img[class=logo]')
    })

    it('displays the correct topic information and notice', function() {
      topics.checkTopics(false, [], [], ['', '', '', '', '', '', '', '', '', '', '', '', '', '', ''])
    })

    navfooter.check('nomination', true)
  })

  describe('the All topics page for a non-admin user', function() {
    before(() => {
      resetDB.reset()
      cy.createUser({username: 'user1', password: 'pwd1'})
      cy.typeLogin({username: 'user1', password: 'pwd1'})
    })

    it('loads successfully', function() {
      cy.visit('/topics')
    })

    it('has a logo', function() {
      cy.get('img[class=logo]')
    })

    it('displays the correct topic information and notice', function() {
      topics.checkTopics(false, [], [], ['', '', '', '', '', '', '', '', '', '', '', '', '', '', ''])
    })

    navfooter.check('nomination', false)
  })
})
