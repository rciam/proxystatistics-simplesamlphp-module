<?php

namespace SimpleSAML\Module\proxystatistics\Auth\Process;

use SimpleSAML\Configuration;
use SimpleSAML\Database;
use SimpleSAML\Logger;
use PDO;

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

class DatabaseConnector
{
    private $databaseDsn;
    private $statisticsTableName;
    private $detailedStatisticsTableName;
    private $identityProvidersMapTableName;
    private $serviceProvidersMapTableName;
    private $mode;
    private $idpEntityId;
    private $idpName;
    private $spEntityId;
    private $spName;
    private $detailedDays;
    private $userIdAttribute;
    private $conn = null;

    const CONFIG_FILE_NAME = 'module_statisticsproxy.php';
    /** @deprecated */
    const SERVER = 'serverName';
    /** @deprecated */
    const PORT = 'port';
    /** @deprecated */
    const USER = 'userName';
    /** @deprecated */
    const PASSWORD = 'password';
    /** @deprecated */
    const DATABASE = 'databaseName';
    const STATS_TABLE_NAME = 'statisticsTableName';
    const DETAILED_STATS_TABLE_NAME = 'detailedStatisticsTableName';
    const IDP_MAP_TABLE_NAME = 'identityProvidersMapTableName';
    const SP_MAP_TABLE_NAME = 'serviceProvidersMapTableName';
    /** @deprecated */
    const ENCRYPTION = 'encryption';
    const STORE = 'store';
    /** @deprecated */
    const SSL_CA = 'ssl_ca';
    /** @deprecated */
    const SSL_CERT = 'ssl_cert_path';
    /** @deprecated */
    const SSL_KEY = 'ssl_key_path';
    /** @deprecated */
    const SSL_CA_PATH = 'ssl_ca_path';
    const MODE = 'mode';
    const IDP_ENTITY_ID = 'idpEntityId';
    const IDP_NAME = 'idpName';
    const SP_ENTITY_ID = 'spEntityId';
    const SP_NAME = 'spName';
    const DETAILED_DAYS = 'detailedDays';
    const USER_ID_ATTRIBUTE = 'userIdAttribute';

    public function __construct()
    {
        $conf = Configuration::getConfig(self::CONFIG_FILE_NAME);
        $this->storeConfig = $conf->getArray(self::STORE, null);

        // TODO: remove
        if (empty($this->storeConfig) && $conf->getString(self::DATABASE, false)) {
            $this->storeConfig = [
                'database.dsn' => sprintf(
                    'mysql:host=%s;port=%d;dbname=%s;charset=utf8',
                    $conf->getString(self::SERVER, 'localhost'),
                    $conf->getInteger(self::PORT, 3306),
                    $conf->getString(self::DATABASE)
                ),
                'database.username' => $conf->getString(self::USER),
                'database.password' => $conf->getString(self::PASSWORD),
            ];
            if ($conf->getBoolean(self::ENCRYPTION, false)) {
                Logger::debug("Getting connection with encryption.");
                $this->storeConfig['database.driver_options'] = [
                    PDO::MYSQL_ATTR_SSL_KEY => $conf->getString(self::SSL_KEY, ''),
                    PDO::MYSQL_ATTR_SSL_CERT => $conf->getString(self::SSL_CERT, ''),
                    PDO::MYSQL_ATTR_SSL_CA => $conf->getString(self::SSL_CA, ''),
                    PDO::MYSQL_ATTR_SSL_CAPATH => $conf->getString(self::SSL_CA_PATH, ''),
                ];
            }

            Logger::debug("Deprecated option(s) used for proxystatistics. Please use the store option.");
        }

        $this->storeConfig = Configuration::loadFromArray($this->storeConfig);
        $this->databaseDsn = $this->storeConfig->getString('database.dsn');

        $this->statisticsTableName = $conf->getString(self::STATS_TABLE_NAME);
        $this->detailedStatisticsTableName = $conf->getString(self::DETAILED_STATS_TABLE_NAME, 'statistics_detail');
        $this->identityProvidersMapTableName = $conf->getString(self::IDP_MAP_TABLE_NAME);
        $this->serviceProvidersMapTableName = $conf->getString(self::SP_MAP_TABLE_NAME);
        $this->mode = $conf->getString(self::MODE, 'PROXY');
        $this->idpEntityId = $conf->getString(self::IDP_ENTITY_ID, '');
        $this->idpName = $conf->getString(self::IDP_NAME, '');
        $this->spEntityId = $conf->getString(self::SP_ENTITY_ID, '');
        $this->spName = $conf->getString(self::SP_NAME, '');
        $this->detailedDays = $conf->getInteger(self::DETAILED_DAYS, 0);
        $this->userIdAttribute = $conf->getString(self::USER_ID_ATTRIBUTE, 'uid');
    }

    public function getConnection()
    {
        return Database::getInstance($this->storeConfig);
    }

    public function getStatisticsTableName()
    {
        return $this->statisticsTableName;
    }

    public function getDetailedStatisticsTableName()
    {
        return $this->detailedStatisticsTableName;
    }

    public function getIdentityProvidersMapTableName()
    {
        return $this->identityProvidersMapTableName;
    }

    public function getServiceProvidersMapTableName()
    {
        return $this->serviceProvidersMapTableName;
    }

    public function getDbDriver()
	{
		preg_match('/.+?(?=:)/', $this->databaseDsn, $driver);
		return $driver[0];
	}

    public function getMode()
    {
        return $this->mode;
    }

    public function getIdpEntityId()
    {
        return $this->idpEntityId;
    }

    public function getIdpName()
    {
        return $this->idpName;
    }

    public function getSpEntityId()
    {
        return $this->spEntityId;
    }

    public function getSpName()
    {
        return $this->spName;
    }

    public function getDetailedDays()
    {
        return $this->detailedDays;
    }

    public function getUserIdAttribute()
    {
        return $this->userIdAttribute;
    }
}
