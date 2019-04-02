<?php namespace ellimelon\socket2me\Socket;

use ellimelon\socket2me\SocketException;
use ellimelon\socket2me\Log;

class Client extends Socket{
	
	private $in_feed='';
	private $feed_events=array();
	private $in_feed_lock=false;
	private $in_feed_max_length;
	private $in_feed_retention_period;
	private $in_feed_time;
	private $in_messages=array();
	private $local=false;
	private $log;
	private $message_end;
	private $out_feed='';
	private $out_feed_lock=false;
	private $out_lock_retention_period;
	private $out_lock_time;
	private $out_messages=array();
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
	
	public function getInFeed(){
		return $this->in_feed;
	}
	
	public function getFeedEvent($feed_event_offset){
		// Check the Feed Event Offset exists
		if(!array_key_exists($feed_event_offset,$this->feed_events)){
			throw new SocketException('S2M011',$this->log);
		}
		return $this->feed_events[$feed_event_offset];
	}
	
	public function getFeedEvents(){
		return $this->feed_events;
	}
	
	public function getInFeedLock(){
		return $this->in_feed_lock;
	}
	
	public function getInFeedMaxLength(){
		return $this->in_feed_max_length;
	}
	
	public function getInFeedRetentionPeriod(){
		return $this->in_feed_retention_period;
	}
	
	public function getInFeedTime(){
		return $this->in_feed_time;
	}
	
	public function getInMessages(){
		return $this->in_messages;
	}
	
	public function getLocal(){
		return $this->local;
	}
	
	public function getLog(){
		return $this->log;
	}
	
	public function getOutFeed(){
		return $this->out_feed;
	}
	
	public function getOutFeedLock(){
		return $this->out_feed_lock;
	}
	
	public function getOutLockRetentionPeriod(){
		return $this->out_lock_retention_period;
	}
	
	public function getOutLockTime(){
		return $this->out_lock_time;
	}
	
	public function getOutMessages(){
		return $this->out_messages;
	}
	
	public function getRemoteIP(){
		return $this->remote_ip;
	}
	
	public function getRemotePort(){
		return $this->remote_port;
	}
	
	public function runClientListen(){
		$this->socketListen();
	}
	
	public function runSendOutMessages(){
		while($this->out_feed_lock===false && count($this->out_messages)>0){
			// Select the message at the front of the queue
			$out_messages=$this->out_messages;
			$out_message=array_shift($out_messages);
			$this->setOutMessages($out_messages);
			
			while(strlen($out_message)>0){
				// If the Out Feed Lock is set, stop adding to the Out Feed
				if($this->out_feed_lock===true){
					array_unshift($out_messages,$out_message);
					$this->setOutMessages($out_messages);
					break;
				}
				
				$this->setOutFeed($this->out_feed.substr($out_message,0,1));
				$out_message=substr($out_message,1);
			}
			
			// If there's data in the Out Feed, send it
			if($this->out_feed!==''){
				$this->runSendOutFeed();
			}
		}
	}
	
	public function setFeedEvents($feed_events){
		$this->validateFeedEvents($feed_events);
		$this->feed_events=$feed_events;
	}
	
	public function setInFeedLock($in_feed_lock){
		$this->validateInFeedLock($in_feed_lock);
		$this->in_feed_lock=$in_feed_lock;
	}
	
	public function setInFeedMaxLength($in_feed_max_length){
		$this->validateInFeedMaxLength($in_feed_max_length);
		$this->in_feed_max_length=$in_feed_max_length;
	}
	
	public function setInFeedRetentionPeriod($in_feed_retention_period){
		$this->validateInFeedRetentionPeriod($in_feed_retention_period);
		$this->in_feed_retention_period=$in_feed_retention_period;
	}
	
	public function setInMessages($in_messages=array()){
		$this->validateInMessages($in_messages);
		$this->in_messages=$in_messages;
	}
	
	public function setOutFeedLock($out_feed_lock){
		$this->validateOutFeedLock($out_feed_lock);
		$this->out_feed_lock=$out_feed_lock;
		$this->out_lock_time=new \DateTime();
	}
	
	public function setOutLockRetentionPeriod($out_lock_retention_period){
		$this->validateOutLockRetentionPeriod($out_lock_retention_period);
		$this->out_lock_retention_period=$out_lock_retention_period;
	}
	
	public function setOutMessages($out_messages){
		$this->validateOutMessages($out_messages);
		$this->out_messages=$out_messages;
	}
	
	public function setRemoteIP($remote_ip){
		$this->validateIP($remote_ip);
		$this->remote_ip=$remote_ip;
	}
	
	public function setRemotePort($remote_port){
		$this->validatePort($remote_port);
		$this->remote_port=$remote_port;
	}
	
