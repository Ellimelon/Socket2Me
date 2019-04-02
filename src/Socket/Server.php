<?php namespace ellimelon\socket2me\Socket;

use ellimelon\socket2me\SocketException;
use ellimelon\socket2me\Socket\Client;
use ellimelon\socket2me\Log;
use ellimelon\socket2me\Socket\FeedEvent;

class Server extends Socket{
	
	private $clients=array();
	private $clients_feed_events=array();
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
	
	public function getClientOffsets(){
		return array_keys($this->clients);
	}
	
	public function getClientInMessages($client_offset){
		$this->validateCurrentClientOffset($client_offset);
		return $this->clients[$client_offset]->getInMessages();
	}
	
	public function getClientOutMessages($client_offset){
		$this->validateCurrentClientOffset($client_offset);
		return $this->clients[$client_offset]->getOutMessages();
	}
	
	public function getClientsCount(){
		return count($this->clients);
	}
	
	public function getClientsFeedEvents(){
		return $this->clients_feed_events;
	}
	
	public function getClientsFeeds(){
		$clients_feeds=array();
		foreach($this->clients as $client_offset=>$client){
			$clients_feeds[$client_offset]=$client->getInFeed();
		}
		
		return $clients_feeds;
	}
	
	public function getClientsInMessages(){
		$clients_in_messages=array();
		foreach($this->clients as $client_offset=>$client){
			$clients_in_messages[$client_offset]=$client->getInMessages();
		}
		return $clients_in_messages;
	}
	
	public function getClientsOutMessages($client_offsets=array()){
		$this->validateCurrentClientOffsets($client_offsets);
		
		// If the Client's Offsets haven't been defined, grab all of them
		if(count($client_offsets)===0){
			$client_offsets=array_keys($this->clients);
		}
		
		$clients_out_messages=array();
		foreach($client_offsets as $client_offset){
			$clients_out_messages[$client_offset]=$this->clients[$client_offset]->getOutMessages();
		}
		return $clients_out_messages;
	}
	
	public function getLocalPort(){
		return $this->local_port;
	}
	
/*	public function setClientsFeeds(){
		foreach($this->clients as $client_offset=>$client){
			$this->clients[$client_offset]->socketListen();
		}
	}*/
	
	public function runClientConnect(){
		$socket=array($this->socket);
		
		socket_select($socket,$write=null,$except=null,0);
		
		if(in_array($this->socket,$socket)){
			try{
				// Create a new Client
				$client=new \Socket2Me\Client\Client(null,null,socket_accept($this->socket));
				$client_feed_events=array();
				foreach($this->clients_feed_events as $clients_feed_event_offset=>$clients_feed_event){
					$client_feed_events[$clients_feed_event_offset]=clone $clients_feed_event;
				}
				$client->setFeedEvents($client_feed_events);
			}
			catch(SocketException $client_exception){
				throw new \RuntimeException("Failed to create Client");
			}
			$clients=$this->clients;
			array_push($clients,$client);
			$this->setClients($clients);
		}
	}
	
	public function runClientsListen(){
		foreach($this->clients as $client_offset=>$client){
			try{
				$client->runClientListen();
			}
			catch(SocketException $e){var_dump($e->getMessage());
				$clients=$this->clients;
				unset($clients[$client_offset]);
				$this->setClients($clients);
			}
		}
	}
	
	public function runClientsSend(){
		foreach($this->clients as $client_offset=>$client){
			try{
				$client->runSendOutMessages();
			}
			catch(SocketException $e){
				$clients=$this->clients;
				unset($clients[$client_offset]);
				$this->setClients($clients);
			}
		}
	}
	
	public function setClientInMessages($client_offset,$in_messages=array()){
		$this->validateCurrentClientOffset($client_offset);
		return $this->clients[$client_offset]->setInMessages($in_messages);
	}
	
	private function setClients($clients){
		$this->validateClients($clients);
		$this->clients=$clients;
	}
	
	public function setClientsFeedEvents($clients_feed_events){
		$this->validateClientsFeedEvents($clients_feed_events);
		$this->clients_feed_events=$clients_feed_events;
	}
	
	public function setClientsInFeedRetentionPeriod($in_feed_retention_period,$client_offsets=array()){
		// If the Client's Offsets haven't been defined, set to all Client's Offsets
		if(count($client_offsets)===0){
			$client_offsets=array_keys($this->clients);
		}
		$this->validateCurrentClientOffsets($client_offsets);
		foreach($client_offsets as $client_offset){
			$this->clients[$client_offset]->setInFeedRetentionPeriod($in_feed_retention_period);
		}
	}
	
	public function setClientsInMessages($in_messages,$client_offsets=array()){
		// If the Client's Offsets haven't been defined, set to all Client's Offsets
		if(count($client_offsets)===0){
			$client_offsets=array_keys($this->clients);
		}
		$this->validateCurrentClientOffsets($client_offsets);
		foreach($client_offsets as $client_offset){
			$this->clients[$client_offset]->setInMessages($in_messages);
		}
	}
	
	public function setClientsOutMessages($out_messages,$client_offsets=array()){
		// If the Client's Offsets haven't been defined, set to all Client's Offsets
		if(count($client_offsets)===0){
			$client_offsets=array_keys($this->clients);
		}
		$this->validateCurrentClientOffsets($client_offsets);
		foreach($client_offsets as $client_offset){
			$this->clients[$client_offset]->setOutMessages($out_messages);
		}
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
		// Valid values for Feed Event's Offset are Whole Numbers
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
	
	public function validateCurrentClientOffsets($client_offsets){
		if(!is_array($client_offsets)){
			throw new \InvalidArgumentException("Invalid Client Offset");
		}
		foreach($client_offsets as $client_offset){
			$this->validateCurrentClientOffset($client_offset);
		}
	}
}
?>