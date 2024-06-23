import dayjs from "dayjs"
var utc = require("dayjs/plugin/utc");
var advancedFormat = require("dayjs/plugin/advancedFormat");
var customParseFormat = require("dayjs/plugin/customParseFormat");
var timezonePlugin = require("dayjs/plugin/timezone");
dayjs.extend(utc);
dayjs.extend(advancedFormat);
dayjs.extend(customParseFormat);
dayjs.extend(timezonePlugin);

describe("The admin page", function() {
  before(() => {
    require("../support/reset_database.js").reset()
    cy.createUser({username: "user1", password: "Test123!pwd1", email: "user1@example.org"})
  })

  it("rejects a non-admin user", function() {
    cy.typeLogin({username: "user1", password: "Test123!pwd1"})
    cy.request({url: "/admin", failOnStatusCode: false}).then((response) => {
      expect(response.status).to.eq(500)
      expect(response.body).to.eq("you don\'t have permissions for this page")
    })
    cy.logout()
  })
})

describe("In the Nomination stage, the admin page", function() {
  const login = (user = {}) => {
    cy.session(user, () => {
      cy.typeLogin(user)
    })
  }

  before(() => {
    require("../support/reset_database.js").reset()
  })

  beforeEach(() => {
    login({username: "admin", password: "secret"})
    cy.visit("/admin")
  })

  it("loads successfully", function() {
    cy.visit("/admin")
  })

  it("has a logo", function() {
    cy.get("img[class=logo]")
  })

  it("has the server and local times", function() {
    const utcnow = dayjs().tz("UTC");
    const utcnowSec = parseInt(utcnow.format("X"), 10)
    const now = dayjs();
    const nowSec = parseInt(now.format("X"), 10)
    cy.get("p[id=servertime]").should("contain", "Server time:").and(($value) => {
      const value = $value.text();
      const serverDate = parseInt(dayjs(value.substring(12), "YYYY-MM-DD HH:mm:ss").format("X"), 10)
      expect(serverDate).to.be.closeTo(utcnowSec, 3)
    })
    cy.get("p[id=localtime]").should("contain", "Local time:").and(($value) => {
      const value = $value.text();
      const serverDate = parseInt(dayjs(value.substring(11), "YYYY-MM-DD HH:mm:ss").utc().format("X"), 10)
      expect(serverDate).to.be.closeTo(nowSec, 3)
    })
  })

  it("has correct nomination begins date", function() {
    cy.get("input[name=nomination_begins][type=date]").should("have.attr", "value").and(($value) => {
      // Nomination begins date is 1 day before NOW() when
      // reset_database was invoked above
      const now = dayjs().subtract(1, "days").hour(0).minute(0).second(0).millisecond(0).tz("UTC").format("YYYY-MM-DD")
      expect($value).to.eq(now)
    })
  })

  it("has correct nomination begins time", function() {
    cy.get("input[name=time_nomination_begins][type=time]").should("have.attr", "value").and(($value) => {
      // Nomination begins time is NOW() when
      // reset_database was invoked above
      const now = dayjs().tz("UTC").format("HH:mm").split(":")
      const expectedMinutes = parseInt(now[0], 10) * 60 + parseInt(now[1], 10)
      const serverTime = dayjs($value, "HH:mm").format("HH:mm").split(":")
      const minutes = parseInt(serverTime[0], 10) * 60 + parseInt(serverTime[1], 10)
      expect(minutes).to.be.closeTo(expectedMinutes, 60)
    })
  })

  it("has correct nomination ends date", function() {
    cy.get("input[name=nomination_ends][type=date]").should("have.attr", "value").and(($value) => {
      // Nomination ends date is 1 day after NOW() when
      // reset_database was invoked above
      const now = dayjs().add(1, "days").add(1, "minutes").hour(0).minute(0).second(0).millisecond(0).tz("UTC").format("YYYY-MM-DD")
      expect($value).to.eq(now)
    })
  })

  it("has correct nomination ends time", function() {
    cy.get("input[name=time_nomination_ends][type=time]").should("have.attr", "value").and(($value) => {
      // Nomination ends time is one minute after NOW() when
      // reset_database was invoked above
      const now = dayjs().add(1, "minutes").tz("UTC").format("HH:mm").split(":")
      const expectedMinutes = parseInt(now[0], 10) * 60 + parseInt(now[1], 10)
      const serverTime = dayjs($value, "HH:mm").format("HH:mm").split(":")
      const minutes = parseInt(serverTime[0], 10) * 60 + parseInt(serverTime[1], 10)
      expect(minutes).to.be.closeTo(expectedMinutes, 60)
    })
  })

  it("has correct voting begins date", function() {
    cy.get("input[name=voting_begins][type=date]").should("have.attr", "value").and(($value) => {
      // Voting begins date is 1 day after NOW() when
      // reset_database was invoked above
      const now = dayjs().add(1, "days").add(1, "hours").tz("UTC").hour(0).minute(0).second(0).millisecond(0).format("YYYY-MM-DD")
      expect($value).to.eq(now)
    })
  })

  it("has correct voting begins time", function() {
    cy.get("input[name=time_voting_begins][type=time]").should("have.attr", "value").and(($value) => {
      // Voting begins time is one hour after NOW() when
      // reset_database was invoked above
      const now = dayjs().add(1, "hours").tz("UTC").format("HH:mm").split(":")
      const expectedMinutes = parseInt(now[0], 10) * 60 + parseInt(now[1], 10)
      const serverTime = dayjs($value, "HH:mm").format("HH:mm").split(":")
      const minutes = parseInt(serverTime[0], 10) * 60 + parseInt(serverTime[1], 10)
      expect(minutes).to.be.closeTo(expectedMinutes, 60)
    })
  })

  it("has correct voting ends date", function() {
    cy.get("input[name=voting_ends][type=date]").should("have.attr", "value").and(($value) => {
      // Voting ends date is 2 days after NOW() when
      // reset_database was invoked above
      const now = dayjs().add(2, "days").add(2, "hours").tz("UTC").hour(0).minute(0).second(0).millisecond(0).format("YYYY-MM-DD")
      expect($value).to.eq(now)
    })
  })

  it("has correct voting ends time", function() {
    cy.get("input[name=time_voting_ends][type=time]").should("have.attr", "value").and(($value) => {
      // Voting ends time is two hours after NOW() when
      // reset_database was invoked above
      const now = dayjs().add(2, "hours").tz("UTC").format("HH:mm").split(":")
      const expectedMinutes = parseInt(now[0], 10) * 60 + parseInt(now[1], 10)
      //const serverTime = dayjs($value, "HH:mm").format("HH:mm").split(":")
      const serverTime = ($value + "").split(":")
      const minutes = parseInt(serverTime[0], 10) * 60 + parseInt(serverTime[1], 10)
      expect(minutes).to.be.closeTo(expectedMinutes, 60)
    })
  })

  it("has three slots with delete buttons", function() {
    cy.get("input[id=round_0][name=\"rounds[]\"][type=text]").should("have.attr", "value", "first")
    cy.get("button[id=delete_round_0]")
    cy.get("input[id=round_1][name=\"rounds[]\"][type=text]").should("have.attr", "value", "second")
    cy.get("button[id=delete_round_1]")
    cy.get("input[id=round_2][name=\"rounds[]\"][type=text]").should("have.attr", "value", "third")
    cy.get("button[id=delete_round_2]")
    cy.get("input[id=round_3][name=\"rounds[]\"][type=text]").should("not.exist")
    cy.get("button[id=delete_round_3]").should("not.exist")
    cy.get("input[id=last_round_id][type=hidden]").should("have.attr", "value", "3")
  })

  describe("has an Add Slot button", function() {
    it("that adds a new slot and adds it to the Prep BoF Slot droplist", function() {
      cy.contains("button", "Add Slot").click().then(function() {
        cy.get("input[id=round_3][name=\"rounds[]\"][type=text]")
        cy.get("button[id=delete_round_3]")
        cy.get("input[id=last_round_id][type=hidden]").should("have.attr", "value", "4")
        cy.get("select[name=prep_bof_round]").get("option[value=\"\"]")
      })
    })
  })

  it("has three rooms with delete buttons", function() {
    cy.get("input[id=location_0][name=\"locations[]\"][type=text]").should("have.attr", "value", "Room A")
    cy.get("button[id=delete_location_0]")
    cy.get("input[id=location_1][name=\"locations[]\"][type=text]").should("have.attr", "value", "Room B")
    cy.get("button[id=delete_location_1]")
    cy.get("input[id=location_2][name=\"locations[]\"][type=text]").should("have.attr", "value", "Room C")
    cy.get("button[id=delete_location_2]")
    cy.get("input[id=location_3][name=\"locations[]\"][type=text]").should("not.exist")
    cy.get("button[id=delete_location_3]").should("not.exist")
    cy.get("input[id=last_location_id][type=hidden]").should("have.attr", "value", "3")
  })

  describe("has an Add Room button", function() {
    it("that adds a new room and adds it to the Prep BoF Room droplist", function() {
      cy.contains("button", "Add Room").click().then(function() {
        cy.get("input[id=location_3][name=\"locations[]\"][type=text]")
        cy.get("button[id=delete_location_3]")
        cy.get("input[id=last_location_id][type=hidden]").should("have.attr", "value", "4")
        cy.get("select[name=prep_bof_location]").get("option[value=\"\"]")
      })
    })
  })

  describe("clicking a Delete slot button", function() {
    it("removes the slot", function() {
      cy.get("button[id=delete_round_1]").click().then(function() {
        cy.get("input[id=round_1][name=\"rounds[]\"][type=text]").should("not.exist")
      })
    })
  })

  describe("clicking a Delete room button", function() {
    it("removes the room", function() {
      cy.get("button[id=delete_location_1]").click().then(function() {
        cy.get("input[id=location_1][name=\"locations[]\"][type=text]").should("not.exist")
      })
    })
  })

  it("has a Prep BoF Has Its Own Slot checkbox that is not checked", function() {
    cy.get("input[type=checkbox][name=schedule_prep]").should("not.be.checked")
  })

  describe("the Prep Bof Has its Own Slot checkbox", function() {
    it("exists", function() {
      cy.get("input[type=checkbox][name=schedule_prep]")
    })

    it("is not checked", function() {
      cy.get("input[type=checkbox][name=schedule_prep]").should("not.be.checked")
    })

    it("checking it disables the Slot and Room droplists", function() {
      cy.get("input[type=checkbox][name=schedule_prep]").check().then(function() {
        cy.get("select[name=prep_bof_round]").should("be.disabled")
        cy.get("select[name=prep_bof_location]").should("be.disabled")
      })
    })

    it("unchecking it re-enables the Slot and Room droplists", function() {
      cy.get("input[type=checkbox][name=schedule_prep]").check().then(function() {
        cy.get("input[type=checkbox][name=schedule_prep]").uncheck().then(function() {
          cy.get("select[name=prep_bof_round]").should("not.be.disabled")
          cy.get("select[name=prep_bof_location]").should("not.be.disabled")
        })
      })
    })
  })

  describe("the Slot for Prep BoF droplist", function() {
    it("exists", function() {
      cy.get("select[name=prep_bof_round]")
    })
    it("has an option for each slot and Default", function() {
      cy.get("select[name=prep_bof_round]").get("option[value=first]")
      cy.get("select[name=prep_bof_round]").get("option[value=third]")
      cy.get("select[name=prep_bof_round]").get("option[value=-1]")
    })
    it("Default is selected", function() {
      cy.get("select[name=prep_bof_round] option:selected").should("have.value", "-1")
    })
  })

  describe("the Room for Prep BoF droplist", function() {
    it("exists", function() {
      cy.get("select[name=prep_bof_location]")
    })
    it("has an option for each room and Default", function() {
      cy.get("select[name=prep_bof_location]").get("option[value=\"Room A\"]")
      cy.get("select[name=prep_bof_location]").get("option[value=\"Room C\"]")
      cy.get("select[name=prep_bof_location]").get("option[value=-1]")
    })
    it("Default is selected", function() {
      cy.get("select[name=prep_bof_location] option:selected").should("have.value", "-1")
    })
  })

  it("has a New Admin Password field", function() {
    cy.get("input[type=password][name=password1]")
  })

  it("has a Confirm Password field", function() {
    cy.get("input[type=password][name=password2]")
  })

  it("has a Save button", function() {
    cy.get("input[type=submit][value=Save]")
  })

  it("has a Reset Database button", function() {
    cy.get("input[type=submit][value=\"Reset Database\"]")
  })

  it("has a Download Database button", function() {
    cy.get("input[type=submit][value=\"Download Database\"]")
  })

  describe("The navigation footer exists", function() {
    beforeEach(() => {
      cy.get("ul[class=\"foot-nav\"]").as("footer")
    })

    it("has a Nominate topic button", function() {
      cy.get("@footer").get("a[href=\"/nomination\"]")
    })

    it("has a Moderate topics button", function() {
      cy.get("@footer").get("a[href=\"/moderation\"]")
    })

    it("does not have a Calculate result button", function() {
      cy.get("@footer").get("a[href=\"/result\"]").should("not.exist")
    })

    it("has an All topics button", function() {
      cy.get("@footer").get("a[href=\"/topics\"]")
    })

    it("has a Projector button", function() {
      cy.get("@footer").get("a[href=\"/projector\"]")
    })

    it("has an Admin button", function() {
      cy.get("@footer").get("a[href=\"/admin\"]")
    })

    it("has a logout button", function() {
      cy.get("@footer").get("a[href=\"/logout\"]")
    })
  })

})

