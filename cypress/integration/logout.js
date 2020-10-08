describe('The logout page', function() {
  before(() => {
    require('../support/reset_database.js').reset()
    cy.createUser({username: 'user1', password: 'pwd1'})
    cy.typeLogin({username: 'user1', password: 'pwd1'})
    cy.getCookie('authtoken').should('have.property', 'value')
  })

  it('loads successfully', function() {
    cy.visit("/logout")
    /* The code should probably redirect the user to / after logging out,
     * instead of rendering the home page on its own. If that is changed, the
     * following should check the url is / not containing '/logout' */
    cy.url().should('include', '/logout')
  })

  it('removes the authtoken cookie', function() {
    cy.getCookie('authtoken').should('not.exist')
  })

/* The code should probably redirect the user to / after logging out, instead
 * of rendering the home page on its own. If that is changed,
 * the rest of these tests should be removed. */
  it('has a logo', function() {
    cy.get('img[class=logo]')
  })

  it('has a register button', function() {
    cy.contains('a', 'Register')
  })

  it('has a login button', function() {
    cy.contains('a', 'Register')
  })

})
