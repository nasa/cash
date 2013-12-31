<?php
require 'stateless.php';

/*******************************************************************************
 ************************   Global Variables   *********************************
 ******************************************************************************/
$logFilePath = "";
$logFileName = "cash.log";
$contentXML;
$configXML;
$configFileName = 'config.xml';
$defaultSecurityValue;
$user = $_SERVER["REMOTE_USER"];
if ($user == null || $user == "") {
    $user = "anonymous";
}
$usersGroups = array();
$aclDict = array();
$restrictedDirectoriesDict = array();
$startPage;
$mainContentFilePath;
$domainName;
$pathToXMLFiles = "";
$savedACD;

$ldapAddress;
$ldapPort;
$organizationalUnit1;
$organizationalUnit2;
$organization;
$countryNaming;
$useLDAP;
$defaultSecurityInLDAPAbsence;

$loggingEnabled = false;
$DEBUG = false;

// The following line is used in conjunction with logMessage()
date_default_timezone_set('America/Los_Angeles');
/******************************************************************************/





/*******************************************************************************
 **************************   Helper Functions   *******************************
 ******************************************************************************/

/**
 * Writes the given message to the pre-defined log filepath.
 *
 * @param string $message
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function logMessage($message) {
    global $logFilePath;
    global $logFileName;
    global $loggingEnabled;

    if ($loggingEnabled) {
        $newSession = false;
        if ($logFilePath == null || $logFilePath == "") {
            $logFilePath = getAdjustedCurrentDirectory()."/".$logFileName;
        }
        if (!file_exists($logFilePath)) {
            $temp = fopen($logFilePath, "a");
            fclose($temp);
            $newSession = true;
        }
        // Open log file
        $openFile = fopen($logFilePath, "a"); /* "a" = append mode. This places
                                                 the file pointer at the end of
                                                 the file and allows you to
                                                 write to, but not read the
                                                 file. */
        // Get date and time
        $dateAndTime = "[".date("Y/m/d h:i:s", mktime())."] ";

        // Write to file
        if ($newSession) {
            fwrite($openFile, $dateAndTime."----- Starting new session ----\n");
        }
        if (!fwrite($openFile, $dateAndTime.$message."\n")) {
            echo "Failed to write to log!!!";
        }

        // Close the file
        fclose($openFile);
    }
}



/**
 * Sets the global variable that specifies the path to all the xml files
 *
 * Reads the desired path from map.xml. This function is called by index.php and
 * loadURL.php
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function setXMLLocation() {
    global $pathToXMLFiles;
    $path = getPathToXMLFile("map.xml");
    $xml = simplexml_load_file($path);
    $xmlDirectory = $xml->xml_directory[0];
    $pathToXMLFiles = (string)$xmlDirectory;
}


/**
 * Returns the root directory of the framework instance, regardless of which
 * php files are in use. Should always be called instead of getcwd().
 *
 * This function solves the problem of sometimes working from root and sometimes
 * from resources/scripts.
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getAdjustedCurrentDirectory() {
    global $logFilePath;
    global $loggingEnabled;
    global $savedACD;

    if ($savedACD == null || $savedACD == "") {
        $originalLoggingSetting = $loggingEnabled;
        if ($logFilePath == null || $logFilePath == "") {
            $loggingEnabled = false;
        }
        $header = "getAdjustedCurrentDirectory(): ";

        // Get current working directory
        $currentDirectoryPath = getcwd();
        logMessage("$header Get current working directory --> ".
                    $currentDirectoryPath);

        // Determine the position of /resources/ in the current directory
        $resourcesPosition = strpos($currentDirectoryPath, "/resources/");
        logMessage("$header Determine the position of /resources/ in the ".
                   "current working directory --> $resourcesPosition");

        $adjustedDirectoryPath;
        if ($resourcesPosition == false) {
            $adjustedDirectoryPath = $currentDirectoryPath;
        } else {
            $adjustedDirectoryPath = substr($currentDirectoryPath, 0,
                                            $resourcesPosition);
        }
        logMessage("$header Return adjustedDirectoryPath = ".
                    $adjustedDirectoryPath);
        $loggingEnabled = $originalLoggingSetting;
        $savedACD = $adjustedDirectoryPath;
    } else {
        $adjustedDirectoryPath = $savedACD;
    }
    return $adjustedDirectoryPath;
}


/**
 * Determines the the absolute path to the xml files directory
 * @return string Absolute path to the xml files directory
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getPathToXMLFiles() {
    global $pathToXMLFiles;
    $output = "";
    if ($pathToXMLFiles == null || $pathToXMLFiles == "") {
        $output = getAdjustedCurrentDirectory();
    } else {
        $output = $pathToXMLFiles;
    }
    return $output;
}


/**
 * Determines the absolute path to the given xml file
 * @param string $fileName
 * @return string Absolute path to the given xml file name
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getPathToXMLFile($fileName) {
    return getPathToXMLFiles()."/".$fileName;
}


/**
* Takes in the current users group and contents security level and looks at the
* permissions dictionary that is retrieved after parsing the config file in
* order to analyze whether that user has valid clearance 
*
* @param array $currentUsersGroups
* @param string $contentsACL
*
* @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
*/
function hasAccess($currentUsersGroups, $contentsACL){
    global $defaultSecurityValue;
    global $aclDict;
    global $useLDAP;
    $header = "hasAccess(): ";
    logMessage("$header Checking access for groups: ".
                getArrayString($currentUsersGroups).
                " and contents ACL: $contentsACL");
    $allowedGroups = array();
    $accessGranted = false;
    if ($contentsACL == null) {
        logMessage("$header Received null contentsACL. Set contentsACL to the ".
                   "default value: '$defaultSecurityValue'");
        $contentsACL = $defaultSecurityValue;
    }
    //Look up groups contained within $contentsACL
    if (array_key_exists($contentsACL, $aclDict)) {
        $allowedGroups = $aclDict[$contentsACL];
    } else {
        logMessage("$header No acl with name '$contentsACL' exists");
    }
    logMessage("$header After dict lookup, allowedGroups = ".
                getArrayString($allowedGroups));
    if ($allowedGroups != null) {
        foreach ($currentUsersGroups as $usersGroup) {
            if (in_array($usersGroup, $allowedGroups)) {
                $accessGranted = true;
                break;
            }
        }
    } else if (!$useLDAP && $contentsACL == "public") {
        $accessGranted = true;
    }
    if ($accessGranted) {
        logMessage("$header determined that groups ".
                getArrayString($currentUsersGroups)." provide sufficient ".
                "security clearance to view content with acl: ".$contentsACL);
    } else {
        logMessage("$header determined that groups ".
                   getArrayString($currentUsersGroups)." DO NOT provide ".
                   "sufficient security clearance to view content with acl: ".
                   $contentsACL);
    }
    return $accessGranted;
}


