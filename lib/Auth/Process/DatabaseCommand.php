<?php

include ("DatabaseConnector.php");

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */
class DatabaseCommand
{
    private $databaseConnector;
    private $conn;
    private $dbDriver;
    private $statisticsTableName;
    private $detailedStatisticsTableName;
    private $identityProvidersMapTableName;
    private $serviceProvidersMapTableName;

    public function __construct()
    {
        $this->databaseConnector = new DatabaseConnector();
        $this->conn = $this->databaseConnector->getConnection();
        assert($this->conn !== null);
        $this->dbDriver = $this->databaseConnector->getDbDriver();
        $this->statisticsTableName = $this->databaseConnector->getStatisticsTableName();
        $this->detailedStatisticsTableName = $this->databaseConnector->getDetailedStatisticsTableName();
        $this->ipStatisticsTableName = $this->databaseConnector->getIpStatisticsTableName();
        $this->identityProvidersMapTableName = $this->databaseConnector->getIdentityProvidersMapTableName();
        $this->serviceProvidersMapTableName = $this->databaseConnector->getServiceProvidersMapTableName();
    }
    private function writeLoginIp($sourceIdp, $service, $user, $ip, $date)
    {
        $params = [
          'ip' => ($this->dbDriver == 'pgsql' ? inet_pton($ip) : $ip),
          'sourceIdp' => $sourceIdp,
          'service' => $service,
          'accessed' => $date,
          'ipVersion' => (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) ? 'ipv4' : 'ipv6')
        ];
        if ($this->dbDriver == 'pgsql') {
          // the word "user" is reserved from PostgreSQL
          $params['userid'] = $user;
        } else {
          $params['user'] = $user;
        }
        $table = $this->ipStatisticsTableName;
        $fields = array_keys($params);
        $placeholders = array_map(function ($field) {
          return ':' . $field;

        }, $fields);

        if(empty($params['ip'])) {
            SimpleSAML_Logger::error("[proxystatistics] Couldn't find ip for storing information to table");
            return false;
        }
        if ($this->dbDriver == 'pgsql') {
            $query = "INSERT INTO " . $table . " (" . implode(', ', $fields) . ")" .
              " VALUES (" . implode(', ', $placeholders) . ")";
        } else {
           
            $query = "INSERT INTO " . $table . " (" . implode(', ', $fields) . ")" .
              " VALUES (" . implode(', ', $placeholders) . ")";
        }

