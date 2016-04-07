<?

	class LW12 extends IPSModule
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			$this->RegisterPropertyString("LW12_IP", "");
			$this->RegisterPropertyInteger("LW12_Port", 5577);
			$this->RegisterPropertyString("LW12_Type", "");
		}
	
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
			if ($this->ReadPropertyString("LW12_Type") == 'FC_820')
				$this->RegisterProfileIntegerEx("Mode.LW12.FC_820", "ArrowRight", "", "", Array(
					Array(0, "static red", "", -1),
					Array(1, "static blue", "", -1),
					Array(2, "static green", "", -1),
					Array(3, "static cyan", "", -1),
					Array(4, "static yellow", "", -1),
					Array(5, "static purple", "", -1),
					Array(6, "static white", "", -1),
					Array(7, "tricolor jump", "", -1),
					Array(8, "seven-color jump", "", -1),
					Array(9, "tricolor gradient", "", -1),
					Array(10, "seven-color gradient", "", -1),
					Array(11, "red gradient", "", -1),
					Array(12, "green gradient", "", -1),
					Array(13, "blue gradient", "", -1),
					Array(14, "yellow gradient", "", -1),
					Array(15, "cyan gradient", "", -1),
					Array(16, "purple gradient", "", -1),
					Array(17, "white gradient", "", -1),
					Array(18, "red-green gradient", "", -1),
					Array(19, "red-blue gradient", "", -1),
					Array(20, "green-blue gradient", "", -1),
					Array(21, "seven-color flash", "", -1),
					Array(22, "red flash", "", -1),
					Array(23, "green flash", "", -1),
					Array(24, "blue flash", "", -1),
					Array(25, "yellow flash", "", -1),
					Array(26, "cyan flash", "", -1),
					Array(27, "purple flash", "", -1),
					Array(28, "white flash", "", -1),
				));
			else
				$this->RegisterProfileIntegerEx("Mode.LW12.LEDXXX", "ArrowRight", "", "", Array(
					Array(1, "Seven color cross fade", "", -1),
					Array(2, "Red gradual change", "", -1),
					Array(3, "Green gradual change", "", -1),
					Array(4, "Blue gradual change", "", -1),
					Array(5, "Yellow gradual change", "", -1),
					Array(6, "Cyan gradual change", "", -1),
					Array(7, "Purple gradual change", "", -1),
					Array(8, "White gradual change", "", -1),
					Array(9, "Red", "", -1),
					Array(10, "Red blue cross fade", "", -1),
					Array(11, "Green blue cross fade", "", -1),
					Array(12, "Seven color strobe flash", "", -1),
					Array(13, "Red strobe flash", "", -1),
					Array(14, "Green strobe flash", "", -1),
					Array(15, "Blue strobe flash", "", -1),
					Array(16, "Yellow strobe flash", "", -1),
					Array(17, "Cyan strobe flash", "", -1),
					Array(18, "Purple strobe flash", "", -1),
					Array(19, "White strobe flash", "", -1),
					Array(20, "Seven color jumping change", "", -1),
				));
				
			$this->RegisterVariableBoolean("power", "Status", "~Switch");
			$this->EnableAction("power");
			
			if ($this->ReadPropertyString("LW12_Type") == 'FC_820')
				$this->RegisterVariableInteger("mode", "Modus", "Mode.LW12.FC_820");
			else
				$this->RegisterVariableInteger("mode", "Modus", "Mode.LW12.LEDXXX");
			$this->EnableAction("mode");
			
			$this->RegisterVariableInteger("speed", "Geschwindigkeit", "~Intensity.100");
			$this->EnableAction("speed");
			
			$this->RegisterVariableInteger("color", "Farbe", "~HexColor");
			$this->EnableAction("color");

			$this->RegisterVariableInteger("brightness", "Helligkeit", "~Intensity.100");
			$this->EnableAction("brightness");
		}
		
		private function init()
		{
			include_once(__DIR__ . "/LW12_LEDXXX.php");
			include_once(__DIR__ . "/LW12_HX001.php");
			include_once(__DIR__ . "/LW12_FC_820.php");
				
			switch($this->ReadPropertyString("LW12_Type"))
			{
				case "LEDXXX":
					$LW12 = new LW12_LEDXXX($this->ReadPropertyString("LW12_IP"), $this->ReadPropertyInteger("LW12_Port"));
					break;
				case "HX001":
					$LW12 = new LW12_HX001($this->ReadPropertyString("LW12_IP"), $this->ReadPropertyInteger("LW12_Port"));
					break;
				case "FC_820":
					$LW12 = new LW12_FC_820($this->ReadPropertyString("LW12_IP"), $this->ReadPropertyInteger("LW12_Port"));
					break;
				default:
					throw new Exception("Invalid controller type");
			}

			$LW12->power = GetValue($this->GetIDForIdent("power"));
			$LW12->mode = GetValue($this->GetIDForIdent("mode"));
			$LW12->speed = GetValue($this->GetIDForIdent("speed"));
			$LW12->color = GetValue($this->GetIDForIdent("color"));
			$LW12->brightness = GetValue($this->GetIDForIdent("brightness"));
			
			return $LW12;
		}
		
		/**
		* This function will be available automatically after the module is imported with the module control.
		* Using the custom prefix this function will be callable from PHP and JSON-RPC through:
		*
		* LW12_PowerOn($id);
		*
		*/
		
		public function PowerOn()
		{
			$LW12 = $this->init();
			$LW12->PowerOn();

			SetValue($this->GetIDForIdent("power"), $LW12->power);
		}
		
		public function PowerOff()
		{
			$LW12 = $this->init();
			$LW12->PowerOff();

			SetValue($this->GetIDForIdent("power"), $LW12->power);
		}
		
		public function Run()
		{
			$LW12 = $this->init();
			$LW12->Run();
		}
		
		public function Stop()
		{
			$LW12 = $this->init();
			$LW12->Stop();
		}
		
		public function SetColorDec($decrgb)
		{
			$LW12 = $this->init();
			$LW12->SetColorDec($decrgb);

			SetValue($this->GetIDForIdent("color"), $LW12->color);
		}
		
		public function SetColorHex($hexrgb)
		{
			$LW12 = $this->init();
			$LW12->SetColorHex($hexrgb);

			SetValue($this->GetIDForIdent("color"), $LW12->color);
		}
		
		public function SetMode($mode)
		{
			$LW12 = $this->init();
			$LW12->SetMode($mode, GetValue($this->GetIDForIdent("speed")));

			SetValue($this->GetIDForIdent("mode"), $LW12->mode);
		}
		
		public function SetSpeed($speed)
		{
			$LW12 = $this->init();
			$LW12->SetMode(GetValue($this->GetIDForIdent("mode")), $speed);

			SetValue($this->GetIDForIdent("speed"), $LW12->speed);
		}
		
		public function SetBrightness($brightness)
		{
			$LW12 = $this->init();
			$LW12->SetBrightness($brightness);

			SetValue($this->GetIDForIdent("brightness"), $LW12->brightness);
		}
		
		public function GetStatus()
		{
			$LW12 = $this->init();
			$LW12->GetStatus();
			
			SetValue($this->GetIDForIdent("power"), $LW12->power);
			SetValue($this->GetIDForIdent("mode"), $LW12->mode);
			SetValue($this->GetIDForIdent("speed"), $LW12->speed);
			SetValue($this->GetIDForIdent("color"), $LW12->color);
			SetValue($this->GetIDForIdent("brightness"), $LW12->brightness);
		}
		
		public function RequestAction($Ident, $Value)
		{
			switch($Ident) {
				case "power":
					if($Value)
					{
						$this->PowerOn();
						SetValue($this->GetIDForIdent($Ident), $Value);
					}
					else
					{
						$this->PowerOff();
						SetValue($this->GetIDForIdent($Ident), $Value);
					}
					break;
				case "mode": // Mode 1-21
					if($Value > 0 && $Value < 22)
					{
						$this->SetMode($Value);
						SetValue($this->GetIDForIdent($Ident), $Value);
					}
					else
					{
						throw new Exception("Invalid mode (only 1-21)");
					}
					break;
				case "speed": // 0-100%
					$this->SetSpeed($Value);
					SetValue($this->GetIDForIdent($Ident), $Value);
					break;
				case "color": // ~HexColor
					$this->SetColorDec($Value);
					SetValue($this->GetIDForIdent($Ident), $Value);
					break;
				case "brightness": // ~brightness
					$this->SetBrightness($Value);
					SetValue($this->GetIDForIdent($Ident), $Value);
					break;
				default:
					throw new Exception("Invalid ident");
			}		
			
			$this->GetStatus();
		}
		
		//Remove on next Symcon update
		protected function RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $MinValue, $MaxValue, $StepSize) {
		
			if(!IPS_VariableProfileExists($Name)) {
				IPS_CreateVariableProfile($Name, 1);
			} else {
				$profile = IPS_GetVariableProfile($Name);
				if($profile['ProfileType'] != 1)
					throw new Exception("Variable profile type does not match for profile ".$Name);
			}
			
			IPS_SetVariableProfileIcon($Name, $Icon);
			IPS_SetVariableProfileText($Name, $Prefix, $Suffix);
			IPS_SetVariableProfileValues($Name, $MinValue, $MaxValue, $StepSize);
			
		}
		
		protected function RegisterProfileIntegerEx($Name, $Icon, $Prefix, $Suffix, $Associations) {
		
			$this->RegisterProfileInteger($Name, $Icon, $Prefix, $Suffix, $Associations[0][0], $Associations[sizeof($Associations)-1][0], 0);
		
			foreach($Associations as $Association) {
				IPS_SetVariableProfileAssociation($Name, $Association[0], $Association[1], $Association[2], $Association[3]);
			}
			
		}
	}

?>
