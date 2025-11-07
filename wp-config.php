<?php
/**
 * The base configuration for WordPress
 *
 * The wp-config.php creation script uses this file during the installation.
 * You don't have to use the website, you can copy this file to "wp-config.php"
 * and fill in the values.
 *
 * This file contains the following configurations:
 *
 * * Database settings
 * * Secret keys
 * * Database table prefix
 * * ABSPATH
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/
 *
 * @package WordPress
 */

// ** Database settings - You can get this info from your web host ** //
/** The name of the database for WordPress */
define( 'DB_NAME', 'headless' );

/** Database username */
define( 'DB_USER', 'prasadmasina' );

/** Database password */
define( 'DB_PASSWORD', 'Sita@1968@manu' );

/** Database hostname */
define( 'DB_HOST', 'localhost' );

/** Database charset to use in creating database tables. */
define( 'DB_CHARSET', 'utf8mb4' );

/** The database collate type. Don't change this if in doubt. */
define( 'DB_COLLATE', '' );

/**#@+
 * Authentication unique keys and salts.
 *
 * Change these to different unique phrases! You can generate these using
 * the {@link https://api.wordpress.org/secret-key/1.1/salt/ WordPress.org secret-key service}.
 *
 * You can change these at any point in time to invalidate all existing cookies.
 * This will force all users to have to log in again.
 *
 * @since 2.6.0
 */
define( 'AUTH_KEY',         ':?*Jib(Y_<,W3AzBys ilnW?VY[m<t_&B*y)2|C3Vjhs#_MeW1N9jJWfs}ek}s%=' );
define( 'SECURE_AUTH_KEY',  '8K[+s4qVO]JpR$3R-v5rlaNrnQ`^f}v;*@In#!PoW+uH`b:<k&l-EFRA6nO,6g5j' );
define( 'LOGGED_IN_KEY',    'jc4g`mX`o#+(W4qn;,tlgo_?laO|8)(rK_d=v9Kt(K027Z(&Zj]#rY{ihI6TH=*!' );
define( 'NONCE_KEY',        '5`+,Y4;/*G2H[8vxu53y+H^n0S;TkkLOAbrobr%>.(cP,F%1:.@[);::YqG4x)A1' );
define( 'AUTH_SALT',        's0y/%O>7@h}2o)$=e/6mh)zcM*=<LPGUtq<:ZX1Wq|%wB+~x)V`4?m^_`O&^Y|}#' );
define( 'SECURE_AUTH_SALT', 'uuV))I?-. .=L}W!>bR3/:?#nu8Uf*voKo?% 5{s6I1}ap|ak!(t%z%Dg:[qsx.i' );
define( 'LOGGED_IN_SALT',   '>Wirx,U!u1TdLN3S-u*(csZM;yFd4G{%/|DH^bt.KAH.MtA:_p=uy8t+9?zSbpQ`' );
define( 'NONCE_SALT',       'L^?1/]VqWT8=D8ja8ak7ZrmaPlZ^}Lzt~iRqni;#U-XAQ$2{~8WSBm^ WEQQc8N`' );

/**#@-*/

/**
 * WordPress database table prefix.
 *
 * You can have multiple installations in one database if you give each
 * a unique prefix. Only numbers, letters, and underscores please!
 *
 * At the installation time, database tables are created with the specified prefix.
 * Changing this value after WordPress is installed will make your site think
 * it has not been installed.
 *
 * @link https://developer.wordpress.org/advanced-administration/wordpress/wp-config/#table-prefix
 */
$table_prefix = 'wp_';

/**
 * For developers: WordPress debugging mode.
 *
 * Change this to true to enable the display of notices during development.
 * It is strongly recommended that plugin and theme developers use WP_DEBUG
 * in their development environments.
 *
 * For information on other constants that can be used for debugging,
 * visit the documentation.
 *
 * @link https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/
 */
define( 'WP_DEBUG', false );

/* Add any custom values between this line and the "stop editing" line. */



/* That's all, stop editing! Happy publishing. */

/** Absolute path to the WordPress directory. */
if ( ! defined( 'ABSPATH' ) ) {
	define( 'ABSPATH', __DIR__ . '/' );
}

/** Sets up WordPress vars and included files. */
require_once ABSPATH . 'wp-settings.php';
