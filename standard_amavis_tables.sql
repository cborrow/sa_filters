-- local users
CREATE TABLE users (
  id         int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,  -- unique id
  priority   integer      NOT NULL DEFAULT '7',  -- sort field, 0 is low prior.
  policy_id  integer unsigned NOT NULL DEFAULT '1',  -- JOINs with policy.id
  email      varbinary(255) NOT NULL UNIQUE,
  fullname   varchar(255) DEFAULT NULL    -- not used by amavisd-new
  -- local   char(1)      -- Y/N  (optional field, see note further down)
);

-- any e-mail address (non- rfc2822-quoted), external or local,
-- used as senders in wblist
CREATE TABLE mailaddr (
  id         int unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
  priority   integer      NOT NULL DEFAULT '7',  -- 0 is low priority
  email      varbinary(255) NOT NULL UNIQUE
);

-- per-recipient whitelist and/or blacklist,
-- puts sender and recipient in relation wb  (white or blacklisted sender)
CREATE TABLE wblist (
  rid        integer unsigned NOT NULL,  -- recipient: users.id
  sid        integer unsigned NOT NULL,  -- sender: mailaddr.id
  wb         varchar(10)  NOT NULL,  -- W or Y / B or N / space=neutral / score
  PRIMARY KEY (rid,sid)
);

-- Amavis wb list lookup
-- $sql_select_white_black_list = 'SELECT wb FROM wblist'.
--    ' WHERE (wblist.rid=?) AND (wblist.email IN (%k))' .
--    ' ORDER BY wblist.priority DESC';