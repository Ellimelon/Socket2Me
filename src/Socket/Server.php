<?php namespace ellimelon\socket2me\Socket;

use ellimelon\socket2me\SocketException;
use ellimelon\socket2me\Socket\Client;
use ellimelon\socket2me\Log;

class Server extends Socket{
	
	private $clients=array();
	private $local_port;
	
	public function __construct($local_port){
		
		$this->setLocalPort($local_port);
		
		$socket=$this->createSocket();
		
		socket_set_option($socket, SOL_SOCKET, SO_REUSEADDR, 1);
		
		if(socket_bind($socket,0,$this->local_port)===false){
			throw RuntimeException("Failed to bind Socket");
		}
		
		if(socket_listen($socket)===false){
			throw new RuntimeException("Failed to listen to Socket");
		}
		
		parent::__construct($socket);
	}
	
	public function clientReceive($client_offset){
		$this->validateCurrentClientOffset($client_offset);
		return $this->clients[$client_offset]->receive();
	}
	
	public function clientSend($client_offset,$data){
		$this->validateCurrentClientOffset($client_offset);
		return $this->clients[$client_offset]->send($data);
	}
	
	public function getClientRemoteIP($client_offset){
		$this->validateCurrentClientOffset($client_offset);
		return $this->clients[$client_offset]->getRemoteIP();
	}
	
	public function getClientRemotePort($client_offset){
		$this->validateCurrentClientOffset($client_offset);
		return $this->clients[$client_offset]->getRemotePort();
	}
	
	public function getClientOffsets(){
		return array_keys($this->clients);
	}
	
	public function getClientsCount(){
		return count($this->clients);
	}
	
	public function getLocalPort(){
		return $this->local_port;
	}
	
	public function checkForNewClients(){
		$socket=array($this->socket);
		
		socket_select($socket,$write=null,$except=null,0);
		
		if(in_array($this->socket,$socket)){
			$client=new \ellimelon\socket2me\Socket\Client(null,null,socket_accept($this->socket));
			$clients=$this->clients;
			array_push($clients,$client);
			$this->setClients($clients);
		}
	}
	
	private function setClients($clients){
		$this->validateClients($clients);
		$this->clients=$clients;
	}
	
	public function setLocalPort($local_port){
		$this->validatePort($local_port);
		$this->local_port=$local_port;
	}
	
	public function validateClient($client){
		if(!($client instanceof \socket2me\Socket\Client)){
			throw new \InvalidArgumentException("Invalid Client");
		}
	}
	
	public function validateClientOffset($clients_offset){
		// Valid values for a Client's Offest are Whole Numbers
		if(!is_int($clients_offset) || $clients_offset < 0 || $clients_offset!==(int)round($clients_offset)){
			throw new \InvalidArgumentException("Invalid Client Offset");
		}
	}
	
	public function validateClients($clients){
		if(!is_array($clients)){
			throw new \InvalidArgumentException("Invalid Clients");
		}
		foreach($clients as $clients_offset=>$client){
			$this->validateClientOffset($clients_offset);
			$this->validateClient($client);
		}
	}
	
	public function validateCurrentClientOffset($client_offset){
		if(!array_key_exists($client_offset,$this->clients)){
			throw new \InvalidArgumentException ("Invalid Client Offset");
		}
	}
}
?>