<?php
/**
 * Determines whether the given xml element has children
 *
 * @param SimpleXMLElement $xmlElement
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function hasChildren(SimpleXMLElement $xmlElement) {
    $children = $xmlElement->children();
    $childrenExist = false;
    foreach ($children as $child) {
        $childrenExist = true;
        break;
    }
    return $childrenExist;
}


function hasNoLinkChildren($xmlElement) {
    return !hasChildrenOfType($xmlElement, "link");
}
function hasChildrenOfType($xmlElement, $type) {
    $children = $xmlElement->children();
    $childrenExist = false;
    $containsType = false;
    foreach ($children as $child) {
        if (!$childrenExist) {
            $childrenExist = true;
        }
        if ($child->getName() == $type) {
            $containsType = true;
        }
    }
    return $childrenExist && $containsLink;
}



/**
 * Determines whether the given xml element is a 'ul' element
 *
 * @param SimpleXMLElement $xmlElement
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function isULElement(SimpleXMLElement $xmlElement) {
    return $xmlElement->getName() == "ul";
}



/**
 * Determines whether the given xml element is a 'li' element
 *
 * @param SimpleXMLElement $xmlElement
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function isLIElement(SimpleXMLElement $xmlElement) {
    return $xmlElement->getName() == "li";
}


/**
 * Normalizes the text from a 'style' attribute so that it can be further
 * processed
 *
 * @param string $styleAttribute A style attribute might look something like:
 * "bold, italic, underlined"
 * @return array
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function formatStyleAttribute($styleAttribute) {
    $acceptedStyles = array("b", "u", "i");
    $output = array();
    $styles = explode(',', $styleAttribute);
    foreach ($styles as $style) {
        $style = trim($style);
        switch ($style) {
            case "bold":
                $style = "b";
                break;
            case "underline":
                $style = "u";
                break;
            case "underlined":
                $style = "u";
                break;
            case "italic":
                $style = "i";
                break;
            case "italics":
                $style = "i";
                break;
            case "italicized":
                $style = "i";
                break;
        }
        if (in_array($style, $acceptedStyles)) {
            array_push($output, $style);
        }
    }
    return $output;
}


/**
 * @param SimpleXMLElement $xmlElement
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getTrimmed($xmlElement) {
    return trim((string)$xmlElement);
}


/**
 * Takes a prefix string (by reference) and prepends $addition
 *
 * @param string $prefix
 * @param string $addition
 * @return string
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function addToPrefix(&$prefix, $addition) { // Take prefix in by reference
    $prefix = $addition.$prefix;
    return $prefix;
}


/**
 * Takes a suffix string (by reference) and appends $addition
 *
 * @param string $suffix
 * @param string $addition
 * @return string
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function addToSuffix(&$suffix, $addition) { // Take suffix in by reference
    $suffix = $suffix.$addition;
    return $suffix;
}


/**
 * @param array $array
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getArrayString($array) {
    if ($array == null) {
        return null;
    }
    $output = "";
    $delimiter = "";
    foreach ($array as $value) {
        $output = $output . $delimiter . $value;
        if ($delimiter == "") {
            $delimiter = ", ";
        }
    }
    return $output;
}


/**
 * Determines whether $dirA is a subdirectory of $dirB
 *
 * @param string $dirA
 * @param string $dirB
 * @return boolean
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function directoryAIsChildOfDirectoryB($dirA, $dirB) {
    $header = "directoryAIsChildOfDirectoryB(): ";
    // Return true if dirA starts with dirB
    $output = stringAStartsWithStringB($dirA, $dirB);
    //$output = strpos($dirA, $dirB) === 0;
    if ($output) {
        logMessage("$header Determined '$dirA' is a child of '$dirB'");
    } else {
        logMessage("$header Determined '$dirA' is not a child of '$dirB'");
    }
    return $output;
}


/**
 * @param string $stringA
 * @param string $stringB
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function stringAStartsWithStringB($stringA, $stringB) {
    return strpos($stringA, $stringB) === 0;
}


/**
 * @param string $stringA
 * @param string $stringB
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function stringAEndsWithStringB($stringA, $stringB) {
    return strpos($stringA, $stringB) === strlen($stringA) - 1;
}


/**
 * @param string $directoryPath
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getDirectoryNameFromPath($directoryPath) {
    $lastSlashIndex = strrpos($directoryPath, "/");
    $currentDirectoryName = substr($directoryPath, $lastSlashIndex + 1);
    return $currentDirectoryName;
}


/**
* Returns an array containing all of the files in the dir passed in
*
* @param string $dir
*
* @author -> Delvison Castillo
*/
function getFilesFromDir($dir){
  $files = array();
  $handler = opendir($dir);
  while ($file = readdir($handler)) {
    if ($file != "." && $file != "..") {
      array_push($files,$file);
    }
  }
  return $files;
}


