<?php
include dirname(__DIR__)."/lib/Auth/Process/DatabaseCommand.php";

/**
 * Hook to run a cron job.
 *
 * @param array &$croninfo  Output
 * @return void
 */
function proxystatistics_hook_cron(&$croninfo)
{
    if ($croninfo['tag'] !== 'daily') {
        SimpleSAML_Logger::debug('cron [proxystatistics]: Skipping cron in cron tag ['.$croninfo['tag'].'] ');
        return;
    }

    SimpleSAML_Logger::info('cron [proxystatistics]: Running cron in cron tag ['.$croninfo['tag'].'] ');

    try {
        $dbCmd = new DatabaseCommand();
        $dbCmd->deleteOldDetailedStatistics();
    } catch (\Exception $e) {
        $croninfo['summary'][] = 'Error during deleting old detailed statistics: '.$e->getMessage();
    }
}
