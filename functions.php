<?php
/*
putyn@ymail.com
16/09/2011
http://freedns.afraid.org dns update client written in php

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.
*/
r
function afraid_login() {
 GLOBAL $foo;

 afraid_request('post','http://freedns.afraid.org/zc.php?step=2',array('username'=>$foo['-u']['value'],'password'=>$foo['-p']['value'],'remember'=>1,'action'=>'auth'));
}

function afraid_domain_id($domain_name) {

 $return = afraid_request('get','http://freedns.afraid.org/domain/');
   return preg_match(sprintf('/%s.*?limit=(\d+)/',$domain_name),$return,$tmp) ? $tmp[1] : 0;
}

function afraid_subdomain_id($subdomain_name) {
 
 $return = afraid_request('get','http://freedns.afraid.org/subdomain/');
  return preg_match(sprintf('/A*?data_id=(\d+)\>%s/',$subdomain_name),$return,$tmp) ? $tmp[1] : 0;
}

function afraid_request($request_type,$request_url,$request_data = array()) {
  
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION,true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);

  if($request_type == 'post') {
    curl_setopt($ch, CURLOPT_POST,true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,$request_data);
  }

  curl_setopt($ch, CURLOPT_URL,$request_url);
  curl_setopt($ch, CURLOPT_COOKIEJAR, '.cookiejar');
  curl_setopt($ch, CURLOPT_COOKIEFILE, '.cookiejar');
  curl_setopt($ch, CURLOPT_USERAGENT, 'dns update client v1.0'); 

  $result = curl_exec($ch);
  curl_close($ch);
  return $result;
}
?>