/**
 * Determines whether the current user has access to the given xml element
 * @param SimpleXMLElement $xmlElement
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function hasAccessTo($xmlElement) {
    global $usersGroups;
    global $defaultSecurityValue;
    $securityRequirement = getCurrentSecurityRequirement($xmlElement,
                                                         $defaultSecurityValue);
    return hasAccess($usersGroups, $securityRequirement);
}


/**
 * Attempts to determine the ACL name associated with the given url
 *
 * @param string $url
 * @return string ACL name returned from getRestrictedDirectoryACL($localPath)
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getACLforURL($url) {
    global $restrictedDirectoriesDict;
    $header = "getACLforURL(): ";

    // Extract directory from url
    $localPath = getLocalPathFromURL($url);
    logMessage("$header localPath = $localPath");
    // Get ACL
    $acl = getRestrictedDirectoryACL($localPath);
    logMessage("$header acl = $acl");
    return $acl;
}


/**
 * Determines the access control list that is associated with the given
 * restricted directory
 * @param string $restrictedDirectoryPath
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getRestrictedDirectoryACL($restrictedDirectoryPath) {
    global $restrictedDirectoriesDict;
    $header = "getRestrictedDirectoryACL(): ";
    logMessage("$header checking restrictedDirectoriesDict for ".
                $restrictedDirectoryPath);
    $acl = "";
    // Check directory against list of restricted directories
    foreach ($restrictedDirectoriesDict as $path=>$aclName) {
        if (strpos($path, "/") != strlen($path)) {
            $path = $path."/";
        }
        logMessage("$header Checking ".(string)$path);
        //if (strpos($restrictedDirectoryPath, (string)$path) === 0) {
        if (directoryAIsChildOfDirectoryB($restrictedDirectoryPath,
                                          (string)$path)) {
            /* We should enter the body of this if statement when
               $restrictedDirectoryPath starts with $path. */
            logMessage("$header Found containing directory for ".
                $restrictedDirectoryPath);
            $acl = $aclName;
            break;
        }
    }
    return $acl;
}


/**
 * Determines the current directory name, relocating to ~CASH_ROOT if under
 * resources
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getAdjustedCurrentDirectoryName() {
    $header = "getAdjustedCurrentDirectoryName(): ";
    logMessage("$header First, get adjusted directory path");
    $adjustedDirectoryPath = getAdjustedCurrentDirectory();
    $currentDirectoryName = getDirectoryNameFromPath($adjustedDirectoryPath);
    logMessage("$header Next, determine current directory name based on last ".
               "forward slash --> '$currentDirectoryName'");
    return $currentDirectoryName;
}


/**
 * Determines whether the given url is absolute or relative
 *
 * @param string $url
 *
 * @return boolean True if absolute, false if relative
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function isAbsoluteURL($url) {
    global $domainName;
    $header = "isAbsoluteURL(): ";
    $isAbsolute = false;
    if (strpos($url, "/") === 0) {
        logMessage("$header '$url' begins with / and thus is absolute");
        $isAbsolute = true;
    } else if (strpos($url, "http") === 0) {
        logMessage("$header '$url' begins with 'http' and thus is absolute");
        $isAbsolute = true;
    } else if (strpos($url, $domainName)) {
        /* Should arrive here if $domainName occurs within the first 10
           characters of $url */
        logMessage("$header '$url' contains '$domainName' within the ".
                   "first 10 characters and thus is absolute");
        $isAbsolute = true;
    } else {
        logMessage("$header '$url' is relative");
    }
    return $isAbsolute;
}




/**
 * Given a url, determines the local path to the linked file
 * @param string $url
 * @return string Returns the local path, if it can be determined. Otherwise,
 *                returns the original url
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getLocalPathFromURL($url) {
    /**
     * Algorithm:
     *   1. Determine whether $url is absolute or relative
     *   2. If absolute, find matching target. If none is found, return
     *      original URL.
     *   3. If relative, append to adjusted current directory
     *
     */
    global $domainName;
    $header = "getLocalPathFromURL(): ";
    $output = "";
    
    logMessage("$header Attempting to get local path from '$url'");

    if (isAbsoluteURL($url)) {
        // Check for $domainName
        logMessage("$header url, '$url' is an absolute url. Check for ".
                   "'$domainName' in '$url'.");
        $domainNamePosition = strpos($url, $domainName);
        if ($domainNamePosition != false) {
            logMessage("$header '$domainName' exists at position ".
                       "'$domainNamePosition'");
            // Replace $domainName with /www
            logMessage("$header Replace the the '$domainName' section ".
                       "of '$url' with '/www'.");
            $output = "/www".substr($url,
                                       $domainNamePosition+
                                       strlen($domainName));
            logMessage("$header After doing so, the url becomes '$output'");
        } else {
            logMessage("$header '$domainName' does not exist in url: ".
                       "'$url'");
            $output = $url;
        }
    } else {
        $adjustedDirectoryPath = getAdjustedCurrentDirectory();
        logMessage("$header url, '$url' is a relative url. Append it to the ".
                   "adjusted current directory path, '$adjustedDirectoryPath'");
        $output = $adjustedDirectoryPath."/".$url;
    }
    return $output;
}


/**
* query LDAP for the current users group and return its value
* takes in associative array returned from getPermissions
* returns an array of all groups the user belongs to mentioned in the config
*
* @return array
* @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
* @author Delvison Castillo
*/
function getUsersGroups() {
  global $user;
  global $usersGroups;
  global $aclDict;

  $header = "getUsersGroups(): ";
  logMessage("$header Trying to get user's group");
  $groupsUserBelongsTo = array(); //array to be returned
  $groupsInConfig = array();
  //logMessage("$header aclDict = ".var_dump($aclDict));
  foreach ((array)$aclDict as $name=>$groups) {
      logMessage("$header name = $name");
    foreach ($groups as $group){
        logMessage("$header found group: $group");
        array_push($groupsInConfig, $group);
    }
  }
  $groupsInConfig = array_unique($groupsInConfig);
  $usersGroups = (array)@queryLDAP($groupsInConfig); // Suppress warnings from
                                                     // this function call
  logMessage("LDAP query determined user: $user belonged to groups: ".
             getArrayString($usersGroups));
  return $usersGroups;
}


