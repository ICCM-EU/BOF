describe('The register page', function() {
  before(() => {
      require('../support/reset_database.js').reset()
      cy.createUser({username: 'test2', password: 'Test123!pwd2', email: 'test2@example.org'})
  })

  beforeEach(() => {
    cy.visit("/register")
  })

  it('loads successfully', function() {
    cy.visit("/register")
  })

  it('has a logo', function() {
    cy.get('img[class=logo]')
  })

  it('has a username field', function() {
    cy.get('input[name=user_name]').type('test1')
  })

  it('has an email field', function() {
    cy.get('input[name=email]').type('test1')
  })

  it('has a password field', function() {
    cy.get('input[name=password]').type('pwd1')
  })

  it('has a register button that adds a user and redirects to the topics page', function() {
    cy.get('input[name=user_name]').type('test1')
    cy.get('input[name=email]').type('test1@example.com')
    cy.get('input[name=password]').type('Test!pwd1')
    cy.get('input[type=submit]').click()
    cy.url().should('include', '/topics')
  })

  it('shows an error if user exists', () => {
    cy.get('input[name=user_name]').type('test2')
    cy.get('input[name=email]').type('test2@example.com')
    cy.get('input[name=password]').type('Test!pwd2')
    cy.get('input[type=submit]').click()
    cy.contains('User already exists')
  })

  it('shows an error if user name is empty', () => {
    cy.get('input[name=email]').type('test3@example.com')
    cy.get('input[name=password]').type('Test!pwd3')
    cy.get('input[type=submit]').click()
    cy.get('div.notice--error').contains('h1', 'Error')
  })

  it('shows an error if email is empty', () => {
    cy.get('input[name=user_name]').type('test3')
    cy.get('input[name=password]').type('Test!pwd3')
    cy.get('input[type=submit]').click()
    cy.get('div.notice--error').contains('h1', 'Error')
  })

  it('shows an error if password is empty', () => {
    cy.get('input[name=user_name]').type('test3')
    cy.get('input[name=email]').type('test3@example.com')
    cy.get('input[type=submit]').click()
    cy.get('div.notice--error').contains('h1', 'Error')
  })
})
