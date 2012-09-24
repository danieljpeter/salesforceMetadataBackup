<?php

require_once ('settings.php');
require_once ('phpToolkit/SforcePartnerClient.php');
require_once ('phpToolkit/SforceHeaderOptions.php');


//========================================= OPERATION 1, do some file copy / delete preparation to clear out the results of the previous time we did this
echo shell_exec('cp /home/dreamforce/sfMetadata/build_template.xml /home/dreamforce/sfMetadata/build.xml');
echo shell_exec('rm -rf /home/dreamforce/sfMetadata/lists/*');
echo shell_exec('rm -rf /home/dreamforce/sfMetadata/metadata/*');
//========================================= END OPERATION 1



//========================================= OPERATION 2, get a bunch of listings via ant's listMetadata so we can parse those resulting files later in the script
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listCustomField');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listAccountCriteriaBasedSharingRule');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listAccountOwnerSharingRule');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listAccountSharingRules');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listBusinessProcess');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listCampaignCriteriaBasedSharingRule');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listCampaignOwnerSharingRule');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listCampaignSharingRules');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listCaseCriteriaBasedSharingRule');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listCaseOwnerSharingRule');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listCaseSharingRules');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listContactCriteriaBasedSharingRule');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listContactOwnerSharingRule');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listContactSharingRules');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listCustomField');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listCustomObjectCriteriaBasedSharingRule');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listCustomObjectSharingRules');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listFieldSet');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listLeadCriteriaBasedSharingRule');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listLeadOwnerSharingRule');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listLeadSharingRules');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listLetterhead');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listListView');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listNamedFilter');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listOpportunityCriteriaBasedSharingRule');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listOpportunityOwnerSharingRule');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listOpportunitySharingRules');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listRecordType');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listSharingReason');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listValidationRule');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml listWebLink');	
//========================================= END OPERATION 2



//========================================= OPERATION 3, get some folder names via the REST API so we can get some metadata components which rely on the folder names
//get a session ID via the REST API
$sessionId = getSession();

$arrReports = getReportFolders($sessionId);
$arrDashboards = getDashboardFolders($sessionId);
$arrEmailTemplates = getEmailTemplateFolders($sessionId);
$arrDocuments = getDocumentFolders($sessionId);

$bulkRetrieve = '';
$bulkRetrieve .=  buildBulkRetrieve('Report', $arrReports);
$bulkRetrieve .=  buildBulkRetrieve('Dashboard', $arrDashboards);
$bulkRetrieve .=  buildBulkRetrieve('EmailTemplate', $arrEmailTemplates);
$bulkRetrieve .=  buildBulkRetrieve('Document', $arrDocuments);

//open the build.xml file and populate the bulkRetrieve based on the folders names we got
searchReplaceBuildXmlFile('<!-- bulkRetrieveFolders populated by script -->', $bulkRetrieve);
//========================================= END OPERATION 3


//========================================= OPERATION 4.  get the standard and custom object names via the rest API, and there subcomponents via ant list
//get a list of standard and custom objects
$arrObjectList = getObjectLists($sessionId);
$arrStdObjects = $arrObjectList['standard'];
$arrCustomObjects = $arrObjectList['custom'];

//loop through the std and custom objects, building this kind of XML:
/*    
	<types>
        <members>Account</members>
        <name>CustomObject</name>
    </types>
*/


$objectXML = "";
$objectXML .= "\t<types>\r\n";
$objectXML .= "\t\t<members>*</members>\r\n";


foreach($arrStdObjects as $stdObj) {	
	$objectXML .= "\t\t<members>".$stdObj."</members>\r\n";
}


foreach($arrCustomObjects as $custObj) {
	$objectXML .= "\t\t<members>".$custObj."</members>\r\n";
}

$objectXML .= "\t\t<name>CustomObject</name>\r\n";
$objectXML .= "\t</types>\r\n";	



// we need to get these subcomponents of our objects:
//CustomField,BusinessProcess,RecordType,WebLink,ValidationRule,NamedFilter,SharingReason,ListView,FieldSet
$arrCustomField 	= filerToStandardObjects(parseLog("/home/dreamforce/sfMetadata/lists/CustomField.log"));
$arrBusinessProcess = parseLog("/home/dreamforce/sfMetadata/lists/BusinessProcess.log");
$arrRecordType 		= parseLog("/home/dreamforce/sfMetadata/lists/RecordType.log");
$arrWebLink 		= parseLog("/home/dreamforce/sfMetadata/lists/WebLink.log");
$arrValidationRule 	= parseLog("/home/dreamforce/sfMetadata/lists/ValidationRule.log");
$arrNamedFilter 	= parseLog("/home/dreamforce/sfMetadata/lists/NamedFilter.log");
$arrSharingReason 	= parseLog("/home/dreamforce/sfMetadata/lists/SharingReason.log");
$arrListView 		= parseLog("/home/dreamforce/sfMetadata/lists/ListView.log");
$arrFieldSet 		= parseLog("/home/dreamforce/sfMetadata/lists/FieldSet.log");

