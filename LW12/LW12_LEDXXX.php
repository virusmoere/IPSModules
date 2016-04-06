<?

class LW12_LEDXXX {
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
		$command = "cc2333";
		
		$this->sendPacket($command, 0);
	}
	
	public function PowerOff()
	{
		$command = "cc2433";
		
		$this->sendPacket($command, 0);
	}
	
	public function Run()
	{
		$command = "cc2133";
		
		$this->sendPacket($command, 0);
	}
	
	public function Stop()
	{
		$command = "cc2233";
		
		$this->sendPacket($command, 0);
	}
	
	public function GetStatus()
	{
		$command = "ef0177";
		
		// 01: Init (0x66)
		// 02: Init (0x01)
		// 03: Off (0x24) / On (0x23)
		// 04: Mode (0x25 - 0x38)
		// 05: Running (0x21) / Stopped (0x20)
		// 06: Speed  (1/10th seconds?) (0x00 - 0xff)
		// 07: Red (0x00 - 0xff)
		// 08: Green (0x00 - 0xff)
		// 09: Blue (0x00 - 0xff)
		// 10: User Memory used (0xFF) / not used (0x51)
		// 11: Termination (0x99)
		
		$status = $this->sendPacket($command, 12);
		$status = bin2hex($status);
		$status = strtoupper($status);
		$status = str_split($status, 2);
		
		
		switch($status[2])
		{
			case '23': 
				$this->power = true;
				break;
			default:
				$this->power = false;
		}			
		
		$this->mode = hexdec($status[3]) - $this->offset;
		
		switch($status[4])
		{
			case '21': 
				$this->modestate = true;
				break;
			default:
				$this->modestate = false;
		}		
		
		$this->speed = intval((abs(hexdec($status[5]) - 32)/31)*100);
		
		$this->color = (hexdec($status[6]) * 256 * 256) + (hexdec($status[7]) * 256) + hexdec($status[8]);
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

		$command = '56' . $hexrgb . 'aa';

		$this->sendPacket($command, 0);
	}
	
	public function SetColorHex($hexrgb)
	{
		$command = '56' . $hexrgb . 'aa';
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
