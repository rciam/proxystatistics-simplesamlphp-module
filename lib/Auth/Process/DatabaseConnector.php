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
	private $idpEntityIdAttrName;
	private $oidcIss;

	const CONFIG_FILE_NAME = 'module_statisticsproxy.php';
	const USE_GLOBAL_CONFIG = 'useGlobalConfig';
	const STATS_TABLE_NAME = 'statisticsTableName';
	const IDP_MAP_TABLE_NAME = 'identityProvidersMapTableName';
	const SP_MAP_TABLE_NAME = 'serviceProvidersMapTableName';
	const IDP_ENTITY_ID_ATTR_NAME = 'idpEntityIdAttrName';
	const OIDC_ISS = 'oidcIssuer';



	public function __construct ()
	{
		$conf = SimpleSAML_Configuration::getConfig(self::CONFIG_FILE_NAME);
		$this->useGlobalConfig = $conf->getBoolean(self::USE_GLOBAL_CONFIG);
		$this->statisticsTableName = $conf->getString(self::STATS_TABLE_NAME);
		$this->identityProvidersMapTableName = $conf->getString(self::IDP_MAP_TABLE_NAME);
		$this->serviceProvidersMapTableName = $conf->getString(self::SP_MAP_TABLE_NAME);
		$this->idpEntityIdAttrName = $conf->getString(self::IDP_ENTITY_ID_ATTR_NAME);
		$this->oidcIss = $conf->getString(self::OIDC_ISS, null);
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

	public function getIdpEntityIdAttrName()
	{
		return $this->idpEntityIdAttrName;
	}

	public function getDbDriver()
	{
		if ($this->useGlobalConfig) {
			$config = SimpleSAML_Configuration::getInstance();
		} else {
			$config = SimpleSAML_Configuration::getConfig(self::CONFIG_FILE_NAME);
		}
		$dsn = $config->getString('database.dsn');
		preg_match('/.+?(?=:)/', $dsn, $driver);
		return $driver[0];
	}

	public function getOidcIssuer()
	{
		return $this->oidcIss;
	}

}