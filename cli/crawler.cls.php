<?php

namespace LiteSpeed\CLI;

defined('WPINC') || exit;

use LiteSpeed\Debug2;
use LiteSpeed\Crawler;
use WP_CLI;

/**
 * Crawler
 */
class Crawler extends Base
{
    private $__crawler;

    public function __construct()
    {
        Debug2::debug('CLI_Crawler init');

        $this->__crawler = Crawler::cls();
    }

    /**
     * List all crawler
     *
     * ## OPTIONS
     *
     * ## EXAMPLES
     *
     *     # Generate domain API key from QUIC.cloud
     *     $ wp litespeed-crawler list
     *
     */
    public function list()
    {
        $crawler_list = $this->__crawler->list_crawlers();
        $summary = Crawler::get_summary();
        if ($summary['curr_crawler'] >= count($crawler_list)) {
            $summary['curr_crawler'] = 0;
        }

        $seconds = $this->conf(Base::O_CRAWLER_RUN_INTERVAL);
        if ($seconds > 0) {
            $recurrence = '';
            $hours = (int)floor($seconds / 3600);
            if ($hours) {
                if ($hours > 1) {
                    $recurrence .= sprintf(__('%d hours', 'litespeed-cache'), $hours);
                } else {
                    $recurrence .= sprintf(__('%d hour', 'litespeed-cache'), $hours);
                }
            }
            $minutes = (int)floor(($seconds % 3600) / 60);
            if ($minutes) {
                $recurrence .= ' ';
                if ($minutes > 1) {
                    $recurrence .= sprintf(__('%d minutes', 'litespeed-cache'), $minutes);
                } else {
                    $recurrence .= sprintf(__('%d minute', 'litespeed-cache'), $minutes);
                }
            }
        }

        $list = array();
        foreach ($crawler_list as $i => $v) {
            $hit = !empty($summary['crawler_stats'][$i]['H']) ? $summary['crawler_stats'][$i]['H'] : 0;
            $miss = !empty($summary['crawler_stats'][$i]['M']) ? $summary['crawler_stats'][$i]['M'] : 0;

            $blacklisted = !empty($summary['crawler_stats'][$i]['B']) ? $summary['crawler_stats'][$i]['B'] : 0;
            $blacklisted += !empty($summary['crawler_stats'][$i]['N']) ? $summary['crawler_stats'][$i]['N'] : 0;

            if (isset($summary['crawler_stats'][$i]['W'])) {
                $waiting = $summary['crawler_stats'][$i]['W'] ?: 0;
            } else {
                $waiting = $summary['list_size'] - $hit - $miss - $blacklisted;
            }

            $status = 'Waiting: ' . $waiting;
            $status .= ' Hit: ' . $hit;
            $status .= ' Miss: ' . $miss;
            $status .= ' Blocked: ' . $blacklisted;

            $running = '';
            if ($i == $summary['curr_crawler']) {
                $running = 'Pos: ' . ($summary['last_pos'] + 1);
                if ($is_running) {
                    $running .= '(Running)';
                }
            }

            $list[] = array(
                'key' => $i + 1,
                'title' => $v['title'],
                'freuency' => $recurrence,
                'status' => $status,
                'running' => $running,
            );
        }

        WP_CLI\Utils\format_items('table', $list, array('#', 'Name', 'Frequency', 'Status', 'Running'));
    }
}
