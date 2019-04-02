<?php namespace ellimelon\socket2me;
class Connection{
	
	private $clients=array();
	private $connection;
	private $ip;
	private $port;
	private $server;
	
	function __construct($port,$ip=null){
		$this->ip=$ip;
		$this->port=$port;
		$this->server=false;
		
		if($this->ip===null){
			$this->server=true;
			
			if(array_key_exists('SERVER_ADDR',$_SERVER)){
				$this->ip=$_SERVER['SERVER_ADDR'];
			}
			$this->connection=new Server\Server($this->port,$this);
		}
		else{
			$this->connection=new Client\Client($this->ip,$this->port);
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
			$client=$this->connection->getClient($client);
		}
		else{
			$client=$this->connection;
		}
		return $client->getInMessages();
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
			$client=$this->connection->getClient($client);
		}
		else{
			$client=$this->connection;
		}
		$client->setInMessages(array());
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
	
	
	
	
	public function clientListen($client){
		$output=array('_ERROR_FATAL_'=>array(),'_ERROR_NON_FATAL_'=>array(),'_RESULT_'=>null);
		
		$socket_client=array($client);
		
		socket_select($socket_client,$write=NULL,$except=NULL,0);
		
		if(count($socket_client)>0){
			$socket_client=$socket_client[0];
			
			socket_recv($socket_client,$socket_data,4096,MSG_DONTWAIT);
			
			if($socket_data===null){
				$output['_ERROR_FATAL_'][]='Failed to listen to Server, connection lost';
				return $output;
			}
			elseif($socket_data!=''){
				$output['_RESULT_']=$socket_data;
			}
		}
		
		return $output;
	}
	
	public function serverListenClient($client){
		$output=array('_ERROR_FATAL_'=>array(),'_ERROR_NON_FATAL_'=>array(),'_RESULT_'=>null);
				
		$socket_client=array($client);
		
		socket_select($socket_client,$write=NULL,$except=NULL,0);
		
		if(count($socket_client)>0){
			$socket_client=$socket_client[0];
			
			socket_recv($socket_client,$socket_data,4096,MSG_DONTWAIT);
			
			if($socket_data===null){
				$output['_ERROR_FATAL_'][]='Failed to listen to Client, connection lost';
				return $output;
			}
			elseif($socket_data!=''){
				$output['_RESULT_']=$socket_data;
			}
		}
		
		return $output;
	}
	
	public function socketSend($message,$socket){
		$output=array('_ERROR_FATAL_'=>array(),'_ERROR_NON_FATAL_'=>array(),'_RESULT_'=>null);
		
		if(socket_write($socket,$message)===false){
			$output['_ERROR_FATAL_'][]='Failed to send message via Socket, socket write failed: '.socket_strerror(socket_last_error($sockets));
			return $output;
		}
		
		return $output;
	}
	
	public function closeServer($server){
		socket_close($server);
	}
}
?>