describe("In the Voting stage, the admin page", function() {
  const login = (user = {}) => {
    cy.session(user, () => {
      cy.typeLogin(user)
    })
  }

  before(() => {
    require("../support/reset_database.js").resetVoting()
  })

  beforeEach(() => {
    login({username: "admin", password: "secret"})
    cy.visit("/admin")
  })

  it("loads successfully", function() {
    cy.visit("/admin")
  })

  it("has a logo", function() {
    cy.get("img[class=logo]")
  })

  describe("The navigation footer exists", function() {
    beforeEach(() => {
      cy.get("ul[class=\"foot-nav\"]").as("footer")
    })

    it("does not have a Nominate topic button", function() {
      cy.get("@footer").get("a[href=\"/nomination\"]").should("not.exist")
    })

    it("does not have a Moderate topic button", function() {
      cy.get("@footer").get("a[href=\"/moderation\"]").should("not.exist")
    })

    it("does not have a Calculate result button", function() {
      cy.get("@footer").get("a[href=\"/result\"]").should("not.exist")
    })

    it("has an All topics button", function() {
      cy.get("@footer").get("a[href=\"/topics\"]")
    })

    it("has a Projector button", function() {
      cy.get("@footer").get("a[href=\"/projector\"]")
    })

    it("has an Admin button", function() {
      cy.get("@footer").get("a[href=\"/admin\"]")
    })

    it("has a logout button", function() {
      cy.get("@footer").get("a[href=\"/logout\"]")
    })
  })

})

