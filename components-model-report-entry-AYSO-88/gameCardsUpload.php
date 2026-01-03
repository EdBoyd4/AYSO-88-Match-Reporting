<?php

// validate the file extention and MIME type
// the code below supports >= 90-95% of file types
function gameCardImageMIMEAndExtensionCheck($gameCardsPhotoNameExpected, $allowedMimeTypes, $allowedExtensions){
    // Step 1: Validate MIME Type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = finfo_file($finfo, $_FILES[$gameCardsPhotoNameExpected]['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mimeType, $allowedMimeTypes)) {
        error_log('Unsupported MIME type: ' . $mimeType);
        die('Unsupported file type!');  //-------------------------------------------------------------------------------------------------------------------------------------------
    }else{
        // Step 2: Validate File Extension
        $fileExtension = strtolower(pathinfo($_FILES[$gameCardsPhotoNameExpected]['name'], PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions)) {
            error_log('Unsupported file extension: ' . $fileExtension);
            die('Unsupported file extension!');  //-------------------------------------------------------------------------------------------------------------------------------------------
        }else{
            return $fileExtension;
        }
    }
}

function gameCardImageIntegrityAndFileSizeCheck($gameCardsPhotoNameExpected){ 
    // Determines the size, dimensions and MIME type of an image. If the file is not a valid image, it returns false.
    $imageInfo = getimagesize($_FILES[$gameCardsPhotoNameExpected]['tmp_name']);
    if ($imageInfo === false) {
        die("Uploaded file is not a valid image.");  //-------------------------------------------------------------------------------------------------------------------------------------------
    }
    // Identifies the type of image file. Returns constants like IMAGETYPE_JPEG, IMAGETYPE_PNG, etc., or false if the file is not an image.
    $imageType = exif_imagetype($_FILES[$gameCardsPhotoNameExpected]['tmp_name']);
    if ($imageType === false) {
        die("Uploaded file is not a valid image.");  //-------------------------------------------------------------------------------------------------------------------------------------------
    }
    /* Comparing the results of the array value created by getimagesize against the array value created by exif_imagetype was considered.
    However, the computational overhead, work involved, and cutomer deadline lead to the decision against implementing this strategy for the initial release. */
    // Creates an image resource from a file. These functions include imagecreatefromjpeg(), imagecreatefrompng(), imagecreatefromgif(), etc. If the file is not a valid image, these functions will return false.
    $imageJpg = ($imageType === IMAGETYPE_JPEG) ? imagecreatefromjpeg($_FILES[$gameCardsPhotoNameExpected]['tmp_name']) : false;
    $imagePng = ($imageType === IMAGETYPE_PNG) ? imagecreatefrompng($_FILES[$gameCardsPhotoNameExpected]['tmp_name']) : false;
    if ($imageJpg !== false || $imagePng !== false) {
        return true;
    }elseif ($imageJpg === false) {
        die("Uploaded file is not a valid JPEG / JPG image.");  //-------------------------------------------------------------------------------------------------------------------------------------------
    }elseif ($imagePng === false) {
        die("Uploaded file is not a valid PNG image.");  //-------------------------------------------------------------------------------------------------------------------------------------------
    }
    // need third party library for heic / heif files

    // this section not implemented as this level of security is not initally necessary, and comes with additional security risks
    // file Command (CLI) check
    /* $fileType = shell_exec("file -b --mime-type " . escapeshellarg($_FILES['image']['tmp_name']));
    if (strpos($fileType, 'image/') === false) {
        die("Uploaded file is not a valid image.");
    } */
}

