drop procedure if exists sp_calculate_answer_v6;
delimiter //
create procedure sp_calculate_answer_v6()
begin
  declare maxID integer;
  declare stepVal integer default 10000;
  declare currId integer default 0;
  select max(id) into maxID from bybit1mins;

  while (currId < maxID) do
    set currId := currId + stepVal;

    update bybit1mins b
      set correct_answer = (select
                                case
                                  when b2.low <= round(b.close * 0.995, 2) then 0
                                  when b2.close > round(b.close * 1.005, 2) then 1
                                  else null
                                end
                              from bybit1mins_temp b2
                              where 1=1 
                                and b2.step = b.step
                                and b2.id > b.id
                                and case
                                      when b2.low <= round(b.close * 0.995, 2) then 0
                                      when b2.close > round(b.close * 1.005, 2) then 1
                                      else null
                                    end is not null
                              order by id asc
                              limit 1
                             )
      where 1=1 
        -- and correct_answer is null
        and id between currId - stepVal and currId
        and correct_answer is null
      order by open_time asc;
      
    update bybit1mins b
      set correct_answer = (select
                                case
                                  when b2.low <= round(b.close * 0.995, 2) then 0
                                  when b2.close > round(b.close * 1.005, 2) then 1
                                  else null
                                end
                              from bybit1mins_temp b2
                              where 1=1 
                                and b2.step = b.step + 1
                                and b2.id > b.id
                                and case
                                      when b2.low <= round(b.close * 0.995, 2) then 0
                                      when b2.close > round(b.close * 1.005, 2) then 1
                                      else null
                                    end is not null
                              order by id asc
                              limit 1
                             )
      where 1=1 
        -- and correct_answer is null
        and id between currId - stepVal and currId
        and correct_answer is null
      order by open_time asc;
  end while;
end //
delimiter ;