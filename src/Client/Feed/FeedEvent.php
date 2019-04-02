<?php namespace ellimelon\socket2me\Client\Feed;

use ellimelon\socket2me\SocketException;

class FeedEvent{
	
	private $feed_regex_pattern;
	private $feed_type;
	private $out_lock;
	private $feed_remove=false;
	private $feed_message_split=false;
	private $out_message;
	
	function __construct($feed_regex_pattern,$feed_type){
		$this->setFeedRegexPattern($feed_regex_pattern);
		$this->setFeedType($feed_type);
	}
	
	public function getFeedMessageSplit(){
		return $this->feed_message_split;
	}
	
	public function getFeedType(){
		return $this->feed_type;
	}
	
	public function getFeedRemove(){
		return $this->feed_remove;
	}
	
	public function getFeedRegexPattern(){
		return $this->feed_regex_pattern;
	}
	
	public function getOutLock(){
		return $this->out_lock;
	}
	
	public function getOutMessage(){
		return $this->out_message;
	}
	
	public function setFeedMessageSplit($feed_message_split){
		$this->validateFeedMessageSplit($feed_message_split);
		$this->feed_message_split=$feed_message_split;
	}
	
	public function setFeedRemove($feed_remove){
		$this->validateFeedRemove($feed_remove);
		$this->feed_remove=$feed_remove;
	}
	
	public function setFeedRegexPattern($feed_regex_pattern){
		$this->validateFeedRegexPattern($feed_regex_pattern);
		$this->feed_regex_pattern=$feed_regex_pattern;
	}
	
	public function setFeedType($feed_type){
		$this->validateFeedType($feed_type);
		$this->feed_type=$feed_type;
	}
	
	public function setOutLock($out_lock){
		$this->validateOutLock($out_lock);
		$this->out_lock=$out_lock;
	}
	
	public function setOutMessage($out_message){
		$this->validateOutMessage($out_message);
		$this->out_message=$out_message;
	}
	
	public function validateFeedMessageSplit($feed_message_split){
		// Valid values for Feed Message Split are TRUE or FALSE
		if($feed_message_split!==true && $feed_message_split!==false){
			throw new SocketException('S2M025');
		}
	}
	
	public function validateFeedRemove($feed_remove){
		// Valid values for Feed Remove are TRUE or FALSE
		if($feed_remove!==true && $feed_remove!==false){
			throw new SocketException('S2M023');
		}
	}
	
	public function validateFeedRegexPattern($feed_regex_pattern){
		if(preg_match($feed_regex_pattern,'')===false){
			throw new SocketException('S2M008');
		}
	}
	
	public function validateFeedType($feed_type){
		if($feed_type!=='in' && $feed_type!=='out'){
			throw new SocketException('S2M018');
		}
	}
	
	public function validateOutLock($out_lock){
		// Valid values for Feed Remove are TRUE, FALSE or NULL
		if($out_lock!==true && $out_lock!==false && $out_lock!==null){
			throw new SocketException('S2M017');
		}
	}
	
	public function validateOutMessage($out_message){
		// Valid values for Out Message are NULL or a string
		if($out_message!==null && !is_string($out_message)){
			throw new SocketException('S2M021');
		}
	}
}
?>