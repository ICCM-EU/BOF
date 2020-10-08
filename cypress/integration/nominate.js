describe('In the Nomination stage, the Nominate topic page', function() {
  before(() => {
    require('../support/reset_database.js').reset()
    cy.createUser({username: 'user1', password: 'pwd1'})
    cy.typeLogin({username: 'user1', password: 'pwd1'})
  })

  beforeEach(() => {
    Cypress.Cookies.preserveOnce('authtoken')
  })

  it('loads successfully', function() {
    cy.visit("/nomination")
  })

  it('has a logo', function() {
    cy.get('img[class=logo]')
  })

  it('has a title input field', function() {
    cy.get('input[type=text][name="title"]').type('nominate-test-non-admin-user-topic')
  })

  it('has a description textarea field', function() {
    cy.get('textarea[name="description"]').type('description for\nnominate-test-non-admin-user-topic\n')
  })

  it('has a submit button', function() {
    cy.get('input[type=submit][value="Nominate"]')
  })

  it ('thanks the user after submission with topics and nominate buttons', function() {
    cy.get('input[type=submit][value="Nominate"]').click().then(() => {
      cy.contains('Thank you')
      cy.get('a[href="/topics"]')
      cy.get('a[href="/nomination"]')
    })
  })

  it ('shows an error if a topic with the same title is submitted again', function() {
      cy.visit("/nomination")
      cy.get('input[type=text][name="title"]').type('nominate-test-non-admin-user-topic')
      cy.get('textarea[name="description"]').type('description for\nnominate-test-non-admin-user-topic\n')
      cy.get('input[type=submit][value="Nominate"]').click().then(() => {
        cy.contains('A BOF with that title has already been submitted')
        cy.get('a[href="/topics"]').should('not.exist')
        cy.get('a[href="/nomination"]')
    })
  })
})

/* Right now, this works, and is intended to work?
describe('In the Voting stage, the Nominate topic page', function() {
  before(() => {
    require('../support/reset_database.js').resetVoting()
    cy.createUser({username: 'user1', password: 'pwd1'})
    cy.typeLogin({username: 'user1', password: 'pwd1'})
  })

  it('fails to load', function() {
    // This isn't filled in, since failure behavior hasn't been defined.
  })
})
*/
