<?php
/**
 * This is example configuration of SimpleSAMLphp Perun interface and additional features.
 * Copy this file to default config directory and edit the properties.
 *
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

$config = array(

	/*
	 * Database
	 *
	 * This database configuration is optional. If you are not using
	 * core functionality or modules that require a database, you can
	 * skip this configuration.
	 */

	/*
	 * Define if you want to use the database defined in config.php (true)
	 * or to use the database options in this file (false). 
	 */
	'useGlobalConfig' => false,

	/*
	 * Database connection string.
	 * Ensure that you have the required PDO database driver installed
	 * for your connection string.
	 * Examples:
	 * mysql:host=localhost;port=3306;dbname=testdb
	 * mysql:unix_socket=/tmp/mysql.sock;dbname=testdb
     * pgsql:host=localhost;port=5432;dbname=testdb
	 */
	'database.dsn' => 'mysql:host=localhost;dbname=STATS;charset=utf8',

	/*
	 * SQL database credentials
	 */
	'database.username' => 'stats',
	'database.password' => 'stats',

	/*
	 * (Optional) Table prefix
	 */
	'database.prefix' => '',

	/*
	 * True or false if you would like a persistent database connection
	 */
	'database.persistent' => false,

	/*
	 * Define connection options
	 * Example, SSL connection:
	 *   array(
	 *       PDO::ATTR_PERSISTENT => true,
	 *       PDO::MYSQL_ATTR_SSL_KEY    =>'/path/to/client-key.pem',
	 *       PDO::MYSQL_ATTR_SSL_CERT=>'/path/to/client-cert.pem',
	 *       PDO::MYSQL_ATTR_SSL_CA    =>'/path/to/ca-cert.pem',
	 *       PDO::MYSQL_ATTR_SSL_CAPATH => '/path/to/ca',
	 *       
	 *   )
	 * 
	 * More info about the options http://php.net/manual/en/ref.pdo-mysql.php
	 * 
	 * NOTE: in case you want to use 'database.driver_options' option you must 
	 * define 'database.persistent' to false because 'database.persistent' will 
	 * overwrite your options (it's a bug of SimpleSAML_Database module 
	 * https://github.com/simplesamlphp/simplesamlphp/blob/master/lib/SimpleSAML/Database.php#L80)
	 */
	'database.driver_options' => array(),

	/*
	 * Database slave configuration is optional as well. If you are only
	 * running a single database server, leave this blank. If you have
	 * a master/slave configuration, you can define as many slave servers
	 * as you want here. Slaves will be picked at random to be queried from.
	 *
	 * Configuration options in the slave array are exactly the same as the
	 * options for the master (shown above) with the exception of the table
	 * prefix.
	 */
	'database.slaves' => array(
		/*
		array(
			'dsn' => 'mysql:host=myslave;dbname=saml',
			'username' => 'simplesamlphp',
			'password' => 'secret',
			'persistent' => false,
		),
		*/
	),

	/*
	 * Fill the table name for statistics
	 */
	'statisticsTableName' => 'statistics',

	/*
	 * Fill the table name for identityProvidersMap
	 */
	'identityProvidersMapTableName' => 'identityProvidersMap',

	/*
	 * Fill the table name for serviceProviders
	 */
	'serviceProvidersMapTableName' => 'serviceProvidersMap',

);
