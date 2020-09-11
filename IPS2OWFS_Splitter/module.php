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
				$this->ConnectionTest();
				$this->SetStatus(102);
			}
			else {
				Echo "Syntax der IP inkorrekt!";
				$this->SendDebug("ApplyChanges", "Syntax der IP inkorrekt!", 0);
				$this->SetStatus(203);
			}
			
		}
		else {
			$this->SetStatus(104);
			
		}	
	}
	
	public function ForwardData($JSONString) 
	{
	 	// Empfangene Daten von der Device Instanz
	    	$data = json_decode($JSONString);
	    	$Result = false;
	 	switch ($data->Function) {
			case "getDeviceList":
				
				break;
		}
	return $Result;
	}
	    
	// Beginn der Funktionen
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
					$this->SetStatus(104);
	   			}
	   			else {
	   				fclose($status);
					$this->SendDebug("Netzanbindung", "Port ist geoeffnet", 0);
					$result = true;
					$this->SetStatus(102);
	   			}
		}
		else {
			IPS_LogMessage("IPS2OWFS Netzanbindung: ","IP ".$GatewayIP." reagiert nicht!");
			$this->SendDebug("Netzanbindung", "IP ".$GatewayIP." reagiert nicht!", 0);
			$this->SetStatus(104);
		}
	return $result;
	}	
	
	  
	

	
	 
}
?>
