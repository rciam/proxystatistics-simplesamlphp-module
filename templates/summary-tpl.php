<?php

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */


require_once 'timeRange.include.php';
require_once 'functions.include.php';
?>

<h2><?php echo $this->t('{proxystatistics:Proxystatistics:templates/graphs_logins}'); ?></h2>
<div class="legend-logins">
    <div>
        <?php echo $this->t('{proxystatistics:Proxystatistics:templates/summary_logins_info}'); ?>
    </div>
    <?php require_once 'loginsDashboard.include.php'; ?>
</div>

<div class="row tableMaxHeight">
    <div class="<?php echo $this->data['summaryGraphs']['identityProviders'] ?>">
        <h2><?php echo $this->t('{proxystatistics:Proxystatistics:templates/graphs_id_providers}'); ?></h2>
        <div class="row">
            <div class="<?php echo $this->data['summaryGraphs']['identityProvidersLegend'] ?>">
                <div class="legend">
                    <div id="summaryIdp">
                        <?php echo $this->t('{proxystatistics:Proxystatistics:templates/summary_idps_info}'); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="<?php echo $this->data['summaryGraphs']['identityProvidersGraph'] ?>">
                <?php pieChart('idpsChart'); ?>
            </div>
        </div>
    </div>
    <div class="<?php echo $this->data['summaryGraphs']['serviceProviders'] ?>">
        <h2><?php echo $this->t('{proxystatistics:Proxystatistics:templates/graphs_service_providers}'); ?></h2>
        <div class="row">
            <div class="<?php echo $this->data['summaryGraphs']['serviceProvidersLegend'] ?>">
                <div class="legend">
                    <div>
                        <?php echo $this->t('{proxystatistics:Proxystatistics:templates/summary_sps_info}'); ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="<?php echo $this->data['summaryGraphs']['serviceProvidersGraph'] ?>">
                <?php pieChart('spsChart'); ?>
            </div>
        </div>
    </div>
</div>
