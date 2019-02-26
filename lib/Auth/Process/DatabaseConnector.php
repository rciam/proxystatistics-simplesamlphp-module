<?php
/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

class databaseConnector
{
	private $useGlobalConfig;
	private $statisticsTableName;
	private $identityProvidersMapTableName;
	private $serviceProvidersMapTableName;

	const CONFIG_FILE_NAME = 'module_statisticsproxy.php';
	const USE_GLOBAL_CONFIG = 'useGlobalConfig';
	const STATS_TABLE_NAME = 'statisticsTableName';
	const IDP_MAP_TABLE_NAME = 'identityProvidersMapTableName';
	const SP_MAP_TABLE_NAME = 'serviceProvidersMapTableName';



	public function __construct ()
	{
		$conf = SimpleSAML_Configuration::getConfig(self::CONFIG_FILE_NAME);
		$this->useGlobalConfig = $conf->getBoolean(self::USE_GLOBAL_CONFIG);
		$this->statisticsTableName = $conf->getString(self::STATS_TABLE_NAME);
		$this->identityProvidersMapTableName = $conf->getString(self::IDP_MAP_TABLE_NAME);
		$this->serviceProvidersMapTableName = $conf->getString(self::SP_MAP_TABLE_NAME);
	}

	public function getConnection()
	{
		if ($this->useGlobalConfig) {
			$conn = SimpleSAML\Database::getInstance();
		} else {
			$dbConfig = SimpleSAML_Configuration::getConfig(self::CONFIG_FILE_NAME);
			$conn = SimpleSAML\Database::getInstance($dbConfig);
		}
		return $conn;
	}

	public function getStatisticsTableName()
	{
		return $this->statisticsTableName;
	}

	public function getIdentityProvidersMapTableName()
	{
		return $this->identityProvidersMapTableName;
	}

	public function getServiceProvidersMapTableName()
	{
		return $this->serviceProvidersMapTableName;
	}

}