function gameCardFoldersConstructor($imageDate, $imageDivision, $imageField, $imageTime){
    // get folders for saving gameCard images
    // gets folder above htdocs, or whatever
    $rootDir = realpath($_SERVER['DOCUMENT_ROOT'].DIRECTORY_SEPARATOR.'..');
    // Define the base directory where subdirectories will be created
    $gameCardsDir = $rootDir.DIRECTORY_SEPARATOR.'AYSORegion88GameCards';
    error_log('gameCardsDir: ' . $gameCardsDir);

    // Build the full directory path in one go
    $fullDirPath = $gameCardsDir.DIRECTORY_SEPARATOR.$imageDate.DIRECTORY_SEPARATOR.$imageField.DIRECTORY_SEPARATOR.$imageDivision.DIRECTORY_SEPARATOR.$imageTime;
    //error_log('gimme a damn map to '.$fullDirPath);

    // Create the full directory path if it does not exist
    if (!is_dir($fullDirPath)) {
        /* The first number is always zero
        The second number specifies permissions for the owner
        The third number specifies permissions for the owner's user group
        The fourth number specifies permissions for everybody else
        Possible values (to set multiple permissions, add up the following numbers):
    
        1 = execute permissions
        2 = write permissions
        4 = read permissions */
        mkdir($fullDirPath, 0710, true); // true allows recursive creation
    }
    //error_log('Nested directories are checked and created if needed.');
    return $fullDirPath;
}

function gameCardPhotoReName($imageDate, $imageDivision, $imageField, $imageTime, $fileExtension, $imageNumber){
    // construct file name with extension
    $gameCardPhotoSystemName = 'Card_Photo_'.$imageNumber.'_'.$imageDate.'_'.$imageDivision.'_'.$imageField.'_'.$imageTime. '.'.$fileExtension;
    //error_log($gameCardPhotoSystemName);
	return $gameCardPhotoSystemName;
}

