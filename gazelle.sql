SET FOREIGN_KEY_CHECKS = 0;

CREATE DATABASE gazelle CHARACTER SET utf8 COLLATE utf8_swedish_ci;

USE gazelle;

CREATE TABLE `api_applications` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `UserID` int(10) NOT NULL,
  `Token` char(32) NOT NULL,
  `Name` varchar(50) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `api_users` (
  `UserID` int(10) NOT NULL,
  `AppID` int(10) NOT NULL,
  `Token` char(32) NOT NULL,
  `State` enum('0','1','2') NOT NULL DEFAULT '0',
  `Time` datetime NOT NULL,
  `Access` text,
  PRIMARY KEY (`UserID`,`AppID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `artists_alias` (
  `AliasID` int(10) NOT NULL AUTO_INCREMENT,
  `ArtistID` int(10) NOT NULL,
  `Name` varchar(200) DEFAULT NULL,
  `Redirect` int(10) NOT NULL DEFAULT '0',
  `UserID` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`AliasID`),
  KEY `ArtistID` (`ArtistID`),
  KEY `Name` (`Name`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `artists_group` (
  `ArtistID` int(10) NOT NULL AUTO_INCREMENT,
  `Name` varchar(200) DEFAULT NULL,
  `RevisionID` int(12) DEFAULT NULL,
  `VanityHouse` tinyint(1) DEFAULT '0',
  `LastCommentID` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ArtistID`),
  KEY `Name` (`Name`),
  KEY `RevisionID` (`RevisionID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `artists_similar` (
  `ArtistID` int(10) NOT NULL DEFAULT '0',
  `SimilarID` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ArtistID`,`SimilarID`),
  KEY `ArtistID` (`ArtistID`),
  KEY `SimilarID` (`SimilarID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `artists_similar_scores` (
  `SimilarID` int(12) NOT NULL AUTO_INCREMENT,
  `Score` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`SimilarID`),
  KEY `Score` (`Score`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `artists_similar_votes` (
  `SimilarID` int(12) NOT NULL,
  `UserID` int(10) NOT NULL,
  `Way` enum('up','down') NOT NULL DEFAULT 'up',
  PRIMARY KEY (`SimilarID`,`UserID`,`Way`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `artists_tags` (
  `TagID` int(10) NOT NULL DEFAULT '0',
  `ArtistID` int(10) NOT NULL DEFAULT '0',
  `PositiveVotes` int(6) NOT NULL DEFAULT '1',
  `NegativeVotes` int(6) NOT NULL DEFAULT '1',
  `UserID` int(10) DEFAULT NULL,
  PRIMARY KEY (`TagID`,`ArtistID`),
  KEY `TagID` (`TagID`),
  KEY `ArtistID` (`ArtistID`),
  KEY `PositiveVotes` (`PositiveVotes`),
  KEY `NegativeVotes` (`NegativeVotes`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `bad_passwords` (
  `Password` char(32) NOT NULL,
  PRIMARY KEY (`Password`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `blog` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UserID` int(10) unsigned NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Body` text NOT NULL,
  `Time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ThreadID` int(10) unsigned DEFAULT NULL,
  `Important` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `UserID` (`UserID`),
  KEY `Time` (`Time`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `bookmarks_artists` (
  `UserID` int(10) NOT NULL,
  `ArtistID` int(10) NOT NULL,
  `Time` datetime NOT NULL,
  KEY `UserID` (`UserID`),
  KEY `ArtistID` (`ArtistID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `bookmarks_collages` (
  `UserID` int(10) NOT NULL,
  `CollageID` int(10) NOT NULL,
  `Time` datetime NOT NULL,
  KEY `UserID` (`UserID`),
  KEY `CollageID` (`CollageID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `bookmarks_requests` (
  `UserID` int(10) NOT NULL,
  `RequestID` int(10) NOT NULL,
  `Time` datetime NOT NULL,
  KEY `UserID` (`UserID`),
  KEY `RequestID` (`RequestID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `bookmarks_torrents` (
  `UserID` int(10) NOT NULL,
  `GroupID` int(10) NOT NULL,
  `Time` datetime NOT NULL,
  `Sort` int(11) NOT NULL DEFAULT '0',
  UNIQUE KEY `groups_users` (`GroupID`,`UserID`),
  KEY `UserID` (`UserID`),
  KEY `GroupID` (`GroupID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `calendar` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) DEFAULT NULL,
  `Body` mediumtext,
  `Category` tinyint(1) DEFAULT NULL,
  `StartDate` datetime DEFAULT NULL,
  `EndDate` datetime DEFAULT NULL,
  `AddedBy` int(10) DEFAULT NULL,
  `Importance` tinyint(1) DEFAULT NULL,
  `Team` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `changelog` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Message` text NOT NULL,
  `Author` varchar(30) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `collages` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) NOT NULL DEFAULT '',
  `Description` text NOT NULL,
  `UserID` int(10) NOT NULL DEFAULT '0',
  `NumTorrents` int(4) NOT NULL DEFAULT '0',
  `Deleted` enum('0','1') DEFAULT '0',
  `Locked` enum('0','1') NOT NULL DEFAULT '0',
  `CategoryID` int(2) NOT NULL DEFAULT '1',
  `TagList` varchar(500) NOT NULL DEFAULT '',
  `MaxGroups` int(10) NOT NULL DEFAULT '0',
  `MaxGroupsPerUser` int(10) NOT NULL DEFAULT '0',
  `Featured` tinyint(4) NOT NULL DEFAULT '0',
  `Subscribers` int(10) DEFAULT '0',
  `updated` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name` (`Name`),
  KEY `UserID` (`UserID`),
  KEY `CategoryID` (`CategoryID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `collages_artists` (
  `CollageID` int(10) NOT NULL,
  `ArtistID` int(10) NOT NULL,
  `UserID` int(10) NOT NULL,
  `Sort` int(10) NOT NULL DEFAULT '0',
  `AddedOn` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`CollageID`,`ArtistID`),
  KEY `UserID` (`UserID`),
  KEY `Sort` (`Sort`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `collages_torrents` (
  `CollageID` int(10) NOT NULL,
  `GroupID` int(10) NOT NULL,
  `UserID` int(10) NOT NULL,
  `Sort` int(10) NOT NULL DEFAULT '0',
  `AddedOn` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`CollageID`,`GroupID`),
  KEY `UserID` (`UserID`),
  KEY `Sort` (`Sort`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `comments` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Page` enum('artist','collages','requests','torrents') NOT NULL,
  `PageID` int(10) NOT NULL,
  `AuthorID` int(10) NOT NULL,
  `AddedTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Body` mediumtext,
  `EditedUserID` int(10) DEFAULT NULL,
  `EditedTime` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `Page` (`Page`,`PageID`),
  KEY `AuthorID` (`AuthorID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `comments_edits` (
  `Page` enum('forums','artist','collages','requests','torrents') DEFAULT NULL,
  `PostID` int(10) DEFAULT NULL,
  `EditUser` int(10) DEFAULT NULL,
  `EditTime` datetime DEFAULT NULL,
  `Body` mediumtext,
  KEY `EditUser` (`EditUser`),
  KEY `PostHistory` (`Page`,`PostID`,`EditTime`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `comments_edits_tmp` (
  `Page` enum('forums','artist','collages','requests','torrents') DEFAULT NULL,
  `PostID` int(10) DEFAULT NULL,
  `EditUser` int(10) DEFAULT NULL,
  `EditTime` datetime DEFAULT NULL,
  `Body` mediumtext,
  KEY `EditUser` (`EditUser`),
  KEY `PostHistory` (`Page`,`PostID`,`EditTime`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `concerts` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `ConcertID` int(10) NOT NULL,
  `TopicID` int(10) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `ConcertID` (`ConcertID`),
  KEY `TopicID` (`TopicID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `cover_art` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `GroupID` int(10) NOT NULL,
  `Image` varchar(255) NOT NULL DEFAULT '',
  `Summary` varchar(100) DEFAULT NULL,
  `UserID` int(10) NOT NULL DEFAULT '0',
  `Time` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `GroupID` (`GroupID`,`Image`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `currency_conversion_rates` (
  `Currency` char(3) NOT NULL,
  `Rate` decimal(9,4) DEFAULT NULL,
  `Time` datetime DEFAULT NULL,
  PRIMARY KEY (`Currency`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `do_not_upload` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) NOT NULL,
  `Comment` varchar(255) NOT NULL,
  `UserID` int(10) NOT NULL,
  `Time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Sequence` mediumint(8) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Time` (`Time`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `donations` (
  `UserID` int(10) NOT NULL,
  `Amount` decimal(6,2) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Time` datetime NOT NULL,
  `Currency` varchar(5) NOT NULL DEFAULT 'USD',
  `Source` varchar(30) NOT NULL DEFAULT '',
  `Reason` mediumtext NOT NULL,
  `Rank` int(10) DEFAULT '0',
  `AddedBy` int(10) DEFAULT '0',
  `TotalRank` int(10) DEFAULT '0',
  KEY `UserID` (`UserID`),
  KEY `Time` (`Time`),
  KEY `Amount` (`Amount`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `donations_bitcoin` (
  `BitcoinAddress` varchar(34) NOT NULL,
  `Amount` decimal(24,8) NOT NULL,
  KEY `BitcoinAddress` (`BitcoinAddress`,`Amount`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `donor_forum_usernames` (
  `UserID` int(10) NOT NULL DEFAULT '0',
  `Prefix` varchar(30) NOT NULL DEFAULT '',
  `Suffix` varchar(30) NOT NULL DEFAULT '',
  `UseComma` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `donor_rewards` (
  `UserID` int(10) NOT NULL DEFAULT '0',
  `IconMouseOverText` varchar(200) NOT NULL DEFAULT '',
  `AvatarMouseOverText` varchar(200) NOT NULL DEFAULT '',
  `CustomIcon` varchar(200) NOT NULL DEFAULT '',
  `SecondAvatar` varchar(200) NOT NULL DEFAULT '',
  `CustomIconLink` varchar(200) NOT NULL DEFAULT '',
  `ProfileInfo1` text NOT NULL,
  `ProfileInfo2` text NOT NULL,
  `ProfileInfo3` text NOT NULL,
  `ProfileInfo4` text NOT NULL,
  `ProfileInfoTitle1` varchar(255) NOT NULL,
  `ProfileInfoTitle2` varchar(255) NOT NULL,
  `ProfileInfoTitle3` varchar(255) NOT NULL,
  `ProfileInfoTitle4` varchar(255) NOT NULL,
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `drives` (
  `DriveID` int(10) NOT NULL AUTO_INCREMENT,
  `Name` varchar(50) NOT NULL,
  `Offset` varchar(10) NOT NULL,
  PRIMARY KEY (`DriveID`),
  KEY `Name` (`Name`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `dupe_groups` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Comments` text,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `email_blacklist` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `UserID` int(10) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Time` datetime NOT NULL,
  `Comment` text NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `featured_albums` (
  `GroupID` int(10) NOT NULL DEFAULT '0',
  `ThreadID` int(10) NOT NULL DEFAULT '0',
  `Title` varchar(35) NOT NULL DEFAULT '',
  `Started` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Ended` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `featured_merch` (
  `ProductID` int(10) NOT NULL DEFAULT '0',
  `Title` varchar(35) NOT NULL DEFAULT '',
  `Image` varchar(255) NOT NULL DEFAULT '',
  `Started` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Ended` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ArtistID` int(10) unsigned DEFAULT '0'
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `forums` (
  `ID` int(6) unsigned NOT NULL AUTO_INCREMENT,
  `CategoryID` tinyint(2) NOT NULL DEFAULT '0',
  `Sort` int(6) unsigned NOT NULL,
  `Name` varchar(40) NOT NULL DEFAULT '',
  `Description` varchar(255) DEFAULT '',
  `MinClassRead` int(4) NOT NULL DEFAULT '0',
  `MinClassWrite` int(4) NOT NULL DEFAULT '0',
  `MinClassCreate` int(4) NOT NULL DEFAULT '0',
  `NumTopics` int(10) NOT NULL DEFAULT '0',
  `NumPosts` int(10) NOT NULL DEFAULT '0',
  `LastPostID` int(10) NOT NULL DEFAULT '0',
  `LastPostAuthorID` int(10) NOT NULL DEFAULT '0',
  `LastPostTopicID` int(10) NOT NULL DEFAULT '0',
  `LastPostTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `AutoLock` enum('0','1') DEFAULT '1',
  `AutoLockWeeks` int(3) unsigned NOT NULL DEFAULT '4',
  PRIMARY KEY (`ID`),
  KEY `Sort` (`Sort`),
  KEY `MinClassRead` (`MinClassRead`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `forums_categories` (
  `ID` tinyint(2) NOT NULL AUTO_INCREMENT,
  `Name` varchar(40) NOT NULL DEFAULT '',
  `Sort` int(6) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `Sort` (`Sort`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `forums_last_read_topics` (
  `UserID` int(10) NOT NULL,
  `TopicID` int(10) NOT NULL,
  `PostID` int(10) NOT NULL,
  PRIMARY KEY (`UserID`,`TopicID`),
  KEY `TopicID` (`TopicID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `forums_polls` (
  `TopicID` int(10) unsigned NOT NULL,
  `Question` varchar(255) NOT NULL,
  `Answers` text NOT NULL,
  `Featured` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Closed` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`TopicID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `forums_polls_votes` (
  `TopicID` int(10) unsigned NOT NULL,
  `UserID` int(10) unsigned NOT NULL,
  `Vote` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY (`TopicID`,`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `forums_posts` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `TopicID` int(10) NOT NULL,
  `AuthorID` int(10) NOT NULL,
  `AddedTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Body` mediumtext,
  `EditedUserID` int(10) DEFAULT NULL,
  `EditedTime` datetime DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `TopicID` (`TopicID`),
  KEY `AuthorID` (`AuthorID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `forums_specific_rules` (
  `ForumID` int(6) unsigned DEFAULT NULL,
  `ThreadID` int(10) DEFAULT NULL
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `forums_topic_notes` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `TopicID` int(10) NOT NULL,
  `AuthorID` int(10) NOT NULL,
  `AddedTime` datetime NOT NULL,
  `Body` mediumtext,
  PRIMARY KEY (`ID`),
  KEY `TopicID` (`TopicID`),
  KEY `AuthorID` (`AuthorID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `forums_topics` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Title` varchar(150) NOT NULL,
  `AuthorID` int(10) NOT NULL,
  `IsLocked` enum('0','1') NOT NULL DEFAULT '0',
  `IsSticky` enum('0','1') NOT NULL DEFAULT '0',
  `ForumID` int(3) NOT NULL,
  `NumPosts` int(10) NOT NULL DEFAULT '0',
  `LastPostID` int(10) NOT NULL,
  `LastPostTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastPostAuthorID` int(10) NOT NULL,
  `StickyPostID` int(10) NOT NULL DEFAULT '0',
  `Ranking` tinyint(2) DEFAULT '0',
  `CreatedTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ID`),
  KEY `AuthorID` (`AuthorID`),
  KEY `ForumID` (`ForumID`),
  KEY `IsSticky` (`IsSticky`),
  KEY `LastPostID` (`LastPostID`),
  KEY `Title` (`Title`),
  KEY `CreatedTime` (`CreatedTime`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `friends` (
  `UserID` int(10) unsigned NOT NULL,
  `FriendID` int(10) unsigned NOT NULL,
  `Comment` text NOT NULL,
  PRIMARY KEY (`UserID`,`FriendID`),
  KEY `UserID` (`UserID`),
  KEY `FriendID` (`FriendID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `geoip_country` (
  `StartIP` int(11) unsigned NOT NULL,
  `EndIP` int(11) unsigned NOT NULL,
  `Code` varchar(2) NOT NULL,
  PRIMARY KEY (`StartIP`,`EndIP`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `group_log` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `GroupID` int(10) NOT NULL,
  `TorrentID` int(10) NOT NULL,
  `UserID` int(10) NOT NULL DEFAULT '0',
  `Info` mediumtext,
  `Time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Hidden` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `GroupID` (`GroupID`),
  KEY `TorrentID` (`TorrentID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `invite_tree` (
  `UserID` int(10) NOT NULL DEFAULT '0',
  `InviterID` int(10) NOT NULL DEFAULT '0',
  `TreePosition` int(8) NOT NULL DEFAULT '1',
  `TreeID` int(10) NOT NULL DEFAULT '1',
  `TreeLevel` int(3) NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserID`),
  KEY `InviterID` (`InviterID`),
  KEY `TreePosition` (`TreePosition`),
  KEY `TreeID` (`TreeID`),
  KEY `TreeLevel` (`TreeLevel`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `invites` (
  `InviterID` int(10) NOT NULL DEFAULT '0',
  `InviteKey` char(32) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `Expires` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Reason` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`InviteKey`),
  KEY `Expires` (`Expires`),
  KEY `InviterID` (`InviterID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `ip_bans` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `FromIP` int(11) unsigned NOT NULL,
  `ToIP` int(11) unsigned NOT NULL,
  `Reason` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `FromIP_2` (`FromIP`,`ToIP`),
  KEY `ToIP` (`ToIP`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `label_aliases` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `BadLabel` varchar(100) NOT NULL,
  `AliasLabel` varchar(100) NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `BadLabel` (`BadLabel`),
  KEY `AliasLabel` (`AliasLabel`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `lastfm_users` (
  `ID` int(10) unsigned NOT NULL,
  `Username` varchar(20) NOT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `library_contest` (
  `UserID` int(10) NOT NULL,
  `TorrentID` int(10) NOT NULL,
  `Points` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserID`,`TorrentID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `log` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Message` varchar(400) NOT NULL,
  `Time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ID`),
  KEY `Time` (`Time`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `login_attempts` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UserID` int(10) unsigned NOT NULL,
  `IP` varchar(15) NOT NULL,
  `LastAttempt` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Attempts` int(10) unsigned NOT NULL,
  `BannedUntil` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Bans` int(10) unsigned NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `UserID` (`UserID`),
  KEY `IP` (`IP`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `new_info_hashes` (
  `TorrentID` int(11) NOT NULL,
  `InfoHash` binary(20) DEFAULT NULL,
  PRIMARY KEY (`TorrentID`),
  KEY `InfoHash` (`InfoHash`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `news` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UserID` int(10) unsigned NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Body` text NOT NULL,
  `Time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ID`),
  KEY `UserID` (`UserID`),
  KEY `Time` (`Time`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `ocelot_query_times` (
  `buffer` enum('users','torrents','snatches','peers') NOT NULL,
  `starttime` datetime NOT NULL,
  `ocelotinstance` datetime NOT NULL,
  `querylength` int(11) NOT NULL,
  `timespent` int(11) NOT NULL,
  UNIQUE KEY `starttime` (`starttime`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `permissions` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Level` int(10) unsigned NOT NULL,
  `Name` varchar(25) NOT NULL,
  `Values` text NOT NULL,
  `DisplayStaff` enum('0','1') NOT NULL DEFAULT '0',
  `PermittedForums` varchar(150) NOT NULL DEFAULT '',
  `Secondary` tinyint(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Level` (`Level`),
  KEY `DisplayStaff` (`DisplayStaff`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `pm_conversations` (
  `ID` int(12) NOT NULL AUTO_INCREMENT,
  `Subject` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `pm_conversations_users` (
  `UserID` int(10) NOT NULL DEFAULT '0',
  `ConvID` int(12) NOT NULL DEFAULT '0',
  `InInbox` enum('1','0') NOT NULL,
  `InSentbox` enum('1','0') NOT NULL,
  `SentDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ReceivedDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `UnRead` enum('1','0') NOT NULL DEFAULT '1',
  `Sticky` enum('1','0') NOT NULL DEFAULT '0',
  `ForwardedTo` int(12) NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserID`,`ConvID`),
  KEY `InInbox` (`InInbox`),
  KEY `InSentbox` (`InSentbox`),
  KEY `ConvID` (`ConvID`),
  KEY `UserID` (`UserID`),
  KEY `SentDate` (`SentDate`),
  KEY `ReceivedDate` (`ReceivedDate`),
  KEY `Sticky` (`Sticky`),
  KEY `ForwardedTo` (`ForwardedTo`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `pm_messages` (
  `ID` int(12) NOT NULL AUTO_INCREMENT,
  `ConvID` int(12) NOT NULL DEFAULT '0',
  `SentDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `SenderID` int(10) NOT NULL DEFAULT '0',
  `Body` text,
  PRIMARY KEY (`ID`),
  KEY `ConvID` (`ConvID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `push_notifications_usage` (
  `PushService` varchar(10) NOT NULL,
  `TimesUsed` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`PushService`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `reports` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UserID` int(10) unsigned NOT NULL DEFAULT '0',
  `ThingID` int(10) unsigned NOT NULL DEFAULT '0',
  `Type` varchar(30) DEFAULT NULL,
  `Comment` text,
  `ResolverID` int(10) unsigned NOT NULL DEFAULT '0',
  `Status` enum('New','InProgress','Resolved') DEFAULT 'New',
  `ResolvedTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ReportedTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Reason` text NOT NULL,
  `ClaimerID` int(10) unsigned NOT NULL DEFAULT '0',
  `Notes` text NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Status` (`Status`),
  KEY `Type` (`Type`),
  KEY `ResolvedTime` (`ResolvedTime`),
  KEY `ResolverID` (`ResolverID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `reports_email_blacklist` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Type` tinyint(4) NOT NULL DEFAULT '0',
  `UserID` int(10) NOT NULL,
  `Time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Checked` tinyint(4) NOT NULL DEFAULT '0',
  `ResolverID` int(10) DEFAULT '0',
  `Email` varchar(255) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `Time` (`Time`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `reportsv2` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `ReporterID` int(10) unsigned NOT NULL DEFAULT '0',
  `TorrentID` int(10) unsigned NOT NULL DEFAULT '0',
  `Type` varchar(20) DEFAULT '',
  `UserComment` text,
  `ResolverID` int(10) unsigned NOT NULL DEFAULT '0',
  `Status` enum('New','InProgress','Resolved') DEFAULT 'New',
  `ReportedTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastChangeTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `ModComment` text,
  `Track` text,
  `Image` text,
  `ExtraID` text,
  `Link` text,
  `LogMessage` text,
  PRIMARY KEY (`ID`),
  KEY `Status` (`Status`),
  KEY `Type` (`Type`(1)),
  KEY `LastChangeTime` (`LastChangeTime`),
  KEY `TorrentID` (`TorrentID`),
  KEY `ResolverID` (`ResolverID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `requests` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UserID` int(10) unsigned NOT NULL DEFAULT '0',
  `TimeAdded` datetime NOT NULL,
  `LastVote` datetime DEFAULT NULL,
  `CategoryID` int(3) NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Year` int(4) DEFAULT NULL,
  `Image` varchar(255) DEFAULT NULL,
  `Description` text NOT NULL,
  `ReleaseType` tinyint(2) DEFAULT NULL,
  `CatalogueNumber` varchar(50) NOT NULL,
  `BitrateList` varchar(255) DEFAULT NULL,
  `FormatList` varchar(255) DEFAULT NULL,
  `MediaList` varchar(255) DEFAULT NULL,
  `LogCue` varchar(20) DEFAULT NULL,
  `FillerID` int(10) unsigned NOT NULL DEFAULT '0',
  `TorrentID` int(10) unsigned NOT NULL DEFAULT '0',
  `TimeFilled` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Visible` binary(1) NOT NULL DEFAULT '1',
  `RecordLabel` varchar(80) DEFAULT NULL,
  `GroupID` int(10) DEFAULT NULL,
  `OCLC` varchar(55) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `Userid` (`UserID`),
  KEY `Name` (`Title`),
  KEY `Filled` (`TorrentID`),
  KEY `FillerID` (`FillerID`),
  KEY `TimeAdded` (`TimeAdded`),
  KEY `Year` (`Year`),
  KEY `TimeFilled` (`TimeFilled`),
  KEY `LastVote` (`LastVote`),
  KEY `GroupID` (`GroupID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `requests_artists` (
  `RequestID` int(10) unsigned NOT NULL,
  `ArtistID` int(10) NOT NULL,
  `AliasID` int(10) NOT NULL,
  `Importance` enum('1','2','3','4','5','6','7') DEFAULT NULL,
  PRIMARY KEY (`RequestID`,`AliasID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `requests_tags` (
  `TagID` int(10) NOT NULL DEFAULT '0',
  `RequestID` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`TagID`,`RequestID`),
  KEY `TagID` (`TagID`),
  KEY `RequestID` (`RequestID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `requests_votes` (
  `RequestID` int(10) NOT NULL DEFAULT '0',
  `UserID` int(10) NOT NULL DEFAULT '0',
  `Bounty` bigint(20) unsigned NOT NULL,
  PRIMARY KEY (`RequestID`,`UserID`),
  KEY `RequestID` (`RequestID`),
  KEY `UserID` (`UserID`),
  KEY `Bounty` (`Bounty`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `schedule` (
  `NextHour` int(2) NOT NULL DEFAULT '0',
  `NextDay` int(2) NOT NULL DEFAULT '0',
  `NextBiWeekly` int(2) NOT NULL DEFAULT '0'
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `site_history` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Title` varchar(255) DEFAULT NULL,
  `Url` varchar(255) NOT NULL DEFAULT '',
  `Category` tinyint(2) DEFAULT NULL,
  `SubCategory` tinyint(2) DEFAULT NULL,
  `Tags` mediumtext,
  `AddedBy` int(10) DEFAULT NULL,
  `Date` datetime DEFAULT NULL,
  `Body` mediumtext,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `sphinx_a` (
  `gid` int(11) DEFAULT NULL,
  `aname` text,
  KEY `gid` (`gid`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `sphinx_delta` (
  `ID` int(10) NOT NULL,
  `GroupID` int(11) NOT NULL DEFAULT '0',
  `GroupName` varchar(255) DEFAULT NULL,
  `ArtistName` varchar(2048) DEFAULT NULL,
  `TagList` varchar(728) DEFAULT NULL,
  `Year` int(4) DEFAULT NULL,
  `CatalogueNumber` varchar(50) DEFAULT NULL,
  `RecordLabel` varchar(50) DEFAULT NULL,
  `CategoryID` tinyint(2) DEFAULT NULL,
  `Time` int(12) DEFAULT NULL,
  `ReleaseType` tinyint(2) DEFAULT NULL,
  `Size` bigint(20) DEFAULT NULL,
  `Snatched` int(10) DEFAULT NULL,
  `Seeders` int(10) DEFAULT NULL,
  `Leechers` int(10) DEFAULT NULL,
  `LogScore` int(3) DEFAULT NULL,
  `Scene` tinyint(1) NOT NULL DEFAULT '0',
  `VanityHouse` tinyint(1) NOT NULL DEFAULT '0',
  `HasLog` tinyint(1) DEFAULT NULL,
  `HasCue` tinyint(1) DEFAULT NULL,
  `FreeTorrent` tinyint(1) DEFAULT NULL,
  `Media` varchar(255) DEFAULT NULL,
  `Format` varchar(255) DEFAULT NULL,
  `Encoding` varchar(255) DEFAULT NULL,
  `RemasterYear` varchar(50) NOT NULL DEFAULT '',
  `RemasterTitle` varchar(512) DEFAULT NULL,
  `RemasterRecordLabel` varchar(50) DEFAULT NULL,
  `RemasterCatalogueNumber` varchar(50) DEFAULT NULL,
  `FileList` mediumtext,
  `Description` text,
  `VoteScore` float NOT NULL DEFAULT '0',
  `LastChanged` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ID`),
  KEY `GroupID` (`GroupID`),
  KEY `Size` (`Size`)
) ENGINE=MyISAM CHARSET utf8;

CREATE TABLE `sphinx_hash` (
  `ID` int(10) NOT NULL,
  `GroupName` varchar(255) DEFAULT NULL,
  `ArtistName` varchar(2048) DEFAULT NULL,
  `TagList` varchar(728) DEFAULT NULL,
  `Year` int(4) DEFAULT NULL,
  `CatalogueNumber` varchar(50) DEFAULT NULL,
  `RecordLabel` varchar(50) DEFAULT NULL,
  `CategoryID` tinyint(2) DEFAULT NULL,
  `Time` int(12) DEFAULT NULL,
  `ReleaseType` tinyint(2) DEFAULT NULL,
  `Size` bigint(20) DEFAULT NULL,
  `Snatched` int(10) DEFAULT NULL,
  `Seeders` int(10) DEFAULT NULL,
  `Leechers` int(10) DEFAULT NULL,
  `LogScore` int(3) DEFAULT NULL,
  `Scene` tinyint(1) NOT NULL DEFAULT '0',
  `VanityHouse` tinyint(1) NOT NULL DEFAULT '0',
  `HasLog` tinyint(1) DEFAULT NULL,
  `HasCue` tinyint(1) DEFAULT NULL,
  `FreeTorrent` tinyint(1) DEFAULT NULL,
  `Media` varchar(255) DEFAULT NULL,
  `Format` varchar(255) DEFAULT NULL,
  `Encoding` varchar(255) DEFAULT NULL,
  `RemasterYear` int(4) DEFAULT NULL,
  `RemasterTitle` varchar(512) DEFAULT NULL,
  `RemasterRecordLabel` varchar(50) DEFAULT NULL,
  `RemasterCatalogueNumber` varchar(50) DEFAULT NULL,
  `FileList` mediumtext,
  PRIMARY KEY (`ID`)
) ENGINE=MyISAM CHARSET utf8;

CREATE TABLE `sphinx_index_last_pos` (
  `Type` varchar(16) NOT NULL DEFAULT '',
  `ID` int(11) DEFAULT NULL,
  PRIMARY KEY (`Type`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `sphinx_requests` (
  `ID` int(10) unsigned NOT NULL,
  `UserID` int(10) unsigned NOT NULL DEFAULT '0',
  `TimeAdded` int(12) unsigned NOT NULL,
  `LastVote` int(12) unsigned NOT NULL,
  `CategoryID` int(3) NOT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `Year` int(4) DEFAULT NULL,
  `ArtistList` varchar(2048) DEFAULT NULL,
  `ReleaseType` tinyint(2) DEFAULT NULL,
  `CatalogueNumber` varchar(50) NOT NULL,
  `BitrateList` varchar(255) DEFAULT NULL,
  `FormatList` varchar(255) DEFAULT NULL,
  `MediaList` varchar(255) DEFAULT NULL,
  `LogCue` varchar(20) DEFAULT NULL,
  `FillerID` int(10) unsigned NOT NULL DEFAULT '0',
  `TorrentID` int(10) unsigned NOT NULL DEFAULT '0',
  `TimeFilled` int(12) unsigned NOT NULL,
  `Visible` binary(1) NOT NULL DEFAULT '1',
  `Bounty` bigint(20) unsigned NOT NULL DEFAULT '0',
  `Votes` int(10) unsigned NOT NULL DEFAULT '0',
  `RecordLabel` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `Userid` (`UserID`),
  KEY `Name` (`Title`),
  KEY `Filled` (`TorrentID`),
  KEY `FillerID` (`FillerID`),
  KEY `TimeAdded` (`TimeAdded`),
  KEY `Year` (`Year`),
  KEY `TimeFilled` (`TimeFilled`),
  KEY `LastVote` (`LastVote`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `sphinx_requests_delta` (
  `ID` int(10) unsigned NOT NULL,
  `UserID` int(10) unsigned NOT NULL DEFAULT '0',
  `TimeAdded` int(12) unsigned DEFAULT NULL,
  `LastVote` int(12) unsigned DEFAULT NULL,
  `CategoryID` tinyint(4) DEFAULT NULL,
  `Title` varchar(255) DEFAULT NULL,
  `TagList` varchar(728) NOT NULL DEFAULT '',
  `Year` int(4) DEFAULT NULL,
  `ArtistList` varchar(2048) DEFAULT NULL,
  `ReleaseType` tinyint(2) DEFAULT NULL,
  `CatalogueNumber` varchar(50) DEFAULT NULL,
  `BitrateList` varchar(255) DEFAULT NULL,
  `FormatList` varchar(255) DEFAULT NULL,
  `MediaList` varchar(255) DEFAULT NULL,
  `LogCue` varchar(20) DEFAULT NULL,
  `FillerID` int(10) unsigned NOT NULL DEFAULT '0',
  `TorrentID` int(10) unsigned NOT NULL DEFAULT '0',
  `TimeFilled` int(12) unsigned DEFAULT NULL,
  `Visible` binary(1) NOT NULL DEFAULT '1',
  `Bounty` bigint(20) unsigned NOT NULL DEFAULT '0',
  `Votes` int(10) unsigned NOT NULL DEFAULT '0',
  `RecordLabel` varchar(80) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `Userid` (`UserID`),
  KEY `Name` (`Title`),
  KEY `Filled` (`TorrentID`),
  KEY `FillerID` (`FillerID`),
  KEY `TimeAdded` (`TimeAdded`),
  KEY `Year` (`Year`),
  KEY `TimeFilled` (`TimeFilled`),
  KEY `LastVote` (`LastVote`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `sphinx_t` (
  `id` int(11) NOT NULL,
  `gid` int(11) NOT NULL,
  `uid` int(11) NOT NULL,
  `size` bigint(20) NOT NULL,
  `snatched` int(11) NOT NULL,
  `seeders` int(11) NOT NULL,
  `leechers` int(11) NOT NULL,
  `time` int(11) NOT NULL,
  `logscore` smallint(6) NOT NULL,
  `scene` tinyint(4) NOT NULL,
  `haslog` tinyint(4) NOT NULL,
  `hascue` tinyint(4) NOT NULL,
  `freetorrent` tinyint(4) NOT NULL,
  `media` varchar(15) NOT NULL,
  `format` varchar(15) NOT NULL,
  `encoding` varchar(30) NOT NULL,
  `remyear` smallint(6) NOT NULL,
  `remtitle` varchar(80) NOT NULL,
  `remrlabel` varchar(80) NOT NULL,
  `remcnumber` varchar(80) NOT NULL,
  `filelist` mediumtext,
  `remident` int(10) unsigned NOT NULL,
  `description` text,
  PRIMARY KEY (`id`),
  KEY `gid_remident` (`gid`,`remident`),
  KEY `format` (`format`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `sphinx_tg` (
  `id` int(11) NOT NULL,
  `name` varchar(300) DEFAULT NULL,
  `tags` varchar(500) DEFAULT NULL,
  `year` smallint(6) DEFAULT NULL,
  `rlabel` varchar(80) DEFAULT NULL,
  `cnumber` varchar(80) DEFAULT NULL,
  `catid` smallint(6) DEFAULT NULL,
  `reltype` smallint(6) DEFAULT NULL,
  `vanityhouse` tinyint(4) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `staff_answers` (
  `QuestionID` int(10) NOT NULL,
  `UserID` int(10) NOT NULL,
  `Answer` mediumtext,
  `Date` datetime NOT NULL,
  PRIMARY KEY (`QuestionID`,`UserID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `staff_blog` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `UserID` int(10) unsigned NOT NULL,
  `Title` varchar(255) NOT NULL,
  `Body` text NOT NULL,
  `Time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`ID`),
  KEY `UserID` (`UserID`),
  KEY `Time` (`Time`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `staff_blog_visits` (
  `UserID` int(10) unsigned NOT NULL,
  `Time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  UNIQUE KEY `UserID` (`UserID`),
  CONSTRAINT `staff_blog_visits_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users_main` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `staff_ignored_questions` (
  `QuestionID` int(10) NOT NULL,
  `UserID` int(10) NOT NULL,
  PRIMARY KEY (`QuestionID`,`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `staff_pm_conversations` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Subject` text,
  `UserID` int(11) DEFAULT NULL,
  `Status` enum('Open','Unanswered','Resolved') DEFAULT NULL,
  `Level` int(11) DEFAULT NULL,
  `AssignedToUser` int(11) DEFAULT NULL,
  `Date` datetime DEFAULT NULL,
  `Unread` tinyint(1) DEFAULT NULL,
  `ResolverID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `StatusAssigned` (`Status`,`AssignedToUser`),
  KEY `StatusLevel` (`Status`,`Level`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `staff_pm_messages` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `UserID` int(11) DEFAULT NULL,
  `SentDate` datetime DEFAULT NULL,
  `Message` text,
  `ConvID` int(11) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `staff_pm_responses` (
  `ID` int(11) NOT NULL AUTO_INCREMENT,
  `Message` text,
  `Name` text,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `styles_backup` (
  `UserID` int(10) NOT NULL DEFAULT '0',
  `StyleID` int(10) DEFAULT NULL,
  `StyleURL` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`UserID`),
  KEY `StyleURL` (`StyleURL`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `stylesheets` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(255) NOT NULL,
  `Description` varchar(255) NOT NULL,
  `Default` enum('0','1') NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `tag_aliases` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `BadTag` varchar(30) DEFAULT NULL,
  `AliasTag` varchar(30) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `BadTag` (`BadTag`),
  KEY `AliasTag` (`AliasTag`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `tags` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) DEFAULT NULL,
  `TagType` enum('genre','other') NOT NULL DEFAULT 'other',
  `Uses` int(12) NOT NULL DEFAULT '1',
  `UserID` int(10) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Name_2` (`Name`),
  KEY `TagType` (`TagType`),
  KEY `Uses` (`Uses`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `top10_history` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Type` enum('Daily','Weekly') DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `top10_history_torrents` (
  `HistoryID` int(10) NOT NULL DEFAULT '0',
  `Rank` tinyint(2) NOT NULL DEFAULT '0',
  `TorrentID` int(10) NOT NULL DEFAULT '0',
  `TitleString` varchar(150) NOT NULL DEFAULT '',
  `TagString` varchar(100) NOT NULL DEFAULT ''
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `top_snatchers` (
  `UserID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `torrents` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `GroupID` int(10) NOT NULL,
  `UserID` int(10) DEFAULT NULL,
  `Media` varchar(20) DEFAULT NULL,
  `Format` varchar(10) DEFAULT NULL,
  `Encoding` varchar(15) DEFAULT NULL,
  `Remastered` enum('0','1') NOT NULL DEFAULT '0',
  `RemasterYear` int(4) DEFAULT NULL,
  `RemasterTitle` varchar(80) NOT NULL DEFAULT '',
  `RemasterCatalogueNumber` varchar(80) NOT NULL DEFAULT '',
  `RemasterRecordLabel` varchar(80) NOT NULL DEFAULT '',
  `Scene` enum('0','1') NOT NULL DEFAULT '0',
  `HasLog` enum('0','1') NOT NULL DEFAULT '0',
  `HasCue` enum('0','1') NOT NULL DEFAULT '0',
  `LogScore` int(6) NOT NULL DEFAULT '0',
  `info_hash` blob NOT NULL,
  `FileCount` int(6) NOT NULL,
  `FileList` mediumtext NOT NULL,
  `FilePath` varchar(255) NOT NULL DEFAULT '',
  `Size` bigint(12) NOT NULL,
  `Leechers` int(6) NOT NULL DEFAULT '0',
  `Seeders` int(6) NOT NULL DEFAULT '0',
  `last_action` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `FreeTorrent` enum('0','1','2') NOT NULL DEFAULT '0',
  `FreeLeechType` enum('0','1','2','3','4','5','6','7') NOT NULL DEFAULT '0',
  `Time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Description` text,
  `Snatched` int(10) unsigned NOT NULL DEFAULT '0',
  `balance` bigint(20) NOT NULL DEFAULT '0',
  `LastReseedRequest` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `TranscodedFrom` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `InfoHash` (`info_hash`(40)),
  KEY `GroupID` (`GroupID`),
  KEY `UserID` (`UserID`),
  KEY `Media` (`Media`),
  KEY `Format` (`Format`),
  KEY `Encoding` (`Encoding`),
  KEY `Year` (`RemasterYear`),
  KEY `FileCount` (`FileCount`),
  KEY `Size` (`Size`),
  KEY `Seeders` (`Seeders`),
  KEY `Leechers` (`Leechers`),
  KEY `Snatched` (`Snatched`),
  KEY `last_action` (`last_action`),
  KEY `Time` (`Time`),
  KEY `FreeTorrent` (`FreeTorrent`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `torrents_artists` (
  `GroupID` int(10) NOT NULL,
  `ArtistID` int(10) NOT NULL,
  `AliasID` int(10) NOT NULL,
  `UserID` int(10) unsigned NOT NULL DEFAULT '0',
  `Importance` enum('1','2','3','4','5','6','7') NOT NULL DEFAULT '1',
  PRIMARY KEY (`GroupID`,`ArtistID`,`Importance`),
  KEY `ArtistID` (`ArtistID`),
  KEY `AliasID` (`AliasID`),
  KEY `Importance` (`Importance`),
  KEY `GroupID` (`GroupID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `torrents_bad_files` (
  `TorrentID` int(11) NOT NULL DEFAULT '0',
  `UserID` int(11) NOT NULL DEFAULT '0',
  `TimeAdded` datetime NOT NULL DEFAULT '0000-00-00 00:00:00'
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `torrents_bad_folders` (
  `TorrentID` int(11) NOT NULL,
  `UserID` int(11) NOT NULL,
  `TimeAdded` datetime NOT NULL,
  PRIMARY KEY (`TorrentID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `torrents_bad_tags` (
  `TorrentID` int(10) NOT NULL DEFAULT '0',
  `UserID` int(10) NOT NULL DEFAULT '0',
  `TimeAdded` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  KEY `TimeAdded` (`TimeAdded`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `torrents_balance_history` (
  `TorrentID` int(10) NOT NULL,
  `GroupID` int(10) NOT NULL,
  `balance` bigint(20) NOT NULL,
  `Time` datetime NOT NULL,
  `Last` enum('0','1','2') DEFAULT '0',
  UNIQUE KEY `TorrentID_2` (`TorrentID`,`Time`),
  UNIQUE KEY `TorrentID_3` (`TorrentID`,`balance`),
  KEY `Time` (`Time`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `torrents_cassette_approved` (
  `TorrentID` int(10) NOT NULL DEFAULT '0',
  `UserID` int(10) NOT NULL DEFAULT '0',
  `TimeAdded` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  KEY `TimeAdded` (`TimeAdded`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `torrents_files` (
  `TorrentID` int(10) NOT NULL,
  `File` mediumblob NOT NULL,
  PRIMARY KEY (`TorrentID`)
) ENGINE=MyISAM CHARSET utf8;

CREATE TABLE `torrents_group` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `ArtistID` int(10) DEFAULT NULL,
  `CategoryID` int(3) DEFAULT NULL,
  `Name` varchar(300) DEFAULT NULL,
  `Year` int(4) DEFAULT NULL,
  `CatalogueNumber` varchar(80) NOT NULL DEFAULT '',
  `RecordLabel` varchar(80) NOT NULL DEFAULT '',
  `ReleaseType` tinyint(2) DEFAULT '21',
  `TagList` varchar(500) NOT NULL,
  `Time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `RevisionID` int(12) DEFAULT NULL,
  `WikiBody` text NOT NULL,
  `WikiImage` varchar(255) NOT NULL,
  `VanityHouse` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`ID`),
  KEY `ArtistID` (`ArtistID`),
  KEY `CategoryID` (`CategoryID`),
  KEY `Name` (`Name`(255)),
  KEY `Year` (`Year`),
  KEY `Time` (`Time`),
  KEY `RevisionID` (`RevisionID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `torrents_logs_new` (
  `LogID` int(10) NOT NULL AUTO_INCREMENT,
  `TorrentID` int(10) NOT NULL DEFAULT '0',
  `Log` mediumtext NOT NULL,
  `Details` mediumtext NOT NULL,
  `Score` int(3) NOT NULL,
  `Revision` int(3) NOT NULL,
  `Adjusted` enum('1','0') NOT NULL DEFAULT '0',
  `AdjustedBy` int(10) NOT NULL DEFAULT '0',
  `NotEnglish` enum('1','0') NOT NULL DEFAULT '0',
  `AdjustmentReason` text,
  PRIMARY KEY (`LogID`),
  KEY `TorrentID` (`TorrentID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `torrents_lossymaster_approved` (
  `TorrentID` int(10) NOT NULL DEFAULT '0',
  `UserID` int(10) NOT NULL DEFAULT '0',
  `TimeAdded` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  KEY `TimeAdded` (`TimeAdded`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `torrents_lossyweb_approved` (
  `TorrentID` int(10) NOT NULL DEFAULT '0',
  `UserID` int(10) NOT NULL DEFAULT '0',
  `TimeAdded` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  KEY `TimeAdded` (`TimeAdded`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `torrents_peerlists` (
  `TorrentID` int(11) NOT NULL,
  `GroupID` int(11) DEFAULT NULL,
  `Seeders` int(11) DEFAULT NULL,
  `Leechers` int(11) DEFAULT NULL,
  `Snatches` int(11) DEFAULT NULL,
  PRIMARY KEY (`TorrentID`),
  KEY `GroupID` (`GroupID`),
  KEY `Stats` (`TorrentID`,`Seeders`,`Leechers`,`Snatches`)
) ENGINE=MyISAM CHARSET utf8;

CREATE TABLE `torrents_peerlists_compare` (
  `TorrentID` int(11) NOT NULL,
  `GroupID` int(11) DEFAULT NULL,
  `Seeders` int(11) DEFAULT NULL,
  `Leechers` int(11) DEFAULT NULL,
  `Snatches` int(11) DEFAULT NULL,
  PRIMARY KEY (`TorrentID`),
  KEY `GroupID` (`GroupID`),
  KEY `Stats` (`TorrentID`,`Seeders`,`Leechers`,`Snatches`)
) ENGINE=MyISAM CHARSET utf8;

CREATE TABLE `torrents_recommended` (
  `GroupID` int(10) NOT NULL,
  `UserID` int(10) NOT NULL,
  `Time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`GroupID`),
  KEY `Time` (`Time`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `torrents_tags` (
  `TagID` int(10) NOT NULL DEFAULT '0',
  `GroupID` int(10) NOT NULL DEFAULT '0',
  `PositiveVotes` int(6) NOT NULL DEFAULT '1',
  `NegativeVotes` int(6) NOT NULL DEFAULT '1',
  `UserID` int(10) DEFAULT NULL,
  PRIMARY KEY (`TagID`,`GroupID`),
  KEY `TagID` (`TagID`),
  KEY `GroupID` (`GroupID`),
  KEY `PositiveVotes` (`PositiveVotes`),
  KEY `NegativeVotes` (`NegativeVotes`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `torrents_tags_votes` (
  `GroupID` int(10) NOT NULL,
  `TagID` int(10) NOT NULL,
  `UserID` int(10) NOT NULL,
  `Way` enum('up','down') NOT NULL DEFAULT 'up',
  PRIMARY KEY (`GroupID`,`TagID`,`UserID`,`Way`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `torrents_votes` (
  `GroupID` int(10) NOT NULL,
  `Ups` int(10) unsigned NOT NULL DEFAULT '0',
  `Total` int(10) unsigned NOT NULL DEFAULT '0',
  `Score` float NOT NULL DEFAULT '0',
  PRIMARY KEY (`GroupID`),
  KEY `Score` (`Score`),
  CONSTRAINT `torrents_votes_ibfk_1` FOREIGN KEY (`GroupID`) REFERENCES `torrents_group` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `user_questions` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Question` mediumtext NOT NULL,
  `UserID` int(10) NOT NULL,
  `Date` datetime NOT NULL,
  PRIMARY KEY (`ID`),
  KEY `Date` (`Date`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_collage_subs` (
  `UserID` int(10) NOT NULL,
  `CollageID` int(10) NOT NULL,
  `LastVisit` datetime DEFAULT NULL,
  PRIMARY KEY (`UserID`,`CollageID`),
  KEY `CollageID` (`CollageID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_comments_last_read` (
  `UserID` int(10) NOT NULL,
  `Page` enum('artist','collages','requests','torrents') NOT NULL,
  `PageID` int(10) NOT NULL,
  `PostID` int(10) NOT NULL,
  PRIMARY KEY (`UserID`,`Page`,`PageID`),
  KEY `Page` (`Page`,`PageID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_donor_ranks` (
  `UserID` int(10) NOT NULL DEFAULT '0',
  `Rank` tinyint(2) NOT NULL DEFAULT '0',
  `DonationTime` datetime DEFAULT NULL,
  `Hidden` tinyint(2) NOT NULL DEFAULT '0',
  `TotalRank` int(10) NOT NULL DEFAULT '0',
  `SpecialRank` tinyint(2) DEFAULT '0',
  `InvitesRecievedRank` tinyint(4) DEFAULT '0',
  `RankExpirationTime` datetime DEFAULT NULL,
  PRIMARY KEY (`UserID`),
  KEY `DonationTime` (`DonationTime`),
  KEY `SpecialRank` (`SpecialRank`),
  KEY `Rank` (`Rank`),
  KEY `TotalRank` (`TotalRank`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_downloads` (
  `UserID` int(10) NOT NULL,
  `TorrentID` int(1) NOT NULL,
  `Time` datetime NOT NULL,
  PRIMARY KEY (`UserID`,`TorrentID`,`Time`),
  KEY `TorrentID` (`TorrentID`),
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_dupes` (
  `GroupID` int(10) unsigned NOT NULL,
  `UserID` int(10) unsigned NOT NULL,
  UNIQUE KEY `UserID` (`UserID`),
  KEY `GroupID` (`GroupID`),
  CONSTRAINT `users_dupes_ibfk_1` FOREIGN KEY (`UserID`) REFERENCES `users_main` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `users_dupes_ibfk_2` FOREIGN KEY (`GroupID`) REFERENCES `dupe_groups` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_enable_recommendations` (
  `ID` int(10) NOT NULL,
  `Enable` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`ID`),
  KEY `Enable` (`Enable`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_freeleeches` (
  `UserID` int(10) NOT NULL,
  `TorrentID` int(10) NOT NULL,
  `Time` datetime NOT NULL,
  `Expired` tinyint(1) NOT NULL DEFAULT '0',
  `Downloaded` bigint(20) NOT NULL DEFAULT '0',
  `Uses` int(10) NOT NULL DEFAULT '1',
  PRIMARY KEY (`UserID`,`TorrentID`),
  KEY `Time` (`Time`),
  KEY `Expired_Time` (`Expired`,`Time`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_geodistribution` (
  `Code` varchar(2) NOT NULL,
  `Users` int(10) NOT NULL
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_history_emails` (
  `UserID` int(10) NOT NULL,
  `Email` varchar(255) DEFAULT NULL,
  `Time` datetime DEFAULT NULL,
  `IP` varchar(15) DEFAULT NULL,
  KEY `UserID` (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_history_ips` (
  `UserID` int(10) NOT NULL,
  `IP` varchar(15) NOT NULL DEFAULT '0.0.0.0',
  `StartTime` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `EndTime` datetime DEFAULT NULL,
  PRIMARY KEY (`UserID`,`IP`,`StartTime`),
  KEY `UserID` (`UserID`),
  KEY `IP` (`IP`),
  KEY `StartTime` (`StartTime`),
  KEY `EndTime` (`EndTime`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_history_passkeys` (
  `UserID` int(10) NOT NULL,
  `OldPassKey` varchar(32) DEFAULT NULL,
  `NewPassKey` varchar(32) DEFAULT NULL,
  `ChangeTime` datetime DEFAULT NULL,
  `ChangerIP` varchar(15) DEFAULT NULL
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_history_passwords` (
  `UserID` int(10) NOT NULL,
  `ChangeTime` datetime DEFAULT NULL,
  `ChangerIP` varchar(15) DEFAULT NULL,
  KEY `User_Time` (`UserID`,`ChangeTime`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_info` (
  `UserID` int(10) unsigned NOT NULL,
  `StyleID` int(10) unsigned NOT NULL,
  `StyleURL` varchar(255) DEFAULT NULL,
  `Info` text NOT NULL,
  `Avatar` varchar(255) NOT NULL,
  `AdminComment` text NOT NULL,
  `SiteOptions` text NOT NULL,
  `ViewAvatars` enum('0','1') NOT NULL DEFAULT '1',
  `Donor` enum('0','1') NOT NULL DEFAULT '0',
  `Artist` enum('0','1') NOT NULL DEFAULT '0',
  `DownloadAlt` enum('0','1') NOT NULL DEFAULT '0',
  `Warned` datetime NOT NULL,
  `SupportFor` varchar(255) NOT NULL,
  `TorrentGrouping` enum('0','1','2') NOT NULL COMMENT '0=Open,1=Closed,2=Off',
  `ShowTags` enum('0','1') NOT NULL DEFAULT '1',
  `NotifyOnQuote` enum('0','1','2') NOT NULL DEFAULT '0',
  `AuthKey` varchar(32) NOT NULL,
  `ResetKey` varchar(32) NOT NULL,
  `ResetExpires` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `JoinDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Inviter` int(10) DEFAULT NULL,
  `BitcoinAddress` varchar(34) DEFAULT NULL,
  `WarnedTimes` int(2) NOT NULL DEFAULT '0',
  `DisableAvatar` enum('0','1') NOT NULL DEFAULT '0',
  `DisableInvites` enum('0','1') NOT NULL DEFAULT '0',
  `DisablePosting` enum('0','1') NOT NULL DEFAULT '0',
  `DisableForums` enum('0','1') NOT NULL DEFAULT '0',
  `DisableIRC` enum('0','1') DEFAULT '0',
  `DisableTagging` enum('0','1') NOT NULL DEFAULT '0',
  `DisableUpload` enum('0','1') NOT NULL DEFAULT '0',
  `DisableWiki` enum('0','1') NOT NULL DEFAULT '0',
  `DisablePM` enum('0','1') NOT NULL DEFAULT '0',
  `RatioWatchEnds` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `RatioWatchDownload` bigint(20) unsigned NOT NULL DEFAULT '0',
  `RatioWatchTimes` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `BanDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `BanReason` enum('0','1','2','3','4') NOT NULL DEFAULT '0',
  `CatchupTime` datetime DEFAULT NULL,
  `LastReadNews` int(10) NOT NULL DEFAULT '0',
  `HideCountryChanges` enum('0','1') NOT NULL DEFAULT '0',
  `RestrictedForums` varchar(150) NOT NULL DEFAULT '',
  `DisableRequests` enum('0','1') NOT NULL DEFAULT '0',
  `PermittedForums` varchar(150) NOT NULL DEFAULT '',
  `UnseededAlerts` enum('0','1') NOT NULL DEFAULT '0',
  `LastReadBlog` int(10) NOT NULL DEFAULT '0',
  `InfoTitle` varchar(255) NOT NULL,
  UNIQUE KEY `UserID` (`UserID`),
  KEY `SupportFor` (`SupportFor`),
  KEY `DisableInvites` (`DisableInvites`),
  KEY `Donor` (`Donor`),
  KEY `Warned` (`Warned`),
  KEY `JoinDate` (`JoinDate`),
  KEY `Inviter` (`Inviter`),
  KEY `RatioWatchEnds` (`RatioWatchEnds`),
  KEY `RatioWatchDownload` (`RatioWatchDownload`),
  KEY `BitcoinAddress` (`BitcoinAddress`(4)),
  KEY `AuthKey` (`AuthKey`),
  KEY `ResetKey` (`ResetKey`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_levels` (
  `UserID` int(10) unsigned NOT NULL,
  `PermissionID` int(10) unsigned NOT NULL,
  PRIMARY KEY (`UserID`,`PermissionID`),
  KEY `PermissionID` (`PermissionID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_main` (
  `ID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Username` varchar(20) NOT NULL,
  `Email` varchar(255) NOT NULL,
  `PassHash` varchar(60) NOT NULL,
  `Secret` char(32) NOT NULL,
  `IRCKey` char(32) DEFAULT NULL,
  `LastLogin` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `LastAccess` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `IP` varchar(15) NOT NULL DEFAULT '0.0.0.0',
  `Class` tinyint(2) NOT NULL DEFAULT '5',
  `Uploaded` bigint(20) unsigned NOT NULL DEFAULT '0',
  `Downloaded` bigint(20) unsigned NOT NULL DEFAULT '0',
  `Title` text NOT NULL,
  `Enabled` enum('0','1','2') NOT NULL DEFAULT '0',
  `Paranoia` text,
  `Visible` enum('1','0') NOT NULL DEFAULT '1',
  `Invites` int(10) unsigned NOT NULL DEFAULT '0',
  `PermissionID` int(10) unsigned NOT NULL,
  `CustomPermissions` text,
  `can_leech` tinyint(4) NOT NULL DEFAULT '1',
  `torrent_pass` char(32) NOT NULL,
  `RequiredRatio` double(10,8) NOT NULL DEFAULT '0.00000000',
  `RequiredRatioWork` double(10,8) NOT NULL DEFAULT '0.00000000',
  `ipcc` varchar(2) NOT NULL DEFAULT '',
  `FLTokens` int(10) NOT NULL DEFAULT '0',
  PRIMARY KEY (`ID`),
  UNIQUE KEY `Username` (`Username`),
  KEY `Email` (`Email`),
  KEY `PassHash` (`PassHash`),
  KEY `LastAccess` (`LastAccess`),
  KEY `IP` (`IP`),
  KEY `Class` (`Class`),
  KEY `Uploaded` (`Uploaded`),
  KEY `Downloaded` (`Downloaded`),
  KEY `Enabled` (`Enabled`),
  KEY `Invites` (`Invites`),
  KEY `torrent_pass` (`torrent_pass`),
  KEY `RequiredRatio` (`RequiredRatio`),
  KEY `cc_index` (`ipcc`),
  KEY `PermissionID` (`PermissionID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_notifications_settings` (
  `UserID` int(10) NOT NULL DEFAULT '0',
  `Inbox` tinyint(1) DEFAULT '1',
  `StaffPM` tinyint(1) DEFAULT '1',
  `News` tinyint(1) DEFAULT '1',
  `Blog` tinyint(1) DEFAULT '1',
  `Torrents` tinyint(1) DEFAULT '1',
  `Collages` tinyint(1) DEFAULT '1',
  `Quotes` tinyint(1) DEFAULT '1',
  `Subscriptions` tinyint(1) DEFAULT '1',
  `SiteAlerts` tinyint(1) DEFAULT '1',
  `RequestAlerts` tinyint(1) DEFAULT '1',
  `CollageAlerts` tinyint(1) DEFAULT '1',
  `TorrentAlerts` tinyint(1) DEFAULT '1',
  `ForumAlerts` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_notify_filters` (
  `ID` int(12) NOT NULL AUTO_INCREMENT,
  `UserID` int(10) NOT NULL,
  `Label` varchar(128) NOT NULL DEFAULT '',
  `Artists` mediumtext NOT NULL,
  `RecordLabels` mediumtext NOT NULL,
  `Users` mediumtext NOT NULL,
  `Tags` varchar(500) NOT NULL DEFAULT '',
  `NotTags` varchar(500) NOT NULL DEFAULT '',
  `Categories` varchar(500) NOT NULL DEFAULT '',
  `Formats` varchar(500) NOT NULL DEFAULT '',
  `Encodings` varchar(500) NOT NULL DEFAULT '',
  `Media` varchar(500) NOT NULL DEFAULT '',
  `FromYear` int(4) NOT NULL DEFAULT '0',
  `ToYear` int(4) NOT NULL DEFAULT '0',
  `ExcludeVA` enum('1','0') NOT NULL DEFAULT '0',
  `NewGroupsOnly` enum('1','0') NOT NULL DEFAULT '0',
  `ReleaseTypes` varchar(500) NOT NULL DEFAULT '',
  PRIMARY KEY (`ID`),
  KEY `UserID` (`UserID`),
  KEY `FromYear` (`FromYear`),
  KEY `ToYear` (`ToYear`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_notify_quoted` (
  `UserID` int(10) NOT NULL,
  `QuoterID` int(10) NOT NULL,
  `Page` enum('forums','artist','collages','requests','torrents') NOT NULL,
  `PageID` int(10) NOT NULL,
  `PostID` int(10) NOT NULL,
  `UnRead` tinyint(1) NOT NULL DEFAULT '1',
  `Date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  PRIMARY KEY (`UserID`,`Page`,`PostID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_notify_torrents` (
  `UserID` int(10) NOT NULL,
  `FilterID` int(10) NOT NULL,
  `GroupID` int(10) NOT NULL,
  `TorrentID` int(10) NOT NULL,
  `UnRead` tinyint(4) NOT NULL DEFAULT '1',
  PRIMARY KEY (`UserID`,`TorrentID`),
  KEY `TorrentID` (`TorrentID`),
  KEY `UserID_Unread` (`UserID`,`UnRead`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_points` (
  `UserID` int(10) NOT NULL,
  `GroupID` int(10) NOT NULL,
  `Points` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`UserID`,`GroupID`),
  KEY `UserID` (`UserID`),
  KEY `GroupID` (`GroupID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_points_requests` (
  `UserID` int(10) NOT NULL,
  `RequestID` int(10) NOT NULL,
  `Points` tinyint(1) NOT NULL DEFAULT '1',
  PRIMARY KEY (`RequestID`),
  KEY `UserID` (`UserID`),
  KEY `RequestID` (`RequestID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_push_notifications` (
  `UserID` int(10) NOT NULL,
  `PushService` tinyint(1) NOT NULL DEFAULT '0',
  `PushOptions` text NOT NULL,
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_sessions` (
  `UserID` int(10) NOT NULL,
  `SessionID` char(32) NOT NULL,
  `KeepLogged` enum('0','1') NOT NULL DEFAULT '0',
  `Browser` varchar(40) DEFAULT NULL,
  `OperatingSystem` varchar(13) DEFAULT NULL,
  `IP` varchar(15) NOT NULL,
  `LastUpdate` datetime NOT NULL,
  `Active` tinyint(4) NOT NULL DEFAULT '1',
  `FullUA` text,
  PRIMARY KEY (`UserID`,`SessionID`),
  KEY `UserID` (`UserID`),
  KEY `LastUpdate` (`LastUpdate`),
  KEY `Active` (`Active`),
  KEY `ActiveAgeKeep` (`Active`,`LastUpdate`,`KeepLogged`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_subscriptions` (
  `UserID` int(10) NOT NULL,
  `TopicID` int(10) NOT NULL,
  PRIMARY KEY (`UserID`,`TopicID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_subscriptions_comments` (
  `UserID` int(10) NOT NULL,
  `Page` enum('artist','collages','requests','torrents') NOT NULL,
  `PageID` int(10) NOT NULL,
  PRIMARY KEY (`UserID`,`Page`,`PageID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_torrent_history` (
  `UserID` int(10) unsigned NOT NULL,
  `NumTorrents` int(6) unsigned NOT NULL,
  `Date` int(8) unsigned NOT NULL,
  `Time` int(11) unsigned NOT NULL DEFAULT '0',
  `LastTime` int(11) unsigned NOT NULL DEFAULT '0',
  `Finished` enum('1','0') NOT NULL DEFAULT '1',
  `Weight` bigint(20) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserID`,`NumTorrents`,`Date`),
  KEY `Finished` (`Finished`),
  KEY `Date` (`Date`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_torrent_history_snatch` (
  `UserID` int(10) unsigned NOT NULL,
  `NumSnatches` int(10) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserID`),
  KEY `NumSnatches` (`NumSnatches`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_torrent_history_temp` (
  `UserID` int(10) unsigned NOT NULL,
  `NumTorrents` int(6) unsigned NOT NULL DEFAULT '0',
  `SumTime` bigint(20) unsigned NOT NULL DEFAULT '0',
  `SeedingAvg` int(6) unsigned NOT NULL DEFAULT '0',
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_votes` (
  `UserID` int(10) unsigned NOT NULL,
  `GroupID` int(10) NOT NULL,
  `Type` enum('Up','Down') DEFAULT NULL,
  `Time` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`UserID`,`GroupID`),
  KEY `GroupID` (`GroupID`),
  KEY `Type` (`Type`),
  KEY `Time` (`Time`),
  KEY `Vote` (`Type`,`GroupID`,`UserID`),
  CONSTRAINT `users_votes_ibfk_1` FOREIGN KEY (`GroupID`) REFERENCES `torrents_group` (`ID`) ON DELETE CASCADE,
  CONSTRAINT `users_votes_ibfk_2` FOREIGN KEY (`UserID`) REFERENCES `users_main` (`ID`) ON DELETE CASCADE
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `users_warnings_forums` (
  `UserID` int(10) unsigned NOT NULL,
  `Comment` text NOT NULL,
  PRIMARY KEY (`UserID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `wiki_aliases` (
  `Alias` varchar(50) NOT NULL,
  `UserID` int(10) NOT NULL,
  `ArticleID` int(10) DEFAULT NULL,
  PRIMARY KEY (`Alias`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `wiki_articles` (
  `ID` int(10) NOT NULL AUTO_INCREMENT,
  `Revision` int(10) NOT NULL DEFAULT '1',
  `Title` varchar(100) DEFAULT NULL,
  `Body` mediumtext,
  `MinClassRead` int(4) DEFAULT NULL,
  `MinClassEdit` int(4) DEFAULT NULL,
  `Date` datetime DEFAULT NULL,
  `Author` int(10) DEFAULT NULL,
  PRIMARY KEY (`ID`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `wiki_artists` (
  `RevisionID` int(12) NOT NULL AUTO_INCREMENT,
  `PageID` int(10) NOT NULL DEFAULT '0',
  `Body` text,
  `UserID` int(10) NOT NULL DEFAULT '0',
  `Summary` varchar(100) DEFAULT NULL,
  `Time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`RevisionID`),
  KEY `PageID` (`PageID`),
  KEY `UserID` (`UserID`),
  KEY `Time` (`Time`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `wiki_revisions` (
  `ID` int(10) NOT NULL,
  `Revision` int(10) NOT NULL,
  `Title` varchar(100) DEFAULT NULL,
  `Body` mediumtext,
  `Date` datetime DEFAULT NULL,
  `Author` int(10) DEFAULT NULL,
  KEY `ID_Revision` (`ID`,`Revision`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `wiki_torrents` (
  `RevisionID` int(12) NOT NULL AUTO_INCREMENT,
  `PageID` int(10) NOT NULL DEFAULT '0',
  `Body` text,
  `UserID` int(10) NOT NULL DEFAULT '0',
  `Summary` varchar(100) DEFAULT NULL,
  `Time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Image` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`RevisionID`),
  KEY `PageID` (`PageID`),
  KEY `UserID` (`UserID`),
  KEY `Time` (`Time`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `xbt_client_whitelist` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `peer_id` varchar(20) DEFAULT NULL,
  `vstring` varchar(200) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `peer_id` (`peer_id`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `xbt_files_users` (
  `uid` int(11) NOT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `announced` int(11) NOT NULL DEFAULT '0',
  `completed` tinyint(1) NOT NULL DEFAULT '0',
  `downloaded` bigint(20) NOT NULL DEFAULT '0',
  `remaining` bigint(20) NOT NULL DEFAULT '0',
  `uploaded` bigint(20) NOT NULL DEFAULT '0',
  `upspeed` int(10) unsigned NOT NULL DEFAULT '0',
  `downspeed` int(10) unsigned NOT NULL DEFAULT '0',
  `corrupt` bigint(20) NOT NULL DEFAULT '0',
  `timespent` int(10) unsigned NOT NULL DEFAULT '0',
  `useragent` varchar(51) NOT NULL DEFAULT '',
  `connectable` tinyint(4) NOT NULL DEFAULT '1',
  `peer_id` binary(20) NOT NULL DEFAULT '\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0\0',
  `fid` int(11) NOT NULL,
  `mtime` int(11) NOT NULL DEFAULT '0',
  `ip` varchar(15) NOT NULL DEFAULT '',
  PRIMARY KEY (`peer_id`,`fid`,`uid`),
  KEY `remaining_idx` (`remaining`),
  KEY `fid_idx` (`fid`),
  KEY `mtime_idx` (`mtime`),
  KEY `uid_active` (`uid`,`active`)
) ENGINE=InnoDB CHARSET utf8;

CREATE TABLE `xbt_snatched` (
  `uid` int(11) NOT NULL DEFAULT '0',
  `tstamp` int(11) NOT NULL,
  `fid` int(11) NOT NULL,
  `IP` varchar(15) NOT NULL,
  KEY `fid` (`fid`),
  KEY `tstamp` (`tstamp`),
  KEY `uid_tstamp` (`uid`,`tstamp`)
) ENGINE=InnoDB CHARSET utf8;

CREATE DEFINER=`root`@`localhost` FUNCTION `binomial_ci`(p int, n int) RETURNS float
    DETERMINISTIC
    SQL SECURITY INVOKER
RETURN IF(n = 0,0.0,((p + 1.35336) / n - 1.6452 * SQRT((p * (n-p)) / n + 0.67668) / n) / (1 + 2.7067 / n));

SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO permissions (ID, Level, Name, `Values`, DisplayStaff) VALUES (15, 1000, 'Sysop', 'a:100:{s:10:\"site_leech\";i:1;s:11:\"site_upload\";i:1;s:9:\"site_vote\";i:1;s:20:\"site_submit_requests\";i:1;s:20:\"site_advanced_search\";i:1;s:10:\"site_top10\";i:1;s:19:\"site_advanced_top10\";i:1;s:16:\"site_album_votes\";i:1;s:20:\"site_torrents_notify\";i:1;s:20:\"site_collages_create\";i:1;s:20:\"site_collages_manage\";i:1;s:20:\"site_collages_delete\";i:1;s:23:\"site_collages_subscribe\";i:1;s:22:\"site_collages_personal\";i:1;s:28:\"site_collages_renamepersonal\";i:1;s:19:\"site_make_bookmarks\";i:1;s:14:\"site_edit_wiki\";i:1;s:22:\"site_can_invite_always\";i:1;s:27:\"site_send_unlimited_invites\";i:1;s:22:\"site_moderate_requests\";i:1;s:18:\"site_delete_artist\";i:1;s:20:\"site_moderate_forums\";i:1;s:17:\"site_admin_forums\";i:1;s:23:\"site_forums_double_post\";i:1;s:14:\"site_view_flow\";i:1;s:18:\"site_view_full_log\";i:1;s:28:\"site_view_torrent_snatchlist\";i:1;s:18:\"site_recommend_own\";i:1;s:27:\"site_manage_recommendations\";i:1;s:15:\"site_delete_tag\";i:1;s:23:\"site_disable_ip_history\";i:1;s:14:\"zip_downloader\";i:1;s:10:\"site_debug\";i:1;s:17:\"site_proxy_images\";i:1;s:16:\"site_search_many\";i:1;s:20:\"users_edit_usernames\";i:1;s:16:\"users_edit_ratio\";i:1;s:20:\"users_edit_own_ratio\";i:1;s:17:\"users_edit_titles\";i:1;s:18:\"users_edit_avatars\";i:1;s:18:\"users_edit_invites\";i:1;s:22:\"users_edit_watch_hours\";i:1;s:21:\"users_edit_reset_keys\";i:1;s:19:\"users_edit_profiles\";i:1;s:18:\"users_view_friends\";i:1;s:20:\"users_reset_own_keys\";i:1;s:19:\"users_edit_password\";i:1;s:19:\"users_promote_below\";i:1;s:16:\"users_promote_to\";i:1;s:16:\"users_give_donor\";i:1;s:10:\"users_warn\";i:1;s:19:\"users_disable_users\";i:1;s:19:\"users_disable_posts\";i:1;s:17:\"users_disable_any\";i:1;s:18:\"users_delete_users\";i:1;s:18:\"users_view_invites\";i:1;s:20:\"users_view_seedleech\";i:1;s:19:\"users_view_uploaded\";i:1;s:15:\"users_view_keys\";i:1;s:14:\"users_view_ips\";i:1;s:16:\"users_view_email\";i:1;s:18:\"users_invite_notes\";i:1;s:23:\"users_override_paranoia\";i:1;s:12:\"users_logout\";i:1;s:20:\"users_make_invisible\";i:1;s:9:\"users_mod\";i:1;s:13:\"torrents_edit\";i:1;s:15:\"torrents_delete\";i:1;s:20:\"torrents_delete_fast\";i:1;s:18:\"torrents_freeleech\";i:1;s:20:\"torrents_search_fast\";i:1;s:17:\"torrents_hide_dnu\";i:1;s:19:\"torrents_fix_ghosts\";i:1;s:17:\"admin_manage_news\";i:1;s:17:\"admin_manage_blog\";i:1;s:18:\"admin_manage_polls\";i:1;s:19:\"admin_manage_forums\";i:1;s:16:\"admin_manage_fls\";i:1;s:13:\"admin_reports\";i:1;s:26:\"admin_advanced_user_search\";i:1;s:18:\"admin_create_users\";i:1;s:15:\"admin_donor_log\";i:1;s:19:\"admin_manage_ipbans\";i:1;s:9:\"admin_dnu\";i:1;s:17:\"admin_clear_cache\";i:1;s:15:\"admin_whitelist\";i:1;s:24:\"admin_manage_permissions\";i:1;s:14:\"admin_schedule\";i:1;s:17:\"admin_login_watch\";i:1;s:17:\"admin_manage_wiki\";i:1;s:18:\"admin_update_geoip\";i:1;s:21:\"site_collages_recover\";i:1;s:19:\"torrents_add_artist\";i:1;s:13:\"edit_unknowns\";i:1;s:19:\"forums_polls_create\";i:1;s:21:\"forums_polls_moderate\";i:1;s:12:\"project_team\";i:1;s:25:\"torrents_edit_vanityhouse\";i:1;s:23:\"artist_edit_vanityhouse\";i:1;s:21:\"site_tag_aliases_read\";i:1;}', '1'), (11, 800, 'Moderator', 'a:89:{s:26:\"admin_advanced_user_search\";i:1;s:17:\"admin_clear_cache\";i:1;s:18:\"admin_create_users\";i:1;s:9:\"admin_dnu\";i:1;s:15:\"admin_donor_log\";i:1;s:17:\"admin_login_watch\";i:1;s:17:\"admin_manage_blog\";i:1;s:19:\"admin_manage_ipbans\";i:1;s:17:\"admin_manage_news\";i:1;s:18:\"admin_manage_polls\";i:1;s:17:\"admin_manage_wiki\";i:1;s:13:\"admin_reports\";i:1;s:23:\"artist_edit_vanityhouse\";i:1;s:13:\"edit_unknowns\";i:1;s:19:\"forums_polls_create\";i:1;s:21:\"forums_polls_moderate\";i:1;s:12:\"project_team\";i:1;s:17:\"site_admin_forums\";i:1;s:20:\"site_advanced_search\";i:1;s:19:\"site_advanced_top10\";i:1;s:16:\"site_album_votes\";i:1;s:22:\"site_can_invite_always\";i:1;s:20:\"site_collages_create\";i:1;s:20:\"site_collages_delete\";i:1;s:20:\"site_collages_manage\";i:1;s:22:\"site_collages_personal\";i:1;s:21:\"site_collages_recover\";i:1;s:28:\"site_collages_renamepersonal\";i:1;s:23:\"site_collages_subscribe\";i:1;s:18:\"site_delete_artist\";i:1;s:15:\"site_delete_tag\";i:1;s:23:\"site_disable_ip_history\";i:1;s:14:\"site_edit_wiki\";i:1;s:23:\"site_forums_double_post\";i:1;s:10:\"site_leech\";i:1;s:19:\"site_make_bookmarks\";i:1;s:27:\"site_manage_recommendations\";i:1;s:20:\"site_moderate_forums\";i:1;s:22:\"site_moderate_requests\";i:1;s:17:\"site_proxy_images\";i:1;s:18:\"site_recommend_own\";i:1;s:16:\"site_search_many\";i:1;s:27:\"site_send_unlimited_invites\";i:1;s:20:\"site_submit_requests\";i:1;s:21:\"site_tag_aliases_read\";i:1;s:10:\"site_top10\";i:1;s:20:\"site_torrents_notify\";i:1;s:11:\"site_upload\";i:1;s:14:\"site_view_flow\";i:1;s:18:\"site_view_full_log\";i:1;s:28:\"site_view_torrent_snatchlist\";i:1;s:9:\"site_vote\";i:1;s:19:\"torrents_add_artist\";i:1;s:15:\"torrents_delete\";i:1;s:20:\"torrents_delete_fast\";i:1;s:13:\"torrents_edit\";i:1;s:25:\"torrents_edit_vanityhouse\";i:1;s:19:\"torrents_fix_ghosts\";i:1;s:18:\"torrents_freeleech\";i:1;s:17:\"torrents_hide_dnu\";i:1;s:20:\"torrents_search_fast\";i:1;s:18:\"users_delete_users\";i:1;s:17:\"users_disable_any\";i:1;s:19:\"users_disable_posts\";i:1;s:19:\"users_disable_users\";i:1;s:18:\"users_edit_avatars\";i:1;s:18:\"users_edit_invites\";i:1;s:20:\"users_edit_own_ratio\";i:1;s:19:\"users_edit_password\";i:1;s:19:\"users_edit_profiles\";i:1;s:16:\"users_edit_ratio\";i:1;s:21:\"users_edit_reset_keys\";i:1;s:17:\"users_edit_titles\";i:1;s:16:\"users_give_donor\";i:1;s:12:\"users_logout\";i:1;s:20:\"users_make_invisible\";i:1;s:9:\"users_mod\";i:1;s:23:\"users_override_paranoia\";i:1;s:19:\"users_promote_below\";i:1;s:20:\"users_reset_own_keys\";i:1;s:10:\"users_warn\";i:1;s:16:\"users_view_email\";i:1;s:18:\"users_view_friends\";i:1;s:18:\"users_view_invites\";i:1;s:14:\"users_view_ips\";i:1;s:15:\"users_view_keys\";i:1;s:20:\"users_view_seedleech\";i:1;s:19:\"users_view_uploaded\";i:1;s:14:\"zip_downloader\";i:1;}', '1'), (2, 100, 'User', 'a:7:{s:10:\"site_leech\";i:1;s:11:\"site_upload\";i:1;s:9:\"site_vote\";i:1;s:20:\"site_advanced_search\";i:1;s:10:\"site_top10\";i:1;s:14:\"site_edit_wiki\";i:1;s:19:\"torrents_add_artist\";i:1;}', '0'), (3, 150, 'Member', 'a:10:{s:10:\"site_leech\";i:1;s:11:\"site_upload\";i:1;s:9:\"site_vote\";i:1;s:20:\"site_submit_requests\";i:1;s:20:\"site_advanced_search\";i:1;s:10:\"site_top10\";i:1;s:20:\"site_collages_manage\";i:1;s:19:\"site_make_bookmarks\";i:1;s:14:\"site_edit_wiki\";i:1;s:19:\"torrents_add_artist\";i:1;}', '0'), (4, 200, 'Power User', 'a:14:{s:10:\"site_leech\";i:1;s:11:\"site_upload\";i:1;s:9:\"site_vote\";i:1;s:20:\"site_submit_requests\";i:1;s:20:\"site_advanced_search\";i:1;s:10:\"site_top10\";i:1;s:20:\"site_torrents_notify\";i:1;s:20:\"site_collages_create\";i:1;s:20:\"site_collages_manage\";i:1;s:19:\"site_make_bookmarks\";i:1;s:14:\"site_edit_wiki\";i:1;s:14:\"zip_downloader\";i:1;s:19:\"forums_polls_create\";i:1;s:19:\"torrents_add_artist\";i:1;} ', '0'), (5, 250, 'Elite', 'a:18:{s:10:\"site_leech\";i:1;s:11:\"site_upload\";i:1;s:9:\"site_vote\";i:1;s:20:\"site_submit_requests\";i:1;s:20:\"site_advanced_search\";i:1;s:10:\"site_top10\";i:1;s:20:\"site_torrents_notify\";i:1;s:20:\"site_collages_create\";i:1;s:20:\"site_collages_manage\";i:1;s:19:\"site_advanced_top10\";i:1;s:19:\"site_make_bookmarks\";i:1;s:14:\"site_edit_wiki\";i:1;s:15:\"site_delete_tag\";i:1;s:14:\"zip_downloader\";i:1;s:19:\"forums_polls_create\";i:1;s:13:\"torrents_edit\";i:1;s:19:\"torrents_add_artist\";i:1;s:17:\"admin_clear_cache\";i:1;}', '0'), (20, 202, 'Donor', 'a:9:{s:9:\"site_vote\";i:1;s:20:\"site_submit_requests\";i:1;s:20:\"site_advanced_search\";i:1;s:10:\"site_top10\";i:1;s:20:\"site_torrents_notify\";i:1;s:20:\"site_collages_create\";i:1;s:20:\"site_collages_manage\";i:1;s:14:\"zip_downloader\";i:1;s:19:\"forums_polls_create\";i:1;}', '0'), (19, 201, 'Artist', 'a:9:{s:10:\"site_leech\";s:1:\"1\";s:11:\"site_upload\";s:1:\"1\";s:9:\"site_vote\";s:1:\"1\";s:20:\"site_submit_requests\";s:1:\"1\";s:20:\"site_advanced_search\";s:1:\"1\";s:10:\"site_top10\";s:1:\"1\";s:19:\"site_make_bookmarks\";s:1:\"1\";s:14:\"site_edit_wiki\";s:1:\"1\";s:18:\"site_recommend_own\";s:1:\"1\";}', '0');

INSERT INTO stylesheets (ID, Name, Description, `Default`) VALUES (9, 'Proton', 'Proton by Protiek', '0'), (2, 'Layer cake', 'Grey stylesheet by Emm', '0'), (21, 'postmod', 'Upgrade on anorex', '1');

INSERT INTO wiki_articles (ID, Revision, Title, Body, MinClassRead, MinClassEdit, Date, Author) VALUES (1, 1, 'Wiki', 'Welcome to your new wiki! Hope this works.', 100, 475, NOW(), 1);

INSERT INTO wiki_aliases (Alias, UserID, ArticleID) VALUES ('wiki', 1, 1);

INSERT INTO wiki_revisions (ID, Revision, Title, Body, Date, Author) VALUES (1, 1, 'Wiki', 'Welcome to your new wiki! Hope this works.', NOW(), 1);

INSERT INTO forums (ID, CategoryID, Sort, Name, Description, MinClassRead, MinClassWrite, MinClassCreate, NumTopics, NumPosts, LastPostID, LastPostAuthorID, LastPostTopicID, LastPostTime) VALUES (1, 1, 20, 'Your Site', 'Totally rad forum', 100, 100, 100, 0, 0, 0, 0, 0, '0000-00-00 00:00:00'), (2, 5, 30, 'Chat', 'Expect this to fill up with spam', 100, 100, 100, 0, 0, 0, 0, 0, '0000-00-00 00:00:00'), (3, 10, 40, 'Help!', 'I fell down and I cant get up', 100, 100, 100, 0, 0, 0, 0, 0, '0000-00-00 00:00:00'), (4, 20, 100, 'Trash', 'Every thread ends up here eventually', 100, 500, 500, 0, 0, 0, 0, 0, '0000-00-00 00:00:00');

INSERT INTO tags (ID, Name, TagType, Uses, UserID) VALUES (1, 'rock', 'genre', 0, 1),(2, 'pop', 'genre', 0, 1),(3, 'female.fronted.symphonic.death.metal', 'genre', 0, 1);

INSERT INTO schedule (NextHour, NextDay, NextBiWeekly) VALUES (0,0,0);

INSERT INTO forums_categories (ID, Sort, Name) VALUES (1,1,'Site');

INSERT INTO forums_categories (ID, Sort, Name) VALUES (5,5,'Community');

INSERT INTO forums_categories (ID, Sort, Name) VALUES (10,10,'Help');

INSERT INTO forums_categories (ID, Sort, Name) VALUES (8,8,'Music');

INSERT INTO forums_categories (ID, Sort, Name) VALUES (20,20,'Trash');

