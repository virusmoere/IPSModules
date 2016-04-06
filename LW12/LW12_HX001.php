<?

class LW12_HX001 {
	private $IP = "";
	private $Port = 5577;
	
	public function __construct( $IP, $Port )
	{
		$this->IP = $IP;
		$this->Port = $Port;
		
		$this->offset = 36;
		
		//Statusinformation
		$this->power = false;
		$this->mode = 1;
		$this->modestate = false;
		$this->speed = 255;
		$this->color = 000000;
	}
	
	public function PowerOn()
	{
		$command = "9D620D00000060F0700000000050F0401010100B";
		$this->sendPacket($command, 0);
	}
	
	public function PowerOff()
	{
		$command = "9D620D00000060F0700000000050F0400010100A";
		$this->sendPacket($command, 0);
	}
	
	public function Run()
	{
		$command = "";
		throw new Exception('Not implemented');
		$this->sendPacket($command, 0);
	}
	
	public function Stop()
	{
		$command = "";
		throw new Exception('Not implemented');
		$this->sendPacket($command, 0);
	}
	
	public function GetStatus()
	{
		$command = "0000000000000000000000000000000000000000";
		throw new Exception('Not implemented');
				
		$status = $this->sendPacket($command, 12);
		$status = bin2hex($status);
		$status = strtoupper($status);
		$status = str_split($status, 2);
		
		// Antwortstring?
		
		}
	
	public function SetColorDec($decrgb)
	{
		$r = floor($decrgb/65536);
		$g = floor(($decrgb-($r*65536))/256);
		$b = $decrgb-($g*256)-($r*65536);
		$hexrgb = str_pad(dechex($r),2,0,STR_PAD_LEFT) . str_pad(dechex($g),2,0,STR_PAD_LEFT) . str_pad(dechex($b),2,0,STR_PAD_LEFT);
		while (strlen($hexrgb) < 6) {
			$hexrgb = '0'.$hexrgb;
		}

		$command = '9D620600000060' . $hexrgb . '0000F000004010101006';
		$this->sendPacket($command, 0);
	}
	
	public function SetColorHex($hexrgb)
	{
		$command = '9D620600000060' . $hexrgb . '0000F000004010101006';
		$this->sendPacket($command, 0);
	}
	
	public function SetBrightness($brightness)
	{
		throw new Exception('Not implemented');
	}

	public function SetMode($mode, $speed)
	{
		$speed = intval(abs(($speed/100) * 31 - 32));
		$command = 'bb' . dechex($mode + $this->offset) . str_pad(dechex($speed),2,0,STR_PAD_LEFT) . '44';
		throw new Exception('Not implemented');
		$this->sendPacket($command, 0);
	}
	
	private function sendPacket( $command, $return )
	{
		$fp = fsockopen($this->IP, $this->Port, $errno, $errstr, 10);
		if (!$fp)
		    throw new Exception("Error opening socket: ".$errstr." (".$errno.")");
		$command = hex2bin($command);
		fputs ($fp, $command);
		$ret = 0;
		if($return > 0)
			$ret= fgets($fp,$return);
		fclose($fp);
		return $ret;
	}
}

?>
