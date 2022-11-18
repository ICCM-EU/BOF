describe('The register page', function() {
  before(() => {
      require('../support/reset_database.js').reset()
      cy.createUser({username: 'test2', password: 'Test123!pwd2', email: 'test2@example.org'})
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

  it('has a password field', function() {
    cy.get('input[name=password]').type('pwd1')
  })

  it('has a register button that adds a user and redirects to the login page', function() {
    cy.get('input[type=submit]').click()
    cy.url().should('include', '/login?newuser=1')

  })

  it('shows an error if user exists', () => {
    cy.visit("/register")
    cy.get('input[name=user_name]').type('test2')
    cy.get('input[name=password]').type('pwd2')
    cy.get('input[type=submit]').click()
    cy.contains('User already exists')
  })
})