function gameCardFileSave($gameCardsPhotoNameExpected, $gameCardsSystemLocation, $gameCardPhotoSystemName){

    /* Array(
        [gameCardsPhoto1] => Array(
        [name] => PXL_20240819_150730109.MP.jpg
        [full_path] => PXL_20240819_150730109.MP.jpg
        [type] => image/jpeg
        [tmp_name] => C:\\xampp\\tmp\\php9A76.tmp
        [error] => 0
        [size] => 3462061
        )
        ...
    ) */

    // Ensure the destination path ends with a directory separator
    $destinationPath = rtrim($gameCardsSystemLocation, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $gameCardPhotoSystemName;
    move_uploaded_file($_FILES[$gameCardsPhotoNameExpected]["tmp_name"], $destinationPath);

    // Check if the file was uploaded successfully
    /* if (isset($_FILES[$gameCardsPhotoNameExpected])) {
        $fileError = $_FILES[$gameCardsPhotoNameExpected]["error"];
        
        
        // Provide detailed error messages
        switch ($fileError) {
            case UPLOAD_ERR_OK:
                if (move_uploaded_file($_FILES[$gameCardsPhotoNameExpected]["tmp_name"], $destinationPath)) {
                    echo "File successfully uploaded as " . htmlspecialchars($gameCardPhotoSystemName) . ".";
                } else {
                    die("Failed to move the uploaded file.");
                }
                break;
            case UPLOAD_ERR_INI_SIZE:
                die("The uploaded file exceeds the upload_max_filesize directive in php.ini.");
            case UPLOAD_ERR_FORM_SIZE:
                die("The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.");
            case UPLOAD_ERR_PARTIAL:
                die("The uploaded file was only partially uploaded.");
            case UPLOAD_ERR_NO_FILE:
                die("No file was uploaded.");
            case UPLOAD_ERR_NO_TMP_DIR:
                die("Missing a temporary folder.");
            case UPLOAD_ERR_CANT_WRITE:
                die("Failed to write file to disk.");
            case UPLOAD_ERR_EXTENSION:
                die("A PHP extension stopped the file upload.");
            default:
                die("Unknown file upload error.");
        }
    } else {
        die("No file was uploaded.");
    } */
}


// process the image for permanent saving
function processGameCardPhotoUpload(&$headerDataItems, $gameCardsPhotoNameExpected, $gameCardsPhotoNumber){
    $allowedMimeTypes = ['image/jpeg', 'image/png', 'image/heic', 'image/heif'];
    $allowedExtensions = ['jpg', 'jpeg', 'png', 'heic', 'heif'];
    // $_FILES was already checked for presence of a file with the expected name
    $fileExtension = gameCardImageMIMEAndExtensionCheck($gameCardsPhotoNameExpected, $allowedMimeTypes, $allowedExtensions);
    if(in_array($fileExtension, $allowedExtensions)){
        if(gameCardImageIntegrityAndFileSizeCheck($gameCardsPhotoNameExpected) != false){
            // convert and process heif / heic conversion  --------------------------------------------------------------------------------------------------------------------------

            $imageDate = $headerDataItems['matchDate'];
            // error_log('Match Date: ' . $formattedDate);
            $imageDivision = $headerDataItems['teamDivision'];
            $imageField = $headerDataItems['playingfield'];
            $imageTime = $headerDataItems['matchStartTime'];
            // Replace colons with hyphens or other safe characters for use in folder and file names
            $safeImageTime = str_replace(':', '-', $imageTime);
            //error_log('the bloody time is - '.$imageTime);
            $gameCardsSystemLocation = gameCardFoldersConstructor($imageDate, $imageDivision, $imageField, $safeImageTime);
            error_log('storage location: ' . $gameCardsSystemLocation);
            /* $imageOriginalFilename = basename($_FILES[$gameCardsPhotoNameExpected]['name']);
            error_log('nightmare - '.$imageOriginalFilename); */
            
            /* $digits = filter_var($gameCardsPhotoNameExpected, FILTER_SANITIZE_NUMBER_INT);
            $imageNumber = substr($digits, -1); */
            $headerDataItems['image_number'.$gameCardsPhotoNumber] = $gameCardsPhotoNumber;
            // get the permanent name of the image file for inclusion in the DB
            $gameCardPhotoSystemName = gameCardPhotoReName($imageDate, $imageDivision, $imageField, $safeImageTime, $fileExtension, $gameCardsPhotoNumber);
            error_log('FileName: ' . $gameCardPhotoSystemName);
            // save the image in a non-public directory under a safe and searchable name 
            gameCardFileSave($gameCardsPhotoNameExpected, $gameCardsSystemLocation, $gameCardPhotoSystemName);
            // insert photo name into the array used for compiling the form data
            $headerDataItems[$gameCardsPhotoNameExpected] = $gameCardPhotoSystemName;
        }
    }
}

// the array used for compiling the form data is passed for ease of use / to ensure all image processing is done in this file
// the file name of the photo is passed as a variable in order to ensure that only images that are expected are processed
function processImageFileFromDATA(&$headerDataItems, $gameCardsPhotoNumber){
    $photoNameFromForm = 'gameCardsPhoto'.$gameCardsPhotoNumber;

    /* Array(
        [gameCardsPhoto1] => Array(
        [name] => PXL_20240819_150730109.MP.jpg
        [full_path] => PXL_20240819_150730109.MP.jpg
        [type] => image/jpeg
        [tmp_name] => C:\\xampp\\tmp\\php9A76.tmp
        [error] => 0
        [size] => 3462061
        )
        ...
    ) */

    /* $checker = implode(' - ', $_FILES[$photoNameFromForm]);
    error_log('demon - '.$checker); */

    // check for presence of the expected image
	if (isset($_FILES[$photoNameFromForm]) && $_FILES[$photoNameFromForm]['error'] == UPLOAD_ERR_OK) {
        // process the image for permanent saving
        processGameCardPhotoUpload($headerDataItems, $photoNameFromForm, $gameCardsPhotoNumber);
	}else{
		error_log('no photo entered for front of '.$gameCardsPhotoNumber.' and back of other');
		echo 'We did not receive a photo of the front of gamecard '.$gameCardsPhotoNumber.' and the back of gamecard the other.';
	}
}