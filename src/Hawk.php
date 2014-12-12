<?php
namespace Hawk;
/**
* A class to generate and parse Hawk authentication headers
*
* @author		Alex Bilbie | www.alexbilbie.com | hello@alexbilbie.com
* @copyright	Copyright (c) 2012, Alex Bilbie.
* @license		http://www.opensource.org/licenses/mit-license.php
* @link		http://alexbilbie.com
*
*/

class Hawk {
  const HAWK_VERSION=1;
  /**
  * Generate the MAC
  * @param  string $secret The shared secret
  * @param  array  $params The MAC data parameters
  * @return string         The base64 encode MAC
  */
  public static function generateMac($secret = '', $params = array(),$after="")
  {
    $default = array(
      'timestamp'	=>	decistamp(),
      'nonce'	=>	null,
      'method'	=>	'GET',
      'path'	=>	'',
      'host'	=>	'',
      'port'	=>	80

    );
    // Only include the necessary parameters
    foreach (array_keys($default) as $key){
      if (isset($params[$key])){
        $default[$key] = $params[$key];
      }
    }

    // Nuke the ext key if it isn't being used
    if ($default['nonce'] === null){
      unset($default['nonce']);
    }
    // Generate the data string
    $data = implode("\n", $default);
    $data="hawk.".self::HAWK_VERSION.".header\n".$data."\n";
    $data.="\n";//no payload
    $data.="\n";//no ext
    $data.=$after;


    // Generate the hash
    $hash = hash_hmac('sha256', $data, $secret);
    // Return base64 value
    return base64_encode(hex2bin($hash));
  }

  public static function generateParams($url,$method=null){

    if ($method==null)
    $method = $_SERVER['REQUEST_METHOD'];
    $url = parse_url($url);
    if ( !isset($url['port'])){
      $params['port'] = ($url['scheme'] === 'https') ? 443 : 80;
    } else {
      $params['port'] = $url['port'];
    }

    $params['host'] = $url['host'];
    $params['path'] = $url['path'] . (isset($url['query']) ? '?'.$url['query'] : '').(isset($url['fragment']) ? '#'.$url['fragment'] : '');
    $params['method'] = $method;

    return $params;
  }
  /**
  * Generate the full Hawk header string
  * @param  string $key    The identifier key
  * @param  string $secret The shared secret
  * @param  array  $params The MAC data parameters
  * @return string         The Hawk header string
  */
  public static function generateHeader($key , $secret , $method = 'GET',$url= 'http://exemple.org', $nonce=null)
  {
    $params=self::generateParams($url,$method);
    $params['nonce'] = ($nonce !=null) ? $nonce : null;

    $params['timestamp'] = (isset($params['timestamp'])) ? $params['timestamp'] : decistamp();
    // Generate the MAC address
    $mac = self::generateMac($secret, $params);
    // Make the header string
    $header = 'Hawk id="'.$key.'", ts="'.$params['timestamp'].'", ';
    $header .= (isset($params['nonce'])) ? 'nonce="'.base64_encode($params['nonce']).'", ' : '';
    $header .= 'mac="'.$mac.'"';
    return $header;
  }
  /**
  * Verify the received Hawk header
  * @param  string $hawk   The Hawk header string
  * @param  array  $params The MAC data parameters
  * @param  string $secret The shared secret
  * @return bool           True if the header validates, otherwise false
  */

  public static function verifyHeader($hawk = '', $secret = '', $method = 'GET', $url = 'http://exemple.org')
    {
      //parse the url

      $params=self::generateParams($url,$method);
      // Parse the header
      $parts =  self::parseHeader($hawk);

      $params['timestamp'] = $parts['timestamp'];

      if (isset($parts['nonce'])) {
        $params['nonce'] = $parts['nonce'];
      }
      // Ensure the method parameter is uppercase
      $params['method'] = strtoupper($params['method']);
      // Generate the MAC
      $genMAC = self::generateMac($secret, $params);	//in hex form
      // Test against the received MAC
      if ($params['timestamp']>time())
      throw new \Exception('Expired HawkId');

      if (!hash_equals($genMAC,$parts['mac']))
      throw new \Exception('Invalid HawkId');

    }

    /**
    * Parse the Hawk header string into an array of parts
    * @param  string $hawk The Hawk header
    * @return array        The induvidual parts of the Hark header
    */
    public static function parseHeader($hawk = '')
    {
      $segments = explode(', ', substr(trim($hawk), 5, -1));
      if (count($segments)<2)
      return null;
      $parts['id'] = substr($segments[0], 4, strlen($segments[0])-5);
      $parts['timestamp'] = substr($segments[1], 4, strlen($segments[1])-5);
      $parts['mac'] = (count($segments) === 4) ? substr($segments[3], 5, strlen($segments[3])) : substr($segments[2], 5, strlen($segments[2]));
      $parts['nonce'] = (count($segments) === 4) ? substr($segments[2], 7, strlen($segments[2])-8) : null;
      if ($parts['nonce'] === null){
        unset($parts['nonce']);
      }
      //Payload
      if (isset($parts['hash']) && $parts['hash'] === null){
        unset($parts['hash']);
      }
      return $parts;
    }

  }
  /*
  * no time attack
  */
  if(!function_exists('hash_equals')) {
    function hash_equals($str1, $str2) {
      if(strlen($str1) != strlen($str2)) {
        return false;
      } else {
        $res = $str1 ^ $str2;
        $ret = 0;
        for($i = strlen($res) - 1; $i >= 0; $i--) $ret |= ord($res[$i]);
        return !$ret;
      }
    }
  }

  /*
  *
  We need to format timestamp data with decimal .00
  */
  if(!function_exists('decistamp')) {
    function decistamp($time=null){
      if ($time===null){
        $time=microtime(true) ;
      }
      $time=number_format((float)$time, 2, '.', '')		;
      return $time;
    }
  }