$objectSubXML = "";

$objectSubXML .= generateXMLTypeBlock($arrCustomField, "CustomField");
$objectSubXML .= generateXMLTypeBlock($arrBusinessProcess, "BusinessProcess");
$objectSubXML .= generateXMLTypeBlock($arrRecordType, "RecordType");
$objectSubXML .= generateXMLTypeBlock($arrWebLink, "WebLink");
$objectSubXML .= generateXMLTypeBlock($arrValidationRule, "ValidationRule");
$objectSubXML .= generateXMLTypeBlock($arrNamedFilter, "NamedFilter");
$objectSubXML .= generateXMLTypeBlock($arrSharingReason, "SharingReason");
$objectSubXML .= generateXMLTypeBlock($arrListView, "ListView");
$objectSubXML .= generateXMLTypeBlock($arrFieldSet, "FieldSet");

$completeObjectXML = $objectXML.$objectSubXML; //join the object, and subcomponent together

createPackageXMLfile($completeObjectXML, "objects.xml"); //write them to the package.xml file

//========================================= END OPERATION 4


//========================================= OPERATION 5.  get any remaining components which need to be dot qualified with info we get from ant 
$arrLetterhead 								= parseLog("/home/dreamforce/sfMetadata/lists/Letterhead.log");
$arrAccountCriteriaBasedSharingRule 		= parseLog("/home/dreamforce/sfMetadata/lists/AccountCriteriaBasedSharingRule.log");
$arrAccountOwnerSharingRule 				= parseLog("/home/dreamforce/sfMetadata/lists/AccountOwnerSharingRule.log");
$arrAccountSharingRules 					= parseLog("/home/dreamforce/sfMetadata/lists/AccountSharingRules.log");
$arrCampaignCriteriaBasedSharingRule 		= parseLog("/home/dreamforce/sfMetadata/lists/CampaignCriteriaBasedSharingRule.log");
$arrCampaignOwnerSharingRule 				= parseLog("/home/dreamforce/sfMetadata/lists/CampaignOwnerSharingRule.log");
$arrCampaignSharingRules 					= parseLog("/home/dreamforce/sfMetadata/lists/CampaignSharingRules.log");
$arrCaseCriteriaBasedSharingRule 			= parseLog("/home/dreamforce/sfMetadata/lists/CaseCriteriaBasedSharingRule.log");
$arrCaseOwnerSharingRule 					= parseLog("/home/dreamforce/sfMetadata/lists/CaseOwnerSharingRule.log");
$arrCaseSharingRules 						= parseLog("/home/dreamforce/sfMetadata/lists/CaseSharingRules.log");
$arrContactCriteriaBasedSharingRule 		= parseLog("/home/dreamforce/sfMetadata/lists/ContactCriteriaBasedSharingRule.log");
$arrContactOwnerSharingRule 				= parseLog("/home/dreamforce/sfMetadata/lists/ContactOwnerSharingRule.log");
$arrContactSharingRules 					= parseLog("/home/dreamforce/sfMetadata/lists/ContactSharingRules.log");
$arrCustomObjectCriteriaBasedSharingRule 	= parseLog("/home/dreamforce/sfMetadata/lists/CustomObjectCriteriaBasedSharingRule.log");
$arrCustomObjectSharingRules 				= parseLog("/home/dreamforce/sfMetadata/lists/CustomObjectSharingRules.log");
$arrLeadCriteriaBasedSharingRule 			= parseLog("/home/dreamforce/sfMetadata/lists/LeadCriteriaBasedSharingRule.log");
$arrLeadOwnerSharingRule 					= parseLog("/home/dreamforce/sfMetadata/lists/LeadOwnerSharingRule.log");
$arrLeadSharingRules 						= parseLog("/home/dreamforce/sfMetadata/lists/LeadSharingRules.log");
$arrOpportunityCriteriaBasedSharingRule 	= parseLog("/home/dreamforce/sfMetadata/lists/OpportunityCriteriaBasedSharingRule.log");
$arrOpportunityOwnerSharingRule 			= parseLog("/home/dreamforce/sfMetadata/lists/OpportunityOwnerSharingRule.log");
$arrOpportunitySharingRules 				= parseLog("/home/dreamforce/sfMetadata/lists/OpportunitySharingRules.log");

