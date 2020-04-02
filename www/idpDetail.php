<?php

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

const CONFIG_FILE_NAME_STATISTICSPROXY = 'module_statisticsproxy.php';
const MODE = 'mode';

$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getSessionFromRequest();

$configStatisticsproxy = SimpleSAML_Configuration::getConfig(CONFIG_FILE_NAME_STATISTICSPROXY);
$mode = $configStatisticsproxy->getString(MODE, 'PROXY');

$t = new SimpleSAML_XHTML_Template($config, 'proxystatistics:idpDetail-tpl.php');

$t->data['lastDays'] = filter_input(
    INPUT_POST,
    'lastDays',
    FILTER_VALIDATE_INT,
    ['options'=>['default'=>0,'min_range'=>0]]
);
$t->data['entityId'] = filter_input(INPUT_GET, 'entityId', FILTER_SANITIZE_STRING);

if ($mode === 'SP') {
    $t->data['idpDetailGraphClass'] = 'hidden';
} else {
    $t->data['idpDetailGraphClass'] = '';
}

$t->show();
