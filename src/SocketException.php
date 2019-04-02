<?php namespace ellimelon\socket2me;
class SocketException extends \RuntimeException{
	
	private $s2m_code;
	private $s2m_code_messages=array(
		'S2M001'=>"Client disconnected",
		'S2M002'=>"Invalid Port",
		'S2M003'=>"Failed to create Socket",
		'S2M004'=>"Failed to bind Socket",
		'S2M005'=>"Failed to listen to Socket",
		'S2M006'=>"Feed Retention Period exceeded",
		'S2M007'=>"Invalid IP",
		'S2M008'=>"Invalid Feed Event Regex Pattern",
		'S2M009'=>"Failed to connect Socket",
		'S2M010'=>"Invalid Socket",
		'S2M011'=>"Invalid Feed Event Offset",
		'S2M012'=>"Invalid Feed",
		'S2M013'=>"Invalid Feed Max Length",
		'S2M014'=>"Invalid Retention Period",
		'S2M015'=>"Invalid Feed Event",
		'S2M016'=>"Invalid In Feed Event",
		'S2M017'=>"Invalid Lock",
		'S2M018'=>"Invalid Feed Type",
		'S2M019'=>"Invalid Client",
		'S2M020'=>"Invalid Out Feed Event",
		'S2M021'=>"Invalid Out Message",
		'S2M022'=>"Invalid Feed Events",
		'S2M023'=>"Invalid Feed Removal",
		'S2M024'=>"Invalid Feed Detection",
		'S2M025'=>"Invalid Feed Message Split",
		'S2M026'=>"Invalid In Messages",
		'S2M027'=>"Invalid Message Offset",
		'S2M028'=>"Invalid In Message",
		'S2M029'=>"Invalid Local IP",
		'S2M030'=>"Invalid Client parameters",
		'S2M031'=>"Failed to get Client's Remote IP and Remote Port",
		'S2M032'=>"Invalid Out Messages",
		'S2M033'=>"Invalid Out Message",
		'S2M034'=>"Client does not exist",
		'S2M035'=>"Invalid Offsets",
		'S2M036'=>"Invalid Out Feed",
		'S2M037'=>"Failed to write to Socket",
		'S2M038'=>"Invalid Out Feed Events",
		'S2M039'=>"Invalid Server Clients",
		'S2M040'=>"Invalid Client offset"
	);
	
	function __construct($s2m_code=null,$log=null,$message=null,$code=0,Exception $previous=null) {
        $this->s2m_code=$s2m_code;
		if($message===null && array_key_exists($s2m_code,$this->s2m_code_messages)){
			$message=$this->s2m_code_messages[$s2m_code];
		}
		if($log instanceof \Socket2Me\Log && $message!==null){
			try{
				$log->addLog($message,$s2m_code);
			}
			catch(\RuntimeException $e){}
		}
        parent::__construct($message, $code, $previous);
    }
	
	public function getS2MCode(){
		return $this->s2m_code;
	}
}
?>