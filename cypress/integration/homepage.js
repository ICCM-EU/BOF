describe('The home page', function() {
  beforeEach(() => {
    cy.visit("/")
  })

  it('loads successfully', function() {
    cy.visit("/")
  })

  it('has a logo', function() {
    cy.get('img[class=logo]')
  })

  it('has a register button that goes to the /register page', function() {
    cy.contains('a', 'Register').click()
    cy.url().should('include', '/register')

  })

  it('has a login button that goes to the /login page', function() {
    cy.contains('a', 'Sign in').click()
    cy.url().should('include', '/login')
  })
})
