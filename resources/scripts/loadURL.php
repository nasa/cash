<?php 
    include 'functions.php';
    setXMLLocation();

    $header = "loadURL: ";

    $url = $_GET['url'];
    logMessage("$header url=$url");
    $displayResults = "";
	$fileExtension = pathinfo($url, PATHINFO_EXTENSION);
    if ($fileExtension == "xml") {
        logMessage("$header Determined that url: '$url' is attempting to ".
                   "point to an xml file.");
        logMessage("$header Try to get local path from url: '$url'");
        //$localPath = getLocalPathFromURL($url);
        $localPath = getPathToXMLFile($url);
        if (!file_exists($localPath)){
            logMessage("$header Determined the desired file: '$localPath' ".
                       "does not exist");
            $displayResults = "File does not exist. Please check your path ".
                              "and try again.";
        } else{
            logMessage("$header Attempt to load '$localPath' as xml");
            $fileXML = simplexml_load_file($localPath);
            if ($fileXML != false) {
              $displayResults = getInnerContent($fileXML);
            } else {
                $displayResults = "Error found in the xml file that's ".
                                  "intending to be loaded. This can very ".
                                  "likely be due to syntax errors. (Remember ".
                                  "that the '&' character should be ".
                                  "represented as '&amp;amp;').";
            }
        }
    } else {
        $url = getSubstringOfAAfterOccuranceOfB(getLocalPathFromURL($url), "/www");
        // Redirect to the url that was passed in
        logMessage("$header Since file extension was not xml, redirect to the ".
                   "url that was passed in");
        header("HTTP/1.1 307 Temporary Redirect");
        header("Location: ".$url);
    }
    echo $displayResults;

?>
