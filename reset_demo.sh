#!/bin/bash

# see database credentials in cfg/settings.php
cat /var/www/bof/sql/testdata.sql | mysql -u myuser mydbname --password="mypwd"

echo "DELETE FROM config;
INSERT INTO config (item, value) VALUES('nomination_begins', DATE_ADD(NOW(), INTERVAL -1 DAY));
INSERT INTO config (item, value) VALUES('nomination_ends', DATE_ADD(NOW(), INTERVAL +1 DAY));
INSERT INTO config (item, value) VALUES('voting_begins', DATE_ADD(NOW(), INTERVAL +1 DAY));
INSERT INTO config (item, value) VALUES('voting_ends', DATE_ADD(NOW(), INTERVAL +2 DAY));"  | \
     mysql -u myuser mydbname --password="mypwd"

