drop procedure if exists sp_calculate_answer_v3;
delimiter //
create procedure sp_calculate_answer_v3()
begin
	  declare maxID integer;
	  declare step integer default 1;
	  declare currId integer default 0;
	  select max(id) into maxID from bybit1mins;

	  while (currId < maxID) do
		    set currId := currId + step;

		    update bybit1mins b
		      set correct_answer2 = (select
			                                case
								                                  when b2.low <= round(b.close * 0.995, 2) then 0
													                                  when b2.close > round(b.close * 1.005, 2) then 1
																		                                  else null
																							                                end
																											                              from bybit1mins_temp b2
																														                              where b2.id > b.id
																																	                                -- and b2.id between currId - step and currId + 5000
																																					                                and b2.step between currId and currId + 2
																																									                                and case
																																													                                      when b2.low <= round(b.close * 0.995, 2) then 0
																																																		                                      when b2.close > round(b.close * 1.005, 2) then 1
																																																							                                      else null
																																																												                                    end is not null
																																																																                              order by open_time asc
																																																																			                              limit 1
																																																																						                             ),
																																																																									        updatecol = now()
																																																																										      where 1=1 
																																																																										        -- and correct_answer is null
																																																																											        -- and id between currId - step and currId
        and step = currId
	        and correct_answer2 is null
		      order by open_time asc;
		  end while;
	end //
	delimiter ;
