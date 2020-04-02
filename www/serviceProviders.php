<?php

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getSessionFromRequest();

$t = new SimpleSAML_XHTML_Template($config, 'proxystatistics:serviceProviders-tpl.php');
$t->data['lastDays'] = filter_input(
    INPUT_GET,
    'lastDays',
    FILTER_VALIDATE_INT,
    ['options'=>['default'=>0,'min_range'=>0]]
);
$t->data['tab'] = 2;
$t->show();
