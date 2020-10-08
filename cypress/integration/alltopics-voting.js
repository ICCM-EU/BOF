describe('In the Voting stage', function() {
  const navfooter = require('../support/navfooter.js')
  const resetDB = require('../support/reset_database.js')
  describe('the All topics page for an admin user', function() {
    before(() => {
      resetDB.resetVoting()
      cy.typeLogin({username: 'admin', password: 'secret'})
    })

    it('loads successfully', function() {
      cy.visit('/topics')
    })

    it('has a logo', function() {
      cy.get('img.logo')
    })

    it('displays the voting notice', function() {
      cy.get('div.notice')
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

    navfooter.check('voting', true)
  })

  describe('the All topics page for a non-admin user', function() {
    before(() => {
      resetDB.resetVoting()
      cy.createUser({username: 'user1', password: 'pwd1'})
      cy.typeLogin({username: 'user1', password: 'pwd1'})
    })

    beforeEach(() => {
      Cypress.Cookies.preserveOnce('authtoken')
    })

    it('loads successfully', function() {
      cy.visit('/topics')
    })

    it('has a logo', function() {
      cy.get('img.logo')
    })

    it('displays the voting notice', function() {
      cy.get('div.notice')
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

    navfooter.check('voting', false)

    describe('Clicking the facilitate button', function() {
      it('adds a full vote for topic1 and sets facilitator', function() {
        cy.contains('h1', 'topic1').parent().children('form').children('input.facilitation__btn').click().then(() => {
          cy.get('div.notice').contains('h2', '2 full votes left, and unlimited number of 1/4 votes')
          cy.contains('h1', 'topic1').parent().contains('p', 'Facilitator: user1')
          cy.contains('h1', 'topic1').parent().parent().children('div.topic__vote').children('form.fullvote').children('input[type=submit]').should('have.class', 'btn--toggled')
          cy.contains('h1', 'topic1').parent().parent().children('div.topic__vote').children('form.quartervote').children('input[type=submit]').should('not.have.class', 'btn--toggled')
          cy.contains('h1', 'topic1').parent().parent().children('div.topic__vote').children('form.clearvote').children('input[type=submit]').should('not.have.class', 'btn--toggled')
        })
      })

      it('adds a quarter vote for Prep BoF topic, and sets facilitator', function() {
        cy.contains('h1', 'Prep Team').parent().children('form').children('input.facilitation__btn').click().then(() => {
          cy.get('div.notice').contains('h2', '2 full votes left, and unlimited number of 1/4 votes')
          cy.contains('h1', 'Prep Team').parent().contains('p', 'Facilitator: user1')
          cy.contains('h1', 'Prep Team').parent().parent().children('div.topic__vote').children('form.fullvote').children('input[type=submit]').should('not.have.class', 'btn--toggled')
          cy.contains('h1', 'Prep Team').parent().parent().children('div.topic__vote').children('form.quartervote').children('input[type=submit]').should('have.class', 'btn--toggled')
          cy.contains('h1', 'Prep Team').parent().parent().children('div.topic__vote').children('form.clearvote').children('input[type=submit]').should('not.have.class', 'btn--toggled')
        })
      })
    })

    describe('Clicking the full vote button', function() {
      it('adds a full vote for topic1 and does not set facilitator', function() {
        cy.contains('h1', 'topic2').parent().parent().children('div.topic__vote').children('form.fullvote').children('input[type=submit]').click().then(() => {
          cy.get('div.notice').contains('h2', '1 full votes left, and unlimited number of 1/4 votes')
          cy.contains('h1', 'topic2').parent().contains('p', 'Facilitator: user1').should('not.exist')
          cy.contains('h1', 'topic2').parent().parent().children('div.topic__vote').children('form.fullvote').children('input[type=submit]').should('have.class', 'btn--toggled')
          cy.contains('h1', 'topic2').parent().parent().children('div.topic__vote').children('form.quartervote').children('input[type=submit]').should('not.have.class', 'btn--toggled')
          cy.contains('h1', 'topic2').parent().parent().children('div.topic__vote').children('form.clearvote').children('input[type=submit]').should('not.have.class', 'btn--toggled')
        })
      })

      it('adds a quarter vote for Prep Team and does not set facilitator', function() {
        cy.contains('h1', 'Prep Team').parent().parent().children('div.topic__vote').children('form.fullvote').children('input[type=submit]').click().then(() => {
          cy.get('div.notice').contains('h2', '1 full votes left, and unlimited number of 1/4 votes')
          cy.contains('h1', 'Prep Team').parent().contains('p', 'Facilitator: user1').should('not.exist')
          cy.contains('h1', 'Prep Team').parent().parent().children('div.topic__vote').children('form.fullvote').children('input[type=submit]').should('not.have.class', 'btn--toggled')
          cy.contains('h1', 'Prep Team').parent().parent().children('div.topic__vote').children('form.quartervote').children('input[type=submit]').should('have.class', 'btn--toggled')
          cy.contains('h1', 'Prep Team').parent().parent().children('div.topic__vote').children('form.clearvote').children('input[type=submit]').should('not.have.class', 'btn--toggled')
        })
      })
    })
  })

  describe('Multiple votes', function() {
    before(() => {
      resetDB.resetVoting()
      cy.typeLogin({username: 'user1', password: 'pwd1'})
    })

    beforeEach(() => {
      Cypress.Cookies.preserveOnce('authtoken')
    })

    it('allows only 3 full votes', function() {
      cy.contains('h1', 'topic1').parent().parent().children('div.topic__vote').children('form.fullvote').children('input[type=submit]').click().then(() => {
        cy.get('div.notice').contains('h2', '2 full votes left, and unlimited number of 1/4 votes')
        cy.contains('h1', 'topic1').parent().parent().children('div.topic__vote').children('form.fullvote').children('input[type=submit]').should('have.class', 'btn--toggled')
        cy.contains('h1', 'topic1').parent().parent().children('div.topic__vote').children('form.quartervote').children('input[type=submit]').should('not.have.class', 'btn--toggled')
        cy.contains('h1', 'topic1').parent().parent().children('div.topic__vote').children('form.clearvote').children('input[type=submit]').should('not.have.class', 'btn--toggled')

        cy.contains('h1', 'topic2').parent().parent().children('div.topic__vote').children('form.fullvote').children('input[type=submit]').click().then(() => {
          cy.get('div.notice').contains('h2', '1 full votes left, and unlimited number of 1/4 votes')
          cy.contains('h1', 'topic2').parent().parent().children('div.topic__vote').children('form.fullvote').children('input[type=submit]').should('have.class', 'btn--toggled')
          cy.contains('h1', 'topic2').parent().parent().children('div.topic__vote').children('form.quartervote').children('input[type=submit]').should('not.have.class', 'btn--toggled')
          cy.contains('h1', 'topic2').parent().parent().children('div.topic__vote').children('form.clearvote').children('input[type=submit]').should('not.have.class', 'btn--toggled')

          cy.contains('h1', 'topic3').parent().parent().children('div.topic__vote').children('form.fullvote').children('input[type=submit]').click().then(() => {
            cy.get('div.notice').contains('h2', '0 full votes left, and unlimited number of 1/4 votes')
            cy.contains('h1', 'topic3').parent().parent().children('div.topic__vote').children('form.fullvote').children('input[type=submit]').should('have.class', 'btn--toggled')
            cy.contains('h1', 'topic3').parent().parent().children('div.topic__vote').children('form.quartervote').children('input[type=submit]').should('not.have.class', 'btn--toggled')
            cy.contains('h1', 'topic3').parent().parent().children('div.topic__vote').children('form.clearvote').children('input[type=submit]').should('not.have.class', 'btn--toggled')

            cy.get('div.topic').each(($topic, index) => {
                if (index < 3) {
                  cy.get('div.topic__detail', {withinSubject: $topic}).siblings('div.topic__vote').children('form.fullvote').children('input[type=submit]').should('be.disabled')
                }
                else {
                  cy.get('div.topic__detail', {withinSubject: $topic}).siblings('div.topic__vote').children('form.fullvote').children('input[type=submit]').should('be.disabled')
                }
                cy.get('div.topic__detail', {withinSubject: $topic}).siblings('div.topic__vote').children('form.quartervote').children('input[type=submit]').should('not.be.disabled')
                cy.get('div.topic__detail', {withinSubject: $topic}).siblings('div.topic__vote').children('form.clearvote').children('input[type=submit]').should('not.be.disabled')
            })
          })
        })
      })
    })

    it('allows switching a full vote to a quarter vote', function() {
      cy.contains('h1', 'topic2').parent().parent().children('div.topic__vote').children('form.quartervote').children('input[type=submit]').click().then(() => {
        cy.get('div.notice').contains('h2', '1 full votes left, and unlimited number of 1/4 votes')
        cy.contains('h1', 'topic2').parent().parent().children('div.topic__vote').children('form.fullvote').children('input[type=submit]').should('not.have.class', 'btn--toggled')
        cy.contains('h1', 'topic2').parent().parent().children('div.topic__vote').children('form.quartervote').children('input[type=submit]').should('have.class', 'btn--toggled')
        cy.contains('h1', 'topic2').parent().parent().children('div.topic__vote').children('form.clearvote').children('input[type=submit]').should('not.have.class', 'btn--toggled')
      })
    })
  })

})
