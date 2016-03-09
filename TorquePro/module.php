<?

	class TorquePro extends IPSModule
	{
		
		public function Create()
		{
			//Never delete this line!
			parent::Create();
			
			$this->RegisterPropertyString("allowedIds", "");
			$this->RegisterPropertyBoolean("forwardRequests", false);
			$this->RegisterPropertyString("forwardRequestsURL", "http://ian-hawkins.com/torque.php");
		}
	
		public function ApplyChanges()
		{
			//Never delete this line!
			parent::ApplyChanges();
			
			$sid = $this->RegisterScript("Hook", "Hook", "<? //Do not delete or modify.\ninclude(IPS_GetKernelDirEx().\"scripts/__ipsmodule.inc.php\");\ninclude(\"../modules/IPSModules/TorquePro/module.php\");\n(new TorquePro(".$this->InstanceID."))->ProcessHookData();");
			$this->RegisterHook("/hook/torque", $sid);
			
			if(@$this->GetIDForIdent("Torque_Keys") === false) {
				$this->RegisterScript("Torque_Keys", "Torque Keys", file_get_contents(__DIR__ . "/keys.txt"));
			}
		}
		
		private function RegisterHook($Hook, $TargetID)
		{
			$ids = IPS_GetInstanceListByModuleID("{015A6EB8-D6E5-4B93-B496-0D3F77AE9FE1}");
			if(sizeof($ids) > 0) {
				$hooks = json_decode(IPS_GetProperty($ids[0], "Hooks"), true);
				$found = false;
				foreach($hooks as $index => $hook) {
					if($hook['Hook'] == "/hook/torque") {
						if($hook['TargetID'] == $TargetID)
							return;
						$hooks[$index]['TargetID'] = $TargetID;
						$found = true;
					}
				}
				if(!$found) {
					$hooks[] = Array("Hook" => "/hook/torque", "TargetID" => $TargetID);
				}
				IPS_SetProperty($ids[0], "Hooks", json_encode($hooks));
				IPS_ApplyChanges($ids[0]);
			}
		}
		
		private function ForwardRequest($RequestURI)
		{
			/* Forward to Ian's Torque API: */
			$ch = curl_init();
			$RequestURI = str_replace("/hook/torque", "", $RequestURI);
			$url = $this->ReadPropertyString("forwardRequestsURL").$RequestURI;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_FAILONERROR, true);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			if(curl_exec($ch) === false)
			{
				IPS_LogMessage("TorquePro", "Forwarding request failed. cURL: ".curl_error($ch));
			}
			curl_close($ch);
		}
		
		private function Map($lat, $long, $parentid)
		{
			$iframe = <<<EOF
<iframe 
	style="width: 600px; height: 350px; margin: 0 auto; display:block;"
	width="600"
	height="350"
	frameborder="0"
	src="https://www.bing.com/maps/embed/viewer.aspx?v=3&amp;cp=$lat~$long&amp;lvl=18&amp;w=600&amp;h=350&amp;sty=h&amp;typ=d&amp;pp=~~$lat~$long&amp;ps=&amp;dir=0&amp;mkt=de-de&amp;src=SHELL&amp;form=BMEMJS">
</iframe>
EOF;

			$variable = @IPS_GetObjectIDByIdent('GoogleMaps', $parentid);
			if($variable === false)
			{
				$variable = IPS_CreateVariable(3);// create string var
				IPS_SetName($variable, 'Position'); // name var by key name
				IPS_SetIdent($variable, 'GoogleMaps');
				IPS_SetVariableCustomProfile($variable, "~HTMLBox");
				IPS_SetParent($variable, $parentid); // set var parent
			}	
			SetValueString($variable, $iframe);
		}
		
		/**
		* This function will be available automatically after the module is imported with the module control.
		* Using the custom prefix this function will be callable from PHP and JSON-RPC through:
		*
		* TORQUE_ProcessHookData($id);
		*
		*/
		public function ProcessHookData()
		{
			include_once(IPS_GetScriptFile($this->GetIDForIdent("Torque_Keys")));
			$error = false;
			
			$data =array();
			foreach ($_GET as $key => $value) {
				$data[$key]  = $value;
			}
			
			if(isset($data['id']) || array_key_exists('id', $data))
			{
				if($data['id'] !== NULL)
				{
					$allowedIds = $this->ReadPropertyString("allowedIds");
					if($allowedIds !== NULL)
					{
						$Ids = explode(",", $allowedIds);
						$i = 0;
						foreach($Ids as $Id) {
							if((string)$data['id'] == md5($Id))
								$i++;
						}
						if(!$i)
						{
							IPS_LogMessage("TorquePro", "Unauthorized ID: ".(string)$data['id']);
							$error = true;
						}
					} else {
						IPS_LogMessage("TorquePro", "Invalid Request: Id invalid");
						$error = true;
					}
				}
			} else {
				IPS_LogMessage("TorquePro", "Invalid Request: Id not existant");
				$error = true;
			}
			
			if(!$error)
			{
				if($this->ReadPropertyBoolean("forwardRequests"))
					$this->ForwardRequest($_SERVER['REQUEST_URI']);
				
				$rootid = $this->InstanceID;
				$parentid = @IPS_GetCategoryIDByName ((string)$data['id'], $rootid);
				
				if($parentid === false){
				// Categorie not exists -> create and set
					$parentid = IPS_CreateCategory();// create float var
					IPS_SetName($parentid, (string)$data['id']); // name var by key name
					IPS_SetParent($parentid, $rootid); // set var parent
				}
				
				foreach($data as $key => $value){
					unset($variable);
					if (array_key_exists($key, $key_names)) {
						$friendly_name = $key_names[$key];
						$variable = @IPS_GetObjectIDByIdent($key, $parentid);
					if($variable === false){
						if (preg_match("/^k/", $key)) {
							// float
							$variable = IPS_CreateVariable(2);// create float var
							IPS_SetName($variable, $friendly_name); // name var by key name
							IPS_SetIdent($variable, $key);
							IPS_SetParent($variable, $parentid); // set var parent
							SetValueFloat($variable, floatval($value)); // set value
						} else if ($key == "time" || $key == "session") {
							$variable = IPS_CreateVariable(1);// create int var
							IPS_SetName($variable, $friendly_name); // name var by key name
							IPS_SetIdent($variable, $key);
							IPS_SetVariableCustomProfile($variable, "~UnixTimestamp");
							IPS_SetParent($variable, $parentid); // set var parent
						} else {
							//string
							$variable = IPS_CreateVariable(3);// create string var
							IPS_SetName($variable, $friendly_name); // name var by key name
							IPS_SetIdent($variable, $key);
							IPS_SetParent($variable, $parentid); // set var parent
						}
					} 
					
					// Variable exists -> just set value
					if (preg_match("/^k/", $key)) {
							SetValue($variable, floatval($value));
						} else if ($key == "time" || $key == "session") {
							SetValue($variable, $value/1000);
						} else {
							SetValue($variable, utf8_decode($value));
						}
						IPS_SetName($variable, $friendly_name);
					}
				}
				
				// Map (GPS long, GPS lat)
				if(isset($data['kff1005']) || isset($data['kff1006']))
				{
					$this->Map($data['kff1006'], $data['kff1005'], $parentid);
				}

				// Required by Torque Pro App
				print "OK!";
			} else {
				print "NOK!";
			}
			IPS_LogMessage("TorquePro", "Query String: ".$_SERVER['QUERY_STRING']);
		}
	}
?>