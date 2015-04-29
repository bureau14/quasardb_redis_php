<?php

require './quasardb_to_redis.php';

$host = '127.0.0.1';
echo "Using Quasardb on " . $host . "\n";
$config = array(array("address" => $host, "port" => 2836));
$db = new QdbCluster($config);
$r = new QuasardbRedisWithMulti($db, "test", "redis");

function dump($a) {
  ob_start();
  var_dump($a);
  $aa = ob_get_contents();
  ob_clean();
  return trim($aa);
}
function compare($a, $b) {
  if ($a !== $b) {
    throw new Exception("Assert failed : <".dump($a)."> != <".dump($b).">");
  }
}

echo("Get Set\n");

$r->del('myKey');

compare($r->get('myKey'), false);
compare($r->set('myKey', "a"), true);
compare($r->get('myKey'), "a");
compare($r->set('myKey', 12), true);
compare($r->get('myKey'), "12");
compare($r->set('myKey2', 13), true);
compare($r->get('myKey'), "12");
compare($r->get('myKey2'), "13");
compare($r->del('myKey'), 1);
compare($r->del('myKey'), 0);
compare($r->del('myKey2'), 1);
compare($r->get('myKey'), false);
compare($r->get('myKey2'), false);

echo("Get Set binary\n");

$r->del('myKey');

compare($r->get('myKey'), false);
compare($r->set('myKey', "toto\r\ntiti"), true);
compare($r->get('myKey'), "toto\r\ntiti");

compare($r->set('myKey', "toto\x00\x01\x02tata"), true);
compare($r->get('myKey'), "toto\x00\x01\x02tata");

$json = file_get_contents('big_json.json');

echo("Get Set big data " . strlen($json)."\n");

$r->del('myKey');
compare($r->get('myKey'), false);
compare($r->set('myKey', $json), true);
compare($r->get('myKey'), $json);
compare($r->del('myKey'), 1);
compare($r->rpush('myKey', $json), 1);
compare($r->rpop('myKey'), $json);

// Commented because i don't have gzcompress() on Windows
// $bin = gzcompress($json);

// echo("Get Set big data binary " . strlen($bin)."\n");

// $r->del('myKey');
// compare($r->get('myKey'), false);
// compare($r->set('myKey', $bin), true);
// compare(gzuncompress($r->get('myKey')), $json);
// compare($r->del('myKey'), 1);
// compare($r->rpush('myKey', $bin), 1);
// compare(gzuncompress($r->rpop('myKey')), $json);

// Commented becase i don't have removeAll() yet
// echo("Flush\n");
// compare($r->set('myKey1', "a"), true);
// compare($r->set('myKey2', "b"), true);
// compare($r->flushdb(), true);
// compare($r->get('myKey1'), false);
// compare($r->get('myKey2'), false);

echo("Array\n");

$r->del('myKey');
compare($r->rpop('myKey'), false);
compare($r->lpop('myKey'), false);
compare($r->lsize('myKey'), 0);
compare($r->rpush('myKey', 'a'), 1);
compare($r->rpush('myKey', 'b'), 2);
compare($r->rpush('myKey', 'c'), 3);
compare($r->rpush('myKey', 12), 4);
compare($r->lsize('myKey'), 4);
compare($r->rpop('myKey'), '12');
compare($r->lsize('myKey'), 3);
compare($r->rpush('myKey', 'e'), 4);
compare($r->lsize('myKey'), 4);
compare($r->rpop('myKey'), 'e');
compare($r->lsize('myKey'), 3);
compare($r->lpop('myKey'), 'a');
compare($r->lsize('myKey'), 2);
compare($r->rpop('myKey'), 'c');
compare($r->lsize('myKey'), 1);
compare($r->lpush('myKey', 'z'), 2);
compare($r->lsize('myKey'), 2);
compare($r->rpop('myKey'), 'b');
compare($r->lsize('myKey'), 1);
compare($r->rpop('myKey'), 'z');
compare($r->lsize('myKey'), 0);
compare($r->rpop('myKey'), false);

echo("Exec/Multi\n");

$r->del('myKey');
compare($r->multi(), $r);
compare($r->exec(), array());
compare($r->multi(), $r);
compare($r->get('myKey'), $r);
compare($r->set('myKey', 'toto2'), $r);
compare($r->get('myKey'), $r);
compare($r->del('myKey'), $r);
compare($r->rpush('myKey', 'a'), $r);
compare($r->rpop('myKey'), $r);
compare($r->exec(), array(false, true, 'toto2', 1, 1, "a"));

echo("Pipeline\n");

$r->del('myKey');
compare($r->pipeline(), $r);
compare($r->exec(), array());
compare($r->multi(), $r);
compare($r->get('myKey'), $r);
compare($r->set('myKey', 'toto2'), $r);
compare($r->get('myKey'), $r);
compare($r->del('myKey'), $r);
compare($r->rpush('myKey', 'a'), $r);
compare($r->rpop('myKey'), $r);
compare($r->exec(), array(false, true, 'toto2', 1, 1, "a"));

echo("SetEx\n");

$r->del('myKey');
compare($r->setex('myKey', 2, 'a'), true);
compare($r->get('myKey'), "a");
compare($r->ttl('myKey'), 2);
sleep(1);
compare($r->ttl('myKey'), 1);
sleep(2);
compare($r->get('myKey'), false);

compare($r->set('myKey', 'a'), true);
sleep(3);
compare($r->get('myKey'), "a");
compare($r->setTimeout('myKey', 2), true);
sleep(1);
compare($r->ttl('myKey'), 1);
sleep(2);
compare($r->get('myKey'), false);

echo("OK\n");
