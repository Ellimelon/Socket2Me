<?php namespace ellimelon\socket2me\Socket;

class Socket{
	
	private $created;
	private $local_ip;
	protected $socket;
	
	public function __construct($socket){
		$this->setSocket($socket);
	}
	
	public function __destruct(){
		socket_close($this->socket);
	}
	
	public function getCreated(){
		return $this->created;
	}
	
	/*CLIENT
	function __construct($remote_ip=null,$remote_port=null,$socket=null){
		
		if($socket===null){
			$this->setRemoteIP($remote_ip);
			$this->setRemotePort($remote_port);
			
			if(($socket=socket_create(AF_INET,SOCK_STREAM,SOL_TCP))===false){
				throw new SocketException('S2M003');
			}
			
			if(socket_connect($socket,$this->remote_ip,$this->remote_port)===false){
				throw new SocketException('S2M009');
			}
			
			$this->local=true;
		}
	//	$this->setSocket($socket);
		
		if($this->remote_ip===null || $this->remote_port===null){
			if(socket_getpeername($this->socket,$remote_ip,$remote_port)===false){
				throw new SocketException('S2M031');
			}
			$this->setRemoteIP($remote_ip);
			$this->setRemotePort($remote_port);
		}
	}
	
	SERVER
	function __construct($local_port){
		$this->local_port=$local_port;
		
		if(!is_int($this->local_port)){
			throw new RuntimeException('Invalid Port');
		}
		
		if(($socket=socket_create(AF_INET,SOCK_STREAM,SOL_TCP))===false){
			throw new SocketException('S2M003');
		}
		
		socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
		
		if(socket_bind($socket,0,$this->local_port)===false){
			throw new SocketException('S2M004');
		}
		
		if(socket_listen($socket)===false){
			throw new SocketException('S2M005');
		}
		
	//	$this->socket=$socket;
	}
	*/
	
	public function getLocalIP(){
		return $this->local_ip;
	}
	
	public function setLocalIP($local_ip){
		$this->validateIP($local_ip);
		$this->local_ip=$local_ip;
	}
	
	public function validateFeedEvent($feed_event){
		if(!($feed_event instanceof \socket2me\Socket\Feed\FeedEvent)){
			throw new \InvalidArgumentException("Invalid Feed Event");
		}
	}
	
	public function validateFeedEvents($feed_events){
		if(!is_array($feed_events)){
			throw new \InvalidArgumentException("Invalid Feed Events");
		}
		foreach($feed_events as $feed_event_offset=>$feed_event){
			$this->validateFeedEventOffset($feed_event_offset);
			$this->validateFeedEvent($feed_event);
		}
	}
	
	public function validateFeedEventOffset($feed_event_offset){
		// Valid values for Feed Event's Offset are Whole Numbers
		if(!is_int($feed_event_offset) || $feed_event_offset < 0 || $feed_event_offset!==(int)round($feed_event_offset)){
			throw new \InvalidArgumentException("Invalid Feed Event Offset");
		}
	}
	
	public function validateIP($ip){
		if(!is_string($ip)){
			throw new \InvalidArgumentException("Invalid IP");
		}
	}
	
	public function validatePort($port){
		if(!is_int($port)){
			throw new \InvalidArgumentException("Invalid Port");
		}
	}
	
	public function validateSocket($socket){
		if(get_resource_type($socket)!=='Socket'){
			throw new \InvalidArgumentException("Invalid Socket");
		}
	}
	
	protected function createSocket(){
		if(($socket=socket_create(AF_INET,SOCK_STREAM,SOL_TCP))===false){
			throw new \RuntimeException("Failed to create Socket");
		}
		return $socket;
	}
	
	private function setSocket($socket){
		$this->validateSocket($socket);
		$this->socket=$socket;
	}
}