<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'umabis');
define('DB_USER', 'umabis');
define('DB_PASS', 'iuR03JPGFaLsayL5');

/* Maximum time of client inactivity in seconds before the session expires.
 * Note that if you set this to a value less than 2 minutes, you'll need to
 * set the client ping frequency accordingly.
 */
define('SESSION_EXPIRATION_DELAY', 3*60);
define('SESSION_TOKEN_LENGTH', 64);
/* Same for the global server inactivity.
 */
define('SERVER_EXPIRATION_DELAY', 3*60);
define('SERVER_TOKEN_LENGTH', 64);

/* The name of the Umabis server, as sent to the client.
 */
define('NAME', 'Eurythmia');
