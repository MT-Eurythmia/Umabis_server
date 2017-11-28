# Umabis Server

## Installation

### Web server

The Umabis server uses the Bullet PHP framework, which requires a quite uncommon
Web server configuration.

Here is an example for NGINX:
```
server {
	listen 80;

	root /home/minetest/umabis_server/public;

	index index.php;

	server_name umabis.langg.net;

	location /api {
		fastcgi_index index.php;
		fastcgi_pass unix:/run/php/php7.0-fpm.sock;
		include fastcgi_params;
		fastcgi_param SCRIPT_FILENAME "${document_root}/index.php";
		fastcgi_param SCRIPT_NAME index.php;
		fastcgi_param QUERY_STRING u=$document_root$fastcgi_script_name&$query_string;
	}
```

**Note that this example uses an HTTP scheme. In production, it is very important
to use HTTPS.**

### PHP website

Copy the `src/config.example.php` to `src/config.php` and edit it. You essentially
need to set the MySQL database configuration parameters accordingly to your setup
as well as the `NAME` parameter. Other settings have sensible default values.

### Setting up the database

There is no automatic setup for the database. However, you executing the SQL file
`database.sql` will do the most for you.

The only thing you actually need to do yourself is to add your server authentication information
(see [configuring Umabis](https://github.com/MT-Eurythmia/Umabis/blob/master/README.md#configuring)),
for example using the following SQL statement (you can also do it graphically in a tool like PHPMyAdmin if you
prefer):

```
INSERT INTO `servers` (`name`, `IP`, `password`, `token`, `expiration_time`) VALUES ('EurythmiaEvolution', '78.126.74.193', 'ba4f5d4f78a2c23fc0ebff112f7f8b1616f0dc5d2713833fd1b458bf6f925301', NULL, NOW());
```

You may set `IP` to `NULL`, although it is a good idea to set one. The `password`
must be hashed using SHA-256.

### Creating the first admin

Once the first user is fully registered, you can make it an admin using:

```
INSERT INTO `global_moderators` (`nick`) VALUES ('playername');
```

(Other admins may then be added using `/umabis` chatcommands)