/**
 * Generates the DN string that's needed to query the LDAP Server
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getLDAPQueryDN($group) {
    global $organizationalUnit1, $organizationalUnit2;
    global $organization;
    global $countryNaming;
    $dn = "cn=$group, ou=$organizationalUnit1, ou=$organizationalUnit2, ".
          "o=$organization, c=$countryNaming";
    return $dn;
}


/**
 * Generates the value string that's needed to query the LDAP Server
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getLDAPQueryValue() {
    global $user;
    global $organizationalUnit1, $organizationalUnit2;
    global $organization;
    global $countryNaming;
    $value = "uid=$user, ou=$organizationalUnit1, ou=$organizationalUnit2, ".
             "o=$organization, c=$countryNaming";
    return $value;
}


/**
 * Generates the attribute string that's needed to query the LDAP Server
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getLDAPQueryAttribute() {
    global $attr;
    return $attr;
}


/**
 * This function queries the LDAP server to determine whether a given user and
 * group combination exists. This function returns an array of groups the given
 * user belongs to
 *
 * @param array $groupsInConfig
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 * @author Delvison Castillo
 */
function queryLDAP($groupsInConfig) {
    $groupsUserBelongsTo = array();
    global $ldapAddress;
    global $ldapPort;
    global $useLDAP;
    //LDAP QUERIES HERE
    if (!$useLDAP) {
        $groupsUserBelongsTo = array("public");
        return $groupsUserBelongsTo;
    }
    if ($ldapAddress == "" || $ldapPort == "") {
        return null;
    }
    $ldap = ldap_connect($ldapAddress, $ldapPort);
    logMessage("queryLDAP() Tried to connect to LDAP and got the following ".
               "object: $ldap");
    //$bindedLDAP = ldap_bind($ldap); //binded for read access
    if ($ldap && ldap_bind($ldap)) {
        logMessage("queryLDAP() is beginning to query groups from config");
        foreach ($groupsInConfig as $group){
            // prepare data
            $dn = getLDAPQueryDN($group);
            $value = getLDAPQueryValue();
            $attr = getLDAPQueryAttribute();
            logMessage("queryLDAP() prepared a query with dn: $dn, value: ".
                       "$value, and attr: $attr");
            $query = ldap_compare($ldap, $dn, $attr, $value);
            logMessage("queryLDAP(): The query returned: ".
                        ($query ? "true" : "false"));
            if ($query === -1){
                //AN ERROR HAS OCCURED
                $groupsUserBelongsTo = array("LDAP Error");
                logMessage("LDAP ERROR");
            } else if ($query) {
                array_push($groupsUserBelongsTo, $group);
                logMessage("queryLDAP() pushed group: $group to list of groups ".
                           "the user belongs to");
            }
        }
    } else {
        $groupsUserBelongsTo = array("Invalid LDAP Connection");
    }
    return $groupsUserBelongsTo;
}


/**
 * Retrieves the current user's username
 * @return string
 * @author Delvison Castillo
 */
function getUser(){
    GLOBAL $user;
    return $user;
}


/**
 * Retrieves the list of groups the current user is a member of
 * @return array
 * @author Delvison Castillo
 */
function getGroups(){
  GLOBAL $usersGroups;
  return $usersGroups;
}


/**
 * Generates the html output for all css, javascript, and php declarations
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getAllScriptDeclarations() {
    $output = "";
    $output = "<base href=";
    $path = getSubstringOfAAfterOccuranceOfB(getAdjustedCurrentDirectory(),
                                             "/www")."/";
    $output .= "'$path'>\n";
    $output .= generateScriptHTML("resources/css");
    $output .= generateScriptHTML("resources/scripts");
    $output .= generateScriptHTML("css");
    $output .= generateScriptHTML("");
    return $output;
}


/**
* Takes in a directory and returns appropriate html tags for js and css files
* included in the directory passed.
*
* @param string $dir Some directory
* @return string The output string contains all <link rel="stylesheet"...
* references
*
* @author Delvison Castillo
*/
function generateScriptHTML($dir){
    $path = getAdjustedCurrentDirectory() . "/" . $dir;
    $script_string="";
    if (file_exists($path)){
        $dir_contents = scandir($path);
        // Grab styles.css first
        $filePath = "$dir/styles.css";
        if (file_exists($filePath)) {
            $script_string .= generateScriptHTMLFromFilePath($filePath);
        }
        foreach ($dir_contents as $file){
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            $filePath = "$dir/$file";
            if ($file != "styles.css" || !file_exists($filePath)) {
                $script_string .= generateScriptHTMLFromFilePath($filePath);
            }
        }
    }
    return $script_string;
}


/**
 * @param string $filepath
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function generateScriptHTMLFromFilePath($filePath) {
    $extension = pathinfo($filePath, PATHINFO_EXTENSION);
    $output = "";
    switch ($extension) {
        case "css":
            $output .= "<link rel=\"stylesheet\" ".
                              "href=\"$filePath\">\n";
            break;
        case "js":
            $output .= "<script src=\"$filePath\"></script>\n";
            break;
    }
    return $output;
}


/**
* Takes in a url, determines whether that url is foreign or not
* and returns a boolean 
*
* @param string $url
*
* @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
*/
function is_link_external($url){
    global $domainName;
    global $DEBUG;
    $header = "is_link_external(): ";
    logMessage("$header Input link = '$url'");
    $is_external = false;
    $relativeTeamPathUnderWWW = getAdjustedCurrentDirectory();
    logMessage("$header relativeTeamPathUnderWWW = ".
               "'$relativeTeamPathUnderWWW'");
    $strippedURL = getLocalPathFromURL($url);
    $strippedURL = stripLeadingSlash($strippedURL);
    $relativeTeamPathUnderWWW = stripLeadingSlash($relativeTeamPathUnderWWW);
    if ($DEBUG && isAbsoluteURL($url)) {
        $prototypeName = getDirectoryNameFromPath($relativeTeamPathUnderWWW);
        $relativeTeamPathUnderWWW = getSubstringOfABeforeOccuranceOfB(
                                                      $relativeTeamPathUnderWWW,
                                                      $prototypeName);
    }
    logMessage("$header Stripped url = '$strippedURL'");
    if (directoryAIsChildOfDirectoryB($strippedURL,
                                      $relativeTeamPathUnderWWW)) {
        logMessage("$header '$strippedURL' is a child of ".
                   "'$relativeTeamPathUnderWWW'. Set external to false.");
        $is_external = false;
    } else {
        $is_external = true;
        logMessage("$header '$strippedURL' is not a child of ".
                   "'$relativeTeamPathUnderWWW'. Set external to true.");
    }
    return $is_external;
}



