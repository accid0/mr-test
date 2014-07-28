<?php
namespace Application;
use SQLite3;
error_reporting(E_ALL);
ini_set('display_errors', 1);
function o($str, $base = 10){
  $numbers                  = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
  $result                   = 0;
  for($index = 0, $len = strlen((string)$str); $index < $len; ++$index){
    $char                   = $str[$index];
    if (false === ($char = array_search($char, $numbers, true))) break;
    $result                 = ($result * $base ) + $char;
  }
  return $result;
}

var_dump(o('10%'), o('123'), o('a1234'), o('$10'));

function ch_date($date){
  $timestamp                = strtotime($date);
  return date('Y-m-d h:i:s', $timestamp);
}

var_dump(ch_date('01/18/2013 01:02:03'));

function t_old(array $data){
  $len                      = count($data);
  for($index = 0; $index < $len ; ++$index){
    if(!array_key_exists($index, $data)) continue;
    for($jndex = $index + 1; $jndex < $len; ++$jndex){
      if(!array_key_exists($jndex, $data)) continue;
      if ($data[$index]['topic'] == $data[$jndex]['topic'] && $data[$index]['message'] == $data[$jndex]['message'])
        unset($data[$jndex]);
    }
  }
  return $data;
}

function get_dummy(){
  $test_data                  = range(0,5000);

  $test_data                  = array_map(function($item){
    return ['id' => $item, 'topic' => $item, 'message' => $item];
  }, $test_data);

  //>>bug
  $test_data[1]               = ['id' => 1, 'topic' => 2, 'message' => 2];

  $test_data[7]               = ['id' => 1, 'topic' => 2, 'message' => 2];
  //<<bug
  return $test_data;
}
$start                      = microtime(true);

$test_data                  = t_old(get_dummy());

echo 'Old result, ms: ', round((microtime(true) - $start)*1000, 3), PHP_EOL;

function t_new(array $data){
  $key                      = [];
  foreach($data as $index => $row){
    $hash                   = md5('--' . $row['topic'] . $row['message']);
    if(array_key_exists($hash, $key)){
      unset($data[$index]);
      continue;
    }
    $key[$hash]             = true;
  }
  return $data;
}

$start                      = microtime(true);

$test_data                  = t_new(get_dummy());

echo 'New result, ms: ', round((microtime(true) - $start)*1000, 3), PHP_EOL;

interface I {

  public function put($key, $value, $expire = null);

  public function get($key);

}

abstract class Cache implements I {

  const ATTR_DEFAULT_EXPIRY = 0;

  public function id($key){
    return md5($key);
  }

  abstract function do_put($key, $value, $expiry);

  abstract function get($key);

  public function put($key, $value, $expiry = null){
    return $this->do_put($key, $value, null === $expiry ? self::ATTR_DEFAULT_EXPIRY : $expiry);
  }
}

class FileCache extends Cache {

  const ATTR_CACHE_DIR      = '/cache/';

  const ATTR_FILE_EXT       = '.cache';

  public $dir               = '';

  public function id($key){
    return $this->dir . parent::id($key);
  }

  public function __construct(){
    $this->init();
  }

  public function init(){
    $this->dir              = __DIR__ . self::ATTR_CACHE_DIR;
    if(!is_dir($this->dir)){
      mkdir($this->dir, 0700);
    }
  }

  function do_put($key, $value, $expiry){
    $file                   = $this->id($key);
    $file                   = fopen($file, 'wb');
    fwrite($file, $expiry . PHP_EOL);
    fwrite($file, serialize($value));
    fclose($file);
  }

  function get($key){
    $file                   = $this->id($key);
    $expiry                 = filemtime($file);
    if(false === $expiry){
      return false;
    }
    $res                    = fopen($file, 'rb');
    $exp                    = intval(fgets($res, 13));
    if($exp && time() - $expiry > $exp){
      unlink($file);
      return false;
    }
    ob_start();
    fpassthru($res);
    $data                   = ob_get_clean();
    return unserialize($data);
  }

}

$cache = new FileCache();
$cache->put('test', [1,2,3], 10);
var_dump($cache->get('test'));