$remainingXML = "";

$remainingXML .= generateXMLTypeBlock($arrLetterhead,"Letterhead");
$remainingXML .= generateXMLTypeBlock($arrAccountCriteriaBasedSharingRule,"AccountCriteriaBasedSharingRule");
$remainingXML .= generateXMLTypeBlock($arrAccountOwnerSharingRule,"AccountOwnerSharingRule");
$remainingXML .= generateXMLTypeBlock($arrAccountSharingRules,"AccountSharingRules");
$remainingXML .= generateXMLTypeBlock($arrCampaignCriteriaBasedSharingRule,"CampaignCriteriaBasedSharingRule");
$remainingXML .= generateXMLTypeBlock($arrCampaignOwnerSharingRule,"CampaignOwnerSharingRule");
$remainingXML .= generateXMLTypeBlock($arrCampaignSharingRules,"CampaignSharingRules");
$remainingXML .= generateXMLTypeBlock($arrCaseCriteriaBasedSharingRule,"CaseCriteriaBasedSharingRule");
$remainingXML .= generateXMLTypeBlock($arrCaseOwnerSharingRule,"CaseOwnerSharingRule");
$remainingXML .= generateXMLTypeBlock($arrCaseSharingRules,"CaseSharingRules");
$remainingXML .= generateXMLTypeBlock($arrContactCriteriaBasedSharingRule,"ContactCriteriaBasedSharingRule");
$remainingXML .= generateXMLTypeBlock($arrContactOwnerSharingRule,"ContactOwnerSharingRule");
$remainingXML .= generateXMLTypeBlock($arrContactSharingRules,"ContactSharingRules");
$remainingXML .= generateXMLTypeBlock($arrCustomObjectCriteriaBasedSharingRule,"CustomObjectCriteriaBasedSharingRule");
$remainingXML .= generateXMLTypeBlock($arrCustomObjectSharingRules,"CustomObjectSharingRules");
$remainingXML .= generateXMLTypeBlock($arrLeadCriteriaBasedSharingRule,"CriteriaBasedSharingRule");
$remainingXML .= generateXMLTypeBlock($arrLeadOwnerSharingRule,"LeadOwnerSharingRule");
$remainingXML .= generateXMLTypeBlock($arrLeadSharingRules,"LeadSharingRules");
$remainingXML .= generateXMLTypeBlock($arrOpportunityCriteriaBasedSharingRule,"OpportunityCriteriaBasedSharingRule");
$remainingXML .= generateXMLTypeBlock($arrOpportunityOwnerSharingRule,"OpportunityOwnerSharingRule");
$remainingXML .= generateXMLTypeBlock($arrOpportunitySharingRules,"OpportunitySharingRules");

createPackageXMLfile($remainingXML, "remaining.xml"); //write them to the package.xml file

//========================================= END OPERATION 5


//========================================= OPERATION 6, run the ant jobs to retrieve the metadata we have defined
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml bulkRetrieve');
echo shell_exec('ant -buildfile /home/dreamforce/sfMetadata/build.xml bulkRetrieveFolders');
//========================================= END OPERATION 6



//========================================= OPERATION 7, remove any files from local git repo which were removed in salesforce, copy all the files to the git repo, add, commit, push to github

/*
we have a local git repo in:
   /home/dpeter/git/salesforce-metadata
we want to copy the metadata we downloaded here:
  /home/dpeter/metadata
which we want to push up to github:
  git@github.com:safarijv/salesforce-metadata.git
*/



//=============================================================
//remove all the files from the local github repo which aren't in the metadata repo (in case these were objects which were deleted in salesforce)
//get the an array of each file listings, recursively.  Strip off the beginning path from each entry in the array so we can do text comparison
$arrMetadata 	= stripBlanks(str_replace('/home/dreamforce/sfMetadata/metadata', 					'', @getFilesFromDir('/home/dreamforce/sfMetadata/metadata')));
$arrGit 		= stripBlanks(str_replace('/home/dreamforce/sfMetadata/git/salesforce-metadata', 	'', @getFilesFromDir('/home/dreamforce/sfMetadata/git/salesforce-metadata')));