/**
 * Parses an xml element that has <link> children and returns the necessary html
 * @param SimpleXMLElement $xmlElement
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function parseLink($xmlElement) {
    global $defaultSecurityValue;
    global $usersGroups;
    $output = "";
    $parentSecurityRequirement = getCurrentSecurityRequirement($xmlElement,
                                                      $defaultSecurityValue);
    $domElement = dom_import_simplexml($xmlElement);
    if ($domElement->hasChildNodes()) {
        $childList = $domElement->childNodes;
        foreach ($childList as $child) {
            $nodeName = $child->nodeName;
            switch ($child->nodeType) {
            case XML_TEXT_NODE:
                $output .= $child->nodeValue;
                break;
            case XML_ELEMENT_NODE:
                if ($nodeName == "link") {
                    $simpleXML = simplexml_import_dom($child);
                    $securityRequirement =
                        getCurrentSecurityRequirement($simpleXML,
                                                    $parentSecurityRequirement);
                    if (hasAccess($usersGroups, $securityRequirement)) {
                        $output .= getElementContentWithStyleTags($simpleXML,
                                                                  false, "");
                    }
                }
                break;
            }
        }
    }
    return $output;
}
/******************************************************************************/









/*******************************************************************************
 ************************  XML Parsing (Stateful)  *****************************
 ******************************************************************************/

/**
 * Calls the necessary function to begin xml parsing
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function initializeMainContent() {
    global $mainContentFilePath;
    $header = "initializeMainContent(): ";
    logMessage("$header Initializing main content...");
    parseConfig();
    return initializeContent($mainContentFilePath);
}



/**
 * Reads in an .xml file and returns a SimpleXMLElement object that represents
 * the file.
 *
 * Note that because this function must be called first, calls to parseConfig()
 * and getUsersGroups() have been added to ensure that all necessary parsing
 * will be complete by the time the content is needed for use.
 *
 * @param $xmlFile This parameter can either be a SimpleXMLElement or a string.
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function initializeContent($xmlFile) {
    global $contentXML;

    if (is_null($contentXML)){
        if ($xmlFile instanceof SimpleXMLElement) {
            $contentXML = $xmlFile;
        } else {
            $xmlFile = getPathToXMLFile($xmlFile);
            if (file_exists($xmlFile)) { //$xmlFile should be a string
                $contentXML = simplexml_load_file($xmlFile);
            } else {
                return null;
            }
        }
        logMessage("Loaded content xml into memory");
        getUsersGroups();
    }
    return $contentXML;
}


/**
 * Returns the proper html textual transcription of an unordered list xml
 * element
 *
 * Note that this function takes into account security access definitions to
 * hide certain content from unauthorized visitors.
 *
 * @param SimpleXMLElement $listXML
 * @param string $inheritedSecurity
 * @param boolean $shouldAddSwapToLinks
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getListContents(SimpleXMLElement $listXML, $inheritedSecurity,
                         $shouldAddSwapToLinks, $shouldIncludeCSSMenuClass) {
    if (!isULElement($listXML)) {
        logMessage("getListContents() received a non-ul element");
        return;
    }
    // By this point, we can be sure that $listXML is a ul element
    global $defaultSecurityValue;
    global $usersGroups;
    global $user; // Used in this function purely for logging

    $header = "getListContents(): ";
    $output = "";
    $securityRequirement = "";
    //$securityRequirement = getCurrentSecurityRequirement($listXML,
    //                                                    $inheritedSecurity);
    $securityRequirement = $inheritedSecurity;
    $ulElementName = $listXML->getName();

    /* Determine whether or not the current user has access to view the current
       xml element */
    logMessage("$header Determine whether or not $user has access to view this".
               " $ulElementName element.");
    logMessage("$header Calling hasAccess() with usersGroups: '".
                getArrayString($usersGroups)."' and securityRequirement: '".
                $securityRequirement."'");

    $accessGranted = hasAccess($usersGroups, $securityRequirement);

    logMessage("$header Given user's groups, '".getArrayString($usersGroups).
               "', and the specified security ACL for this xml element, '".
               "$securityRequirement', the user's security clearance has ".
               "been identified as ".($accessGranted ? "clear" : "prohibited").
               ". Note that the defaultSecurityValue is '".
               "$defaultSecurityValue'.");

    /* As long as the current security requirement is not null, add the
       appropriate opening tag for this xml element. This is so that child
       elements of lesser security requirements can still be displayed.
     */
    $output = $output."<$ulElementName>\n";

    /* Now that the opening tag for this xml element (again, should be ul) has
       been added, we can begin traversing through the child elements to get
       content of this list.
     */
    foreach($listXML as $content) {
        // This code works under the assumption that ul elements can ONLY
        // contain li elements and that nested ul elements must be enclosed in
        // an li element.
        if (isULElement($content)) {
            // This should not have happened!!
            logMessage("$header Encountered a ul element when we should have ".
                       "only been dealing with li elements.");
        }

        // Get security requirement of current element
        $securityRequirement = getCurrentSecurityRequirement($content,
                                                            $inheritedSecurity);
        if ($securityRequirement == null || $securityRequirement == "") {
            $securityRequirement = $inheritedSecurity;
        }
        logMessage("$header Get security requirement of current element --> ".
                    $securityRequirement);

        // Determine whether user has access to current xml element
        logMessage("$header Determine whether '$user' has access to '".
                    trim((string)$content)."'");
        $accessGranted = hasAccess($usersGroups, $securityRequirement);



        $urlAttributeValue = getAttributeValue($content, "url");
        $elementName = $content->getName();

        $shouldExcludeClosingTag = hasChildren($content);
        if ($shouldExcludeClosingTag) {
            logMessage("$header Determined that the current xml element: ".
                       "$content has children.");
            if ($accessGranted) {
                $intermediateContent = getOutputHTML($content,
                                                     $shouldAddSwapToLinks,
                                                     true,
                                                     $shouldIncludeCSSMenuClass);
                $output = $output.$intermediateContent;
                logMessage("$header Added $intermediateContent to output");
                $children = $content->children();
                // First child should be a ul element
                logMessage("$header Begin recursive call...");
                $output = $output.getListContents($children[0],
                                                  $securityRequirement,
                                                  $shouldAddSwapToLinks,
                                                  $shouldIncludeCSSMenuClass);
                // Close the li tag
                $output = $output."</li>\n";
            }
        } else {
            if ($accessGranted) {
                $output = $output.getOutputHTML($content, $shouldAddSwapToLinks,
                                                false, false);
            }
        }
    }
    $output = $output."</$ulElementName>\n";
    return $output;
}


