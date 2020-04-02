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

$t = new SimpleSAML_XHTML_Template($config, 'proxystatistics:spDetail-tpl.php');

$t->data['lastDays'] = filter_input(
    INPUT_POST,
    'lastDays',
    FILTER_VALIDATE_INT,
    ['options'=>['default'=>0,'min_range'=>0]]
);
$t->data['identifier'] = filter_input(INPUT_GET, 'identifier', FILTER_SANITIZE_STRING);

if ($mode === 'IDP') {
    $t->data['spDetailGraphClass'] = 'hidden';
} else {
    $t->data['spDetailGraphClass'] = '';
}

$t->show();
