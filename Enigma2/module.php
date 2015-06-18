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
			
			$this->RegisterProfileIntegerEx("MessageType.E2", "Mail", "", "", Array(
				Array(0, "Ja/Nein", "", -1),
				Array(1, "Info", "", -1),
				Array(2, "Warnung", "", -1),
				Array(3, "Kritisch", "", -1),
			));
			
			$this->RegisterVariableBoolean("power", "Status", "~Switch");
			$this->EnableAction("power");
      
			$this->RegisterVariableInteger("messagetype", "Nachrichtentyp", "MessageType.E2");
			$this->EnableAction("messagetype");
			$this->RegisterVariableString("message", "Nachricht", "~TextBox");
			$this->EnableAction("message");
			
			$this->RegisterVariableString("program", "Programm", "~String");
			$this->RegisterVariableString("show", "Sendung", "~String");
			$this->RegisterVariableString("description", "Beschreibung", "~TextBox");
			
			$this->RegisterScript("update", "Aktualisieren", "<?\n\nE2_RequestUpdate(IPS_GetParent(\$_IPS['SELF']));\n\n?>", 0);
		}
		
		// http://dream.reichholf.net/wiki/Enigma2:WebInterface
		
		public function GetPowerstate()
		{
			$command = "powerstate?";
			$xml = $this->SendCommand($command);
			$status = $xml->e2instandby;
			if(strpos($status, "false"))
			{
				$status = true;
			}
			else
			{
				$status = false;
			}
			SetValue($this->GetIDForIdent("power"), $status);
			return $status;
		}
		
		public function SetPowerstate($state)
		{
			// 0 Toogle Standby
			// 1 Deepstandby
			// 2 Reboot
			// 3 Restart Enigma2
			// 4 Wakeup form Standby
			// 5 Standby
			
			if($state < 0 || $state > 5)
				throw new Exception("Invalid enigma2 powerstate!");
						
			$command = "powerstate?newstate=" . $state;
			$xml = $this->SendCommand($command);
			return $xml->e2instandby;
		}
		
		public function GetCurrentChannelInformation()
		{
			$command = "subservices";
			$xml = $this->SendCommand($command);
			SetValue($this->GetIDForIdent("program"), utf8_decode($xml->e2service->e2servicename));
      
			$command = "epgservice?sRef=" . $xml->e2service->e2servicereference;
			$xml = $this->SendCommand($command);
			
			if($xml->e2event[0])
			{
				SetValue($this->GetIDForIdent("show"), utf8_decode($xml->e2event[0]->e2eventtitle) . " - " . utf8_decode($xml->e2event[0]->e2eventdescription));
				SetValue($this->GetIDForIdent("description"), utf8_decode($xml->e2event[0]->e2eventdescriptionextended));
			}
			else 
			{
				SetValue($this->GetIDForIdent("show"), "N/A");
				SetValue($this->GetIDForIdent("description"), "");
			}
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
			
			if(curl_errno($ch))
			{
				throw new Exception("Connection error:" . curl_error($ch));
			}
			
			curl_close($ch);
			// $xml === False on failure
			$xml = simplexml_load_string($returned);
			if(!$xml)
				throw new Exception("Invalid command / empty response");
			return $xml;
		}
		
		public function RequestUpdate()
		{
			$this->GetPowerstate();
			$this->GetCurrentChannelInformation();
		}
		
		public function RequestAction($Ident, $Value)
		{
			switch($Ident) {
				case "update":
					$this->RequestUpdate();
					SetValue($this->GetIDForIdent($Ident), $Value);
					break;
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
				case "message":
					$this->ShowMessage($Value , GetValue($this->GetIDForIdent("messagetype")), 0);
					SetValue($this->GetIDForIdent($Ident), $Value);
					break;
				case "messagetype":
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