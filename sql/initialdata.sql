insert into participant values('1', 'admin', PASSWORD('secret'));
insert into location (name) values ('Room A');
insert into location (name) values ('Room B');
insert into location (name) values ('Room C');
insert into round (time_period) values('first');
insert into round (time_period) values('second');
insert into round (time_period) values('third');
insert into config_old(id, nomination_begins, nomination_ends, voting_begins, voting_ends)
values(1, '2018-01-25 13:00:00', '2018-01-28 13:00:00', '2018-01-28 13:00:00', '2018-01-28 18:00:00');
insert into config(id, item, value) values(1, 'nomination_begins', '2018-01-25 13:00:00');
insert into config(id, item, value) values(2, 'nomination_ends', '2018-01-28 13:00:00');
insert into config(id, item, value) values(3, 'voting_begins', '2018-01-28 13:00:00');
insert into config(id, item, value) values(4, 'voting_ends', '2018-01-28 18:00:00');