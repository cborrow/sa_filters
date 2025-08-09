CREATE TABLE user_maps (
  owner_id        integer unsigned NOT NULL,  -- Unix account owner: users.id
  user_id        integer unsigned NOT NULL,  -- Email address: users.id
  PRIMARY KEY (owner_id,user_id)
);