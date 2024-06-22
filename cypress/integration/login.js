describe('The login page', function() {
  before(() => {
    require('../support/reset_database.js').reset()
    cy.createUser({username: 'user1', password: 'Test123!pwd1', email: 'user1@example.org'})
  })

  beforeEach(() => {
    cy.visit("/login")
  })

  it('loads successfully', function() {
    cy.visit("/login")
  })

  it('has a logo', function() {
    cy.get('img[class=logo]')
  })

  it('has a username field', function() {
    cy.get('input[name=user_name]').type('user1')
  })

  it('has a password field', function() {
    cy.get('input[name=password]').type('Test123!pwd1')
  })

  it('has a Login button', function() {
    cy.get('input[type=submit]')
  })

  it('redirects to the topics page when a non-admin user logs in', function() {
    cy.get('input[name=user_name]').type('user1')
    cy.get('input[name=password]').type('Test123!pwd1')
    cy.get('input[type=submit]').click()
    cy.url().should('include', '/topics')
    cy.getCookie('authtoken').should('have.property', 'value')
    cy.logout()
  })


  it('redirects to the admin page when the admin user logs in', function() {
    cy.get('input[name=user_name]').type('admin')
    cy.get('input[name=password]').type('secret')
    cy.get('input[type=submit]').click()
    cy.url().should('include', '/admin')
    cy.getCookie('authtoken').should('have.property', 'value')
    cy.logout()
  })

  it('shows an error if user does not exist', () => {
    cy.get('input[name=user_name]').type('test1')
    cy.get('input[name=password]').type('pwd1')
    cy.get('input[type=submit]').click()
    cy.get('div.notice--error').contains('h1', 'Error')
    cy.getCookie('authtoken').should('not.exist')
  })

  it('shows an error if password is incorrect', () => {
    cy.get('input[name=user_name]').type('user1')
    cy.get('input[name=password]').type('pwd2')
    cy.get('input[type=submit]').click()
    cy.get('div.notice--error').contains('h1', 'Error')
    cy.getCookie('authtoken').should('not.exist')
  })
})
