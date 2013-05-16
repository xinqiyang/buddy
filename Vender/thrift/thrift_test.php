<?php

/***
Thrift Test Class by xinqiyang

*/

ini_set('display_error', E_ALL);

$GLOBALS['THRIFT_ROOT'] = './lib';


/* Dependencies. In the proper order. */
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift/Transport/TTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift/Transport/TSocket.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift/Protocol/TProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift/Protocol/TBinaryProtocol.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift/Transport/TBufferedTransport.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift/Type/TMessageType.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift/Factory/TStringFuncFactory.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift/StringFunc/TStringFunc.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift/StringFunc/Core.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift/Type/TType.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift/Exception/TException.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift/Exception/TTransportException.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Thrift/Exception/TProtocolException.php';





/* Remember these two files? */
require_once $GLOBALS['THRIFT_ROOT'].'/Types.php';
require_once $GLOBALS['THRIFT_ROOT'].'/Hbase.php';




use Thrift\Protocol\TBinaryProtocol;
use Thrift\Transport\TSocket;
use Thrift\Transport\TSocketPool;
use Thrift\Transport\TFramedTransport;
use Thrift\Transport\TBufferedTransport;
use Hbase\HbaseClient;


//define host and port
$host = '192.168.56.56';
$port = 9090;
$socket = new Thrift\Transport\TSocket($host, $port);

$transport = new TBufferedTransport($socket);
$protocol = new TBinaryProtocol($transport);
// Create a calculator client
$client = new HbaseClient($protocol);
$transport->open();



//echo "Time: " . $client -> time();

$tables = $client->getTableNames();
sort($tables);

foreach ($tables as $name) {

	echo $name."\r\n";
}

//create a fc and then create a table
$columns = array(
	new \Hbase\ColumnDescriptor(array(
			'name' => 'id:',
			'maxVersions' => 10
		)),
	new \Hbase\ColumnDescriptor(array(
			'name' => 'name:'
		)),
	new \Hbase\ColumnDescriptor(array(
			'name' => 'score:'
		)),
);

$tableName = "student";



/*
try {
    $client->createTable($tableName, $columns);
} catch (AlreadyExists $ae) {
    var_dump( "WARN: {$ae->message}\n" );
}
*/

// get table descriptors
$descriptors = $client->getColumnDescriptors($tableName);
asort($descriptors);
foreach ($descriptors as $col) {
	var_dump( "  column: {$col->name}, maxVer: {$col->maxVersions}\n" );
}

//set clomn



//add update column data

$time = time();

var_dump($time);

$row = '2';
$valid = "foobar-".$time;



$mutations = array(
	new \Hbase\Mutation(array(
			'column' => 'score',
			'value' => $valid
		)),
);


$mutations1 = array(
	new \Hbase\Mutation(array(
			'column' => 'score:a',
			'value' => $time,
		)),
);


$attributes = array (

);



//add row, write a row
$row1 = $time;
$client->mutateRow($tableName, $row1, $mutations1, $attributes);

echo "-------write row $row1 ---\r\n";


//update row
$client->mutateRow($tableName, $row, $mutations, $attributes);


//get column data
$row_name = $time;
$fam_col_name = 'score:a';
$arr = $client->get($tableName, $row_name, $fam_col_name, $attributes);

// $arr = array
foreach ($arr as $k => $v) {
	// $k = TCell
	echo " ------ get one : value = {$v->value} , <br>  ";
	echo " ------ get one : timestamp = {$v->timestamp}  <br>";
}

echo "----------\r\n";

$arr = $client->getRow($tableName, $row_name, $attributes);
// $client->getRow return a array
foreach ($arr as $k => $TRowResult) {
	// $k = 0 ; non-use
	// $TRowResult = TRowResult
	var_dump($TRowResult);
}


echo "----------\r\n";
/******
  //no test
  public function scannerOpenWithScan($tableName, \Hbase\TScan $scan, $attributes);

  public function scannerOpen($tableName, $startRow, $columns, $attributes);
  public function scannerOpenWithStop($tableName, $startRow, $stopRow, $columns, $attributes);
  public function scannerOpenWithPrefix($tableName, $startAndPrefix, $columns, $attributes);
  public function scannerOpenTs($tableName, $startRow, $columns, $timestamp, $attributes);
  public function scannerOpenWithStopTs($tableName, $startRow, $stopRow, $columns, $timestamp, $attributes);
  public function scannerGet($id);
  public function scannerGetList($id, $nbRows);
  public function scannerClose($id);
*/


echo "----scanner get ------\r\n";
$startRow = '1';
$columns = array ('column' => 'score', );


//

$scan = $client->scannerOpen($tableName, $startRow, $columns, $attributes);

//$startAndPrefix = '13686667';
//$scan = $client->scannerOpenWithPrefix($tableName,$startAndPrefix,$columns,$attributes);

//$startRow = '1';
//$stopRow = '2';
//$scan = $client->scannerOpenWithStop($tableName, $startRow, $stopRow, $columns, $attributes);



//$arr = $client->scannerGet($scan);

$nbRows = 1000;

$arr = $client->scannerGetList($scan, $nbRows);

var_dump('count of result :'.count($arr));

foreach ($arr as $k => $TRowResult) {
	// code...
	//var_dump($TRowResult);
}

$client->scannerClose($scan);

//close transport
$transport->close();
