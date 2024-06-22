exports.check = function(stage, admin) {
  var pages = [
    { name: 'Nominate topic', dest: '/nomination', exists: stage === 'nomination' },
    { name: 'Moderate topic', dest: '/moderation', exists: (admin && stage !== 'voting') },
    { name: 'Calculate result', dest: '/result', exists: (admin && stage === '') },
    { name: 'All topics', dest: '/topics', exists: true },
    { name: 'Projector', dest: '/projector', exists: true },
    { name: 'Admin', dest: '/admin', exists: admin },
    { name: 'Logout', dest: '/logout', exists: true }
  ]
  describe('the navigation footer exists', function() {
    beforeEach(() => {
      cy.get('ul[class="foot-nav"]').as('footer')
    })

    pages.forEach((page) => {
      var testDescription = (page.exists ? 'has' : 'does not have') + ' ' + page.name + ' button'
      var exist = page.exists ? 'exist' : 'not.exist'
      it(testDescription, function() {
        cy.get('@footer').get('a[href="' + page.dest + '"]').should(exist)
      })
    })
  })
}