describe("After the Voting stage, the admin page", function() {
  const login = (user = {}) => {
    cy.session(user, () => {
      cy.typeLogin(user)
    })
  }

  before(() => {
    require("../support/reset_database.js").resetFinished()
  })

  beforeEach(() => {
    login({username: "admin", password: "secret"})
    cy.visit("/admin")
  })

  it("loads successfully", function() {
    cy.typeLogin({username: "admin", password: "secret"})
    cy.visit("/admin")
  })

  it("has a logo", function() {
    cy.get("img[class=logo]")
  })

  describe("The navigation footer exists", function() {
    beforeEach(() => {
      cy.get("ul[class=\"foot-nav\"]").as("footer")
    })

    it("does not have a Nominate topic button", function() {
      cy.get("@footer").get("a[href=\"/nomination\"]").should("not.exist")
    })

    it("has a Moderate topic button", function() {
      cy.get("@footer").get("a[href=\"/moderation\"]")
    })

    it("has a Calculate result button", function() {
      cy.get("@footer").get("a[href=\"/result\"]")
    })

    it("has an All topics button", function() {
      cy.get("@footer").get("a[href=\"/topics\"]")
    })

    it("has a Projector button", function() {
      cy.get("@footer").get("a[href=\"/projector\"]")
    })

    it("has an Admin button", function() {
      cy.get("@footer").get("a[href=\"/admin\"]")
    })

    it("has a logout button", function() {
      cy.get("@footer").get("a[href=\"/logout\"]")
    })
  })
})

