<?php

require './aerospike_to_redis.php';

if (isset($_ENV['USE_REDIS'])) {
  echo "Using Redis !!!!\n";
  $r = new Redis();
  $r->connect('127.0.0.1', 6379);
}
else {
  $host = isset($_ENV['HOST']) ? ($_ENV['HOST']) : 'localhost';
  echo "Using Aerospike on " . $host . "\n";
  $config = array("hosts" => array(array("addr" => $host, "port" => 3000)));
  $db = new Aerospike($config, false);
  $r = new AerospikeRedis($db, "test", "redis");
}

function dump($a) {
  ob_start();
  var_dump($a);
  $aa = ob_get_contents();
  ob_clean();
  return trim($aa);
}
function compare($a, $b) {
  if ($a != $b) {
    throw new Exception("Assert failed : <".dump($a)."> != <".dump($b).">");
  }
}

echo("Get Set\n");

$r->del('myKey');

compare($r->get('myKey'), NULL);
compare($r->set('myKey', "a"), true);
compare($r->get('myKey'), "a");
compare($r->set('myKey', 12), true);
compare($r->get('myKey'), 12);
compare($r->set('myKey2', 13), true);
compare($r->get('myKey'), 12);
compare($r->get('myKey2'), 13);
compare($r->del('myKey'), 1);
compare($r->del('myKey2'), 1);
compare($r->get('myKey'), NULL);
compare($r->get('myKey2'), NULL);

echo("Flush\n");
compare($r->set('myKey1', "a"), true);
compare($r->set('myKey2', "b"), true);
compare($r->flushdb(), true);
compare($r->get('myKey1'), NULL);
compare($r->get('myKey2'), NULL);

echo("Array\n");

$r->del('myKey');
compare($r->lsize('myKey'), NULL);
compare($r->rpush('myKey', 'a'), 1);
compare($r->rpush('myKey', 'b'), 2);
compare($r->rpush('myKey', 'c'), 3);
compare($r->rpush('myKey', 'd'), 4);
compare($r->lsize('myKey'), 4);
compare($r->rpop('myKey'), 'd');
compare($r->lsize('myKey'), 3);
compare($r->rpush('myKey', 'e'), 4);
compare($r->lsize('myKey'), 4);
compare($r->rpop('myKey'), 'e');
compare($r->lsize('myKey'), 3);
compare($r->rpop('myKey'), 'c');
compare($r->lsize('myKey'), 2);
compare($r->rpop('myKey'), 'b');
compare($r->lsize('myKey'), 1);
compare($r->rpop('myKey'), 'a');
compare($r->lsize('myKey'), 0);
compare($r->rpop('myKey'), NULL);

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
compare($r->exec(), array(NULL, true, 'toto2', 1, 1, "a"));

echo("SetEx\n");

$r->del('myKey');
compare($r->setex('myKey', 2, 'a'), true);
compare($r->get('myKey'), "a");
compare($r->ttl('myKey'), 2);
sleep(1);
compare($r->ttl('myKey'), 1);
sleep(2);
compare($r->get('myKey'), NULL);

echo("OK\n");
