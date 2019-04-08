<?php namespace ellimelon\socket2me;
class Connection{
	
	/*
	The purpose of this class is to manage Server/Clients as the same system, you shouldn't be able to tell which you've created! Basically a Client is treated like a server...
	*/
	
	private $client;
	private $ip;
	private $port;
	private $server;
	
	function __construct($port,$ip=null){
		$this->ip=$ip;
		$this->port=$port;
		
		if($this->ip===null){			
			$this->server=new Socket\Server($this->port,$this);
		}
		else{
			$this->client=new Socket\Client($this->ip,$this->port);
		}
	}
	
	public function clientReceive($client_offset=null){
		if($this->server!==null){
			return $this->server->clientReceive($client_offset);
		}
		return $this->client->receive();
	}
	
	public function clientSend($client_offset=null,$data){
		if($this->server!==null){
			return $this->server->clientSend($client_offset,$data);
		}
		return $this->client->send($data);
	}
	
	public function getBlacklist(){
		if($this->server!==null){
			return $this->server->getBlacklist();
		}
		return array();
	}
	
	public function getClientRemoteIP($client_offset=null){
		if($this->server!==null){
			return $this->server->getClientRemoteIP($client_offset);
		}
		return $this->client->getRemoteIP();
	}
	
	public function getClientRemotePort($client_offset){
		if($this->server!==null){
			return $this->server->getClientRemoteIP($client_offset);
		}
		return $this->client->getRemotePort();
	}
	
	public function getClientOffsets(){
		if($this->server!==null){
			return $this->server->getClientOffsets();
		}
		return array(0);
	}
	
	public function getClientsCounts(){
		if($this->server!==null){
			return $this->server->getClientsCount();
		}
		return 1;
	}
	
	public function getWhitelist(){
		if($this->server!==null){
			return $this->server->getWhitelist();
		}
		return array();
	}
	
	public function setBlacklist($blacklist){
		if($this->server!==null){
			$this->server->setBlacklist();
		}
	}
	
	
	
	public function addOutMessage($out_message,$client_offsets=array()){
		if($this->server===true){
			$clients_out_messages=$this->connection->getClientsOutMessages($client_offsets);
			foreach($clients_out_messages as $client_offset=>$client_out_messages){
				array_push($client_out_messages,$out_message);
				$this->connection->setClientsOutMessages($client_out_messages,array($client_offset));
			}
		}
		else{
			$out_messages=$this->connection->getOutMessages();
			array_push($out_messages,$out_message);
			$this->connection->setOutMessages($out_messages);
		}
	}
	
	public function getClients(){
		if($this->server===true){
			return $this->connection->getClientOffsets();
		}
		return array(0);
	}
	
	public function setFeedEvents($feed_events){
		if($this->server===true){
			$this->connection->setClientsFeedEvents($feed_events);
		}
		else{
			$this->connection->setFeedEvents($feed_events);
		}
	}
	
	public function getClientsCount(){
		if($this->server===true){
			return $this->connection->getClientsCount();
		}
		return 0;
	}
	
	public function getClientInMessages($client){
		if($this->server===true){
			return $this->connection->getClientInMessages($client);
		}
		return $this->connection->getInMessages();
	}
	
	public function getInMessages(){
		$in_messages=array();
		if($this->server===false){
			$client_in_messages=$this->connection->getInMessages();
			foreach($client_in_messages as $message_offset=>$message){
				$in_messages[]=array('message'=>$message);
			}
		}
		else{
			$clients_in_messages=$this->connection->getClientsInMessages();
			foreach($clients_in_messages as $client_offset=>$client_in_messages){
				foreach($client_in_messages as $message_offset=>$message){
					$in_messages[]=array('message'=>$message,'client'=>$client_offset);
				}
			}
		}
		
		return $in_messages;
	}
	
	public function getLog(){
		return $this->connection->getLog();
	}
	
	public function getOutMessages(){
		$out_messages=array();
		if($this->server===true){
			$clients_out_messages=$this->connection->getClientsOutMessages();
			foreach($clients_out_messages as $client_offset=>$client_out_messages){
				foreach($client_out_messages as $message_offset=>$message){
					$out_messages[]=array('message'=>$message,'client'=>$client_offset);
				}
			}
		}
		else{
			$client_out_messages=$this->connection->getOutMessages();
			foreach($client_out_messages as $message_offset=>$message){
				$out_messages[]=array('message'=>$message);
			}
		}
		return $out_messages;
	}
	
	public function getServer(){		
		return $this->server;
	}
	
	public function resetClientInMessages($client){
		if($this->server===true){
			return $this->connection->setClientInMessages($client);
		}
		return $this->connection->setInMessages();
	}
	
	public function runListen(){
		
		//Update all Socket Client feeds
		if($this->server===false){
			$this->connection->runClientListen();
		}
		else{
			$this->connection->runClientsListen();
		}
	}
	
	public function runSend(){
		if($this->server===true){
			$this->connection->runClientsSend();
		}
		else{
			$this->connection->runSendOutMessages();
		}
	}
	
	public function setClients(){
		if($this->server===true){
			$this->connection->runClientConnect();
		}
	}
	
	public function setInFeedRetentionPeriod($in_feed_retention_period,$client_offsets=array()){
		if($this->server===true){
			$this->connection->setClientsInFeedRetentionPeriod($in_feed_retention_period,$client_offsets);
		}
		else{
			$this->connection->setInFeedRetentionPeriod($in_feed_retention_period);
		}
	}
	
	public function setInMessages($in_messages){
		//$this->validateInMessages($in_messages);
		if($this->server===true){
			$this->connection->setClientsInMessages($in_messages);
		}
		else{
			$this->connection->setInMessages($in_messages);
		}
	}
	
	public function setOutMessages($out_messages){
		$this->validateOutMessages($out_messages);
		if($this->server===true){
			$clients_out_messages=array();
			foreach($out_messages as $out_message_offset=>$out_message_array){
				$clients_out_messages[]=$out_message_array['message'];
			}
		}
		else{
			$this->connection->setOutMessages($out_messages);
		}
	}
	
	public function validateOutMessages($out_messages){
		if(!is_array($out_messages)){
			throw new SocketException('S2M032');
		}
	}
}
?>