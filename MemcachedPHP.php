<?php

class MemcachedPHP {
	
	private $socket = false;
	private $server = '127.0.0.1';
	private $port = 11211;
	
	private function socket(){
		if(!$this->socket){
			$this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP) or die("Unable to create socket\n");
			$result = socket_connect($this->socket, $this->server, $this->port);
		}
		
		return $this->socket;
	}
	
	public function __construct($s = '127.0.0.1', $p = 11211){
		$this->server = $s;
		$this->port = $p;
		$this->socket();
	}
	
	public function set($key, $value, $expire = 2592000){
		$bytes = self::tobytes($value);
		$expire = (int)$expire;
		$command = "set <#key#> <#flags#> <#exptime#> <#bytes#>\r\n<#value#>\r\n";
		$command = str_replace('<#key#>', $key, $command);
		$command = str_replace('<#flags#>', 0, $command);
		$command = str_replace('<#exptime#>', $expire, $command);
		$command = str_replace('<#bytes#>', $bytes, $command);
		$command = str_replace('<#value#>', $value, $command);
		$result = $this->command($command);
		return ($result == 'STORED')? true : false;
	}
	
	public function get($key){
		$result = $this->command(sprintf("get %s\r\n", preg_replace('/[\r\n]+/', '', $key)));
		
		if(preg_match('/VALUE\s([^\n]+)\r\n(.*)\r\nEND/', $result, $match)){
			return $match[2];
		}
		
		return false;
	}
	
	public function delete($key){
		$result = $this->command(sprintf("delete %s\r\n", preg_replace('/[\r\n]+/', '', $key)));
		return ($result == 'DELETED')? true : false;
	}
	
	public function command($command){
		socket_write($this->socket, $command, self::tobytes($command));
		
		$return = '';
		
		while(true){
			$b = socket_read($this->socket, 128);
			
			$bytes = self::tobytes($b);
			$return .= $b;
			if(!$b || $bytes < 128) break;
		}
		
		return rtrim($return);
	}
	
	public static function tobytes($str){
		// STRINGS ARE EXPECTED TO BE IN ASCII OR UTF-8 FORMAT
		
		// Number of characters in string
		$strlen_var = strlen($str);
		
		// string bytes counter
		$d = 0;
		
		/*
		* Iterate over every character in the string,
		* escaping with a slash or encoding to UTF-8 where necessary
		*/
		for($c = 0; $c < $strlen_var; ++$c){
			$ord_var_c = ord($str{$c});
			switch(true){
				case(($ord_var_c >= 0x20) && ($ord_var_c <= 0x7F)):
					// characters U-00000000 - U-0000007F (same as ASCII)
					$d++;
					break;
				case(($ord_var_c & 0xE0) == 0xC0):
					// characters U-00000080 - U-000007FF, mask 110XXXXX
					$d+=2;
					break;
				case(($ord_var_c & 0xF0) == 0xE0):
					// characters U-00000800 - U-0000FFFF, mask 1110XXXX
					$d+=3;
					break;
				case(($ord_var_c & 0xF8) == 0xF0):
					// characters U-00010000 - U-001FFFFF, mask 11110XXX
					$d+=4;
					break;
				case(($ord_var_c & 0xFC) == 0xF8):
					// characters U-00200000 - U-03FFFFFF, mask 111110XX
					$d+=5;
					break;
				case(($ord_var_c & 0xFE) == 0xFC):
					// characters U-04000000 - U-7FFFFFFF, mask 1111110X
					$d+=6;
					break;
				default:
					$d++;
			};
		};
		return $d;
	}
	
	public function __destruct(){
		if($this->socket) socket_close($this->socket);
	}
	
}