        return $this->conn->write($query, $params);
    }
    private function writeLogin($year, $month, $day, $sourceIdp, $service, $user = null)
    {
        $params = [
            'year' => $year,
            'month' => $month,
            'day' => $day,
            'sourceIdp' => $sourceIdp,
            'service' => $service,
            'count' => 1,
        ];
        $table = $this->statisticsTableName;
        if ($user && $this->databaseConnector->getDetailedDays() > 0) {
            // write also into aggregated statistics
            self::writeLogin($year, $month, $day, $sourceIdp, $service);
            if ($this->dbDriver == 'pgsql') {
                // the word "user" is reserved from PostgreSQL
                $params['userid'] = $user;
            } else {
                $params['user'] = $user;
            }
            $table = $this->detailedStatisticsTableName;
        }
        $fields = array_keys($params);
        $placeholders = array_map(function ($field) {
            return ':' . $field;

        }, $fields);
        if ($this->dbDriver == 'pgsql') {
            // remove count field from ON CONFLICT statement
            $conflictFields = $fields;
            $pos = array_search('count', $conflictFields);
            unset($conflictFields[$pos]);
            $query = "INSERT INTO " . $table . " (" . implode(', ', $fields) . ")" .
                     " VALUES (" . implode(', ', $placeholders) . ")" .
                     " ON CONFLICT (" . implode(', ', $conflictFields) . ")" .
                     " DO UPDATE SET count =  " . $table . ".count + 1";
        } else {
            $query = "INSERT INTO " . $table . " (" . implode(', ', $fields) . ")" .
                     " VALUES (" . implode(', ', $placeholders) . ")" .
                     " ON DUPLICATE KEY UPDATE count = count + 1";
        }

        return $this->conn->write($query, $params);
    }

    public function insertLogin(&$request, &$date)
    {
        if (!in_array($this->databaseConnector->getMode(), ['PROXY', 'IDP', 'SP'])) {
            throw new SimpleSAML_Error_Exception('Unknown mode is set. Mode has to be one of the following: PROXY, IDP, SP.');
        }
        if ($this->databaseConnector->getMode() !== 'IDP') {
            if (!empty($request['saml:sp:IdP'])) {
                $idpEntityID = $request['saml:sp:IdP'];
                $idpMetadata = SimpleSAML_Metadata_MetaDataStorageHandler::getMetadataHandler()->getMetaData($idpEntityID, 'saml20-idp-remote');
            } else {
                $idpEntityID = $request['Source']['entityid'];
                $idpMetadata = $request['Source'];
            }
            $idpName = self::getIdPDisplayName($idpMetadata);
        }
        if ($this->databaseConnector->getMode() !== 'SP') {
            if (!empty($request['saml:RequesterID']) && !empty($this->databaseConnector->getOidcIssuer()) && (strpos($request['Destination']['entityid'], $this->databaseConnector->getOidcIssuer()) !== false)) {
                $spEntityId = str_replace($this->databaseConnector->getOidcIssuer() . "/", "", $request['saml:RequesterID'][0]);
                $spName = null;
            } else {
                $spEntityId = $request['Destination']['entityid'];
                $spName = self::getSPDisplayName($request['Destination']);
            }
        }

        if ($this->databaseConnector->getMode() === 'IDP') {
            $idpName = $this->databaseConnector->getIdpName();
            $idpEntityID = $this->databaseConnector->getIdpEntityId();
        } elseif ($this->databaseConnector->getMode() === 'SP') {
            $spEntityId = $this->databaseConnector->getSpEntityId();
            $spName = $this->databaseConnector->getSpName();
        }

        $year = $date->format('Y');
        $month = $date->format('m');
        $day = $date->format('d');
        $dateTimestamp = $date->format('Y-m-d H:i:s T');
        $ip = $_SERVER['HTTP_X_REAL_IP'];

        if (empty($idpEntityID) || empty($spEntityId)) {
            SimpleSAML_Logger::error(
                "'idpEntityId' or 'spEntityId'" .
                " is empty and login log wasn't inserted into the database."
            );
        } else {
            $idAttribute = $this->databaseConnector->getUserIdAttribute();
            $userId = isset($request['Attributes'][$idAttribute]) ? $request['Attributes'][$idAttribute][0] : null;
            if ($this->writeLogin($year, $month, $day, $idpEntityID, $spEntityId, $userId) === false) {
                SimpleSAML_Logger::error("The login log wasn't inserted into table: " . $this->statisticsTableName . ".");
            }
            if ($this->writeLoginIp($idpEntityID, $spEntityId, $userId, $ip, $dateTimestamp) === false) {
                SimpleSAML_Logger::error("The login log for ip wasn't inserted into table: " . $this->ipStatisticsTableName . ".");
            }
            if (!empty($idpName)) {
                if ($this->dbDriver == 'pgsql') {
                    $query = "INSERT INTO " . $this->identityProvidersMapTableName .
                             " (entityId, name) VALUES (:idp, :name1) ON CONFLICT (entityId) DO UPDATE SET name = :name2";
                } else {
                    $query = "INSERT INTO " . $this->identityProvidersMapTableName .
                             " (entityId, name) VALUES (:idp, :name1) ON DUPLICATE KEY UPDATE name = :name2";
                }
                $this->conn->write(
                    $query,
                    ['idp'=>$idpEntityID, 'name1'=>$idpName, 'name2'=>$idpName]
                );
            }

            if (!empty($spName)) {
                if ($this->dbDriver == 'pgsql') {
                    $query = "INSERT INTO " . $this->serviceProvidersMapTableName .
                             " (identifier, name) VALUES (:sp, :name1) ON CONFLICT (identifier) DO UPDATE SET name = :name2";
                } else {
                    $query = "INSERT INTO " . $this->serviceProvidersMapTableName .
                             " (identifier, name) VALUES (:sp, :name1) ON DUPLICATE KEY UPDATE name = :name2";
                }
                $this->conn->write(
                    $query,
                    ['sp'=>$spEntityId, 'name1'=>$spName, 'name2'=>$spName]
                );
            }
        }

    }

    public function getSpNameBySpIdentifier($identifier)
    {
        return $this->conn->read(
            "SELECT name " .
            "FROM " . $this->serviceProvidersMapTableName . " " .
            "WHERE identifier=:sp",
            ['sp'=>$identifier]
        )->fetchColumn();
    }

    public function getIdPNameByEntityId($idpEntityId)
    {
        return $this->conn->read(
            "SELECT name " .
            "FROM " . $this->identityProvidersMapTableName . " " .
            "WHERE entityId=:idp",
            ['idp'=>$idpEntityId]
        )->fetchColumn();
    }

    public function getLoginCountPerDay($days)
    {
        $query = "SELECT year, month, day, SUM(count) AS count " .
                 "FROM " . $this->statisticsTableName . " " .
                 "WHERE service != '' ";
        $params = [];
        self::addDaysRange($days, $this->dbDriver, $query, $params);
        $query .= "GROUP BY year,month,day " .
                  "ORDER BY year ASC,month ASC,day ASC";

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLoginCountPerDayForService($days, $spIdentifier)
    {
        $query = "SELECT year, month, day, SUM(count) AS count " .
                 "FROM " . $this->statisticsTableName . " " .
                 "WHERE service=:service ";
        $params = ['service' => $spIdentifier];
        self::addDaysRange($days, $this->dbDriver, $query, $params);
        $query .= "GROUP BY year,month,day " .
                  "ORDER BY year ASC,month ASC,day ASC";

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getLoginCountPerDayForIdp($days, $idpIdentifier)
    {
        $query = "SELECT year, month, day, SUM(count) AS count " .
                 "FROM " . $this->statisticsTableName . " " .
                 "WHERE sourceIdP=:sourceIdP ";
        $params = ['sourceIdP'=>$idpIdentifier];
        self::addDaysRange($days, $this->dbDriver, $query, $params);
        $query .= "GROUP BY year,month,day " .
                  "ORDER BY year ASC,month ASC,day ASC";

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getAccessCountPerService($days)
    {
        if ($this->dbDriver == 'pgsql') {
            $query = "SELECT COALESCE(name,service) AS spname, service, SUM(count) AS count " .
                     "FROM " . $this->serviceProvidersMapTableName . " " .
                     "LEFT OUTER JOIN " . $this->statisticsTableName . " ON service = identifier ";
            $querySuffix = "GROUP BY service, name HAVING service != '' " .
                           "ORDER BY count DESC";
        } else {
            $query = "SELECT IFNULL(name,service) AS spName, service, SUM(count) AS count " .
                     "FROM " . $this->serviceProvidersMapTableName . " " .
                     "LEFT OUTER JOIN " . $this->statisticsTableName . " ON service = identifier ";
            $querySuffix = "GROUP BY service HAVING service != '' " .
                           "ORDER BY count DESC";
        }
        $params = [];
        self::addDaysRange($days, $this->dbDriver, $query, $params);
        $query .= $querySuffix;

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_NUM);
    }

    public function getAccessCountForServicePerIdentityProviders($days, $spIdentifier)
    {
        if ($this->dbDriver == 'pgsql') {
            $query = "SELECT COALESCE(name,sourceIdp) AS idpname, SUM(count) AS count " .
                     "FROM " . $this->identityProvidersMapTableName . " " .
                     "LEFT OUTER JOIN " . $this->statisticsTableName . " ON sourceIdp = entityId ";
            $querySuffix = "GROUP BY sourceIdp, service, idpname HAVING sourceIdp != '' AND service=:service " .
                           "ORDER BY count DESC";
        } else {
            $query = "SELECT IFNULL(name,sourceIdp) AS idpname, SUM(count) AS count " .
                     "FROM " . $this->identityProvidersMapTableName . " " .
                     "LEFT OUTER JOIN " . $this->statisticsTableName . " ON sourceIdp = entityId ";
            $querySuffix = "GROUP BY sourceIdp, service HAVING sourceIdp != '' AND service=:service " .
                           "ORDER BY count DESC";
        }
        $params = ['service' => $spIdentifier];
        self::addDaysRange($days, $this->dbDriver, $query, $params);
        $query .= $querySuffix;

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_NUM);
    }

    public function getAccessCountForIdentityProviderPerServiceProviders($days, $idpEntityId)
    {
        if ($this->dbDriver == 'pgsql') {
            $query = "SELECT COALESCE(name,service) AS spname, SUM(count) AS count " .
                     "FROM " . $this->serviceProvidersMapTableName . " " .
                     "LEFT OUTER JOIN " . $this->statisticsTableName . " ON service = identifier ";
            $querySuffix = "GROUP BY sourceIdp, service, name HAVING service != '' AND sourceIdp=:sourceIdp " .
                           "ORDER BY count DESC";
        } else {
            $query = "SELECT IFNULL(name,service) AS spname, SUM(count) AS count " .
                     "FROM " . $this->serviceProvidersMapTableName . " " .
                     "LEFT OUTER JOIN " . $this->statisticsTableName . " ON service = identifier ";
            $querySuffix = "GROUP BY sourceIdp, service HAVING service != '' AND sourceIdp=:sourceIdp " .
                           "ORDER BY count DESC";
        }
        $params = ['sourceIdp'=>$idpEntityId];
        self::addDaysRange($days, $this->dbDriver, $query, $params);
        $query .= $querySuffix;

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_NUM);
    }

    public function getLoginCountPerIdp($days)
    {
        if ($this->dbDriver == 'pgsql') {
            $query = "SELECT COALESCE(name,sourceIdp) AS idpname, sourceidp, SUM(count) AS count " .
                     "FROM " . $this->identityProvidersMapTableName . " " .
                     "LEFT OUTER JOIN " . $this->statisticsTableName . " ON sourceIdp = entityId ";
            $querySuffix = "GROUP BY sourceidp, name HAVING sourceIdp != '' " .
                           "ORDER BY count DESC";
        } else {
            $query = "SELECT IFNULL(name,sourceIdp) AS idpName, sourceIdp, SUM(count) AS count " .
                     "FROM " . $this->identityProvidersMapTableName . " " .
                     "LEFT OUTER JOIN " . $this->statisticsTableName . " ON sourceIdp = entityId ";
            $querySuffix = "GROUP BY sourceIdp HAVING sourceIdp != '' " .
                           "ORDER BY count DESC";
        }
        $params = [];
        self::addDaysRange($days, $this->dbDriver, $query, $params);
        $query .= $querySuffix;

        return $this->conn->read($query, $params)->fetchAll(PDO::FETCH_NUM);
    }

    private static function addDaysRange($days, $dbDriver, &$query, &$params, $not = false)
    {
        if ($days != 0) {    // 0 = all time
            if (stripos($query, "WHERE") === false) {
                $query .= "WHERE";
            } else {
                $query .= "AND";
            }
            if ($dbDriver == 'pgsql') {
                $query .= " CAST(CONCAT(year,'-',LPAD(CAST(month AS varchar),2,'0'),'-',LPAD(CAST(day AS varchar),2,'0')) AS date) ";
                $querySuffix = "> current_date - INTERVAL '1 days' * :days ";
            } else {
                $query .= " CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) ";
                $querySuffix = "BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() ";
            }
            if ($not) {
                $query .= "NOT ";
            }
            $query .= $querySuffix;
            $params['days'] = $days;
        }
    }

    public function deleteOldDetailedStatistics()
    {
        if ($this->databaseConnector->getDetailedDays() > 0) {
            $query = "DELETE FROM " . $this->detailedStatisticsTableName . " ";
            $params = [];
            self::addDaysRange($this->databaseConnector->getDetailedDays(), $this->dbDriver, $query, $params, true);
            return $this->conn->write($query, $params);
        }
    }

    public static function getIdPDisplayName($idpMetadata)
    {
        if (!empty($idpMetadata['UIInfo']['DisplayName'])) {
            $displayName = $idpMetadata['UIInfo']['DisplayName'];
            // Should always be an array of language code -> translation
            assert(is_array($displayName));
            // TODO: Use \SimpleSAML\Locale\Translate::getPreferredTranslation()
            // in SSP 2.0
            if (!empty($displayName['en'])) {
                return $displayName['en'];
            }
        }

        if (!empty($idpMetadata['name'])) {
            // TODO: Use \SimpleSAML\Locale\Translate::getPreferredTranslation()
            // in SSP 2.0
            if (!empty($idpMetadata['name']['en'])) {
                return $idpMetadata['name']['en'];
            } else {
                return $idpMetadata['name'];
            }
        }

        return null;
    }

    public static function getSPDisplayName($spMetadata) 
    {
        if (!empty($spMetadata['name'])) {
            // TODO: Use \SimpleSAML\Locale\Translate::getPreferredTranslation()
            // in SSP 2.0
            if (!empty($spMetadata['name']['en'])) {
                return $spMetadata['name']['en'];
            } else {
                return $spMetadata['name'];
            }
        }

        if (!empty($spMetadata['OrganizationDisplayName'])) {
            // TODO: Use \SimpleSAML\Locale\Translate::getPreferredTranslation()
            // in SSP 2.0
            if (!empty($spMetadata['OrganizationDisplayName']['en'])) {
                return $spMetadata['OrganizationDisplayName']['en'];
            } else {
                return $spMetadata['OrganizationDisplayName'];
            }
        }

        return null;
    }
}