//remove array entries in the /.git directory
$arrGit = stripGit($arrGit); 

$arrDeleteFromGit = array();

$fileInGitAndMetadata = false;
foreach ($arrGit as $gitFile) {
	$fileInGitAndMetadata = false;
	foreach ($arrMetadata as $metadataFile) {
		if ($gitFile == $metadataFile) {
			$fileInGitAndMetadata = true;
		}
	}
	if (!$fileInGitAndMetadata) {
		array_push($arrDeleteFromGit, $gitFile);
	}
}

//print_r($arrDeleteFromGit);

foreach ($arrDeleteFromGit as $gitFile) {
	echo shell_exec('git --git-dir=/home/dreamforce/sfMetadata/git/salesforce-metadata/.git --work-tree=/home/dreamforce/sfMetadata/git/salesforce-metadata/ rm -r -f /home/dreamforce/sfMetadata/git/salesforce-metadata'.$gitFile);
}

//=============================================================


//copy all the files from the metadata downloads back into the local github repo
echo shell_exec('cp -R -f metadata/* git/salesforce-metadata');

//add the files to the local git repo
echo shell_exec('git --git-dir=/home/dreamforce/sfMetadata/git/salesforce-metadata/.git --work-tree=/home/dreamforce/sfMetadata/git/salesforce-metadata/ add /home/dreamforce/sfMetadata/git/salesforce-metadata/*');

//commit the files
echo shell_exec('git --git-dir=/home/dreamforce/sfMetadata/git/salesforce-metadata/.git --work-tree=/home/dreamforce/sfMetadata/git/salesforce-metadata/ commit -m "Automated Daily Commit from SFSYNC"');

//push the files
echo shell_exec('git --git-dir=/home/dreamforce/sfMetadata/git/salesforce-metadata/.git --work-tree=/home/dreamforce/sfMetadata/git/salesforce-metadata/ push origin master');

//========================================= END OPERATION 7









function generateXMLTypeBlock($arrMembers, $sName) {
	//Build an XML block like below, iterating over $arrMembers

	/*	
		<types>
			<members>Account</members>
			<name>CustomObject</name>
		</types>
	*/

	$sXML = "";
	
	if (count($arrMembers) > 0) {
		$sXML .= "\t<types>\r\n";	
		foreach ($arrMembers as $arrField) {
			$sXML .= "\t\t<members>".$arrField."</members>\r\n";	
		}
		$sXML .= "\t\t<name>".$sName."</name>\r\n";	
		$sXML .= "\t</types>\r\n";	
	}
	
	return $sXML;	
}


function parseLog($fileName) {
	$arrFields = array();		
	$myFile = $fileName;
	if (file_exists($myFile)) {
		$fh = fopen($myFile, 'r');
		$theData = fread($fh, filesize($myFile));
		fclose($fh);	
		
			
		//read the file into an array of lines
		$arrLines = explode("\n", $theData); 
		
		//loop through the lines and get the ones that start with "FullName/Id:"
		//lines look like this: FullName/Id: Case.Queue_Feedback/00B000000095c8IEAQ		
		foreach ($arrLines as $arrLine) {
			if (strpos($arrLine, "FullName/Id:") !== false) {
				//parse out just the objectName.ListView, FieldName, etc from the string
				$arrTemp = explode("FullName/Id:", $arrLine, 2);
				if (count($arrTemp) == 2) {
					$arrTemp = explode("/", $arrTemp[1], 2);
					if (count($arrTemp) == 2) {
						array_push($arrFields, trim($arrTemp[0]));		
					}
				}
			}
		}	
	}

	sort($arrFields);
	return $arrFields;
}

function filerToStandardObjects($arrInput) {
	//now we want to filter this list down to just the custom fields on the standard object.  we don't want the custom fields on the custom objects, because they 
	//will already get included with the custom objects.
	$arrStandard = array();
	foreach ($arrInput as $arrField) {
		//Account.Auto_renewing__c  		//standard looks like this
		//Alert__c.Send_Support_Page__c 	//custom looks like this
		//Support_Alert__kav.Details__c		//custom also looks like this
		$isCustom = false;
		$arrTemp = explode(".", $arrField, 2);
		if (count($arrTemp) == 2) {
			if (strtolower(substr($arrTemp[0], -3)) == "__c") {
				$isCustom = true;
			}
			if (strtolower(substr($arrTemp[0], -5)) == "__kav") {
				$isCustom = true;
			}			
			if (!$isCustom) {
				array_push($arrStandard, $arrField);
			}			
		}
	}
	return $arrStandard;
}