	public function socketSend($data){
		$bytes_sent=socket_write($this->socket,$data);
		
		if($bytes_sent===false){
			throw new SocketException('S2M037',$this->log);
		}
		
		// If all the data has not been sent, send the remaining data
		if(strlen($data)!==$bytes_sent){
			$this->socketSend(substr($data,$bytes_sent));
		}
	}
	
	public function validateCurrentInFeed(){
		// If the In Feed's Retention Period is set, and the In Feed contains old data
		if($this->in_feed_retention_period!==null && $this->in_feed!==''){
			// If it's been longer than the In Feed Retention Period since the In Feed was updated with data, reset it
			$in_feed_cutoff_time=new \DateTime();
			$in_feed_cutoff_time->sub(new \DateInterval('PT'.$this->in_feed_retention_period.'S'));
			if($this->in_feed_time<$in_feed_cutoff_time){
				throw new SocketException('S2M006',$this->log);
			}
		}
	}
	
	public function validateFeedEvent($feed_event){
		if(!($feed_event instanceof Feed\FeedEvent)){
			throw new SocketException('S2M015',$this->log);
		}
	}
	
	public function validateFeedEventOffset($feed_event_offset){
		// Valid values for Feed Event's Offset are Whole Numbers
		if(!is_int($feed_event_offset) || $feed_event_offset < 0 || $feed_event_offset!==(int)round($feed_event_offset)){
			throw new SocketException('S2M011',$this->log);
		}
	}
	
	public function validateInFeed($in_feed){
		// Valid values for the In Feed are strings, that are shorter than the In Feed's Max Length
		if(!is_string($in_feed) || ($this->in_feed_max_length!==null && strlen($in_feed)>$this->in_feed_max_length)){
			throw new SocketException('S2M012',$this->log);
		}
	}
	
	public function validateFeedEvents($feed_events){
		if(!is_array($feed_events)){
			throw new SocketException('S2M022',$this->log);
		}
		foreach($feed_events as $feed_event_offset=>$feed_event){
			$this->validateFeedEventOffset($feed_event_offset);
			$this->validateFeedEvent($feed_event);
		}
	}
	
	public function validateInFeedLock($in_feed_lock){
		// Valid values for the In Feed Lock are TRUE or FALSE
		if($in_feed_lock!==true && $in_feed_lock!==false){
			throw new SocketException('S2M017',$this->log);
		}
	}
	
	public function validateInFeedMaxLength($in_feed_max_length){
		// Valid values for the In Feed's Max Length are NULL, or Natural Numbers
		if($in_feed_max_length!==null && (!is_int($in_feed_max_length) || $in_feed_max_length < 1 || $in_feed_max_length!==(int)round($in_feed_max_length))){
			throw new SocketException('S2M013',$this->log);
		}
	}
	
	public function validateInFeedRetentionPeriod($in_feed_retention_period){
		// Valid values for In Feed's Retention Period are NULL, or Natural Numbers
		if($in_feed_retention_period!==null && (!is_int($in_feed_retention_period) || $in_feed_retention_period < 1 || $in_feed_retention_period!==(int)round($in_feed_retention_period))){
			throw new SocketException('S2M014',$this->log);
		}
	}
	
	public function validateInMessage($in_message){
		if(!is_string($in_message)){
			throw new SocketException('S2M028',$this->log);
		}
	}
	
	public function validateInMessages($in_messages){
		if(!is_array($in_messages)){
			throw new SocketException('S2M026',$this->log);
		}
		foreach($in_messages as $in_message_offset=>$in_message){
			$this->validateMessageOffset($in_message_offset);
			$this->validateInMessage($in_message);
		}
	}
	
	public function validateMessageOffset($message_offset){
		// Valid values for Message's Offset are Whole Numbers
		if(!is_int($message_offset) || $message_offset < 0 || $message_offset!==(int)round($message_offset)){
			throw new SocketException('S2M011',$this->log);
		}
	}
	
	public function validateOutFeed($out_feed){
		if(!is_string($out_feed)){
			throw new SocketException('S2M012',$this->log);
		}
	}
	
	public function validateOutFeedLock($out_feed_lock){
		if($out_feed_lock!==true && $out_feed_lock!==false){
			throw new SocketException('S2M017',$this->log);
		}
	}
	
	public function validateOutLockRetentionPeriod($out_lock_retention_period){
		// Valid values for Out Lock's Retention Period are NULL, or Natural Numbers
		if($out_lock_retention_period!==null && (!is_int($out_lock_retention_period) || $out_lock_retention_period < 1 || $out_lock_retention_period!==round($out_lock_retention_period))){
			throw new SocketException('S2M014',$this->log);
		}
	}
	
