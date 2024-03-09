<?
    // Klassendefinition
    class IPS2OWFS_DS2408 extends IPSModule 
    {
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->RegisterMessage(0, IPS_KERNELSTARTED);
		
		$this->ConnectParent("{A76DD90C-A117-2100-C84C-452FE558C622}");
		$this->RegisterPropertyBoolean("Open", false);
		$this->RegisterPropertyString("DeviceID", "");
		$this->RegisterTimer("Timer_1", 0, 'IPS2OWFSDS2408_GetState($_IPS["TARGET"]);');
		for ($i = 0; $i <= 7; $i++) {
			$this->RegisterPropertyBoolean("Function_P".($i), false);
		}
		
		//Status-Variablen anlegen
		for ($i = 0; $i <= 7; $i++) {
			$this->RegisterVariableBoolean("Status_P".($i), "Status P".$i, "~Switch", ($i + 1) * 10);
			$this->EnableAction("Status_P".($i));
		}
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
		$arrayElements[] = array("type" => "Label", "label" => "Eingang > false, Ausgang > true");
		for ($i = 0; $i <= 7; $i++) {
			$arrayElements[] = array("name" => "Function_P".($i), "type" => "CheckBox",  "caption" => "Funktion P".($i));
		}
		
		
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
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
				If (IPS_GetKernelRunlevel() == KR_READY) {
					$this->GetState();
					$this->SetTimerInterval("Timer_1", 15000);
					for ($i = 0; $i <= 7; $i++) {
						If ($this->ReadPropertyBoolean("Function_P".$i) == true) {
							$this->EnableAction("Status_P".$i);
						}
						else {
							$this->DisableAction("Status_P".$i);
						}
					}
				}
			}
			else {
				Echo "Syntax der Device ID inkorrekt!";
				$this->SendDebug("ApplyChanges", "Syntax der Device ID inkorrekt!", 0);
				If ($this->GetStatus() <> 202) {
					$this->SetStatus(202);
				}
			}
		}
		else {
			If ($this->GetStatus() <> 104) {
				$this->SetStatus(104);
			}
			$this->SetTimerInterval("Timer_1", 0);
		}	
	}
	
	public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    	{
		switch ($Message) {
			case IPS_KERNELSTARTED:
				// IPS_KERNELSTARTED
				$this->ApplyChanges();
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
			$this->SendDebug("GetState", serialize($Content), 0);
			If (is_array($Content) == true) {
				//$this->SendDebug("GetState", "Temperatur: ".$Content['temperature'], 0);	
				//$this->SetValue("Temperature", $Content['temperature']);
			}
			else {
				//$this->SendDebug("GetState", "Temperatur: Fehlerhafte Datenermittlung!", 0);
			}
		}
	}
}
?>
