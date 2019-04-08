<?php namespace ellimelon\socket2me\Socket;

class Socket{
	
	private $created;
	private $local_address;
	private $local_port;
	protected $log;
	private $socket;
	
	public function __construct($socket){
		$this->created=new \DateTime();
		$this->setSocket($socket);
	}
	
	public function __destruct(){
		socket_close($this->socket);
	}
	
	public function getCreated(){
		return $this->created;
	}
	
	public function getLog(){
		return $this->log;
	}
	
	public function getLocalAddress(){
		return $this->local_address;
	}
	
	public function getLocalPort(){
		return $this->local_port;
	}
	
	protected function getSocket(){
		return $this->socket;
	}
	
	public function setLocalAddress($local_address){
		$this->validateAddress($local_address);
		$this->local_address=$local_address;
	}
	
	public function setLocalPort($local_port){
		$this->validatePort($local_port);
		$this->local_port=$local_port;
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
	
	public function validateAddress($address){
		if(!is_string($address)){
			throw new \InvalidArgumentException("Invalid Address");
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