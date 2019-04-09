<?php namespace ellimelon\socket2me\Socket;

use ellimelon\socket2me\Log;

class Client extends Socket{
	
	private $last_received;
	private $last_sent;
	private $local=false;
	private $received='';
	private $remote_address;
	private $remote_port;
	
	public function __construct($remote_address=null,$remote_port=null,$socket=null){
		$this->log=new Log();
		
		if($socket===null && ($remote_port===null || $remote_address===null)){
			throw new \InvalidArgumentException("Insufficient arguments provided");
		}
		
		if($socket===null){
			$this->setRemoteAddress($remote_address);
			$this->setRemotePort($remote_port);
			
			$socket=$this->createSocket();
			
			if(socket_connect($socket,$this->remote_address,$this->remote_port)===false){
				throw new \RuntimeException("Failed to connect Socket");
			}
			
			$this->local=true;
		}
		
		parent::__construct($socket);
		
		if($this->remote_address===null || $this->remote_port===null){
			if(socket_getpeername($this->getSocket(),$remote_address,$remote_port)===false){
				throw new \RuntimeException("Failed to get Remote Address and Port");
			}
			$this->setRemoteAddress($remote_address);
			$this->setRemotePort($remote_port);
		}
		
		if(socket_getsockname($this->getSocket(),$local_address,$local_port)===false){
			throw new \RuntimeException("Failed to get Local Address and Port");
		}
		$this->setLocalAddress($local_address);
		$this->setLocalPort($local_port);
	}
	
	public function getLocal(){
		return $this->local;
	}
	
	public function getLog(){
		return $this->log;
	}
	
	public function getReceived(){
		return $this->received;
	}
	
	public function getRemoteAddress(){
		return $this->remote_address;
	}
	
	public function getRemotePort(){
		return $this->remote_port;
	}
	
	public function receive(){
		// Check the Socket for new data
		$socket=array($this->getSocket());
		$write=null;
		$except=null;
		socket_select($socket,$write,$except,0);
		
		// If there's new data
		if(count($socket)>0){
			socket_recv($this->getSocket(),$socket_received,4096,MSG_DONTWAIT);
			
			// If the Socket has disconnected, throw an exception
			if(!is_string($socket_received)){
				//log replace exception
				throw new \RuntimeException("Client disconnected");
			}
			
			$this->last_received= new \DateTime();
			$this->setReceived($this->getReceived().$socket_received);
		}
		
		return $this->getReceived();
	}
	
	public function resetReceived(){
		$this->setReceived('');
	}
	
	public function send($data){
		if(!is_string($data)){
			throw new \InvalidArgumentException("Invalid sending data");
		}
		
		$bytes_sent=socket_write($this->getSocket(),$data);
		
		if($bytes_sent===false){
			throw new \RuntimeException("Failed to write to Socket");
		}
		
		$this->last_sent=new \DateTime();
		
		// If all the data has not been sent, send the remaining data
		if(strlen($data)!==$bytes_sent){
			$this->send(substr($data,$bytes_sent));
		}
	}
	
	public function validateReceived($received){
		if(!is_string($received)){
			return false;
		}
		return true;
	}
	
	private function setReceived($received){
		if($this->validateReceived($received)===false){
			throw new \InvalidArgumentException("Cannot set Received, invalid Received");
		}
		$this->received=$received;
	}
	
	private function setRemoteAddress($remote_address){
		$this->validateAddress($remote_address);
		$this->remote_address=$remote_address;
	}
	
	private function setRemotePort($remote_port){
		$this->validatePort($remote_port);
		$this->remote_port=$remote_port;
	}
}
?>