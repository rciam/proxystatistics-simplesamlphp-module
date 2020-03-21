<?php

/**
 * @author Pavel Vyskočil <vyskocilpavel@muni.cz>
 * @author Dominik Baránek <0Baranek.dominik0@gmail.com>
 */

?>

<?php
require_once 'timeRange.include.php';
require_once 'functions.include.php';
?>

<h2><?php echo $this->t('{proxystatistics:Proxystatistics:templates/graphs_id_providers}'); ?></h2>
<div class="legend">
    <div><?php echo $this->t('{proxystatistics:Proxystatistics:templates/identityProviders_legend}'); ?></div>
</div>
<div class="row">
    <div class="col-md-8">
        <?php pieChart('idpsChart'); ?>
    </div>
    <div class="col-md-4">
        <div id="idpsTable" class="table-container"></div>
    </div>
</div>
