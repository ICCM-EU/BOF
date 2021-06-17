insert into participant values('1', 'admin', '$2y$10$Coh8TTXxnNnmouU8dxJ8aO91vyBHLTdwtWs3axSdVDfTJrZjPU5fy');
insert into location (id,name) values (0,'Room A');
insert into location (id,name) values (1,'Room B');
insert into location (id,name) values (2,'Room C');
insert into round (id,time_period) values(0,'first');
insert into round (id,time_period) values(1,'second');
insert into round (id,time_period) values(2,'third');
insert into config(id, item, value) values(1, 'nomination_begins', DATE_ADD(NOW(), INTERVAL -2 DAY));
insert into config(id, item, value) values(2, 'nomination_ends', DATE_ADD(NOW(), INTERVAL -1 DAY));
insert into config(id, item, value) values(3, 'voting_begins', DATE_ADD(DATE_ADD(NOW(), INTERVAL -1 DAY), INTERVAL +2 HOUR));
insert into config(id, item, value) values(4, 'voting_ends', DATE_ADD(NOW(), INTERVAL + 1 DAY));
/*
insert into config(id, item, value) values(5, 'branding', 'Africa');
*/
/*
insert into config(id, item, value) values(5, 'branding', 'Americas');
*/
/*
insert into config(id, item, value) values(5, 'branding', 'Asia');
*/
/*
insert into config(id, item, value) values(5, 'branding', 'Australia');
*/
insert into config(id, item, value) values(5, 'branding', 'Europe');
insert into config(id, item, value) values(6, 'allow_edit_nomination', 'false');
insert into config(id, item, value) values(7, 'allow_nomination_comments', 'false');
insert into workshop(id, name, description) values(1, 'Prep Team', 'The Prep Team is a handful of people who plan these annual conferences. If you might be interested in joining this team please come to this BOF. We\'re always looking for new ideas and help to make ICCM special every year!');
