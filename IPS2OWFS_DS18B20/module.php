<?
    // Klassendefinition
    class IPS2OWFS_DS18B20 extends IPSModule 
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
		$this->RegisterTimer("Timer_1", 0, 'IPS2OWFSDS18B20_GetState($_IPS["TARGET"]);');
		
		
		
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
		$arrayElements[] = array("type" => "Label", "caption" => "UNGETESTET!!"); 
		
		$arrayElements[] = array("name" => "Open", "type" => "CheckBox",  "caption" => "Aktiv");
		$arrayElements[] = array("type" => "ValidationTextBox", "name" => "DeviceID", "caption" => "Device ID");
		
		$arrayActions = array(); 
		
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements, "actions" => $arrayActions)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		If (($this->ReadPropertyBoolean("Open") == true) AND (IPS_GetKernelRunlevel() == KR_READY)) {
			If ($this->ReadPropertyInteger("DeviceID") >= 65537) {
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
				$this->SetTimerInterval("Timer_1", 1000);
				break;
			
		}
    	}               
	    
	public function RequestAction($Ident, $Value) 
	{
  		switch($Ident) {
	        case "Position":
	            	$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{F1CAC7F7-BA28-F711-7E0E-481F338200A4}", 
				"Function" => "Blind", "DeviceID" => $this->ReadPropertyInteger("DeviceID"), "Value" => $Value )));
	            	SetValueFloat($this->GetIDForIdent($Ident), $Value);
			$this->GetState();
		break;
		
	        default:
	            throw new Exception("Invalid Ident");
	    }
	}
	    
	// Beginn der Funktionen
	public function GetState()
	{
		If ($this->ReadPropertyBoolean("Open") == true) {
			$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{F1CAC7F7-BA28-F711-7E0E-481F338200A4}", 
					"Function" => "DeviceState", "DeviceID" => $this->ReadPropertyString("DeviceID") )));
			$this->SendDebug("GetState", "Ergebnis: ".$Result, 0);
			
			
			
		}
	}
	
	
	
	
}
?>