/**
 * @param string $text
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function stripLeadingSlash($text) {
    // If starts with slash, remove it. Otherwise, return original text
    $header = "stripLeadingSlash(): ";
    logMessage("$header Input = '$text'");
    $output = $text;
    if (stringAStartsWithStringB($text, "/")) {
        $output = substr($text, 1);
    }
    logMessage("$header Output = '$output'");
    return $output;
}


/**
 * @param string $text
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function stripTrailingSlash($text) {
    // If ends with slash, remove it. Otherwise, return original text
    $header = "stripTrailingSlash(): ";
    logMessage("$header Input = '$text'");
    $output = $text;
    if (stringAEndsWithStringB($text, "/")) {
        $output = substr($text, 0, strlen($text) - 1);
    }
    logMessage("$header Output = '$output'");
    return $output;
}


/**
 * @param string $stringA
 * @param string $stringB
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getSubstringOfAAfterOccuranceOfB($stringA, $stringB) {
    $header = "getSubstringOfAAfterOccuranceOfB(): ";
    logMessage("$header Input: a='$stringA', b='$stringB'");
    $positionOfB = strpos($stringA, $stringB);
    $output = $stringA;
    if ($positionOfB !== false) {
        $output = substr($stringA, $positionOfB + strlen($stringB));
    }
    logMessage("$header Output = '$output'");
    return $output;
}


/**
 * @param string $stringA
 * @param string $stringB
 *
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function getSubstringOfABeforeOccuranceOfB($stringA, $stringB) {
    $header = "getSubstringOfABeforeOccuranceOfB(): ";
    logMessage("$header Input: a='$stringA', b='$stringB'");
    $positionOfB = strpos($stringA, $stringB);
    $output = $stringA;
    if ($positionOfB !== false) {
        $output = substr($stringA, 0, $positionOfB);
    }
    logMessage("$header Output = '$output'");
    return $output;
}


/**
 * Escapes spaces and ampersands (&) from a url
 *
 * @param string $url
 * @return string Returns the url with the necessary characters escaped.
 *
 * @author Delvison Castillo
 */
function clean_url($url) {
    $header = "clean_url(): ";
    logMessage("$header input url = '$url'");
    $url = str_replace(" ", "%20", $url);
    $url = str_replace("&", "%26", $url);
    logMessage("$header output url = '$url'");
    return $url;
}


/**
 * @param SimpleXMLElement $xmlElement
 * @return string
 * @author Andrew Darwin <andrew.p.darwin@jpl.nasa.gov>
 */
function stripEnclosingXMLTag($xmlElement) {
    $tagName = $xmlElement->getName();
    $xmlString = $xmlElement->asXML();
    $xmlString = getSubstringOfAAfterOccuranceOfB($xmlString, "<$tagName");
    $xmlString = getSubstringOfAAfterOccuranceOfB($xmlString, ">");
    $xmlString = getSubstringOfABeforeOccuranceOfB($xmlString, "</$tagName>");
    return $xmlString;
}
?>