class SqlCache extends Cache {

  const ATTR_SQL_DATABASE   = '/cache.sqlite';

  public $res               = null;

  public $put               = null;

  public $get               = null;

  public $delete            = null;

  public function __construct(){
    $this->init();
  }

  public function init(){
    $this->res               = new SQLite3(__DIR__ . self::ATTR_SQL_DATABASE);
    $this->res->exec( 
<<<EOQ
CREATE TABLE IF NOT EXISTS cache
  (
    id varchar(32) PRIMARY KEY,
    value text,
    expiry int(11),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
  );
EOQ
    );
    $this->put              = $this->res->prepare(
<<<EOQ
REPLACE INTO cache (`id`, `value`, `expiry`) VALUES (
  :key,
  :data,
  :expiry
)
EOQ
    );
    $this->get              = $this->res->prepare(
<<<EOQ
SELECT * FROM cache WHERE id=:key LIMIT 1
EOQ
    );
    $this->delete           = $this->res->prepare(
<<<EOQ
DELETE FROM cache WHERE id=:key
EOQ
    );
  }

  public function do_put($key, $value, $expiry){
    $data                   = serialize($value);
    $key                    = $this->id($key);
    $this->put->bindValue(':key', $key);
    $this->put->bindValue(':expiry', $expiry);
    $this->put->bindValue(':data', $data);
    return $this->put->execute();
  }

  public function get($key){
    $key                    = $this->id($key);
    $this->get->bindValue(':key', $key);
    $data                   = $this->get->execute();
    if(!$data){
      return false;
    }
    $data                   = $data->fetchArray(SQLITE3_ASSOC);
    if($data['expiry'] && time() - strtotime($data['timestamp']) > $data['expiry']){
      $this->delete->bindValue(':key', $key);
      return false;
    }
    return unserialize($data['value']);
  }

}

$cache = new SqlCache();
$cache->put('test', [1,2,3], 10);
var_dump($cache->get('test'));


/* #5
WITH RECURSIVE temp (name, id)
AS (
  SELECT c1.name as name, c1.id as id
    FROM categories c1
    WHERE c1.parent_id = NULL
  UNION
  SELECT CAST(temp.name||'->'||c2.name AS VARCHAR(255)) as name, c2.id as id
    FROM categories c2
    JOIN temp ON temp.id=c2.parent_id
);
SELECT name FROM temp WHERE id=? LIMIT 1;

#6
SELECT COUNT(p.id) as ct, p.id
  FROM logs.posts p
  WHERE p.id_statuses NOT NULL
  GROUP BY p.id
  HAVING ct > 5;
*/
?>
<!DOCTYPE HTML>
<html>
  <head>
  <script type="text/javascript">
  document.addEventListener("DOMContentLoaded", init, false);
  function init(){
    var $                             = function(sel){
        	var res 				            = document.querySelectorAll(sel);
        	return Array.prototype.slice.call(res);
    	  },
        $el                           = $('*[validate]');


    function Validator(el){
      this.init(el);
    }
    
    Validator.prototype.init          = function(){
      this.$el                        = el;
      this.message                    = el.getAttribute('message');
      this.type                       = el.getAttribute('validate');
      this.method                     = this.type.split('-')[0];
      el.onchange                     = this['validate_' + this.method];
    };
  
    Validator.prototype.validate_digits               = function(event){
      var val                                         = this.value;
      event.preventDefault();
    };
    Validator.prototype.validate_email                = function(event){
      var val                                         = this.value;
      event.preventDefault();
    };
    Validator.prototype.validate_length               = function(event){
      var val                                         = this.value;
      event.preventDefault();
    };

    $el.map(function(el){
		  new Validator(el);
    });
    
  };
  </script>
  </head>
  <body>
  <div>
    <input id="first" type="text" validate='digits' validate­message='Digits only'>
  </div>
  <div>
    <input id="second" type="text" validate='email' validate­message='Invalid email'>
  </div>
  <div>
    <textarea validate='length­ma' validate­message='Max 10 symbols'></textarea>
  </div>
  <div>
    <input type="text" validate='digits' validate­message='Digits only'>
  </div>
  </body>
</html>
