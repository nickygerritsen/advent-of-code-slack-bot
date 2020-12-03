<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\HttpClient\HttpClient;

class AocCommand extends Command
{
    protected static $defaultName = 'aoc';
    protected int    $aocYear;
    protected string $aocSessionId;
    protected int    $aocLeaderboardId;
    protected string $dataFile;
    protected string $slackWebhook;
    protected string $botName;

    public function __construct(int $aocYear, string $aocSessionId, int $aocLeaderboardId, string $dataFile, string $slackWebhook, string $botName)
    {
        parent::__construct(null);
        $this->aocYear          = $aocYear;
        $this->aocSessionId     = $aocSessionId;
        $this->aocLeaderboardId = $aocLeaderboardId;
        $this->dataFile         = $dataFile;
        $this->slackWebhook     = $slackWebhook;
        $this->botName          = $botName;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $client = HttpClient::create([
            'base_uri' => 'https://adventofcode.com',
            'headers'  => [
                'Cookie' => sprintf('session=%s', $this->aocSessionId),
            ],
        ]);

        $uri = sprintf('/%d/leaderboard/private/view/%d.json', $this->aocYear, $this->aocLeaderboardId);

        $results = $client->request('GET', $uri);
        $data    = $results->toArray();
        $members = $data['members'];
        uasort($members, function ($a, $b) {
            if ($a['local_score'] != $b['local_score']) {
                return $b['local_score'] <=> $a['local_score'];
            }

            if ($a['stars'] != $b['stars']) {
                return $b['stars'] <=> $a['stars'];
            }

            if ($a['last_star_ts'] != $b['last_star_ts']) {
                return $a['last_star_ts'] <=> $b['last_star_ts'];
            }

            return $a['name'] <=> $b['name'];
        });

        if (file_exists($this->dataFile)) {
            $currentData = json_decode(file_get_contents($this->dataFile), true);
        } else {
            $currentData = [];
        }

        $changed = false;
        foreach ($members as $memberId => $member) {
            $days = $member['completion_day_level'];
            ksort($days);
            foreach ($days as $day => $stars) {
                ksort($stars);
                foreach ($stars as $part => $stats) {
                    if (!isset($currentData[$memberId][$day][$part])) {
                        $dt = date('d-m-Y H:i:s', $stats['get_star_ts']);
                        $this->postMessage(sprintf('Huray, %s solved day %d part %d at %s', $member['name'], $day, $part, $dt));
                        $currentData[$memberId][$day][$part] = true;
                        $changed                             = true;
                    }
                }
            }
        }

        if ($changed) {
            $message = 'Scores changed, new leaderboard:' . PHP_EOL . PHP_EOL;
            $rank    = 1;
            foreach ($members as $member) {
                $message .= sprintf('#%d, *%s*: %d points, %d stars', $rank, $member['name'], $member['local_score'], $member['stars']) . PHP_EOL;
                $rank++;
            }

            $message .= PHP_EOL;
            $message .= sprintf('<https://adventofcode.com/%d/leaderboard/private/view/%d|View Online Leaderboard>', $this->aocYear, $this->aocLeaderboardId);

            $this->postMessage($message);
        }

        file_put_contents($this->dataFile, json_encode($currentData, JSON_PRETTY_PRINT));

        return static::SUCCESS;
    }

    protected function postMessage(string $message)
    {
        $client  = HttpClient::create();
        $payload = [
            'icon_emoji' => ':christmas_tree:',
            'username'   => $this->botName,
            'text'       => $message,
        ];

        $client->request('POST', $this->slackWebhook, [
            'json' => $payload,
        ]);
    }
}
