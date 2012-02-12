<?php

/**
 * Checks if the Graphviz binaries can be found on the system
 *
 * @param           String  Filename of the binary
 * @param           Bool            Print error message?
 * @return  String  HTML Code
 * @author  Lars Michelsen <lars@vertical-visions.de>
 */
function automap_check_graphviz($binary, $printErr) {
    global $automap_graphviz_path;
    /**
     * Check if the graphviz binaries can be found in the PATH or in the
     * configured path. Prefer the configured path.
     */
    $bFound = false;
    foreach(Array(cfg('automap','graphvizpath').$binary, $binary) AS $path) {
        // Check if dot can be found in path (If it is there $returnCode is 0, if not it is 1)
        exec('which '.$path.' 2>/dev/null', $arrReturn, $exitCode);

        if($exitCode == 0) {
            $this->graphvizPath = str_replace($binary, '', $arrReturn[0]);
            $bFound = true;
            break;
        }
    }

    if(!$bFound) {
        if($printErr)
            throw new NagVisException(l('graphvizBinaryNotFound', Array('NAME' => $binary,
                                    'PATHS' => $_SERVER['PATH'].':'.cfg('automap','graphvizpath'))));
        return false;
    } else
        return true;
}

function automap_check_preflight($params) {
    // If this is a preview for the index page do not print errors
    if($params['preview']) {
        $printErr = 0;
    } else {
        $printErr = 1;
    }

    $CORE = $GlobalCore::getInstance();
    $CORE->checkVarFolderWriteable($printErr);

    // Check all possibly used binaries of graphviz
    if(!automap_check_graphviz('dot', $printErr) &&
       !automap_check_graphviz('neato', $printErr) &&
       !automap_check_graphviz('twopi', $printErr) &&
       !automap_check_graphviz('circo', $printErr) &&
       !automap_check_graphviz('fdp', $printErr)) {
        $this->noBinaryFound = true;
    }
}


// Do the preflight checks
//automap_check_preflight($params);

?>