function parseCustomFieldLog() {
		
	$myFile = "lists/CustomField.log";
	$fh = fopen($myFile, 'r');
	$theData = fread($fh, filesize($myFile));
	fclose($fh);	
	
		
	//read the file into an array of lines
	$arrLines = explode("\n", $theData); 
	
	//loop through the lines and get the ones that start with "FullName/Id:"
	//lines look like this: FullName/Id: Opportunity.Qualified_Pre_Trial_Checklist__c/00N000000072Qe0EAE	
	$arrFields = array();
	foreach ($arrLines as $arrLine) {
		if (strpos($arrLine, "FullName/Id:") !== false) {
			//parse out just the objectName.FieldName from the string
			$arrTemp = explode("FullName/Id:", $arrLine, 2);
			if (count($arrTemp) == 2) {
				$arrTemp = explode("/", $arrTemp[1], 2);
				if (count($arrTemp) == 2) {
					array_push($arrFields, trim($arrTemp[0]));		
				}
			}
		}
	}
	
	//now we want to filter this list down to just the custom fields on the standard object.  we don't want the custom fields on the custom objects, because they 
	//will already get included with the custom objects.
	$arrStandard = array();
	foreach ($arrFields as $arrField) {
		//Account.Auto_renewing__c  		//standard looks like this
		//Alert__c.Send_Support_Page__c 	//custom looks like this
		//Support_Alert__kav.Details__c		//custom also looks like this
		$isCustom = false;
		$arrTemp = explode(".", $arrField, 2);
		if (count($arrTemp) == 2) {
			if (strtolower(substr($arrTemp[0], -3)) == "__c") {
				$isCustom = true;
			}
			if (strtolower(substr($arrTemp[0], -5)) == "__kav") {
				$isCustom = true;
			}			
			if (!$isCustom) {
				array_push($arrStandard, $arrField);
			}			
		}
	}
	$arrFields = $arrStandard;
	
	sort($arrFields);
	return $arrFields;
}






function createPackageXMLfile($sXML, $sFilename) {
	$myFile = $sFilename;
	$fh = fopen($myFile, 'w') or die("can't open file");	
	fwrite($fh, getPackageXMLheader().$sXML.getPackageXMLfooter());
	fclose($fh);
}

function getPackageXMLheader() {
	$sReturn = '<?xml version="1.0" encoding="UTF-8"?>'."\r\n";
	$sReturn .= '<Package xmlns="http://soap.sforce.com/2006/04/metadata">'."\r\n";
	return $sReturn;
}

function getPackageXMLfooter() {
	$sReturn = '</Package>'."\r\n";
	return $sReturn;
}

function getCustomFieldsFromObjectRaw($arrObject) {
	$arrReturn = array();
	foreach ((array) $arrObject['fields'] as $field) {
		if ($field['custom'] == true) {
			array_push($arrReturn, $field['name']);  
		}
	}	
	return $arrReturn;
}

function getObjectInfoRaw($objectName, $access_token) {
    $url = "https://".SFURL."/services/data/v24.0/sobjects/".$objectName."/describe/";
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth $access_token"));
    $json_response = curl_exec($curl);
    curl_close($curl);
    $response = json_decode($json_response, true);
	return $response;
}

function getObjectLists($access_token) {
	//this function returns an assoc array.  The value "standard" is an array of the std objects, "custom" is an array of the custom objects
	$arrReturn = array();
	$arrReturn['standard'] = array();
	$arrReturn['custom'] = array();

    $url = "https://".SFURL."/services/data/v24.0/sobjects/";

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth $access_token"));

    $json_response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($json_response, true);
    
    foreach ((array) $response['sobjects'] as $record) {
		if ($record['custom'] == true) {
			array_push($arrReturn['custom'], $record['name']);  
		} else {
			array_push($arrReturn['standard'], $record['name']);
		}
    }
	
	return $arrReturn;

}






function searchReplaceBuildXmlFile($search, $replace) {
	// Open file for read and string modification 
	$file = "/home/dreamforce/sfMetadata/build.xml"; 
	$fh = fopen($file, 'r'); 
	$contents = fread($fh, filesize($file)); 
	$new_contents = str_replace($search, $replace, $contents); 
	fclose($fh); 	
	
	// Open file to write 
	$fh = fopen($file, 'w'); 
	fwrite($fh, $new_contents); 
	fclose($fh); 	
	
}


