<?php
include ("DatabaseConnector.php");
/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

class DatabaseCommand
{

    public static function insertLogin(&$request, &$date)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        $dbDriver = $databaseConnector->getDbDriver();
        assert($conn != NULL);
        $statisticsTableName = $conn->applyPrefix($databaseConnector->getStatisticsTableName());
        $identityProvidersMapTableName = $conn->applyPrefix($databaseConnector->getIdentityProvidersMapTableName());
        $serviceProvidersMapTableName = $conn->applyPrefix($databaseConnector->getServiceProvidersMapTableName());
        if (is_null($request['Attributes']['authnAuthority'][0]) || empty($request['Attributes']['authnAuthority'][0])) {
            $idpEntityID = $request['saml:sp:IdP'];
            $idpName = $request['Attributes']['sourceIdPName'][0];
        } else {
            $idpEntityID = $request['Attributes']['authnAuthority'][0];
            $idpName = null;
        }
        $spEntityId = $request['Destination']['entityid'];
        $spName = $request['Destination']['name']['en'];
        $year = $date->format('Y');
        $month = $date->format('m');
        $day = $date->format('d');

        if (is_null($idpEntityID) || empty($idpEntityID) || is_null($spEntityId) || empty($spEntityId)) {
            SimpleSAML_Logger::error("Some from attribute: 'idpEntityId', 'idpName', 'spEntityId' and 'spName' is null or empty and login log wasn't inserted into the database.");
        } else {
            if ($dbDriver == 'pgsql') {
                $query = "INSERT INTO $statisticsTableName (year, month, day, sourceIdp, service, count) VALUES (:year, :month, :day, :idpEntityId, :spEntityId, 1) ON CONFLICT (year, month, day, sourceIdp, service) DO UPDATE SET count =  statistics.count + 1";
            } else {
                $query = "INSERT INTO $statisticsTableName (year, month, day, sourceIdp, service, count) VALUES (:year, :month, :day, :idpEntityId, :spEntityId, 1) ON DUPLICATE KEY UPDATE count = count + 1";
            }
            $queryParams = array(
                'year' => array($year, PDO::PARAM_INT),
                'month' => array($month, PDO::PARAM_INT),
                'day' => array($day, PDO::PARAM_INT),
                'idpEntityId' => array($idpEntityID, PDO::PARAM_STR),
                'spEntityId' => array($spEntityId, PDO::PARAM_STR),
            );
            try {
                $stmt = $conn->write($query, $queryParams);
            } catch (Exception $e) {
                SimpleSAML_Logger::error("The login log wasn't inserted into table: " . $statisticsTableName . ".\nCaught exception: " . $e->getMessage() . "\n");
            }

            if (!is_null($idpName) && !empty($idpName)) {
                if ($dbDriver == 'pgsql') {
                    $query = "INSERT INTO $identityProvidersMapTableName (entityId, name) VALUES (:idpEntityId, :idpName) ON CONFLICT (entityId) DO UPDATE SET name = :idpName";
                } else {
                    $query = "INSERT INTO $identityProvidersMapTableName (entityId, name) VALUES (:idpEntityId, :idpName) ON DUPLICATE KEY UPDATE name = :idpName";
                }
                $queryParams = array(
                    'idpEntityId' => array($idpEntityID, PDO::PARAM_STR),
                    'idpName' => array($idpName, PDO::PARAM_STR),
                    'idpName' => array($idpName, PDO::PARAM_STR),
                );
                try {
                    $stmt = $conn->write($query, $queryParams);
                } catch (Exception $e) {
                    SimpleSAML_Logger::error("The login log wasn't inserted into table: " . $identityProvidersMapTableName . ".\nCaught exception: " . $e->getMessage() . "\n");
                }
            }

            if (!is_null($spName) && !empty($spName)) {
                if ($dbDriver == 'pgsql') {
                    $query = "INSERT INTO $serviceProvidersMapTableName (identifier, name) VALUES (:spEntityId, :spName) ON CONFLICT (identifier) DO UPDATE SET name = :spName";
                } else {
                    $query = "INSERT INTO $serviceProvidersMapTableName (identifier, name) VALUES (:spEntityId, :spName) ON DUPLICATE KEY UPDATE name = :spName";
                }
                $queryParams = array(
                    'spEntityId' => array($spEntityId, PDO::PARAM_STR),
                    'spName' => array($spName, PDO::PARAM_STR),
                    'spName' => array($spName, PDO::PARAM_STR),
                );
                try {
                    $stmt = $conn->write($query, $queryParams);
                } catch (Exception $e) {
                    SimpleSAML_Logger::error("The login log wasn't inserted into table: " . $serviceProvidersMapTableName . ".\nCaught exception: " . $e->getMessage() . "\n");
                }
            }
            SimpleSAML_Logger::info("The login log was successfully stored in database");
        }

    }

    public static function getSpNameBySpIdentifier($identifier) {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != NULL);
        $tableName = $conn->applyPrefix($databaseConnector->getServiceProvidersMapTableName());
        $query = "SELECT name FROM $tableName WHERE identifier=:identifier";
        $queryParams = array(
            'identifier' => array($identifier, PDO::PARAM_STR),
        );
        $stmt = $conn->read($query, $queryParams);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['name'];
    }

    public static function getIdPNameByEntityId($idpEntityId) {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        assert($conn != NULL);
        $tableName = $conn->applyPrefix($databaseConnector->getIdentityProvidersMapTableName());
        $query = "SELECT name FROM $tableName WHERE entityId=:idpEntityId";
        $queryParams = array(
            'idpEntityId' => array($idpEntityId, PDO::PARAM_STR),
        );
        $stmt = $conn->read($query, $queryParams);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['name'];
    }

    public static function getLoginCountPerDay($days)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        $dbDriver = $databaseConnector->getDbDriver();
        assert($conn != NULL);
        $table_name = $conn->applyPrefix($databaseConnector->getStatisticsTableName());
        if($days == 0) {    // 0 = all time
            if ($dbDriver == 'pgsql') {
                $query = "SELECT year, month, day, SUM(count) AS count FROM $table_name WHERE service != '' GROUP BY year, month, day ORDER BY year DESC,month DESC,day DESC";
            } else {
                $query = "SELECT year, month, day, SUM(count) AS count FROM $table_name WHERE service != '' GROUP BY year DESC,month DESC,day DESC";
            }
            $stmt = $conn->read($query);
        } else {
            if ($dbDriver == 'pgsql') {
                $query = "SELECT year, month, day, SUM(count) AS count FROM $table_name WHERE service != '' AND CAST(CONCAT(year,'-',LPAD(CAST(month AS varchar),2,'0'),'-',LPAD(CAST(day AS varchar),2,'0')) AS date) > current_date - INTERVAL '1 days' * :days GROUP BY year, month, day ORDER BY year DESC,month DESC,day DESC";
            } else {
                $query = "SELECT year, month, day, SUM(count) AS count FROM $table_name WHERE service != '' AND CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() GROUP BY year DESC,month DESC,day DESC";
            }
            $queryParams = array(
                'days' => array($days, PDO::PARAM_INT),
            );
            $stmt = $conn->read($query, $queryParams);
        }
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "[new Date(".$row["year"].",". ($row["month"] - 1 ). ", ".$row["day"]."), {v:".$row["count"]."}],";
        }
    }

    public static function getLoginCountPerDayForService($days, $spIdentifier)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        $dbDriver = $databaseConnector->getDbDriver();
        assert($conn != NULL);
        $table_name = $conn->applyPrefix($databaseConnector->getStatisticsTableName());
        if($days == 0) {    // 0 = all time
            if ($dbDriver == 'pgsql') {
                $query = "SELECT year, month, day, SUM(count) AS count FROM $table_name WHERE service=:spIdentifier GROUP BY year, month, day ORDER BY year DESC,month DESC,day DESC";
            } else {
                $query = "SELECT year, month, day, SUM(count) AS count FROM $table_name WHERE service=:spIdentifier GROUP BY year DESC,month DESC,day DESC";
            }
            $queryParams = array(
                'spIdentifier' => array($spIdentifier, PDO::PARAM_STR),
            );
        } else {
            if ($dbDriver == 'pgsql') {
                $query = "SELECT year, month, day, SUM(count) AS count FROM $table_name WHERE service=:spIdentifier AND CAST(CONCAT(year,'-',LPAD(CAST(month AS varchar),2,'0'),'-',LPAD(CAST(day AS varchar),2,'0')) AS date) > current_date - INTERVAL '1 days' * :days GROUP BY year, month, day ORDER BY year DESC,month DESC,day DESC";
            } else {
                $query = "SELECT year, month, day, SUM(count) AS count FROM $table_name WHERE service=:spIdentifier AND CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() GROUP BY year DESC,month DESC,day DESC";
            }
            $queryParams = array(
                'spIdentifier' => array($spIdentifier, PDO::PARAM_STR),
                'days' => array($days, PDO::PARAM_INT),
            );
        }
        $stmt = $conn->read($query, $queryParams);
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "[new Date(".$row["year"].",". ($row["month"] - 1 ). ", ".$row["day"]."), {v:".$row["count"]."}],";
        }
    }

    public static function getLoginCountPerDayForIdp($days, $idpIdentifier)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        $dbDriver = $databaseConnector->getDbDriver();
        assert($conn != NULL);
        $table_name = $conn->applyPrefix($databaseConnector->getStatisticsTableName());
        if($days == 0) {    // 0 = all time
            if ($dbDriver == 'pgsql') {
                $query = "SELECT year, month, day, SUM(count) AS count FROM $table_name WHERE sourceIdP=:idpIdentifier GROUP BY year, month,day ORDER BY year DESC,month DESC,day DESC";
            } else {
                $query = "SELECT year, month, day, SUM(count) AS count FROM $table_name WHERE sourceIdP=:idpIdentifier GROUP BY year DESC,month DESC,day DESC";
            }
            $queryParams = array(
                'idpIdentifier' => array($idpIdentifier, PDO::PARAM_STR),
            );
        } else {
            if ($dbDriver == 'pgsql') {
                $query = "SELECT year, month, day, SUM(count) AS count FROM $table_name WHERE sourceIdP=:idpIdentifier AND CAST(CONCAT(year,'-',LPAD(CAST(month AS varchar),2,'0'),'-',LPAD(CAST(day AS varchar),2,'0')) AS date) > current_date - INTERVAL '1 days' * :days GROUP BY year, month, day ORDER BY year DESC,month DESC,day DESC";
            } else {
                $query = "SELECT year, month, day, SUM(count) AS count FROM $table_name WHERE sourceIdP=:idpIdentifier AND CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() GROUP BY year DESC,month DESC,day DESC";
            }
            $queryParams = array(
                'idpIdentifier' => array($idpIdentifier, PDO::PARAM_STR),
                'days' => array($days, PDO::PARAM_INT),
            );
        }
        $stmt = $conn->read($query, $queryParams);
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "[new Date(".$row["year"].",". ($row["month"] - 1 ). ", ".$row["day"]."), {v:".$row["count"]."}],";
        }
    }

    public static function getAccessCountPerService($days)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        $dbDriver = $databaseConnector->getDbDriver();
        assert($conn != NULL);
        $table_name = $conn->applyPrefix($databaseConnector->getStatisticsTableName());
        $serviceProvidersMapTableName = $conn->applyPrefix($databaseConnector->getServiceProvidersMapTableName());
        if($days == 0) {    // 0 = all time
            if ($dbDriver == 'pgsql') {
                $query = "SELECT service, COALESCE(name,service) AS spname, SUM(count) AS count FROM $table_name LEFT OUTER JOIN $serviceProvidersMapTableName ON service = identifier GROUP BY service, name HAVING service != ''  ORDER BY count DESC";
            } else {
                $query = "SELECT service, IFNULL(name,service) AS spname, SUM(count) AS count FROM $table_name LEFT OUTER JOIN " . $serviceProvidersMapTableName . " ON service = identifier GROUP BY service HAVING service != ''  ORDER BY count DESC";
            }
            $stmt = $conn->read($query);
        } else {
            if ($dbDriver == 'pgsql') {
                $query = "SELECT year, month, day, service, COALESCE(name,service) AS spname, SUM(count) AS count FROM $table_name LEFT OUTER JOIN $serviceProvidersMapTableName ON service = identifier WHERE CAST(CONCAT(year,'-',LPAD(CAST(month AS varchar),2,'0'),'-',LPAD(CAST(day AS varchar),2,'0')) AS date) > current_date - INTERVAL '1 days' * :days GROUP BY service, name, year, month, day HAVING service != ''  ORDER BY count DESC";
            } else {
                $query = "SELECT year, month, day, service, IFNULL(name,service) AS spname, SUM(count) AS count FROM $table_name LEFT OUTER JOIN $serviceProvidersMapTableName ON service = identifier WHERE CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() GROUP BY service HAVING service != ''  ORDER BY count DESC";
            }
            $queryParams = array(
                'days' => array($days, PDO::PARAM_INT),
            );
            $stmt = $conn->read($query, $queryParams);
        }
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            //echo "['<a href=spDetail.php?identifier=" .$row["service"] . "> " . str_replace("'", "\'", $row["spName"]) . "</a>', " . $row["count"] . "],";
            echo "['" . str_replace("'", "\'", $row["spname"]) . "', '". $row["service"] . "', " .  $row["count"] . "],";
        }
    }

    public static function getAccessCountForServicePerIdentityProviders($days, $spIdentifier)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        $dbDriver = $databaseConnector->getDbDriver();
        assert($conn != NULL);
        $table_name = $conn->applyPrefix($databaseConnector->getStatisticsTableName());
        $identityProvidersMapTableName = $conn->applyPrefix($databaseConnector->getIdentityProvidersMapTableName());
        if($days == 0) {    // 0 = all time
            if ($dbDriver == 'pgsql') {
                $query = "SELECT sourceIdp, service, COALESCE(name,sourceIdp) AS idpname, SUM(count) AS count FROM $table_name LEFT OUTER JOIN $identityProvidersMapTableName ON sourceIdp = entityId GROUP BY sourceIdp, service, idpname HAVING sourceIdp != '' AND service = :spIdentifier ORDER BY count DESC";
            } else {
                $query = "SELECT sourceIdp, service, IFNULL(name,sourceIdp) AS idpname, SUM(count) AS count FROM $table_name LEFT OUTER JOIN $identityProvidersMapTableName ON sourceIdp = entityId GROUP BY sourceIdp, service HAVING sourceIdp != '' AND service = :spIdentifier  ORDER BY count DESC";
            }
            $queryParams = array(
                'spIdentifier' => array($spIdentifier, PDO::PARAM_STR),
            );
        } else {
            if ($dbDriver == 'pgsql') {
                $query = "SELECT year, month, day, sourceIdp, service, COALESCE(name,sourceIdp) AS idpname, SUM(count) AS count FROM $table_name LEFT OUTER JOIN $identityProvidersMapTableName ON sourceIdp = entityId WHERE CAST(CONCAT(year,'-',LPAD(CAST(month AS varchar),2,'0'),'-',LPAD(CAST(day AS varchar),2,'0')) AS date) > current_date - INTERVAL '1 days' * :days GROUP BY sourceIdp, service, idpname, year, month, day HAVING sourceIdp != '' AND service = :spIdentifier ORDER BY count DESC";
            } else {
                $query = "SELECT year, month, day, sourceIdp, service, IFNULL(name,sourceIdp) AS idpname, SUM(count) AS count FROM $table_name LEFT OUTER JOIN $identityProvidersMapTableName ON sourceIdp = entityId WHERE CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() GROUP BY sourceIdp, service HAVING sourceIdp != '' AND service = :spIdentifier ORDER BY count DESC";
            }
            $queryParams = array(
                'days' => array($days, PDO::PARAM_INT),
                'spIdentifier' => array($spIdentifier, PDO::PARAM_STR),
            );
        }
        $stmt = $conn->read($query, $queryParams);
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "['" . str_replace("'", "\'", $row["idpname"]) . "', " . $row["count"] . "],";
        }
    }

    public static function getAccessCountForIdentityProviderPerServiceProviders($days, $idpEntityId)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        $dbDriver = $databaseConnector->getDbDriver();
        assert($conn != NULL);
        $table_name = $conn->applyPrefix($databaseConnector->getStatisticsTableName());
        $serviceProvidersMapTableName = $conn->applyPrefix($databaseConnector->getServiceProvidersMapTableName());
        if($days == 0) {    // 0 = all time
            if ($dbDriver == 'pgsql') {
                $query = "SELECT sourceIdp, service, COALESCE(name,service) AS spname, SUM(count) AS count FROM $table_name LEFT OUTER JOIN $serviceProvidersMapTableName ON service = identifier GROUP BY sourceIdp, service, name HAVING service != '' AND sourceIdp = :idpEntityId ORDER BY count DESC";
            } else {
                $query = "SELECT sourceIdp, service, IFNULL(name,service) AS spname, SUM(count) AS count FROM $table_name LEFT OUTER JOIN $serviceProvidersMapTableName ON service = identifier GROUP BY sourceIdp, service HAVING service != '' AND sourceIdp = :idpEntityId ORDER BY count DESC";
            }
            $queryParams = array(
                'idpEntityId' => array($idpEntityId, PDO::PARAM_STR),
            );
        } else {
            if ($dbDriver == 'pgsql') {
                $query = "SELECT year, month, day, sourceIdp, service, COALESCE(name,service) AS spname, SUM(count) AS count FROM $table_name LEFT OUTER JOIN $serviceProvidersMapTableName ON service = identifier WHERE CAST(CONCAT(year,'-',LPAD(CAST(month AS varchar),2,'0'),'-',LPAD(CAST(day AS varchar),2,'0')) AS date) > current_date - INTERVAL '1 days' * :days GROUP BY sourceIdp, service, name, year, month, day HAVING service != '' AND sourceIdp = :idpEntityId ORDER BY count DESC";
            } else {
                $query = "SELECT year, month, day, sourceIdp, service, IFNULL(name,service) AS spname, SUM(count) AS count FROM $table_name LEFT OUTER JOIN $serviceProvidersMapTableName ON service = identifier WHERE CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() GROUP BY sourceIdp, service HAVING service != '' AND sourceIdp = :idpEntityId ORDER BY count DESC";
            }
            $queryParams = array(
                'days' => array($days, PDO::PARAM_INT),
                'idpEntityId' => array($idpEntityId, PDO::PARAM_STR),
            );
        }
        $stmt = $conn->read($query, $queryParams);
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "['" . str_replace("'", "\'", $row["spname"]) . "', " . $row["count"] . "],";
        }
    }

    public static function getLoginCountPerIdp($days)
    {
        $databaseConnector = new DatabaseConnector();
        $conn = $databaseConnector->getConnection();
        $dbDriver = $databaseConnector->getDbDriver();
        assert($conn != NULL);
        $tableName = $conn->applyPrefix($databaseConnector->getStatisticsTableName());
        $identityProvidersMapTableName = $conn->applyPrefix($databaseConnector->getIdentityProvidersMapTableName());
        if($days == 0) {    // 0 = all time
            if ($dbDriver == 'pgsql') {
                $query = "SELECT sourceidp, COALESCE(name,sourceIdp) AS idpname, SUM(count) AS count FROM $tableName LEFT OUTER JOIN $identityProvidersMapTableName ON sourceidp = entityId GROUP BY sourceidp, name HAVING sourceidp != '' ORDER BY count DESC";
            } else {
                $query = "SELECT sourceidp, IFNULL(name,sourceIdp) AS idpname, SUM(count) AS count FROM $tableName LEFT OUTER JOIN $identityProvidersMapTableName ON sourceidp = entityId GROUP BY sourceidp HAVING sourceidp != '' ORDER BY count DESC";
            }
            $stmt = $conn->read($query);
        } else {
            if ($dbDriver == 'pgsql') {
                $query = "SELECT year, month, day, sourceidp, COALESCE(name,sourceIdp) AS idpname, SUM(count) AS count FROM $tableName LEFT OUTER JOIN $identityProvidersMapTableName ON sourceidp = entityId WHERE CAST(CONCAT(year,'-',LPAD(CAST(month AS varchar),2,'0'),'-',LPAD(CAST(day AS varchar),2,'0')) AS date) > current_date - INTERVAL '1 days' * :days GROUP BY sourceidp, name, year, month, day HAVING sourceidp != '' ORDER BY count DESC";
            } else {
                $query = "SELECT year, month, day, sourceidp, IFNULL(name,sourceIdp) AS idpname, SUM(count) AS count FROM $tableName LEFT OUTER JOIN $identityProvidersMapTableName ON sourceidp = entityId WHERE CONCAT(year,'-',LPAD(month,2,'00'),'-',LPAD(day,2,'00')) BETWEEN CURDATE() - INTERVAL :days DAY AND CURDATE() GROUP BY sourceidp HAVING sourceidp != '' ORDER BY count DESC";
            }
            $queryParams = array(
                'days' => array($days, PDO::PARAM_INT),
            );
            $stmt = $conn->read($query, $queryParams);
        }
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "['" . str_replace("'", "\'", $row["idpname"]) . "', '" . $row["sourceidp"] . "', " . $row["count"] . "],";
        }
    }

}