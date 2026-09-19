-- Indexes that the panel's queries want on the GAME database.
--
-- The panel never creates or changes anything in that database, so this is a
-- reference to run yourself, on a copy first. Some of these may already exist:
-- check with `SHOW INDEX FROM times;` before adding a duplicate.
--
-- Measured on 60,000 players / 250,000 runs (MariaDB 11), the panel's queries:
--
--                                       no indexes   with these
--   latest runs, page 1                    857 ms        0.8 ms
--   latest runs, page 2500 (offset 30k)    816 ms         32 ms
--   latest runs, records only            3,151 ms        1.5 ms
--   a player's profile                   1,213 ms        8.9 ms
--   runs in the last 24 hours              798 ms        1.5 ms
--   leaderboard: most records               52 ms        0.4 ms
--
-- Player search (name LIKE '%term%') cannot use any index and costs ~35 ms per
-- scan at 60,000 players, growing in step with the table. The panel caches each
-- search page for two minutes for that reason.

-- The feed sorts by (record_date DESC, user_uuid DESC); both directions must match
-- for the index to be usable.
ALTER TABLE times
    ADD INDEX idx_times_date (record_date, user_uuid),
    ADD INDEX idx_times_run  (user_uuid, map_uuid, category_id),
    ADD INDEX idx_times_cat  (category_id, record_date, user_uuid);

ALTER TABLE ranked_times
    ADD INDEX idx_rt_run   (user_uuid, map_uuid, category_id),
    ADD INDEX idx_rt_board (map_uuid, category_id, `rank`),
    ADD INDEX idx_rt_rank  (`rank`, user_uuid);

ALTER TABLE users       ADD INDEX idx_users_name (name);
ALTER TABLE played_time ADD INDEX idx_pt_auth (auth_id);
