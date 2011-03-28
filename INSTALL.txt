INSTALLATION NOTES
1. Set up MySQL and memcached. We run memcached with the command:
	memcached -d -m 5120 -s /var/run/memcached.sock -a 0777 -t16 -C -u root
	This gives it 5 gigs of RAM, you probably want to set that a bit lower!
2. Run gazelle.sql (preferably as root) to create the database, the table, and the default data.
3. Install sphinx - we recommend you use the included sphinx.conf
	For documentation, read http://www.sphinxsearch.com/docs/current.html
	
	After you've installed, create the indices:
	/usr/local/bin/indexer -c /etc/sphinx/sphinx.conf --all
	
4. Move classes/config.template to classes/config.php. Edit the config.php as needed. 
	We use http://grc.com/passwords.html for our passwords - you'll be generating a lot of these.
5. Sign up. The first user is made a sysop!
6. Set up cron jobs. You need a cron job for the schedule, a cron job for 
the peerupdate (all groups are cached, but the peer counts change often, 
so peerupdate is a script to update them), and the two sphinx indices. 
These are our cron jobs:

0,15,30,45 *    *       *       *       /usr/local/bin/php /var/www/vhosts/what/schedule.php SCHEDULE_KEY >> /root/schedule.log
10,25,40,55 *  *        *       *       /usr/local/bin/php /var/www/vhosts/what/peerupdate.php SCHEDULE_KEY >> /root/peerupdate.log
*       *       *       *       *       /usr/local/bin/indexer -c /etc/sphinx/sphinx.conf --rotate delta
5       0,12    *       *       *       /usr/local/bin/indexer -c /etc/sphinx/sphinx.conf --rotate --all

7. You're probably going to want geoip information, so first you need to fill in the geoip_country tables by visiting /tools.php?action=update_geoip .
	After that finishes parsing information from maxmind, you'll may want to map users to countries by running:
		"INSERT INTO users_geodistribution (Code, Users) SELECT g.Code, COUNT(u.ID) AS Users FROM geoip_country AS g JOIN users_main AS u ON INET_ATON(u.IP) BETWEEN g.StartIP AND g.EndIP WHERE u.Enabled='1' GROUP BY g.Code ORDER BY Users DESC"
	This will fill in the table needed for stats.

8. Start modifying stuff. Hopefully, everything will have gone smoothly so far and nothing will have exploded (ha ha ha)
