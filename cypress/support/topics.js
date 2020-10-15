exports.checkVote = function(whichVote, topicVote, toggled, enabled) {
  cy.get('form.' + whichVote, {withinSubject: topicVote}).then(($vote) => {
    cy.get('input[type=submit]', {withinSubject: $vote}).should(toggled, 'btn--toggled').should(enabled)
  })
}

exports.checkVotes = function(isVoting, h1Text, pText, fullVoteToggled, fullVoteEnabled, quarterVoteToggled) {
  cy.get('div.topic').children('div.topic__detail').contains('h1', h1Text)
  const topicP = cy.get('div.topic').children('div.topic__detail').contains('p', pText)
  if (isVoting) {
    topicP.parent().parent().children('div.topic__vote').then(($topicVote) => {
      exports.checkVote('fullvote', $topicVote, fullVoteToggled, fullVoteEnabled)
      exports.checkVote('quartervote', $topicVote, quarterVoteToggled, 'not.be.disabled')
      exports.checkVote('clearvote', $topicVote, 'not.have.class', 'not.be.disabled')
    })
  }
}

exports.checkTopics = function(isVoting, fullVotes, quarterVotes, facilitators) {
  var fullVotesLeft = 3 - fullVotes.length
  if (isVoting) {
    cy.get('div.notice').contains('h2', fullVotesLeft + ' full votes left, and unlimited number of 1/4 votes')
  }
  else {
    cy.get('div.notice', { timeout: 0 }).should('not.exist')
  }

  // Check there are 15 topics
  cy.get('div.topic').its('length').should('eq', 15)

  // Check facilitator
  if (facilitators[0].length > 0) {
    cy.contains('h1', 'Prep Team').parent().contains('p', 'Facilitator: ' + facilitators[0])
  }
  else {
    cy.contains('h1', 'Prep Team').parent().contains('p', 'Facilitator', { timeout: 0}).should('not.exist')
  }

  // Check for the Prep Team topic
  const fullVoteEnabled = fullVotesLeft <= 0 ? 'be.disabled' : 'not.be.disabled'
  var quarterVoteToggled = quarterVotes.includes(0) ? 'have.class' : 'not.have.class'
  exports.checkVotes(isVoting,
                     'Prep Team',
                     'The Prep Team is a handful of people who plan these annual conferences. If you might be interested in joining this team please come to this BOF. We\'re always looking for new ideas and help to make ICCM special every year!',
                     'not.have.class',
                     fullVoteEnabled,
                     quarterVoteToggled)

  // Check the rest of the topics, topic1 - topic14. 15
  for (var index = 1; index < 15; index++) {
    const fullVoteToggled = fullVotes.includes(index) ? 'have.class' : 'not.have.class'
    quarterVoteToggled = quarterVotes.includes(index) ? 'have.class' : 'not.have.class'

    if (facilitators[index].length > 0) {
      cy.contains('h1', 'topic'+index).parent().contains('p', 'Facilitator: ' + facilitators[index])
    }
    else {
      cy.contains('h1', 'topic'+index).parent().contains('p', 'Facilitator', { timeout: 0 }).should('not.exist')
    }

    exports.checkVotes(isVoting,
                       'topic'+index,
                       'description for topic'+index,
                       fullVoteToggled,
                       fullVoteEnabled,
                       quarterVoteToggled)
  }

}