describe("Saving changes updates the database", function() {
  const checkDB = require("../support/check_database.js")
  const resetDB = require("../support/reset_database.js")
  const login = (user = {}) => {
    cy.session(user, () => {
      cy.typeLogin(user)
    })
  }

  beforeEach(() => {
    resetDB.reset()
    login({username: "admin", password: "secret"})
    cy.visit("/admin")
  })

  it("sets the Prep BoF to its own slot", function() {
    cy.get("input[type=checkbox][name=schedule_prep]").check().then(function() {
      cy.get("input[type=submit][value=Save]").click().then(function() {
        checkDB.checkPrepBoF("False", -1, -1)
      })
    })
  })

  it("sets the Prep BoF to second round", function() {
    cy.get("select[name=prep_bof_round]").select("second").then(function() {
      cy.get("input[type=submit][value=Save]").click().then(function() {
        checkDB.checkPrepBoF("True", 1, -1)
      })
    })
  })

  it("sets the Prep BoF to Room B", function() {
    cy.get("select[name=prep_bof_location]").select("Room B").then(function() {
      cy.get("input[type=submit][value=Save]").click().then(function() {
        checkDB.checkPrepBoF("True", -1, 1)
      })
    })
  })

})

describe("Download the database", function() {
  const login = (user = {}) => {
    cy.session(user, () => {
      cy.typeLogin(user)
    })
  }

  before(() => {
    require("../support/reset_database.js").reset()
    login({username: "admin", password: "secret"})
    cy.visit("/admin")
  })

  it("works", function() {
    cy.request({url: "/admin", method: "POST", form: true, body: {download_database: "yes"}}).then((response) => {
      let now = dayjs.utc().format("YYYY-MM-DD_hhmm")
      now = dayjs().utc().format("YYYY-MM-DD_hhmm")
      const disposition = "attachment; filename=db-backup-BOF-" + now + ".sql"
      expect(response.status).to.eq(200)
      expect(response.headers["content-disposition"]).to.eq(disposition)
      expect(response.headers["content-type"]).to.eq("application/octet-stream")
    })
  })
})

