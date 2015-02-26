<?php
/**
 * Created by PhpStorm.
 * User: wavegisAAA
 * Date: 2/25/2015
 * Time: 11:25 AM
 */

//mysql
$serverName = 'localhost';
$username = 'username';
$password = 'password';

$file_name	= time().RandomString(5);
$tempFile = $_FILES['Filedata']['tmp_name'];
$targetFileName = $file_name.'.'.getExt( $_FILES['Filedata']['name']);
$userName 	= ($_POST['userName']);
$writeMode = ($_POST['writeMode']);
//Checking filesize
$POST_MAX_SIZE = ini_get('post_max_size');
$unit = strtoupper(substr($POST_MAX_SIZE, -1));
$multiplier = ($unit == 'M' ? 1048576 : ($unit == 'K' ? 1024 : ($unit == 'G' ? 1073741824 : 1)));

if ((int)$_SERVER['CONTENT_LENGTH'] > $multiplier*(int)$POST_MAX_SIZE && $POST_MAX_SIZE) {
    header("HTTP/1.1 500 Internal Server Error"); // This will trigger an uploadError event in SWFUpload
    upload_error("POST exceeded maximum allowed size.");
    exit(0);
}

//Checking uploading errors
$uploadErrors = array(
    0=>"There is no error, the file uploaded with success",
    1=>"The uploaded file exceeds the upload_max_filesize directive in php.ini",
    2=>"The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form",
    3=>"The uploaded file was only partially uploaded",
    4=>"No file was uploaded",
    6=>"Missing a temporary folder"
);
if (!isset($_FILES['Filedata'])) {
    upload_error("No file was selected");
    exit(0);
} else if (isset($_FILES['Filedata']["error"]) && $_FILES['Filedata']["error"] != 0) {
    upload_error($uploadErrors[$_FILES['Filedata']["error"]]);
    exit(0);
} else if (!isset($_FILES['Filedata']["tmp_name"]) || !@is_uploaded_file($_FILES['Filedata']["tmp_name"])) {
    upload_error("Upload failed is_uploaded_file test.");
    exit(0);
} else if (!isset($_FILES['Filedata']['name'])) {
    upload_error("File has no name.");
    exit(0);
}
// Create connection
    $conn = new mysqli($serverName, $username, $password);

// Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo 'Connected successfully';

    $sql = "show tables like \"".$userName."\"";
    $result = $conn->query($sql);
    if($result->num_rows>0){
        //table exist
    }
    else{
        //create table
        $sql="CREATE TABLE `".$userName."` (
  `sn` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(32) NOT NULL,
  `target_filename` varchar(32) NOT NULL,
  `filesize_in_kb` int(11) NOT NULL,
  `upload_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `sn` (`sn`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
        if ($conn->query($sql) === TRUE) {
            echo "Table MyGuests created successfully";
        } else {
            echo "Error creating table: " . $conn->error;
        }

    }
// query user id
$sql="SELECT
	user_list.sn
FROM
	user_list
WHERE
	user_list.`name` = \'".$userName."\'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // output data of each row
    if($row = $result->fetch_assoc()) {
        $userID=$row['sn'];
        $file_size=round($_FILES['Filedata']["size"]/1000);
        $sql="SELECT
	SUM(filesize_in_kb)
      FROM"." ".$userName;

        $result = $conn->query($sql);
        if ($result->num_rows > 0)
        {
            if($row = $result->fetch_assoc()) {
                $totalQuatoUsed=$row['SUM(filesize_in_kb)'];
                if(floatval($totalQuatoUsed)+$file_size>5242880)
                {
                    //over quato limit:5G
                }
                else
                {
                    //insert file
                }
            }
        }
        else {
            echo '0 results';
        }
    }
} else {
    echo '0 results';
}

//function used to display error
function upload_error($error)
{
    echo json_encode(array("error"=>$error));
}

//Funtion of Random String
function RandomString($length)
{
    $string = md5(microtime());
    $highest_startpoint = 32-$length;
    $randomString = substr($string,rand(0,$highest_startpoint),$length);
    return $randomString;

}

//Function Used TO Get Extensio Of File
function GetExt($file){
    return substr($file, strrpos($file,'.') + 1);
}
