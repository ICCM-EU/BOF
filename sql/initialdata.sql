insert into participant values('1', 'admin', PASSWORD('secret'));
insert into location (name) values ('Room A');
insert into location (name) values ('Room B');
insert into location (name) values ('Room C');
insert into round (time_period) values('first');
insert into round (time_period) values('second');
insert into round (time_period) values('third');
insert into config(id, nomination_begins, nomination_ends, voting_begins, voting_ends) 
values(1, '2018-01-25 13:00:00', '2018-01-28 13:00:00', '2018-01-28 13:00:00', '2018-01-28 18:00:00');