	public function validateOutMessages($out_messages){
		if(!is_array($out_messages)){
			throw new SocketException('S2M032',$this->log);
		}
		foreach($out_messages as $out_message_offset=>$out_message){
			$this->validateMessageOffset($out_message_offset);
			$this->validateOutMessage($out_message);
		}
	}
	
	public function validateOutMessage($out_message){
		if(!is_string($out_message)){
			throw new SocketException('S2M033',$this->log);
		}
	}
	
	private function runFeedEvents($feed_type){
		if($feed_type==='in'){
			$feed='in_feed';
		}
		elseif($feed_type==='out'){
			$feed='out_feed';
		}
		else{
			throw new \RuntimeException("Invalid Parameter");
		}
		foreach($this->feed_events as $feed_event_offset=>$feed_event){
			
			if($feed_event->getFeedType()===$feed_type){
				if(preg_match($feed_event->getFeedRegexPattern(),$this->{$feed},$feed_pattern_matches,PREG_OFFSET_CAPTURE)===false){
					throw new \RuntimeException("Regex matching failed");
				}
				
				// If there's been a match, take the first matches details
				if(isset($feed_pattern_matches[0])){
					$matching_string=$feed_pattern_matches[0][0];
					$matching_string_offset=$feed_pattern_matches[0][1];
					$matching_string_end_offset=$matching_string_offset+strlen($matching_string);
					
					// If the Feed Event affects the Out Feed Lock, set the Out Feed Lock
					if($feed_event->getOutLock()!==null){
						$this->setOutFeedLock($feed_event->getOutLock());
					}
					
					// If the Feed Event contains an Out Message, set the next Out Message
					if($feed_event->getOutMessage()!==null){
						$out_messages=$this->out_messages;
						array_unshift($out_messages,$feed_event->getOutMessage());
						$this->setOutMessages($out_messages);
					}
					
					// If the Feed Event removes the matching string, remove it
					if($feed_event->getFeedRemove()===true){
						$feed_pre_match=substr($this->{$feed},0,$matching_string_offset);
						
						// PHP < 7.0 Compatibility fix
						if(strlen($this->{$feed})===$matching_string_end_offset){
							$feed_post_match='';
						}
						else{
							$feed_post_match=substr($this->{$feed},$matching_string_end_offset);
						}
						
						$new_feed=$feed_pre_match.$feed_post_match;
						
						if($feed_type==='in'){
							$this->setInFeed($new_feed);
						}
						else{
							$this->setOutFeed($new_feed);
						}
						$matching_string_end_offset=$matching_string_offset;
					}
					
					// If the Feed Event splits the In Feed into In Messages, 
					if($feed_event->getFeedMessageSplit()===true){
						$new_in_messages=$this->in_messages;
						array_push($new_in_messages,substr($this->{$feed},0,$matching_string_end_offset));
						$this->setInMessages($new_in_messages);
						
						// PHP < 7.0 Compatibility fix
						if(strlen($this->{$feed})===$matching_string_end_offset){
							$new_feed='';
						}
						else{
							$new_feed=substr($this->{$feed},$matching_string_end_offset);
						}
						
						$this->setInFeed($new_feed);
					}
				}
			}
		}
	}
	
	private function runSendOutFeed(){
		$this->socketSend($this->out_feed);
		$this->setOutFeed('');
	}
	
	private function setInFeed($in_feed){
		$this->validateInFeed($in_feed);
		$this->in_feed=$in_feed;
		$this->in_feed_time=new \DateTime();
		$this->runFeedEvents('in');
	}
	
	private function setOutFeed($out_feed){
		$this->validateOutFeed($out_feed);
		$this->out_feed=$out_feed;
		$this->runFeedEvents('out');
	}
	
	private function socketListen(){
		// Check the current In Feed, and if invalid, reset the In Feed
		try{
			$this->validateCurrentInFeed();
		}
		catch(SocketException $e){
			$this->setInFeed('');
		}
		
		// Check the Socket for new data
		$socket=array($this->socket);
		socket_select($socket,$write=null,$except=null,0);
		
		// If there's new data
		if(count($socket)>0){
			socket_recv($this->socket,$socket_received,4096,MSG_DONTWAIT);
			
			// If the Socket has disconnected, throw an exception
			if(!is_string($socket_received)){
				throw new SocketException('S2M001',$this->log);
			}
			
			$new_in_feed=$this->in_feed.$socket_received;
			// Check the new In Feed is still valid
			try{
				$this->validateInFeed($new_in_feed);
			}
			catch(SocketException $set_in_feed_exception){
				// If the new In Feed is invalid, reset it
				$new_in_feed='';
			}
			$this->setInFeed($new_in_feed);
		}
	}
}
?>