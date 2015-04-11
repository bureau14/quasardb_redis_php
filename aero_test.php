<?php

require './aero.php';

if (isset($_ENV['USE_REDIS'])) {
  echo "Using Redis !!!!";
  $r = new Redis();
  $r->connect('127.0.0.1', 6379);
}
else {
  $host = isset($_ENV['HOST']) ? ($_ENV['HOST']) : 'localhost'; 
  $config = array("hosts" => array(array("addr" => $host, "port" => 3000)));
  $db = new Aerospike($config, false);
  $r = new AerospikeRedis($db, "test", "redis");
}

echo("Get Set\n");

$r->del('myKey');

assert($r->get('myKey') == NULL);
$r->set('myKey', "a");
assert($r->get('myKey') == "a");
$r->set('myKey', 12);
assert($r->get('myKey') == 12);
$r->set('myKey2', 13);
assert($r->get('myKey') == 12);
assert($r->get('myKey2') == 13);
$r->del('myKey');
$r->del('myKey2');
assert($r->get('myKey') == NULL);
assert($r->get('myKey2') == NULL);

echo("Flush\n");
$r->set('myKey1', "a");
$r->set('myKey2', "b");
$r->flushdb();
assert($r->get('myKey1') == NULL);
assert($r->get('myKey2') == NULL);

echo("Array\n");

$r->del('myKey');
assert($r->lsize('myKey') == NULL);
$r->rpush('myKey', 'a');
$r->rpush('myKey', 'b');
$r->rpush('myKey', 'c');
$r->rpush('myKey', 'd');
assert($r->lsize('myKey') == 4);
assert($r->rpop('myKey') == 'd');
assert($r->lsize('myKey') == 3);
$r->rpush('myKey', 'e');
assert($r->lsize('myKey') == 4);
assert($r->rpop('myKey') == 'e');
assert($r->lsize('myKey') == 3);
assert($r->rpop('myKey') == 'c');
assert($r->lsize('myKey') == 2);
assert($r->rpop('myKey') == 'b');
assert($r->lsize('myKey') == 1);
assert($r->rpop('myKey') == 'a');
assert($r->lsize('myKey') == 0);
assert($r->rpop('myKey') == NULL);

echo("SetEx\n");

$r->del('myKey');
$r->setex('myKey', 'a', 2);
assert($r->get('myKey') == "a");
assert($r->ttl('myKey') == 2);
sleep(1);
assert($r->ttl('myKey') == 1);
sleep(2);
assert($r->get('myKey') == NULL);

echo("OK\n");