function buildBulkRetrieve($metadataType, $arrFolders) {
	$sReturn = '';
	
	foreach ($arrFolders as $value) {
		$sReturn .= "\t\t".'<sf:bulkRetrieve username="${sf.username}" password="${sf.password}" serverurl="${sf.serverurl}" metadataType="'.$metadataType.'" containingFolder="'.$value.'" retrieveTarget="/home/dreamforce/sfMetadata/metadata"/>'."\r\n";	
	}
	
	return rtrim($sReturn);
}

function getSession() {
	$sessionId = '';	
	try {
		$mySforceConnection = new SforcePartnerClient();
		$mySoapClient = $mySforceConnection->createConnection('phpToolkit/partner.wsdl.xml');
		$mylogin = $mySforceConnection->login(USERNAME, PASSWORD);
		$sessionId = $mylogin->sessionId;
	} catch (Exception $e) {
		print_r($mySforceConnection->getLastRequest());
		echo $e->faultstring;
	}
	return $sessionId;
}


function getReportFolders($access_token) {
	$arrReturn = array();

    $query = "SELECT Id, DeveloperName FROM Folder WHERE Type='Report' AND DeveloperName != ''";
    $url = "https://".SFURL."/services/data/v20.0/query?q=" . urlencode($query);

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth $access_token"));

    $json_response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($json_response, true);
    
    foreach ((array) $response['records'] as $record) {
		array_push($arrReturn, $record['DeveloperName']);      	   
    }
	
	return $arrReturn;

}

function getDashboardFolders($access_token) {
	$arrReturn = array();

    $query = "SELECT Id, DeveloperName FROM Folder WHERE Type='Dashboard' AND DeveloperName != ''";
    $url = "https://".SFURL."/services/data/v20.0/query?q=" . urlencode($query);

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth $access_token"));

    $json_response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($json_response, true);
    
    foreach ((array) $response['records'] as $record) {
		array_push($arrReturn, $record['DeveloperName']);      	   
    }

	return $arrReturn;
	
}


function getDocumentFolders($access_token) {
	$arrReturn = array();

    $query = "SELECT Id, DeveloperName FROM Folder WHERE Type='Document' AND DeveloperName != ''";
    $url = "https://".SFURL."/services/data/v20.0/query?q=" . urlencode($query);

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth $access_token"));

    $json_response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($json_response, true);
    
    foreach ((array) $response['records'] as $record) {
		array_push($arrReturn, $record['DeveloperName']);      	   
    }

	return $arrReturn;
	
}


function getEmailTemplateFolders($access_token) {
	$arrReturn = array();

    $query = "SELECT Id, DeveloperName FROM Folder WHERE Type='Email' AND DeveloperName != ''";
    $url = "https://".SFURL."/services/data/v20.0/query?q=" . urlencode($query);

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array("Authorization: OAuth $access_token"));

    $json_response = curl_exec($curl);
    curl_close($curl);

    $response = json_decode($json_response, true);
    
    foreach ((array) $response['records'] as $record) {
		array_push($arrReturn, $record['DeveloperName']);      	   
    }

	return $arrReturn;
	
}



function getFilesFromDir($dir) {
  $files = array(); 
  if ($handle = opendir($dir)) { 
    while (false !== ($file = readdir($handle))) { 
        if ($file != "." && $file != "..") { 
            if(is_dir($dir.'/'.$file)) { 
                $dir2 = $dir.'/'.$file; 
                $files[] = @getFilesFromDir($dir2); 
            } 
            else { 
              $files[] = $dir.'/'.$file; 
            } 
        } 
    } 
    closedir($handle); 
  }
  return @array_flat($files); 
} 

function array_flat($array) { 
  foreach($array as $a) { 
    if(is_array($a)) { 
      $tmp = array_merge($tmp, array_flat($a)); 
    } 
    else { 
      $tmp[] = $a; 
    } 
  } 
  return $tmp; 
} 

function stripGit($arrInput) {
	$arrStripped = array();
	foreach ($arrInput as $stripFile) {
		if (substr($stripFile, 0, 5) == "/.git") {
			//don't add it
		} else {
			array_push($arrStripped, $stripFile);
		}		
	}	
	return $arrStripped;
}

function stripBlanks($arrInput) {
	$arrStripped = array();
	foreach ($arrInput as $stripFile) {
		if (trim($stripFile) == "") {
			//don't add it
		} else {
			array_push($arrStripped, trim($stripFile));
		}		
	}	
	return $arrStripped;
}
?>






