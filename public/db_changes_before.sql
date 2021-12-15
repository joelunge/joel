alter table bybit1mins add (step integer);
create index idx_bybit1mins_step on bybit1mins(step);
update bybit1mins set step = id / 10000;
create table bybit1mins_temp as select * from bybit1mins;
alter table bybit1mins_temp add primary key(id);
create index idx_bybit1mins_temp_step on bybit1mins_temp(step);