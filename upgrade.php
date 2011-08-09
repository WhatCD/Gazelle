SET @vhid = (SELECT `ID` FROM `tags` WHERE `name` = 'vanity.house');

UPDATE torrents_group SET VanityHouse = (SELECT COUNT(*) FROM torrents_tags WHERE torrents_tags.TagID = @vhid AND torrents_tags.GroupID = torrents_group.ID);
