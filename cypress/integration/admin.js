describe('The admin page', function() {
  before(() => {
    require('../support/reset_database.js').reset()
    cy.createUser({username: 'user1', password: 'pwd1'})
  })

  it('rejects a non-admin user', function() {
    cy.typeLogin({username: 'user1', password: 'pwd1'})
    cy.request({url: '/admin', failOnStatusCode: false}).then((response) => {
      expect(response.status).to.eq(500)
      expect(response.body).to.eq('you don\'t have permissions for this page')
    })
    cy.logout()
  })
})

describe('In the Nomination stage, the admin page', function() {
  before(() => {
    require('../support/reset_database.js').reset()
  })

  it('loads successfully', function() {
    cy.typeLogin({username: 'admin', password: 'secret'})
    cy.visit('/admin')
  })

  it('has a logo', function() {
    cy.get('img[class=logo]')
  })

  it('has the server time', function() {
    cy.get('input[type=text][readonly]').should('have.attr', 'value').and(($value) => {
      var now = parseInt(Cypress.moment().format('X'))
      var serverDate = parseInt(Cypress.moment($value, 'YYYY-MM-DD HH:mm:ss').format('X'))
      expect(serverDate).to.be.closeTo(now, 3)
    })
  })

  it('has correct nomination begins date', function() {
    cy.get('input[name=nomination_begins][type=date]').should('have.attr', 'value').and(($value) => {
      // Nomination begins date is 1 day before NOW() when
      // reset_database was invoked above
      var now = parseInt(Cypress.moment().subtract(1, 'days').hours(0).minutes(0).seconds(0).milliseconds(0).format('X'))
      var serverDate = parseInt(Cypress.moment($value, 'YYYY-MM-DD').format('X'))
      expect(serverDate).to.eq(now)
    })
  })

  it('has correct nomination begins time', function() {
    cy.get('input[name=time_nomination_begins][type=time]').should('have.attr', 'value').and(($value) => {
      // Nomination begins time is NOW() when
      // reset_database was invoked above
      var now = parseInt(Cypress.moment().seconds(0).milliseconds(0).format('X'))
      var serverDate = parseInt(Cypress.moment($value, 'HH:mm').format('X'))
      expect(serverDate).to.be.closeTo(now, 60)
    })
  })

  it('has correct nomination ends date', function() {
    cy.get('input[name=nomination_ends][type=date]').should('have.attr', 'value').and(($value) => {
      // Nomination ends date is 1 day after NOW() when
      // reset_database was invoked above
      var now = parseInt(Cypress.moment().add(1, 'days').hours(0).minutes(0).seconds(0).milliseconds(0).format('X'))
      var serverDate = parseInt(Cypress.moment($value, 'YYYY-MM-DD').format('X'))
      expect(serverDate).to.eq(now)
    })
  })

  it('has correct nomination ends time', function() {
    cy.get('input[name=time_nomination_ends][type=time]').should('have.attr', 'value').and(($value) => {
      // Nomination ends time is one minute after NOW() when
      // reset_database was invoked above
      var now = parseInt(Cypress.moment().add(1, 'minutes').seconds(0).milliseconds(0).format('X'))
      var serverDate = parseInt(Cypress.moment($value, 'HH:mm').format('X'))
      expect(serverDate).to.be.closeTo(now, 60)
    })
  })

  it('has correct voting begins date', function() {
    cy.get('input[name=voting_begins][type=date]').should('have.attr', 'value').and(($value) => {
      // Voting begins date is 1 day after NOW() when
      // reset_database was invoked above
      var now = parseInt(Cypress.moment().add(1, 'days').hours(0).minutes(0).seconds(0).milliseconds(0).format('X'))
      var serverDate = parseInt(Cypress.moment($value, 'YYYY-MM-DD').format('X'))
      expect(serverDate).to.eq(now)
    })
  })

  it('has correct voting begins time', function() {
    cy.get('input[name=time_voting_begins][type=time]').should('have.attr', 'value').and(($value) => {
      // Voting begins time is one hour after NOW() when
      // reset_database was invoked above
      var now = parseInt(Cypress.moment().add(1, 'hours').seconds(0).milliseconds(0).format('X'))
      var serverDate = parseInt(Cypress.moment($value, 'HH:mm').format('X'))
      expect(serverDate).to.be.closeTo(now, 60)
    })
  })

  it('has correct voting ends date', function() {
    cy.get('input[name=voting_ends][type=date]').should('have.attr', 'value').and(($value) => {
      // Voting ends date is 2 days after NOW() when
      // reset_database was invoked above
      var now = parseInt(Cypress.moment().add(2, 'days').hours(0).minutes(0).seconds(0).milliseconds(0).format('X'))
      var serverDate = parseInt(Cypress.moment($value, 'YYYY-MM-DD').format('X'))
      expect(serverDate).to.eq(now)
    })
  })

  it('has correct voting ends time', function() {
    cy.get('input[name=time_voting_ends][type=time]').should('have.attr', 'value').and(($value) => {
      // Voting ends time is two hours after NOW() when
      // reset_database was invoked above
      var now = parseInt(Cypress.moment().add(2, 'hours').seconds(0).milliseconds(0).format('X'))
      var serverDate = parseInt(Cypress.moment($value, 'HH:mm').format('X'))
      expect(serverDate).to.be.closeTo(now, 60)
    })
  })

  it('has three slots with delete buttons', function() {
    cy.get('input[id=round_0][name="rounds[]"][type=text]').should('have.attr', 'value', 'first')
    cy.get('button[id=delete_round_0]')
    cy.get('input[id=round_1][name="rounds[]"][type=text]').should('have.attr', 'value', 'second')
    cy.get('button[id=delete_round_1]')
    cy.get('input[id=round_2][name="rounds[]"][type=text]').should('have.attr', 'value', 'third')
    cy.get('button[id=delete_round_2]')
    cy.get('input[id=round_3][name="rounds[]"][type=text]').should('not.exist')
    cy.get('button[id=delete_round_3]').should('not.exist')
    cy.get('input[id=last_round_id][type=hidden]').should('have.attr', 'value', '3')
  })

  describe('has an Add Slot button', function() {
    it('that adds a new slot', function() {
      cy.contains('button', 'Add Slot').click().then(function() {
        cy.get('input[id=round_3][name="rounds[]"][type=text]')
        cy.get('button[id=delete_round_3]')
        cy.get('input[id=last_round_id][type=hidden]').should('have.attr', 'value', '4')
      })
    })
  })

  it('has three rooms with delete buttons', function() {
    cy.get('input[id=location_0][name="locations[]"][type=text]').should('have.attr', 'value', 'Room A')
    cy.get('button[id=delete_location_0]')
    cy.get('input[id=location_1][name="locations[]"][type=text]').should('have.attr', 'value', 'Room B')
    cy.get('button[id=delete_location_1]')
    cy.get('input[id=location_2][name="locations[]"][type=text]').should('have.attr', 'value', 'Room C')
    cy.get('button[id=delete_location_2]')
    cy.get('input[id=location_3][name="locations[]"][type=text]').should('not.exist')
    cy.get('button[id=delete_location_3]').should('not.exist')
    cy.get('input[id=last_location_id][type=hidden]').should('have.attr', 'value', '3')
  })

  describe('has an Add Room button', function() {
    it('that adds a new room', function() {
      cy.contains('button', 'Add Room').click().then(function() {
        cy.get('input[id=location_3][name="locations[]"][type=text]')
        cy.get('button[id=delete_location_3]')
        cy.get('input[id=last_location_id][type=hidden]').should('have.attr', 'value', '4')
      })
    })
  })

  describe('clicking a Delete slot button', function() {
    it('removes the slot', function() {
      cy.get('button[id=delete_round_1]').click().then(function() {
        cy.get('input[id=round_1][name="rounds[]"][type=text]').should('not.exist')
      })
    })
  })

  describe('clicking a Delete room button', function() {
    it('removes the room', function() {
      cy.get('button[id=delete_location_1]').click().then(function() {
        cy.get('input[id=location_1][name="locations[]"][type=text]').should('not.exist')
      })
    })
  })

  it('has a New Admin Password field', function() {
    cy.get('input[type=password][name=password1]')
  })

  it('has a Confirm Password field', function() {
    cy.get('input[type=password][name=password2]')
  })

  it('has a Save button', function() {
    cy.get('input[type=submit][value=Save]')
  })

  it('has a Reset Database button', function() {
    cy.get('input[type=submit][value="Reset Database"]')
  })

  it('has a Download Database button', function() {
    cy.get('input[type=submit][value="Download Database"]')
  })

  describe('The navigation footer exists', function() {
    beforeEach(() => {
      cy.get('ul[class="foot-nav"]').as('footer')
    })

    it('has a Nominate topic button', function() {
      cy.get('@footer').get('a[href="/nomination"]')
    })

    it('has a Moderate topics button', function() {
      cy.get('@footer').get('a[href="/moderation"]')
    })

    it('does not have a Calculate result button', function() {
      cy.get('@footer').get('a[href="/result"]').should('not.exist')
    })

    it('has an All topics button', function() {
      cy.get('@footer').get('a[href="/topics"]')
    })

    it('has a Projector button', function() {
      cy.get('@footer').get('a[href="/projector"]')
    })

    it('has an Admin button', function() {
      cy.get('@footer').get('a[href="/admin"]')
    })

    it('has a logout button', function() {
      cy.get('@footer').get('a[href="/logout"]')
    })
  })
})

