<?php

use SportStatisticsAnalyzer\Classes\FootballScore;

spl_autoload_register(function ($class) {
    $path = str_replace('\\', '/', "../" . __NAMESPACE__ . "\\" . $class . ".php");

    require($path);
});

require_once('data.php');

$footballScore = new FootballScore($data);

$allMatchesResult = $footballScore->getAllMatchesResult();
$oneMatchResult = $footballScore->match(25, 10);

echo "\nПрогноз всех матчей сборной России\n\n";
print_r($allMatchesResult['Россия']);

echo "\nПрогноз матча Дания - России $oneMatchResult[0] : $oneMatchResult[1]\n\n";


