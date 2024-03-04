<?
    // Klassendefinition
    class IPS2OWFS_Splitter extends IPSModule 
    {	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString("GatewayIP", "127.0.0.1");
		$this->RegisterPropertyInteger("Port", "2121");
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
				
		$arrayElements = array(); 
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv");
		$arrayElements[] = array("type" => "Label", "caption" => "OWFS-Gateway-Zugangsdaten");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "GatewayIP", "caption" => "Gateway IP");
		$arrayElements[] = array("type" => "NumberSpinner", "name" => "Port", "caption" => "Port", "minimum" => 0, "maximum" => 65535);
		$arrayActions = array();
		  	
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		If ($this->ReadPropertyBoolean("Open") == true) {
			$IP = $this->ReadPropertyString("GatewayIP");
			If (filter_var($IP, FILTER_VALIDATE_IP)) {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				$this->ConnectionTest();
			}
			else {
				Echo "Syntax der IP inkorrekt!";
				$this->SendDebug("ApplyChanges", "Syntax der IP inkorrekt!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
			}
			
		}
		else {
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
			
		}	
	}
	
	public function ForwardData($JSONString) 
	{
	 	// Empfangene Daten von der Device Instanz
	    	$data = json_decode($JSONString);
	    	$Result = false;
	 	switch ($data->Function) {
			case "DeviceState":
				$Result = $this->DeviceState($data->DeviceID);
				break;
			case "DeviceList":
				$Result = $this->DeviceList();
				break;
		}
	return $Result;
	}
	    
	// Beginn der Funktionen
	private function DeviceState($DeviceID)
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			if (IPS_SemaphoreEnter("DeviceState", 3000)) {
				$this->SendDebug("DeviceState", "Datenermittlung Device: ".$DeviceID, 0);
				$GatewayIP = $this->ReadPropertyString("GatewayIP");
				$Port = $this->ReadPropertyInteger("Port");	
				
				$URL = 'http://'.$GatewayIP.':'.$Port.'/json/'.$DeviceID;
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
				curl_setopt($ch, CURLOPT_TIMEOUT, 2);
				curl_setopt($ch, CURLOPT_URL, $URL);
				$Content = curl_exec($ch);

				If ($Content === false) {
					$this->SendDebug("DeviceState", "Fehler bei der Datenermittlung!", 0);
					$this->ConnectionTest();
				}
			IPS_SemaphoreLeave("DeviceState");
			}	
		return $Content;
		}
	}
	
	private function DeviceList()
	{
		$DeviceArray = array();
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->ConnectionTest() == true)) {
			$GatewayIP = $this->ReadPropertyString("GatewayIP");
			$Port = $this->ReadPropertyInteger("Port");
			$URL = 'http://'.$GatewayIP.':'.$Port.'/json/';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
			curl_setopt($ch, CURLOPT_TIMEOUT, 2);
			curl_setopt($ch, CURLOPT_URL, $URL);
			$Content = curl_exec($ch);
			
			$Content = json_decode($Content, true);
			
			foreach ($Content as $Device => $Value) {
    				$DeviceFilter = str_replace(".", "", $Device);
    				If ((ctype_xdigit ($DeviceFilter) == true) AND (strlen($Device) == 15)) {
        				$URL = 'http://'.$GatewayIP.':'.$Port.'/json/'.$Device;
					curl_setopt($ch, CURLOPT_URL, $URL);
					$DeviceInfo = curl_exec($ch);
					$DeviceInfo = json_decode($DeviceInfo, true);
					If (is_null($DeviceInfo['address']) == true) {
						$DeviceArray[$Device]['Address'] = "unknown";
					}
					else {
						$DeviceArray[$Device]['Address'] = $DeviceInfo['address'];
					}
					If (is_null($DeviceInfo['Type']) == true) {
        					$DeviceArray[$Device]['Type'] = "unknown";
					}
					else {
						$DeviceArray[$Device]['Type'] = $DeviceInfo['type'];
					}
    				}
   			}
			$this->SendDebug("DeviceList", serialize($DeviceArray), 0);
		}
	return serialize($DeviceArray);
	}
	    
	    
	    
	private function ConnectionTest()
	{
	      	$result = false;
		$GatewayIP = $this->ReadPropertyString("GatewayIP");
		$Port = $this->ReadPropertyInteger("Port");	 
		If (Sys_Ping($GatewayIP, 500)) {
		      	// IP reagiert
		      	$this->SendDebug("Netzanbindung", "Angegebene IP ".$GatewayIP." reagiert", 0);
			$status = @fsockopen($GatewayIP, $Port, $errno, $errstr, 10);
				if (!$status) {
					IPS_LogMessage("IPS2OWFS Netzanbindung: ","Port ist geschlossen!");
					$this->SendDebug("Netzanbindung", "Port ist geschlossen!", 0);
					If ($this->GetStatus() <> 202) {
						$this->SetStatus(202);
					}
	   			}
	   			else {
	   				fclose($status);
					//$this->SendDebug("Netzanbindung", "Port ist geoeffnet", 0);
					$result = true;
					If ($this->GetStatus() <> 102) {
						$this->SetStatus(102);
					}
	   			}
		}
		else {
			IPS_LogMessage("IPS2OWFS Netzanbindung: ","IP ".$GatewayIP." reagiert nicht!");
			$this->SendDebug("Netzanbindung", "IP ".$GatewayIP." reagiert nicht!", 0);
			If ($this->GetStatus() <> 202) {
				$this->SetStatus(202);
			}
		}
	return $result;
	}	 
}
?>
