<?

class LW12_FC_820 {
	private $IP = "";
	private $Port = 5000;
	
	public function __construct( $IP, $Port )
	{
		$this->IP = $IP;
		$this->Port = $Port;
		
		$this->offset = 0x80;
		
		//Statusinformation
		$this->power = false;
		$this->mode = 1;
		$this->modestate = false;
		$this->speed = 100;
		$this->brightness = 100;
		$this->color = 000000;
	}
	
	public function PowerOn()
	{
		$command = "7e040401ffffff00ef";
		
		$this->sendPacket($command, 0);

		$this->power = true;
	}
	
	public function PowerOff()
	{
		$command = "7e040400ffffff00ef";

		$this->sendPacket($command, 0);

		$this->power = false;
	}
	
	public function Run()
	{
		$this->modestate = true;
	}
	
	public function Stop()
	{
		$this->modestate = false;
	}	

	public function GetStatus()
	{
		// Das Modul antwortet nicht und ich bekomme es auch nicht dazu seinen Status auszugeben. 
		// Demnach ist der Status in IPS der einzige der vorliegt.		
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
		
		$command = '7e070503' . $hexrgb . '00ef';
		
		$this->sendPacket($command, 0);

		$this->color = $decrgb;
		$this->modestate = false;
	}
	
	public function SetColorHex($hexrgb)
	{
		$command = '7e070503' . $hexrgb . '00ef';
		
		$answer = $this->sendPacket($command, 0);

		$this->color = hexdec($hexrgb);
		$this->modestate = false;
	}

	public function SetBrightness($brightness)	// percentage of the desired brightness
	{
		$brightness_hex = str_pad(dechex($brightness),2,0,STR_PAD_LEFT);
		$command = '7e0401' . $brightness_hex . 'ffffff00ef';
		
		$this->sendPacket($command, 0);

		$this->brightness = $brightness;
	}
	
	public function SetMode($mode, $speed)
	{
		$command = '7e0402' . str_pad(dechex($speed),2,0,STR_PAD_LEFT) . 'ffffff00ef';
		
		$this->sendPacket($command, 0);
		
		$command = '7e0503' . dechex($mode + $this->offset) . '03ffff00ef';
		
		$this->sendPacket($command, 0);

		$this->mode = $mode;
		$this->speed = $speed;
		$this->modestate = true;
	}
	
	private function sendPacket($command, $return)
	{
		$fp = fsockopen("udp://" . $this->IP, $this->Port, $errno, $errstr, 10);
		if (!$fp)
		    throw new Exception("Error opening socket: ".$errstr." (".$errno.")");
		$command = hex2bin($command);
		fputs ($fp, $command);
		$ret = 0;
		if($return > 0)
			stream_set_timeout($fp, 0, 100000);
			$ret= fgets($fp);
		fclose($fp);
		return $ret;
	}

	// some unused methods
	public function GetIdent()	// returns some kind of identifier, only method that returns anything
	{
		$command = '7e0709ffffffffffef';
		$ret = $this->sendPacket($command, 1);
		
		return $ret;
	}
	
	public function reset()	// be careful - also resets wifi settings
	{
		$command = '7e070bffffffffffef';
		$ret = $this->sendPacket($command, 0);
	}

	public function SetDim($dim)	// sets dim value between [0...A]
	{
		$command = '7e05038' . $dim . '01ffff00ef';
		$ret = $this->sendPacket($command, 0);
	}

	public function SetTemperature($temperature)	// sets temperature value between [0...A]
	{
		$command = '7e05038' . $temperature . '02ffff00ef';
		$ret = $this->sendPacket($command, 0);
	}
}

?>
