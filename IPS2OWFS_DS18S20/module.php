<?
    // Klassendefinition
    class IPS2OWFS_DS18S20 extends IPSModule 
    {
	public function Destroy() 
	{
		//Never delete this line!
		parent::Destroy();
		$this->SetTimerInterval("Timer_1", 0);
	}  
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterMessage(0, IPS_KERNELMESSAGE);
		
		$this->ConnectParent("{A76DD90C-A117-2100-C84C-452FE558C622}");
		$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString("DeviceID", "");
		$this->RegisterTimer("Timer_1", 0, 'IPS2OWFSDS18S20_GetState($_IPS["TARGET"]);');
		
		//Status-Variablen anlegen
		$this->RegisterVariableFloat("Temperature", "Temperatur", "~Temperature", 10);
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
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "DeviceID", "caption" => "Device ID");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		If (($this->ReadPropertyBoolean("Open") == true) AND (IPS_GetKernelRunlevel() == KR_READY)) {
			$DeviceFilter = str_replace(".", "", $this->ReadPropertyString("DeviceID"));
			
			If ((ctype_xdigit ($DeviceFilter) == true) AND (strlen($this->ReadPropertyString("DeviceID")) == 15)) {
				$this->SetStatus(102);
				If (IPS_GetKernelRunlevel() == KR_READY) {
					$this->GetState();
					$this->SetTimerInterval("Timer_1", 15000);
				}
			}
			else {
				Echo "Syntax der Device ID inkorrekt!";
				$this->SendDebug("ApplyChanges", "Syntax der Device ID inkorrekt!", 0);
				$this->SetStatus(202);
			}
		}
		else {
			$this->SetStatus(104);
			$this->SetTimerInterval("Timer_1", 0);
		}	
	}
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		switch ($Message) {
			case 10100:
				// IPS_KERNELSTARTED
				$this->GetState();
				$this->SetTimerInterval("Timer_1", 15000);
				break;
		}
    	}               
	    
	// Beginn der Funktionen
	public function GetState()
	{
		If (($this->ReadPropertyBoolean("Open") == true) AND ($this->HasActiveParent() == true)) {
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{F1CAC7F7-BA28-F711-7E0E-481F338200A4}", 
					"Function" => "DeviceState", "DeviceID" => $this->ReadPropertyString("DeviceID") )));
			$Content = json_decode($Result, true);
			If (is_array($Content) == true) {
				$this->SendDebug("GetState", "Temperatur: ".$Content['temperature'], 0);	
				$this->SetValue("Temperature", $Content['temperature']);
			}
			else {
				$this->SendDebug("GetState", "Temperatur: Fehlerhafte Datenermittlung!", 0);
			}
		}
	}
}
?>