/**
* Parses the content of the xml file and outputs its HTML
* representation.
*
* Assumes you have given it the content xml element
* 
* @param SimpleXMLElement $contentBlock
* @param string $inheritedSecurity
*
* @return string
* @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
* @author Delvison Castillo
*/
function getContent($contentBlock, $inheritedSecurityValue) {
    global $usersGroups;
    global $defaultSecurityValue;

    if ($inheritedSecurityValue == "") {
        $inheritedSecurityValue = $defaultSecurityValue;
    }

    $header = "getContent(contentBlock): ";
    logMessage("$header Attempting to get content");

    // Determine number of column declarations
    $columnBlocks = $contentBlock->column;
    $numberOfColumns = 0;
    $numberOfColumnsEncountered = 0;
    foreach ($columnBlocks as $columnBlock) {
        $securityValue = getCurrentSecurityRequirement($columnBlock,
                                                       $defaultSecurityValue);
        if(hasAccess($usersGroups, $securityValue)) {
            $numberOfColumns++;
        }
    }
        
    $contentString = "";
    if ($contentBlock == null) {
        logMessage("$header contentBlock is null. This may indicate trying to ".
                   "access a file on a remote server.");
    }
    logMessage("$header Beginning to iterate through xml elements in the ".
               "content block");
    foreach ($contentBlock as $xmlElement) {
        $elementName = $xmlElement->getName();
        $securityValue = getCurrentSecurityRequirement($xmlElement,
                                                       $inheritedSecurityValue);
        $accessGranted = hasAccess($usersGroups, $securityValue);
        //IF THE ELEMENT IS A UL ELEMENT
        if (isULElement($xmlElement)) {
            logMessage("$header Encountered unordered list. Send duties to ".
                "getListContents().");
            //Don't need to worry about checking security here because
            //getListContents() handles it
            $contentString .= getListContents($xmlElement, "", true, false);
        //PARSE TEXT COLUMN ELEMENTS
        } else if ($elementName == "column") {
            if ($accessGranted) {
                $numberOfColumnsEncountered++;
                $contentString .= "<div style='float: ";
                if ($numberOfColumnsEncountered >= $numberOfColumns) {
                    $contentString .= "right";
                } else {
                    $contentString .= "left";
                }
                $percentage = round(100/$numberOfColumns);
                $contentString .= "; width: ".$percentage."%;'>\n";
                $contentString .= getContent($xmlElement, "");
                $contentString .= "</div>\n";
            }
        
        //PARSE TABLE ELEMENTS
        } else if ($elementName == "table") {
            if (hasAccess($usersGroups, $securityValue)) {
                $contentString .= "<table style='width: 100%;'>\n".
                                  getContent($xmlElement, $securityValue).
                                  "</table>\n";
            }
        
        //PARSE ROW ELEMENTS
        } else if ($elementName == "row") {
            if (hasAccess($usersGroups, $securityValue)) {
                $contentString .= "<tr>\n".getContent($xmlElement,
                                                      $securityValue)."</tr>\n";
            }
        
        //PARSE COL ELEMENTS 
        } else if ($elementName == "cell") {
            if (hasAccess($usersGroups, $securityValue)) {
                $contentString .= getOutputHTMLWithCustomTag("td", $xmlElement,
                                                            true, false, false);
            } else {
                $contentString .= "<td></td>"; 
            }

        //PARSE TABLE TITLES
        } else if ($elementName == "col_title") {
            if (hasAccess($usersGroups, $securityValue)) {
                $contentString .= getOutputHTMLWithCustomTag("th", $xmlElement,
                                                            true, false, false);
            } else {
                $contentString .= "<th></th>"; 
            }
        } else if ($elementName == 'img') {
            $contentString .= getImageHTML($xmlElement, "150px", "auto");
        } else if ($elementName == "embed") {
            if ($accessGranted) {
                $contentString .= stripEnclosingXMLTag($xmlElement);
            }
        } else {
            if ($accessGranted) {
                logMessage("$header Security has been granted for ".
                           "<$elementName>");
                $contentString = $contentString.getOutputHTML($xmlElement, true,
                                                              false, false);
            } else {
                logMessage("$header Security has not been granted for ".
                           "<$elementName>");
            }
        }
    }
    return $contentString;
}


/**
 * Currently, this function does nothing more than return getContent().
 * It exists in case we run into a situation where we need to do some special
 * processing befor or after calling getContent().
 *
 * @param SimpleXMLElement $inputXML
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getInnerContent($inputXML) {
    global $contentXML;
    $header = "getInnerContent(): ";
    logMessage("$header Attempting to get inner content");
    parseConfig(); // Must parse config first to set global variables
    initializeContent($inputXML);
    $output = "<html>\n<head>\n";
    $output = $output.getAllScriptDeclarations();
    $output = $output . "</head>\n<body>\n";
    $output = $output.getContent($contentXML->content[0], "");
    $output = $output . "</body>\n</html>";
    //logMessage("$header $output");
    return $output;
}


/**
 * This function examins an xml element and returns its specified
 * security requirement.
 *
 * @param SimpleXMLElement $xmlElement
 * @param string $fallbackSecurityValue
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getCurrentSecurityRequirement(SimpleXMLElement $xmlElement,
                                       $fallbackSecurityValue) {
    global $defaultSecurityValue;
    $header = "getCurrentSecurityRequirement(): ";
    $output = "";
    $urlACL = "";
    if ($fallbackSecurityValue == null || $fallbackSecurityValue == "") {
        $fallbackSecurityValue = $defaultSecurityValue;
    }
    logMessage("$header Attempting to get current security requirement for\n".
                $xmlElement->asXML());
    $attributeValue = getAttributeValue($xmlElement, "security");
    logMessage("$header security attribute = $attributeValue");
    $url = getAttributeValue($xmlElement, "url");
    logMessage("$header url attribute = $url");
    if ($url != null) {
        $urlACL = getACLforURL($url);
    }
    logMessage("$header urlACL = $urlACL");
    if ($attributeValue != null) {
        $output = $attributeValue;
    } else if ($urlACL != null) {
        $output = $urlACL;
    } else {
        $output = $fallbackSecurityValue;
        logMessage("$header acl not found. Set it to '$fallbackSecurityValue'");
    }
    return (string)$output;
}


/**
 * parses the config file
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 * @author Delvison Castillo
 */
