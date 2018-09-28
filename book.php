<?php
/**
 * Created by PhpStorm.
 * User: XYX
 * Date: 2018/9/28
 * Time: 16:20
 */
require __DIR__.'/vendor/autoload.php';
use FishRead\Site\YooRead;
use Symfony\Component\Console\Application;

$application = new Application();
$application->add(new YooRead());
$application->run();