<?php namespace ellimelon\socket2me;
class Log{
	private $logs=array();
	
	public function addLog($message,$code=null){
		$log=array('message'=>$message);
		if($code!==null){
			$log['code']=$code;
		}
		$log['time']=new \DateTime();
		$this->validateLog($log);
		$this->logs[]=$log;
	}
	
	public function getLogs(){
		return $this->logs;
	}
	
	public function resetLogs(){
		$this->logs=array();
	}
	
	public function validateLog($log){
		/*
			A log must have the key 'message', with a string value
			If the log has the key 'code' set, its value must be a string
			A log must have the key 'time', with a DateTime value
		*/
		if(!is_array($log) || !array_key_exists('message',$log) || !is_string($log['message']) || (array_key_exists('code',$log) && !is_string($log['code'])) || !array_key_exists('time',$log) || !($log['time'] instanceof \DateTime)){
			throw new \RuntimeException("Invalid Log");
		}
	}
}
?>