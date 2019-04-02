<?php namespace ellimelon\socket2me\Socket;

use ellimelon\socket2me\SocketException;
use ellimelon\socket2me\Log;

class Client extends Socket{
	
	private $last_received;
	private $last_sent;
	private $local=false;
	private $log;
	private $remote_ip;
	private $remote_port;
	private $socket;
	
	public function __construct($remote_ip=null,$remote_port=null,$socket=null){
		$this->log=new Log();
		
		if($socket===null && ($remote_port===null || $remote_ip===null)){
			throw new \InvalidArgumentException("Insufficient arguments provided");
		}
		
		if($socket===null){
			$this->setRemoteIP($remote_ip);
			$this->setRemotePort($remote_port);
			
			$socket=$this->createSocket();
			
			if(socket_connect($socket,$this->remote_ip,$this->remote_port)===false){
				throw new \RuntimeException("Failed to connect Socket");
			}
			
			$this->local=true;
		}
		
		parent::__construct($socket);
		
		if($this->remote_ip===null || $this->remote_port===null){
			if(socket_getpeername($this->socket,$remote_ip,$remote_port)===false){
				throw new RuntimeException("Failed to get Remote IP and Port");
			}
			$this->setRemoteIP($remote_ip);
			$this->setRemotePort($remote_port);
		}
	}
	
	public function getLocal(){
		return $this->local;
	}
	
	public function getLog(){
		return $this->log;
	}
	
	public function getRemoteIP(){
		return $this->remote_ip;
	}
	
	public function getRemotePort(){
		return $this->remote_port;
	}
	
	public function receive(){
		// Check the Socket for new data
		$socket=array($this->socket);
		socket_select($socket,$write=null,$except=null,0);
		
		// If there's new data
		if(count($socket)>0){
			socket_recv($this->socket,$socket_received,4096,MSG_DONTWAIT);
			
			// If the Socket has disconnected, throw an exception
			if(!is_string($socket_received)){
				//log replace exception
				throw new SocketException('S2M001',$this->log);
			}
			
			$this->last_received= new \DateTime();
			
			return $socket_received;
		}
		
		return '';
	}
	
	public function send($data){
		if(!is_string($data)){
			throw new \InvalidArgumentException("Invalid sending data");
		}
		
		$bytes_sent=socket_write($this->socket,$data);
		
		if($bytes_sent===false){
			throw new \RuntimeException("Failed to write to Socket");
		}
		
		$this->last_sent=new DateTime();
		
		// If all the data has not been sent, send the remaining data
		if(strlen($data)!==$bytes_sent){
			$this->send(substr($data,$bytes_sent));
		}
	}		
	
	public function setRemoteIP($remote_ip){
		$this->validateIP($remote_ip);
		$this->remote_ip=$remote_ip;
	}
	
	public function setRemotePort($remote_port){
		$this->validatePort($remote_port);
		$this->remote_port=$remote_port;
	}
}
?>