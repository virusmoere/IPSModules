<?

	class Enigma2 extends IPSModule
	{
		
		public function __construct($InstanceID)
		{
			//Never delete this line!
			parent::__construct($InstanceID);
			
			//These lines are parsed on Symcon Startup or Instance creation
			//You cannot use variables here. Just static values.
			$this->RegisterPropertyString("IP", "");
			$this->RegisterPropertyInteger("Port", 80);
			$this->RegisterPropertyBoolean("SSL", false);
			$this->RegisterPropertyString("Username", "root");
			$this->RegisterPropertyString("Password", "");
		}
	
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
			$this->RegisterVariableBoolean("power", "Status", "~Switch");
			$this->EnableAction("power");
			
		}
		
		// http://dream.reichholf.net/wiki/Enigma2:WebInterface
		
		public function GetPowerstate()
		{
			$command = "powerstate?";
			$xml = $this->SendCommand($command);
			return $xml->e2instandby;
		}
		
		public function SetPowerstate($state)
		{
			switch($state)
			{
				case 0:
					$state = 0; // Toogle Standby
					break;
				case 1:
					$state = 1; // Deepstandby
					break;
				case 2:
					$state = 2; // Reboot
					break;
				case 3:
					$state = 3; // Restart Enigma2
					break;
				case 4:
					$state = 4; // Wakeup form Standby
					break;
				case 5:
					$state = 5; // Standby
					break;
				default:
					throw new Exception("Invalid enigma2 powerstate!");
			}
			
			$command = "powerstate?newstate=" . $state;
			$xml = $this->SendCommand($command);
			return $xml->e2instandby;
		}
		
		public function GetCurrentChannel()
		{
			$command = "subservices";
			$xml = $this->SendCommand($command);
			return utf8_decode($xml->e2service->e2servicename);
		}
		
		public function GetCurrentChannelEventDescription()
		{
			$command = "subservices";
			$xml = $this->SendCommand($command);
			$command = "epgservice?sRef=" . $xml->e2service->e2servicereference;
			$xml = $this->SendCommand($command);
			return utf8_decode($xml->e2event[0]->e2eventdescriptionextended);
		}
		
		public function ShowMessage($message, $type, $timeout)
		{
			$command = "message?text=" . $message . "&type=" . $type . "&timeout=" . $timeout;
			$xml = $this->SendCommand($command);
			return $xml->e2result;
		}
		
		private function SendCommand($command)
		{
			$IP = $this->ReadPropertyString("IP");
			$Port = ":" . $this->ReadPropertyInteger("Port");
			$SSL = $this->ReadPropertyBoolean("SSL");
			$Username = $this->ReadPropertyString("Username");
			$Password = $this->ReadPropertyString("Password");
			
			$authstring = "";
			if($Username != "" && $Password == "")
			{
				$authstring = $Username . "@";
			}
			elseif($Username != "" && $Password != "")
			{
				$authstring = $Username . ":" . $Password . "@";
			}
			
			$ch = curl_init();
			
			$protocol = "http://";
			if($SSL)
			{
				$protocol = "https://";
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			}
			
			
			$path = $protocol . $authstring . $IP . $Port . "/web/" . $command;
			
			curl_setopt($ch, CURLOPT_URL, $path);
			curl_setopt($ch, CURLOPT_FAILONERROR, 1);
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_TIMEOUT, 15);
			
			$returned = curl_exec($ch);
			curl_close($ch);
			// $xml === False on failure
			$xml = simplexml_load_string($returned);
			if(!$xml)
				throw new Exception("Invalid enigma2 request! Request: ". $command);
			return $xml;
		}
		
		public function RequestAction($Ident, $Value)
		{
			switch($Ident) {
				case "power":
					if($Value)
					{
						$this->SetPowerstate(4);
					}
					else
					{
						$this->SetPowerstate(5);
					}
					SetValue($this->GetIDForIdent($Ident), $Value);
					break;
				default:
					throw new Exception("Invalid ident");
			}		
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