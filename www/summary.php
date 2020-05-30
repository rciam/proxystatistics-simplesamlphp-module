<?php

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 */

const CONFIG_FILE_NAME = 'module_statisticsproxy.php';
const MODE = 'mode';

$configMode = SimpleSAML_Configuration::getConfig(CONFIG_FILE_NAME);
$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getSessionFromRequest();

$mode = $configMode->getString(MODE, 'PROXY');

$t = new SimpleSAML_XHTML_Template($config, 'proxystatistics:summary-tpl.php');

$t->data['lastDays'] = filter_input(
    INPUT_GET,
    'lastDays',
    FILTER_VALIDATE_INT,
    ['options'=>['default'=>0,'min_range'=>0]]
);
$t->data['tab'] = 0;

if ($mode === 'IDP') {
    $t->data['summaryGraphs'] = [
        'identityProviders' => 'hidden',
        'identityProvidersLegend' => '',
        'identityProvidersGraph' => '',
        'serviceProviders' => 'col-md-12 graph',
        'serviceProvidersLegend' => 'col-md-6',
        'serviceProvidersGraph' => 'col-md-6 col-md-offset-3'
    ];
} elseif ($mode === 'SP') {
    $t->data['summaryGraphs'] = [
        'identityProviders' => 'col-md-12 graph',
        'identityProvidersLegend' => 'col-md-6',
        'identityProvidersGraph' => 'col-md-6 col-md-offset-3',
        'serviceProviders' => 'hidden',
        'serviceProvidersLegend' => '',
        'serviceProvidersGraph' => ''
    ];
} elseif ($mode === 'PROXY') {
    $t->data['summaryGraphs'] = [
        'identityProviders' => 'col-md-6 graph',
        'identityProvidersLegend' => 'col-md-12',
        'identityProvidersGraph' => 'col-md-12',
        'serviceProviders' => 'col-md-6 graph',
        'serviceProvidersLegend' => 'col-md-12',
        'serviceProvidersGraph' => 'col-md-12'
    ];
} else {
    SimpleSAML\Logger::error('Unknown mode is set. Mode has to be one of the following: PROXY, IDP, SP.');
}

$t->show();
