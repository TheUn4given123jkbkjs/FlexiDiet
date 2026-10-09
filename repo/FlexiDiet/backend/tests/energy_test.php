<?php
declare(strict_types=1);
require __DIR__ . '/../src/Http.php';
require __DIR__ . '/../src/Energy.php';
function eq(mixed $a,mixed $b,string $label): void {
    if ($a !== $b) { fwrite(STDERR, "FAIL $label: ".var_export($a,true)." !== ".var_export($b,true)."\n"); exit(1); }
    echo "PASS $label\n";
}
eq(bmrFor(68.5,172,24,'male'),1645.0,'Mifflin male');
eq(bmrFor(55,160,30,'female'),1239.0,'Mifflin female');
eq(bmrFor(70,175,25,'male','katch_mcardle',20.0),1579.6,'Katch-McArdle');
eq(targetFor(1000,'lose','male')['target'],1500.0,'male floor');
eq(targetFor(1000,'lose','female')['target'],1200.0,'female floor');
eq(targetFor(2000,'lose','male')['target'],2040.0,'15% reduction');
eq(exerciseCredit(300,'partial'),150.0,'partial workout credit');
eq(exerciseCredit(700,'capped'),500.0,'capped workout credit');
eq(exerciseCredit(300,'full'),300.0,'full workout credit');
eq(workoutKcal(8.5,68.5,30),291.1,'MET calculation');
