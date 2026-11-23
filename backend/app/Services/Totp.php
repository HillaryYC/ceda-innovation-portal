<?php
namespace App\Services;
class Totp {
 public static function secret():string{$alphabet='ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';$s='';for($i=0;$i<32;$i++)$s.=$alphabet[random_int(0,31)];return $s;}
 public static function code(string $secret,int $step):string{$bits='';foreach(str_split($secret) as $c)$bits.=str_pad(decbin(strpos('ABCDEFGHIJKLMNOPQRSTUVWXYZ234567',$c)),5,'0',STR_PAD_LEFT);$key='';foreach(str_split($bits,8) as $b)if(strlen($b)===8)$key.=chr(bindec($b));$hash=hash_hmac('sha1',pack('N2',0,$step),$key,true);$offset=ord($hash[19])&15;$value=unpack('N',substr($hash,$offset,4))[1]&0x7fffffff;return str_pad((string)($value%1000000),6,'0',STR_PAD_LEFT);}
 public static function step(string $secret,string $code):?int{if(!preg_match('/^\d{6}$/',$code))return null;$now=intdiv(time(),30);foreach([$now-1,$now,$now+1] as $step)if(hash_equals(self::code($secret,$step),$code))return $step;return null;}
}
