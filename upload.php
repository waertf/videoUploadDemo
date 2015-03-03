<?php
/**
 * Created by PhpStorm.
 * User: wavegisAAA
 * Date: 2/25/2015
 * Time: 11:25 AM
 */
include("Video.php");
//mysql
$serverName = 'localhost';
$username = 'root';
$password = 'root';
$dbname = "alonso";

$file_name	= time().RandomString(5);
$tempFile = $_FILES['Filedata']['tmp_name'];
$targetFileName = $file_name.'.'.getExt( $_FILES['Filedata']['name']);
$userName 	= ($_POST['userName']);
$writeMode = ($_POST['writeMode']);
$targetFile=dirname(__FILE__).'/'.$targetFileName;
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
    $conn = new mysqli($serverName, $username, $password,$dbname);

// Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    echo 'Connected successfully';


// query user id
$sql="SELECT
	user_list.sn
FROM
	user_list
WHERE
	user_list.name = '".$userName."'";

$result = $conn->query($sql);

if ($result->num_rows > 0) {
    // output data of each row
    if($row = $result->fetch_assoc()) {
        $userID=$row['sn'];

        $sql = "show tables like '".$userID."'";
        $result = $conn->query($sql);
        if($result->num_rows>0){
            //table exist
        }
        else{
            //create table
            //datetime for php over 5.5
            $sql="CREATE TABLE `".$userID."` (
  `sn` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(32) NOT NULL,
  `target_filename` varchar(32) NOT NULL,
  `filesize_in_kb` varchar(32) NOT NULL,
  `upload_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `sn` (`sn`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
            //TIMESTAMP for php 5.5 and under
            $sql="CREATE TABLE `".$userID."` (
  `sn` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `filename` varchar(32) NOT NULL,
  `target_filename` varchar(32) NOT NULL,
  `filesize_in_kb` varchar(32) NOT NULL,
  `upload_time` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `sn` (`sn`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1";
            shell_exec("mkdir ".$userID);
            if ($conn->query($sql) === TRUE) {
                echo "Table MyGuests created successfully";
            } else {
                echo "Error creating table: " . $conn->error;
            }

        }

        $file_size=round($_FILES['Filedata']["size"]/1000);
        $sql="SELECT
	SUM(filesize_in_kb)
      FROM"." `".$userID.'`';

        $result = $conn->query($sql);
        if ($result->num_rows > 0)
        {
            if($row = $result->fetch_assoc()) {
                $totalQuatoUsed=$row['SUM(filesize_in_kb)'];
                if(floatval($totalQuatoUsed)+$file_size>5242880)
                {
                    //over quato limit:5G
                    switch($writeMode)
                    {
                        case "overWrite":
                            //delete oldest file then insert new file
                            $tmpFileSize=$file_size;
                            while(true)
                            {
                                $sql="SELECT
	Min(sn),
	target_filename,
	filesize_in_kb
FROM
	`".$userID."`";
                                $result = $conn->query($sql);
                                if ($result->num_rows > 0)
                                    if($row = $result->fetch_assoc())
                                    {
                                        $sn=$row['Min(sn)'];
                                        $target_filename=$row['target_filename'];
                                        $filesize_in_kb=floatval($row['filesize_in_kb']);
                                        $myFileName=basename($target_filename);
                                        shell_exec("rm ".dirname(__FILE__).'/'.$target_filename);
                                        shell_exec("rm ".dirname(__FILE__).'/'.$myFileName.'.jpg');
                                        $sql="DELETE FROM `1` WHERE (`sn`='".$sn."')";
                                        $tmpFileSize-=$filesize_in_kb;
                                        if($tmpFileSize>0)
                                            continue;
                                        else
                                            break;
                                    }
                            }
                            {
                                //insert file
                                move_uploaded_file($tempFile,$targetFile);
                                /*
                                $video = new Video(\SqlFileInfo($targetFileName));
                                $anotherVideo = $video->encodeInto("flv");
                                $tumb=$video->getThumbnail();
                                echo "WxH: {$anotherVideo->getWidth()}x{$anotherVideo->getWidth()}";
            */
                                $fileFlv=dirname(__FILE__).'/'.$userID.'/'.$file_name.'.flv';
                                $flvJpg=dirname(__FILE__).'/'.$userID.'/'.$file_name.'.jpg';
                                $videoJPGWidthheight = "120x72";
                                echo shell_exec("/usr/bin/ffmpeg -i ".$targetFile." -ar 22050 -ab 32 -f flv -s 320x256 ".$fileFlv."");
                                echo shell_exec("rm ".$targetFile);
                                echo shell_exec("/usr/bin/ffmpeg -i ".$fileFlv." -vframes 1 -ss 00:00:06 -s 120x72 -f image2 ".$flvJpg." >/dev/null 2>/dev/null &");
                                //wriet to sql
                                $sqlCmd='INSERT INTO `'.$userID.'` (filename, target_filename, filesize_in_kb) VALUES (\''.$_FILES['Filedata']['name'].'\', \''.$userID.'/'.$file_name.'.flv'.'\', \''.$file_size.'\')';
                                SqlInsert($conn,$sqlCmd);
                                print_r($video->getRawInfo());
                            }
                            break;
                        case "denyWrite":
                            upload_error("Cannot upload file because of exceed of quato,5G");
                            exit(0);
                            break;
                    }
                }
                else
                {
                    //insert file
                    move_uploaded_file($tempFile,$targetFile);
                    /*
                    $video = new Video(\SqlFileInfo($targetFileName));
                    $anotherVideo = $video->encodeInto("flv");
                    $tumb=$video->getThumbnail();
                    echo "WxH: {$anotherVideo->getWidth()}x{$anotherVideo->getWidth()}";
*/
                    $fileFlv=dirname(__FILE__).'/'.$userID.'/'.$file_name.'.flv';
                    $flvJpg=dirname(__FILE__).'/'.$userID.'/'.$file_name.'.jpg';
                    $videoJPGWidthheight = "120x72";
                    echo shell_exec("/usr/bin/ffmpeg -i ".$targetFile." -ar 22050 -ab 32 -f flv -s 320x256 ".$fileFlv."");
                    echo shell_exec("rm ".$targetFile);
                    echo shell_exec("/usr/bin/ffmpeg -i ".$fileFlv." -vframes 1 -ss 00:00:06 -s 120x72 -f image2 ".$flvJpg." >/dev/null 2>/dev/null &");
                    //wriet to sql
                    $sqlCmd='INSERT INTO `'.$userID.'` (filename, target_filename, filesize_in_kb) VALUES (\''.$_FILES['Filedata']['name'].'\', \''.$userID.'/'.$file_name.'.flv'.'\', \''.$file_size.'\')';
                    SqlInsert($conn,$sqlCmd);
                    print_r($video->getRawInfo());
                }
            }
        }
        else {
            echo '1-0 results';
        }
    }
} else {
    echo '2-0 results';
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

function SqlInsert($sqlObject,$sqlCmd)
{
    if($sqlObject->query($sqlCmd)=== TRUE)
    {
        echo "New record created successfully";
    }
    else {
        echo "Error: " . $sqlCmd . "<br>" . $sqlObject->error;
    }
}
