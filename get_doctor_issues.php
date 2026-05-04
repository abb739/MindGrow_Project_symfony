<?php
require 'vendor/autoload.php';
use Symfony\Component\Dotenv\Dotenv;
(new Dotenv())->bootEnv(__DIR__.'/.env');

$env = 'dev';
$kernel = new App\Kernel($env, true);
$kernel->boot();
$profiler = $kernel->getContainer()->get('profiler');
$tokens = $profiler->find('', '', 1, '', '', '');
if (empty($tokens)) { die("No profiles found\n"); }
$token = $tokens[0]['token'];
$profile = $profiler->loadProfile($token);
$collector = $profile->getCollector('doctrine_doctor');
if (!$collector) { die("Collector not found\n"); }

$issues = $collector->getIssues();
print_r($issues);