function parseConfig() {
    global $configFileName;
    global $configXML;
    global $user;
    global $aclDict;
    global $restrictedDirectoriesDict;
    global $startPage;
    global $mainContentFilePath;
    global $defaultSecurityValue;
    global $ldapAddress;
    global $ldapPort;
    global $organizationalUnit1, $organizationalUnit2;
    global $organization, $countryNaming, $attr;
    global $useLDAP;
    global $defaultSecurityInLDAPAbsence;
    global $domainName;

    $logHeader = "parseConfig()";

    //$pathToXMLFiles = getPathToXMLFiles();
    $configFilePath = getPathToXMLFile($configFileName);
    logMessage("$logHeader configFilePath = $configFilePath");
    $configXML = simplexml_load_file($configFilePath);
    logMessage("parseConfig() loaded config file and will now begin parsing");
    //parse access_config
    $accessConfigXML = $configXML->access_config[0];
    foreach ($accessConfigXML as $xmlElement){
        $elementName = $xmlElement->getName();
        if ($elementName == "acl") {
            $aclName = (string)$xmlElement->name;
            $groups = $xmlElement->group;
            $groupsArray = array();
            foreach ($groups as $groupItem) {
                array_push($groupsArray, (string)$groupItem);
            }
            logMessage("$logHeader Encountered acl element with name = ".
                       "$aclName and groups = ".getArrayString($groupsArray));
            $tempArray = array((string)$aclName => $groupsArray); /*Had to cast
                                                        $aclName to string here
                                                        to avoid an illegal
                                                        offset type warning*/

            $aclDict = array_merge($aclDict, $tempArray); /* Had to use
                                                             array_merge instead
                                                             of array_push */
        } else if ($elementName == "restricted_directory") {
            $path = (string)$xmlElement->path;
            $acl_name = (string)$xmlElement->acl_name;
            logMessage("$logHeader Encountered restricted_directory element ".
                       "with path = $path and acl_name = $acl_name");
            $restrictedDirectoriesDict[$path] = $acl_name;
        }

    }
    $siteConfigXML = $configXML->site_config[0];
    foreach ($siteConfigXML as $xmlElement) {
        $elementName = $xmlElement->getName();
        $stringValue = (string)$xmlElement;
        switch ($elementName) {
            case "start_page":
                $startPage = $stringValue; break;
            case "main_content":
                $mainContentFilePath = $stringValue; break;
            case "default_security":
                $defaultSecurityValue = $stringValue; break;
            case "domain_name":
                $domainName = $stringValue; break;

        }
    }
    $ldapConfigXML = $configXML->ldap_config[0];
    foreach ($ldapConfigXML as $xmlElement) {
        $elementName = $xmlElement->getName();
        $stringValue = getTrimmed((string)$xmlElement);
        switch ($elementName) {
            case "address":
                $ldapAddress = $stringValue; break;
            case "port":
                $ldapPort = $stringValue; break;
            case "organizational_unit1":
                $organizationalUnit1 = $stringValue; break;
            case "organizational_unit2":
                $organizationalUnit2 = $stringValue; break;
            case "organization":
                $organization = $stringValue; break;
            case "country_naming":
                $countryNaming = $stringValue; break;
            case "attribute":
                $attr = $stringValue; break;
            case "use_ldap":
                $useLDAP = $stringValue == "true"; break;
            case "default_security_in_ldap_absence":
                $defaultSecurityInLDAPAbsence = $stringValue; break;
        }
    }
    if (!$useLDAP) {
        $defaultSecurityValue = $defaultSecurityInLDAPAbsence;
    }
    logMessage("$logHeader finished parsing and produced aclDict = '".
                getArrayString($aclDict)."' and restrictedDirectoriesDict = '".
                getArrayString($restrictedDirectoriesDict)."'");
}
/******************************************************************************/







/*******************************************************************************
 ************************  XML Parsing (Stateless)  ****************************
 ******************************************************************************/

