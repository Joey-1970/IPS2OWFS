<?
    // Klassendefinition
    class IPS2OWFS_Configurator extends IPSModule 
    {
	    
	// Überschreibt die interne IPS_Create($id) Funktion
        public function Create() 
        {
            	// Diese Zeile nicht löschen.
            	parent::Create();
		$this->ConnectParent("{A76DD90C-A117-2100-C84C-452FE558C622}");
		$this->RegisterPropertyInteger("Category", 0);  
        }
 	
	public function GetConfigurationForm() 
	{ 
		$arrayStatus = array(); 
		$arrayStatus[] = array("code" => 101, "icon" => "inactive", "caption" => "Instanz wird erstellt"); 
		$arrayStatus[] = array("code" => 102, "icon" => "active", "caption" => "Instanz ist aktiv");
		$arrayStatus[] = array("code" => 104, "icon" => "inactive", "caption" => "Instanz ist inaktiv");
		$arrayStatus[] = array("code" => 202, "icon" => "error", "caption" => "Kommunikationfehler!");
				
		$arrayElements = array(); 
		$arrayElements[] = array("type" => "SelectCategory", "name" => "Category", "caption" => "Zielkategorie");
		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		$arraySort = array();
		$arraySort = array("column" => "DeviceID", "direction" => "ascending");
		
		$arrayColumns = array();
		$arrayColumns[] = array("caption" => "Geräte ID", "name" => "DeviceID", "width" => "300px", "visible" => true);
		$arrayColumns[] = array("caption" => "Typ", "name" => "Type", "width" => "auto", "visible" => true);
		
		$Category = $this->ReadPropertyInteger("Category");
		$RootNames = [];
		$RootId = $Category;
		while ($RootId != 0) {
		    	if ($RootId != 0) {
				$RootNames[] = IPS_GetName($RootId);
		    	}
		    	$RootId = IPS_GetParent($RootId);
			}
		$RootNames = array_reverse($RootNames);
		
		$DeviceArray = array();
		If (($this->HasActiveParent() == true) AND (IPS_GetKernelRunlevel() == KR_READY)) {
			$DeviceArray = unserialize($this->GetData());
		}
		$arrayValues = array();
		for ($i = 0; $i < Count($DeviceArray); $i++) {
			
			$arrayCreate = array();
			If (($DeviceArray[$i]["Type"] == "DS18B20") OR ($DeviceArray[$i]["Type"] == "DS18S20")) {
				If ($DeviceArray[$i]["Type"] == "DS18B20") {
					$arrayCreate[] = array("moduleID" => "{11809B39-06FB-EBB8-7671-7C36CBC3FFDF}", "location" => $RootNames,
					       "configuration" => array("DeviceID" => $DeviceArray[$i]["DeviceID"], "Open" => true ));
				}
				elseif ($DeviceArray[$i]["Type"] == "DS18S20") {
					$arrayCreate[] = array("moduleID" => "{5E558624-DA9D-1F79-91D6-360C4F71476B}", "location" => $RootNames,
					       "configuration" => array("DeviceID" => $DeviceArray[$i]["DeviceID"], "Open" => true ));
				}
				
				$arrayValues[] = array("DeviceID" => $DeviceArray[$i]["DeviceID"], "Type" => $DeviceArray[$i]["Type"],
					       "instanceID" => $DeviceArray[$i]["Instance"], "create" => $arrayCreate);
			}
			else {
				$arrayValues[] = array("DeviceID" => $DeviceArray[$i]["DeviceID"], "Type" => $DeviceArray[$i]["Type"],
					       "instanceID" => $DeviceArray[$i]["Instance"]);
			}
			
		}	
		$arrayElements[] = array("type" => "Configurator", "name" => "DeviceList", "caption" => "OneWire-Geräte", "rowCount" => 10, "delete" => false, "sort" => $arraySort, "columns" => $arrayColumns, "values" => $arrayValues);

		$arrayElements[] = array("type" => "Label", "label" => "_____________________________________________________________________________________________________");
		
 		return JSON_encode(array("status" => $arrayStatus, "elements" => $arrayElements)); 		 
 	}       
	   
        // Überschreibt die intere IPS_ApplyChanges($id) Funktion
        public function ApplyChanges() 
        {
            	// Diese Zeile nicht löschen
            	parent::ApplyChanges();
		
		If (IPS_GetKernelRunlevel() == 10103) {	
			If ($this->HasActiveParent() == true) {
				If ($this->GetStatus() <> 102) {
					$this->SetStatus(102);
				}
			}
			else {
				If ($this->GetStatus() <> 104) {
					$this->SetStatus(104);
				}
			}
		}
	}
	    
	// Beginn der Funktionen
	private function GetData()
	{
		$Result = $this->SendDataToParent(json_encode(Array("DataID"=> "{F1CAC7F7-BA28-F711-7E0E-481F338200A4}", 
				"Function" => "DeviceList" )));
		//$this->SendDebug("GetData", $Result, 0);
		$DeviceArray = unserialize($Result);
		If (is_array($DeviceArray)) {
			If ($this->GetStatus() <> 102) {
				$this->SetStatus(102);
			}
			$this->SendDebug("GetData", $Result, 0);
			$Devices = array();
			$i = 0;
			foreach($DeviceArray as $Key => $Device) {
				$Devices[$i]["Address"] = $Device["Address"];
				$Devices[$i]["Type"] = $Device["Type"];
				$Devices[$i]["DeviceID"] = $Key;
				$Devices[$i]["Instance"] = $this->GetDeviceInstanceID($Key, $Device["Type"]);
				$i = $i + 1;
			}
		}
	return serialize($Devices);;
	}
	
	function GetDeviceInstanceID(string $DeviceID, string $Type)
	{
		$this->SendDebug("GetDeviceInstanceID", "Device ID: ".$DeviceID." Typ: ".$Type, 0);
		If ($Type == "DS18B20") {
			$guid = "{11809B39-06FB-EBB8-7671-7C36CBC3FFDF}";
			$this->SendDebug("GetDeviceInstanceID", "DS18B20", 0);
		}
		elseif ($Type == "DS18S20") {
			$guid = "{5E558624-DA9D-1F79-91D6-360C4F71476B}";
			$this->SendDebug("GetDeviceInstanceID", "DS18S20", 0);
		}
		else {
			$Result = 0;
			return $Result;
		}
	    	$Result = 0;
	    	// Modulinstanzen suchen
	    	$InstanceArray = array();
	    	$InstanceArray = @(IPS_GetInstanceListByModuleID($guid));
		$this->SendDebug("GetDeviceInstanceID", "Anzahl: ".count($InstanceArray), 0);
	    	If (is_array($InstanceArray)) {
			foreach($InstanceArray as $Module) {
				If (IPS_GetProperty($Module, "DeviceID") == $DeviceID) {
					$this->SendDebug("GetDeviceInstanceID", "Gefundene Instanz: ".$Module, 0);
					$Result = $Module;
					break;
				}
				else {
					$Result = 0;
				}
			}
		}
	return $Result;
	}
}
?>
