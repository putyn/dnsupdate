#!/usr/bin/php -q
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
require_once('functions.php');

//information holder
$foo = array('-u'=>array('value'=>''),
              '-p'=>array('value'=>''),
              '-h'=>array('value'=>'','callback'=>'update_hosts'),
              '-l'=>array('value'=>'','callback'=>'list_hosts'),
              '-a'=>array('value'=>'','callback'=>'add_host'),
              '-d'=>array('value'=>'','callback'=>'delete_host'),
             );
//function that handles the main call             
function dnsupdate() {
  GLOBAL $foo;
  
  $got_args = false;
  $argv_string = join('|',$_SERVER['argv']).'|';

  foreach($foo as $key=>$boo) {
    if(preg_match(sprintf('/\|%s=(.+?)\|/i',$key),$argv_string,$tmp)) {
      $got_args = true;
      if(isset($foo[$key]['callback']))
        $foo[$key]['value'] = call_user_func($foo[$key]['callback'],$tmp[1]) ? $tmp[1] : '';
         else
       $foo[$key]['value'] = sprintf('%s',$tmp[1]);
    }elseif(preg_match(sprintf('/\|%s\|/i',$key),$argv_string,$tmp)) {
      $got_args = true;
      if(isset($foo[$key]['callback']))
        call_user_func($foo[$key]['callback']);
    }
  }
  if(!$got_args)
   print_help();
}

//function that prints the help
function print_help() {
 $help = <<<HELP
Usage :
  -u  username for freedns.afraid.org account
  -p  password for freedns.afraid.org account
  -h  domains/subdomains that you want to update, separated by comma
  -a  add new subdomain to your account
  -d  delete subdomain from your account
  -l  list your hosts\n
Examples: 
  dnsupdate.php -u=foo -p=boo -l
  dnsupdate.php -u=foo -p=boo -h=foo.domain.tld
  dnsupdate.php -u=foo -p=boo -h=foo.domain.tld,foo2.domain.tld,foo.domain2.tld
  dnsupdate.php -u=foo -p=boo -a=foo3.domain.tld
  dnsupdate.php -u=foo -p=boo -d=foo3.domain.tld\n
HELP;
 print($help);
}

function add_host($name) {
  GLOBAL $foo;
  
  if(!file_exists('.cookiejar'))
    afraid_login();
    
  if(preg_match('/^(.+?)\.(.+?\..+?)$/',$name,$tmp)) {
 
    $subdomain = $tmp[1];
    $domain = $tmp[2];
    
    if(($domain_id = afraid_domain_id($domain)) === false || $domain_id == 0) {
     info(sprintf('are you sure you typed the domain correct ?can\'t find %s',$domain),0);
     return;
    }
    
    $server_ip = get_current_ip();

    afraid_request('post','http://freedns.afraid.org/subdomain/save.php?step=2',array('subdomain'=>$subdomain,'domain_id'=>$domain_id,'address'=>$server_ip,'type'=>'A'));

    get_hosts();
    
    if(isset($foo['-l']['value'][$name]))
     info(sprintf('subdomain %s was added to your account',$name));
    else
     info('there was an error, could no execute last request',0);
     
  } else
    info(sprintf('can\'t use %s to create subdomain',$name),0);
}

function delete_host($subdomain) {
  GLOBAL $foo;
  
  
  if(($subdomain_id = afraid_subdomain_id($subdomain)) === false || $subdomain_id == 0) {
   info(sprintf('can\'t find subdomain %s, are you sure you typed correct ?',$subdomain),0);
   return;
  }
  
  afraid_request('post','http://freedns.afraid.org/subdomain/delete2.php', array('data_id[]'=>$subdomain_id));

  get_hosts();
  
  if(!isset($foo['-l']['value'][$subdomain]))
     info(sprintf('subdomain %s was removed to your account',$subdomain));
    else
     info('there was an error, could no execute last request',0);
  
}

//simple function to display errors/informations
function info($msg,$level=1) {
 $icon = $level ? ':)' : '!';
 printf("[%s]dns update says: %s\n",$icon,$msg);
}

//function to get the users domains/subdomains
function get_hosts() {
  GLOBAL $foo;
  
  $hash_key = sha1(sprintf('%s|%s',$foo['-u']['value'],$foo['-p']['value']));
  
  $tmp = afraid_request('get',sprintf('http://freedns.afraid.org/api/?action=getdyndns&sha=%s',$hash_key));
  
  if(strstr($tmp,'ERROR')) {
   info('check username or/and password',0);
   return false;
  }
  
  preg_match_all('/^(.+?)\|(.+?)\|(.+?)\n/m',$tmp."\n",$hosts,PREG_SET_ORDER);
  foreach($hosts as $host)
   $foo['-l']['value'][$host[1]] = array('host'=>sprintf('%s',$host[1]),'ip'=>sprintf('%s',$host[2]),'update_link'=>sprintf('%s',$host[3]));
   
  return true;
   
}

//function to get the current ip
function get_current_ip() {
  $tmp = afraid_request('get','http://freedns.afraid.org/dynamic/check.php');
  return (preg_match('/\d+\.\d+\.\d+\.\d+/',$tmp,$ip) ? $ip[0]  : '');
}

//callback functions 

//validate ip
function validip($ip) {
  return (long2ip(ip2long($ip)) === $ip ? true : false);
}

//function that manages domains update
function update_hosts($hosts) {
  GLOBAL $foo;

  if(!get_hosts())
   return false;
  
  $hosts = strstr($hosts,',') ? explode(',',$hosts) : array($hosts);
  
  
  if(($server_ip = get_current_ip()) === false || !validip($server_ip)) {
   info('could not get a valid ip');
   return;
  }
  
  foreach($hosts as $host) {
   if(isset($foo['-l']['value'][$host])) {
     if($foo['-l']['value'][$host]['ip'] != $server_ip) {
       info(sprintf('ip for host %s is different, trying to update ...',$host));
       info(file_get_contents($foo['-l']['value'][$host]['update_link']));
     }
     else 
       info(sprintf('nothing to update for host %s',$host));
   }
    else
      info(sprintf('host %s was not found in hosts list',$host),0);
      
  }
  
  
}

//function that lists the domain/subdomains for an account
function list_hosts() {
  GLOBAL $foo;
  
  if(empty($foo['-l']['value']) !== false)
   get_hosts();
   
  foreach($foo['-l']['value'] as $host)
   info(sprintf('found host %s with ip %s',$host['host'],$host['ip']));
}

//main function call
dnsupdate();
?>