describe('In the Voting stage, the admin page', function() {
  before(() => {
    require('../support/reset_database.js').resetVoting()
  })

  it('loads successfully', function() {
    cy.typeLogin({username: 'admin', password: 'secret'})
    cy.visit('/admin')
  })

  it('has a logo', function() {
    cy.get('img[class=logo]')
  })

  describe('The navigation footer exists', function() {
    beforeEach(() => {
      cy.get('ul[class="foot-nav"]').as('footer')
    })

    it('does not have a Nominate topic button', function() {
      cy.get('@footer').get('a[href="/nomination"]').should('not.exist')
    })

    it('does not have a Moderate topic button', function() {
      cy.get('@footer').get('a[href="/moderation"]').should('not.exist')
    })

    it('does not have a Calculate result button', function() {
      cy.get('@footer').get('a[href="/result"]').should('not.exist')
    })

    it('has an All topics button', function() {
      cy.get('@footer').get('a[href="/topics"]')
    })

    it('has a Projector button', function() {
      cy.get('@footer').get('a[href="/projector"]')
    })

    it('has an Admin button', function() {
      cy.get('@footer').get('a[href="/admin"]')
    })

    it('has a logout button', function() {
      cy.get('@footer').get('a[href="/logout"]')
    })
  })

})

describe('After the Voting stage, the admin page', function() {
  before(() => {
    require('../support/reset_database.js').resetFinished()
  })

  it('loads successfully', function() {
    cy.typeLogin({username: 'admin', password: 'secret'})
    cy.visit('/admin')
  })

  it('has a logo', function() {
    cy.get('img[class=logo]')
  })

  describe('The navigation footer exists', function() {
    beforeEach(() => {
      cy.get('ul[class="foot-nav"]').as('footer')
    })

    it('does not have a Nominate topic button', function() {
      cy.get('@footer').get('a[href="/nomination"]').should('not.exist')
    })

    it('has a Moderate topic button', function() {
      cy.get('@footer').get('a[href="/moderation"]')
    })

    it('has a Calculate result button', function() {
      cy.get('@footer').get('a[href="/result"]')
    })

    it('has an All topics button', function() {
      cy.get('@footer').get('a[href="/topics"]')
    })

    it('has a Projector button', function() {
      cy.get('@footer').get('a[href="/projector"]')
    })

    it('has an Admin button', function() {
      cy.get('@footer').get('a[href="/admin"]')
    })

    it('has a logout button', function() {
      cy.get('@footer').get('a[href="/logout"]')
    })
  })
})

describe('Download the database', function() {
  before(() => {
    require('../support/reset_database.js').reset()
    cy.typeLogin({username: 'admin', password: 'secret'})
  })

  it('works', function() {
    cy.request({url: '/admin', method: 'POST', form: true, body: {download_database: 'yes'}}).then((response) => {
      var now = Cypress.moment().format('Y-MM-DD_hhmm')
      var disposition = 'attachment; filename=db-backup-BOF-' + now + '.sql'
      expect(response.status).to.eq(200)
      expect(response.headers['content-disposition']).to.eq(disposition)
      expect(response.headers['content-type']).to.eq('application/octet-stream')
    })
  })
})

