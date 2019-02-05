insert into participant values('1', 'admin', PASSWORD('secret'));
insert into location (id,name) values (0,'Room A');
insert into location (id,name) values (1,'Room B');
insert into location (id,name) values (2,'Room C');
insert into round (id,time_period) values(0,'first');
insert into round (id,time_period) values(1,'second');
insert into round (id,time_period) values(2,'third');
insert into config_old(id, nomination_begins, nomination_ends, voting_begins, voting_ends)
values(1, '2018-01-25 13:00:00', '2018-01-28 13:00:00', '2018-01-28 13:00:00', '2018-01-28 18:00:00');
insert into config(id, item, value) values(1, 'nomination_begins', '2018-01-25 13:00:00');
insert into config(id, item, value) values(2, 'nomination_ends', '2018-01-28 13:00:00');
insert into config(id, item, value) values(3, 'voting_begins', '2018-01-28 13:00:00');
insert into config(id, item, value) values(4, 'voting_ends', '2018-01-28 18:00:00');
insert into workshop(id, name, description) values(0, 'Prep Team', 'This is where the Prep Team meets to review this conference and discuss ideas for the next years conference. Everyone is welcome to join!');
