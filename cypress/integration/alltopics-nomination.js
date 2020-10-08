describe('In the Nomination stage', function() {
  const navfooter = require('../support/navfooter.js')
  const resetDB = require('../support/reset_database.js')
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

    it('does not display the voting notice', function() {
      cy.get('div.notice').should('not.exist')
    })

    it('displays the correct topic information', function() {
      //topics.its('length').should('eq', 15)
      cy.get('div.topic').each(($topic, index) => {
        cy.get('div.topic__detail', { withinSubject: $topic }).within(($detail) => {
    if (index == 0) {
      cy.get('h1', {withinSubject: $detail}).should('have.text', 'Prep Team')
      cy.get('p', {withinSubject: $detail}).should('have.text', 'The Prep Team is a handful of people who plan these annual conferences. If you might be interested in joining this team please come to this BOF. We\'re always looking for new ideas and help to make ICCM special every year!')
    }
    else {
      cy.get('h1', {withinSubject: $detail}).should('have.text', 'topic'+index)
      cy.get('p', {withinSubject: $detail}).should('have.text', 'description for topic'+index)
    }
        })
      }).its('length').should('eq', 15)
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

    it('does not display the voting notice', function() {
      cy.get('div.notice').should('not.exist')
    })

    it('displays the correct topic information', function() {
      cy.get('div.topic').each(($topic, index) => {
        cy.get('div.topic__detail', { withinSubject: $topic }).within(($detail) => {
    if (index == 0) {
      cy.get('h1', {withinSubject: $detail}).should('have.text', 'Prep Team')
      cy.get('p', {withinSubject: $detail}).should('have.text', 'The Prep Team is a handful of people who plan these annual conferences. If you might be interested in joining this team please come to this BOF. We\'re always looking for new ideas and help to make ICCM special every year!')
    }
    else {
      cy.get('h1', {withinSubject: $detail}).should('have.text', 'topic'+index)
      cy.get('p', {withinSubject: $detail}).should('have.text', 'description for topic'+index)
    }
        })
      }).its('length').should('eq', 15)
    })

    navfooter.check('nomination', false)
  })
})