/**
 * Returns the html title entry
 *
 * @param SimpleXMLElement $contentXML
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getTitleHTML(SimpleXMLElement $contentXML) {
    $tag = "title";
    $content = $contentXML->header[0]->title;
    $output = "<$tag>$content</$tag>\n";
    logMessage("Got html title: $output");
    return $output;
}


/**
 * Returns all parts of the html header that are not the title.
 *
 * @param SimpleXMLElement $contentXML
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getHeaderHTML(SimpleXMLElement $contentXML) {
    $header = "getHeaderHTML(): ";
    $headerContent = $contentXML->header[0]; // There should only be one header
    $output = "";
    foreach($headerContent as $content) {
        $elementName = $content->getName();
        if ($elementName == "img") {
            logMessage("$header Encountered img element. Add it to output");
            $output .= getImageHTML($content, "auto", "60px");
        } else if ($elementName == "title") {
            logMessage("$header Encountered title element. Do nothing.");
        } else {
            logMessage("$header Encountered unknown element. Add it to output");
            $output .= getOutputHTML($content, false, false, false);
        }
    }
    return $output;
}


/**
 * Returns the html code for the global navigation bar, located at the top of
 * the webpage.
 *
 * @param SimpleXMLElement $contentXML
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getNavBarHTML($contentXML) {
    $header = "getNavBarHTML(): ";
    $navBarContent = $contentXML->navbar[0]; // Only one navbar[0]
    $output = "";
    $navList = "<ul class='navlist'>\n";
    foreach($navBarContent as $content) {
        $elementName = $content->getName();
        if ($elementName == "img") {
            $output .= getImageHTML($content, "auto", "43px");
        } else if($elementName == "li") {
            logMessage("$header Encountered li element. Add it to output.");
            $navList .= getOutputHTML($content, false, false, false);
        }
    }
    $output .= "$navList</ul>\n";
    return $output;
}


/**
 * A convenience function that takes an <img> xml element and outputs
 * the appropriate HTML code
 *
 * Input Parameters: SimpleXMLElement
 * @param SimpleXMLElement $imgXMLElement
 * @param string $imageWidth
 * @param string $imageHeight
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getImageHTML(SimpleXMLElement $imgXMLElement,
                      $imageWidth, $imageHeight)  {
    $output = "";
    if (hasAccessTo($imgXMLElement)) {
        $urlPrefix = "";
        $urlSuffix = "";
        if ($imageWidth == null) {
            $imageWidth = "auto";
        }
        if ($imageHeight == null) {
            $imageHeight = "auto";
        }
        $elementName = $imgXMLElement->getName();
        $src = getAttributeValue($imgXMLElement, "src");
        $width = getAttributeValue($imgXMLElement, "width");
        $height = getAttributeValue($imgXMLElement, "height");
        $url = getAttributeValue($imgXMLElement, "url");
        $cssEmbedAttributeValue = getAttributeValue($imgXMLElement, "css_embed");
        $cssClassValue = getAttributeValue($imgXMLElement, "css_class");
        if ($url != null && $url != "") {
            $urlPrefix = "<a href=$url>";
            $urlSuffix = "</a>";
        }
        if ($width == null) {
            $width = "auto";
        }
        if ($height == null) {
            $height = "auto";
        }
        $altText = (string)$imgXMLElement;
        if ($altText != null && $altText != "") {
            $altText = "alt=\"$altText\"";
        }
        $embeddedCSSText = "width:$width; height:$height;";
        if ($cssEmbedAttributeValue != null) {
            $embeddedCSSText .= $cssEmbedAttributeValue;
        }
        if ($cssClassValue != null) {
            $cssClassValue = " class='$cssClassValue'";
        } else {
            $cssClassValue = "";
        }
        if ($elementName == "img") {
            $output = "$urlPrefix<$elementName$cssClassValue"." style=".
                      "'$embeddedCSSText' src='$src' $altText/>$urlSuffix\n";
        }
    }
    return $output;
}


/**
 * Returns the entire HTML code for the navigation sidebar
 * Calls getNavigationTitle and getNagivationItems
 *
 * @param SimpleXMLElement $contentXML
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getNavigation(SimpleXMLElement $contentXML) {
    $output = "";
    $output .= getNavigationTitle($contentXML);
    $output .= getNavigationItems($contentXML);
    return $output;
}


/**
 * Returns the content for the navigation sidebar, formatted as HTML
 *
 * @param SimpleXMLElement $contentXML
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getNavigationItems(SimpleXMLElement $contentXML) {
    $header = "getNavigation(): ";
    $navigationContent = $contentXML->navigation[0];
    $output = "";
    foreach($navigationContent as $content) {
        $elementName = $content->getName();
        if ($elementName == "ul") {
            logMessage("$header Encountered ul element. Call ".
                       "getListContents() and add return value to output.");
            $output .= getListContents($content, "", true, true);
        }
    }
    return $output;
}


/**
 * Returns the title for the navigation sidebar, formatted as HTML
 *
 * @param SimpleXMLElement $contentXML
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getNavigationTitle(SimpleXMLElement $contentXML) {
    $header = "getNavigation(): ";
    $navigationContent = $contentXML->navigation[0];
    $output = "";
    foreach($navigationContent as $content) {
        $elementName = $content->getName();
        if ($elementName == "sidebar_title") {
            logMessage("$header Encountered title element. Format and add");
            $heading = getAttributeValue($content, "heading");
            if ($heading == null || $heading == "") {
                $heading = "h3";
            }
            $output = $output."<center>".
                    getCustomTaggedHTMLElementString($heading, $content, "",
                                                     false)."</center>\n";
            break;
        }
    }
    return $output;
}


/**
 * This function examins an xml element and returns the specified attribute
 * value
 *
 * @param SimpleXMLElement $xmlElement
 * @param string $targetAttributeName
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getAttributeValue($xmlElement, $targetAttributeName) {
    $attributes = $xmlElement->attributes();
    $output = "";
    if ($attributes != null) {
        foreach ($attributes as $attribute) {
            $currentAttributeName = $attribute->getName();
            if ($currentAttributeName == $targetAttributeName) {
                $output = $attribute;
                break;
            }
        }
    }
    return $output;
}


/**
 * Returns the html output of any given xml element
 *
 * @param SimpleXMLElement $xmlElement
 * @param boolean $shouldAddSwapToLinks Specifies whether to add 'swap' to links
 * @param boolean $shouldExcludeClosingTag Specifies whether to include the
 * closing tag
 * @param boolean $shouldInsertCSSMenuClass Specifies whether to insert the CSS
 * 'listhead' class
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getOutputHTML($xmlElement, $shouldAddSwapToLinks,
                       $shouldExcludeClosingTag, $shouldInsertCSSMenuClass) {
    return getOutputHTMLWithCustomTag($xmlElement->getName(),
                                      $xmlElement,
                                      $shouldAddSwapToLinks,
                                      $shouldExcludeClosingTag,
                                      $shouldInsertCSSMenuClass);
}


/**
 * Generates the output html code for an xml element with a custom tag
 * @param string $tagName
 *        Specifies custom tag name to use for given xml element content
 * @param SimpleXMLElement $xmlElement
 * @param boolean $shouldAddSwapToLinks
 *        Specifies whether to add 'swap' to links
 * @param boolean $shouldExcludeClosingTag
 *        Specifies whether to include the closing tag
 * @param boolean $shouldInsertCSSMenuClass
 *        Specifies whether to insert the CSS 'listhead' class
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getOutputHTMLWithCustomTag($tagName, $xmlElement,
                                    $shouldAddSwapToLinks,
                                    $shouldExcludeClosingTag,
                                    $shouldInsertCSSMenuClass) {
    $header = "getOutputHTMLWithCustomTag(): ";
    logMessage("$header\nBeginning call with:\nTagName=$tagName\n".
               "xmlElement=".getTrimmed($xmlElement)."\naddSwap=....");
    $outputHTMLAttributes = "";
    //get attribute value css class
    $cssClassAttributeValue = getAttributeValue($xmlElement, "css_class");
    //get attribute value for embedded css
    $cssEmbedAttributeValue = getAttributeValue($xmlElement, "css_embed");
    $cssMenuClassName = "";
    //insert listhead as a class if necessary
    if ($shouldInsertCSSMenuClass) {
        $cssMenuClassName = "listhead";
    }
    $styledContentString = getElementContentWithStyleTags($xmlElement,
                                                     $shouldAddSwapToLinks,
                                                     $cssMenuClassName);
    if ($shouldInsertCSSMenuClass) {
        $styledContentString = "<span class=\"$cssMenuClassName\">".
                                $styledContentString.
                               "</span>";
    }
    //print out appropriate css class
    if ($cssClassAttributeValue != null) {
        $outputHTMLAttributes = "class=\"$cssClassAttributeValue\" ";
    }
    //print out appropriate embedded css
    if ($cssEmbedAttributeValue != null) {
        $outputHTMLAttributes .= "style=\"$cssEmbedAttributeValue\" ";
    }

    $output = getCustomTaggedHTMLElementString($tagName,
                                               $styledContentString,
                                               $outputHTMLAttributes,
                                               $shouldExcludeClosingTag);
    logMessage("$header output = '$output'");
    return $output;
}


/**
 * Generates the output html code for a custom tagged xml element
 * @param string $tagName
 * @param string $xmlElementContentWithStyling
 * @param string $outputHTMLAttributes
 * @param boolean $shouldExcludeClosingTag
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getCustomTaggedHTMLElementString(
                              $tagName, $xmlElementContentWithStyling,
                              $outputHTMLAttributes, $shouldExcludeClosingTag) {
    $header = "getCustomTaggedHTMLElementString(): ";
    logMessage("$header Starting function call...\n".
               "tagName = '$tagName'\n".
               "xmlElementContentWithStyling = ".
               "'$xmlElementContentWithStyling'\n".
               "outputHTMLAttributes = '$outputHTMLAttributes'\n".
               "shouldExcludeClosingTag = some boolean");
    if ($outputHTMLAttributes == null) {
        $outputHTMLAttributes = "";
    } else if ($outputHTMLAttributes != "") {
        $outputHTMLAttributes = " ".$outputHTMLAttributes;
    }
    $output = "<$tagName$outputHTMLAttributes>$xmlElementContentWithStyling";
    if ($shouldExcludeClosingTag) {
        $output = $output."\n";
    } else {
        $output = $output."</$tagName>\n";
    }
    logMessage("$header Return:\n$output");
    return $output;
}


/**
 * Parses the style tags of the given xml element and uses them to generate
 * the associated html code.
 * @param SimpleXMLElement $xmlElement
 * @param boolean $shouldAddSwapToLinks
 * @param string $cssMenuClassName
 *
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getElementContentWithStyleTags($xmlElement, $shouldAddSwapToLinks,
                                        $cssMenuClassName) {
    $header = "getElementContentWithStyleTags(): ";
    logMessage("$header Beginning call...\n".
               "xmlElement = '".getTrimmed($xmlElement)."'\n".
               "shouldAddSwapToLinks = some boolean\n".
               "cssMenuClassName = '$cssMenuClassName'");
    $attributes = $xmlElement->attributes();
    logMessage("$header Obtained attributes from XML Element, '$xmlElement'");
    $output = "";
    $prefix = "";
    $suffix = "";
    $urlSpecified = false;
    $urlOpenTag = "";
    $urlCloseTag = "";
    $outputHTMLAttributes = "";
    if ($attributes != null) {
        foreach ($attributes as $attribute) {
            $currentAttributeName = $attribute->getName();
            switch ($currentAttributeName) {
                case "style":
                    $styles = formatStyleAttribute($attribute);
                    foreach ($styles as $style) {
                        addToPrefix($prefix, "<$style>");
                        addToSuffix($suffix, "</$style>");
                    }
                    break;
                case "css":
                    $outputHTMLAttributes = "class=\"$attribute\"";
                    break;
                case "url":
                    // Must apply url last, so lets save it to a variable and do
                    // stuff with it later
                    $urlSpecified = true;
                    $loadURLPrefix = "";
                    if (strpos(getcwd(), "resources/scripts") == false) {
                        $loadURLPrefix = "resources/scripts/";
                    }
                    
                    // Escape any necessary characters from the url
                    $attribute = clean_url($attribute);
                    logMessage("$header Clean url = '$attribute'");
                    $target = getAttributeValue($xmlElement, "target");
                    $isExternal = is_link_external($attribute);
                    $loadURLPrefix .= "loadURL.php";
                    $extension = pathinfo($attribute, PATHINFO_EXTENSION);
                    if ($extension == "xml") {
                        $href = "'$loadURLPrefix?url=$attribute'";
                    } else {
                        $href = "'$attribute'";
                    }
                    if ($target == "new_tab" ||
                            ($target != "same_tab" &&
                             is_link_external($attribute))) {
                        $href .= " target='_blank'";
                        $shouldAddSwapToLinks = false;
                    }
                    $urlOpenTag = "<a href=$href";

                    if ($shouldAddSwapToLinks) {
                        $urlOpenTag .= " target='innerframe'";
                    }
                    if ($cssMenuClassName != "") {
                        $urlOpenTag .= " class='$cssMenuClassName'";
                    }
                    $urlOpenTag .= ">";
                    $urlCloseTag = "</a>";
            }
        }
        if ($urlSpecified) {
            addToPrefix($prefix, $urlOpenTag);
            addToSuffix($suffix, $urlCloseTag);
        }
    } else {
        logMessage("$header The XML Element, '$xmlElement' had no attributes. ".
                   "Continue without applying any attributes or styles.");
    }
    $innerContent = getTrimmed($xmlElement);
    if (hasChildren($xmlElement)) {
        $children = $xmlElement->children();
        $child = $children[0];
        if ($child->getName() == "link") {
            logMessage("$header Determined that XML Element, '".
                        getTrimmed($xmlElement)."' has link children. Set ".
                        "inner content to [recursive call]...");
            $innerContent = parseLink($xmlElement);
            logMessage("$header InnerContent is now '$innerContent' after ".
                       "calling parseLink");
        } else {
            logMessage("$header Determined xml element, '".
                        getTrimmed($xmlElement).
                        "' has children, but not LINK children");
        }
    } else {
        logMessage("$header Determined that XML Element, '".
                    getTrimmed($xmlElement)."' does not have any link ".
                    "children. Set inner content to '".getTrimmed($xmlElement).
                    "'");
    }
    $output = $prefix.$innerContent.$suffix;
    logMessage("$header Return '$output'");
    return $output;
}
/******************************************************************************/
?>
