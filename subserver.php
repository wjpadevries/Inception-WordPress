<?php

define('LOGPATHWITHIP', false);
// if you want logging, specify path to writable log folder
define( 'LOGPATH'  , dirname(__FILE__) . '/log/'); // including ending '/'

define('InceptionArticleFolder' , 'inception');
define('TEMPDIR' , dirname(__FILE__) .'/temp/');


// --------------------------------------------------
// if you want to finetune the logging,
// you might consider to play with the settings below
// Normally no changes are required
// --------------------------------------------------

// specify the name of the logfile, normally this is the name of the script
define( 'LOGNAME', basename(__FILE__) );


define( 'LOG_ALL',true); // if true, everything will be logged, 
                        // if false, only IP's listed will be logged
                       


// define IP-addresses to log, only the specified IP-addresses will be logged
define( 'LOG_IP', serialize( array('localhost',
                                   )
                            ) );   


// see : http://php.net/manual/en/timezones.asia.php
ini_set( 'date.timezone', 'Europe/Amsterdam');
// main entry point for this service

include dirname(__FILE__) . '/server/awsconfig.php';
#include dirname(__FILE__) . '/server/logging.php';

// ========================================
// take care the php problems are reported
// ========================================
error_reporting(E_ALL);
ini_set('display_errors','On');
ini_set ('error_log', LOGPATH . 'php.log');
set_error_handler( 'ErrorHandler' );

function ErrorHandler( $errno, $errmsg, $file, $line, $debug )
{
   print ("ERROR in PHP: Errno:$errno  errMsg[$errmsg] $file:$line");
}


// main check to see if any postdata is available
// Input socket
$inputSocket = fopen('php://input','rb');
$rawrequest = stream_get_contents($inputSocket);
fclose($inputSocket);

// see if there are GET parameters
MyLog('GET parameters:' . print_r($_GET,1));


MyLog('rawrequest [' . print_r($rawrequest,1) . ']');
if ( $rawrequest != '' )
{

  // there seems to be some POST data, so lets send to dispatcher
   
  $request = json_decode( $rawrequest, true );
  MyLog('request [' . print_r($request,1) . ']');
  
  if ( $request['Type'] == 'SubscriptionConfirmation')
  {
	  MyLog('Handle SubscriptionConfirmation');
	  $filedata = file_get_contents( $request['SubscribeURL'] );
	  MyLog('data:' . $filedata );
  }
  
  if ( $request['Type'] == 'Notification' )
  {
	 
	  $topicARN = $request['TopicArn'];
	  MyLog('Handle Notification from ARN:' . $topicARN );
	  if ( strpos($topicARN, 'inception') > 0 )
	  {	
		  MyLog('Found inception ARN');
		  $message = json_decode($request['Message']);
		 
		  if (isset($_GET['iframe'])){
			  MyLog('Iframe option detected');
			  $message->iframe = 'true';
		  }
		  
		  		  
		  processInceptionArticle( $message);
		  
	  }
  }
  
  if ( $request['Type'] == 'getAllFiles')
  {
	  $allFiles = getAllFiles();
  
	  print json_encode($allFiles);
	}	  
  
  if ( $request['Type'] == 'getNewFiles')
  {
	  $newFiles = getNewFiles($request['Caller']);

	  print json_encode($newFiles);  
  }
  
}
else
{
	MyLog( "no post data, acting as displayer");
	displayArticles();
}





// --------------------------------------------
// -- functions below this line ---------------
// --------------------------------------------

function processInceptionArticle( $message)
{
	MyLog('processInceptionArticle');
	MyLog('message:' . print_r($message,1));
	$articleName = str_replace(' ','_',$message->name);
	
	MyLog('received article:' . $articleName);
    $inceptionFolder =  dirname(__FILE__) . '/' . InceptionArticleFolder;
    if ( ! file_exists( $inceptionFolder) )
    {
      mkdir($inceptionFolder,0777);
      chmod($inceptionFolder,0777);
    }  
	$messageFile =  $inceptionFolder . '/' . $articleName . '.msg'; 
	MyLog('Writing message data to [' . $messageFile . ']');

   file_put_contents($messageFile, json_encode($message) );
	
}



function getNewFiles($caller)
{
	MyLog('getNewFiles');
	
	$newFiles = array();
	$inceptionFolder =  dirname(__FILE__) . '/' . InceptionArticleFolder ;
	$lastScanMark = $inceptionFolder . '/lastScanMarker-'.$caller . '.txt';
	MyLog("timestamp of lastScanMark[$lastScanMark]:" . date ("F d Y H:i:s.", filemtime($lastScanMark)));
	if ( file_exists($inceptionFolder ) )
	{
		$dh  = opendir($inceptionFolder);
		if ( $dh !== false)
		{
			$filename = readdir($dh);
			
			while ($filename !== false) 
			{
				// only process .msg files	
				if ( pathinfo($filename, PATHINFO_EXTENSION) == 'msg')
				{	 
					MyLog('filename:' . $inceptionFolder .'/'.  $filename );
					MyLog('timestamp of file:' . date ("F d Y H:i:s.", filemtime($inceptionFolder . '/' . $filename)));
					MyLog(filemtime( $inceptionFolder . '/' . $filename) . '>=' . filemtime($lastScanMark));
					if ( file_exists($lastScanMark))
					{
						if ( filemtime( $inceptionFolder . '/' . $filename) >= filemtime($lastScanMark))
						{
							MyLog('Adding based on timestamp' );
							$fileContent = file_get_contents( $inceptionFolder .'/'. $filename );
							$newFiles[ basename($filename) ] = $fileContent;
						}
						else
						{
							MyLog('File is older');
						}
					}else{
						MyLog('Adding based missing timestamp' );
						$fileContent = file_get_contents( $inceptionFolder .'/'. $filename );
						$newFiles[ basename($filename) ] = $fileContent;
					}						
				}	
				$filename = readdir($dh);
			}
		}	
	}	
	
    unlink ( $lastScanMark );
   
    file_put_contents( $lastScanMark , 'date:' . date ("F d Y H:i:s.") );
    
	MyLog('returning files:' . implode( ',',$newFiles) );
	return $newFiles;
	
}

function getAllFiles()
{
	MyLog('getAllFiles');
	$newFiles = array();
	$inceptionFolder =  dirname(__FILE__) . '/' . InceptionArticleFolder ;
	if ( file_exists($inceptionFolder ) )
	{
		$dh  = opendir($inceptionFolder);
		if ( $dh !== false)
		{
			$filename = readdir($dh);
			
			while ($filename !== false) 
			{
				if ( pathinfo($filename, PATHINFO_EXTENSION) == 'msg')
				{	 
					MyLog('filename:' . $filename );
					$fileContent = file_get_contents( $inceptionFolder .'/'. $filename );
					$newFiles[ basename($filename) ] = $fileContent;
					
				}	
				$filename = readdir($dh);
			}
		}	
	}	
	return $newFiles;
}
  
  
  
// --------------------------------------------
// -- html output section ---------------
// --------------------------------------------  

function htmlHeader()
{
	echo "
<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Transitional//EN' 'http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd'>
<html xmlns='http://www.w3.org/1999/xhtml'>
<head>
<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
<meta name='robots' content='noindex, nofollow'>
<title>Inception Viewer</title>
<script src='js/jquery-3.2.1.min' type='text/javascript'></script>
<script src='js/awssns.js' type='text/javascript'></script>
";

}
  
function displayArticles()
{
	htmlHeader();
	$files = getAllFiles();
	$articleList = '';
	$firstArticleData = '';
	foreach ( $files as $name => $data)
	{
		$data = json_decode($data);
		$url = $data->url;
		$meta = $data->metadataUrl;
		if ($firstArticleData == '' ) { $firstArticleData = $data;}
		$articleList .= '- ' . $name;
		$articleList .= '<a href="' . $url . '">url</a>';
		$articleList .= '<a href="' . $meta . '">meta</a>';
		
		$articleList .= '<br>';
	}
	if ( $articleList == '')
	{
		print "No files found";
	}
	else
	{
		print $articleList;
	}
	
	if ($firstArticleData != '' ) {
		showArticle( $firstArticleData);
	}	
}


function showArticle( $data )
{
	$url = $data->url;
	$zipdata = file_get_contents( $url);
	// save to disk
	$zipname = basename($url);
	echo "zipname:" . $zipname . '<br>';;
	file_put_contents( TEMPDIR . $zipname, $zipdata);
	
	$dirname = basename($zipname,'.article');
	echo "dirname:" . $dirname . '<br>';
	if (file_exists(TEMPDIR . $dirname)) { unlink (TEMPDIR . $dirname); }
	if ( ! file_exists( TEMPDIR . $dirname) )
    {
      mkdir(TEMPDIR . $dirname,0777);
      chmod(TEMPDIR . $dirname,0777);
    }   
	$zip = new ZipArchive;
	if ($zip->open(TEMPDIR . $zipname) === TRUE) {
    	$zip->extractTo(TEMPDIR . $dirname);
		$zip->close();
		echo '<iframe src="' . 'temp/' . $dirname .'/output.html' . '" width="100%" height="600" frameborder="0">';
		echo 'ok';
	} else {
    	echo 'failed to retrieve/unzip from url [' . $url . ']';
	}
}


 // -------------------------------
// -------- LOG FUNCTIONS --------
// -------------------------------
function getRealIpAddr()
{
    $ip = '::1';
    if (!empty($_SERVER['HTTP_CLIENT_IP']))   //check ip from share internet
    {
      $ip=$_SERVER['HTTP_CLIENT_IP'];
    }
    elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   //to check ip is pass from proxy
    {
      $ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
    }
    elseif (!empty($_SERVER['REMOTE_ADDR']))
    {
      $ip=$_SERVER['REMOTE_ADDR'];
    }
    
    
    if ( $ip == '::1' ) { $ip = 'localhost';}
    return $ip;
}


function getLogPath()
{
   $logfolder = LOGPATH;
   $date = date('Ymd');
   
    if ( ! file_exists( $logfolder) )
    { 
       error_log (basename(__FILE__) . ' -> ERROR: Logfolder [' . $logfolder . '] does not exists, please create',0);
       print basename(__FILE__) . ' -> ERROR: Logfolder [' . $logfolder . '] does not exists, please create';
       exit;
    }
    
   $logfolder = $logfolder . $date ;
   if ( ! file_exists( $logfolder) )
   {
     mkdir($logfolder,0777);
     chmod($logfolder,0777);
   } 
      
   // add IPAdres if required
   if ( defined ('LOGPATHWITHIP') &&
   		LOGPATHWITHIP === true )
   {
	  $ip = getRealIpAddr();
   	  $logfolder = $logfolder . '/' . $ip;
   }   

   if ( ! file_exists( $logfolder) )
   {
     mkdir($logfolder,0777);
     chmod($logfolder,0777);
   }    

   return $logfolder .'/';
}

function getLogTimeStamp()
{
  list($ms, $sec) = explode(" ", microtime()); // get seconds with ms part
  $msFmt = sprintf( '%03d', round( $ms*1000, 0 ));
  return date('Y-m-d H-i-s (T)',$sec).'.'.$msFmt;
}

function mustLog()
{
   global $loggedInUser;
   $do_log = false;
  // error_log('LOG_ALL:' . LOG_ALL );
   $ip = getRealIpAddr();
   
   if ( LOG_ALL === false)
   {
    
     $logip = unserialize(LOG_IP);
    // error_log('logip:' . print_r($logip,1));
    // error_log('ip:' . print_r($ip,1));
      
     if (in_array($ip,$logip) )
     {
       $do_log = true;
     }  
   
    
   
   }
   else
   {
     $do_log = true;
   } 
   //error_log( 'do_log:' . $do_log );
   return $do_log;
}


function MyLogS( $logline )
{
   MyLog( $logline, true );
}

function MyLog( $logline , $toBrowser = false)
{ 
   global $loggedInUser, $currentCommand, $logTimeStamp, $LOGNAME, $logfilename;
   
   if ( isset($logfilename))
   {
     $LOGNAME = $logfilename;
   }
   else
   {
     $LOGNAME = LOGNAME;
   }
   
   if ( mustLog() === true )
   {
      
      $userID = 0;
      if ( isset($loggedInUser->user_id) )
      {
        $userID = $loggedInUser->user_id;
      }
      $ip = getRealIpAddr();

      $datetime = getLogTimeStamp() . "[$ip] [$userID]";
      //'[' . date("d-M-Y H:i:s") . "] [$ip] [$userID]";
      
      $logfolder = getLogPath();
      $logname = $LOGNAME;
      
      
                                        
      if ( $currentCommand != '' &&
           $logTimeStamp   != '')
      {
         $logfile = $logfolder . '/' .$logTimeStamp . '-' . $currentCommand .  '.log';
      }
      else
      {                                  
        $logfile = $logfolder . '/' . $logname . '.log';
      }
      
      $logh = fopen($logfile, 'a');
      if ( $logh !== false)
      {
         fwrite( $logh, $datetime .  $logline . "\n");
         fclose( $logh );
         chmod ( $logfile, 0777 );
      }
      else
      {
          error_log ( basename(__FILE__) . ' -> ERROR: writing to logfile [$logfile]' );
      }
    
      if ( $toBrowser )
      {
        print $logline . "<br>\n"; 
        try {while (ob_get_level() > 0) ob_end_flush();} catch( Exception $e ) {}
      }     
    }
 } 


/**
 * Places dangerous characters with "-" characters. Dangerous characters are the ones that 
 * might error at several file systems while creating files or folders. This function does
 * NOT check the platform, since the Server and Filestore can run at different platforms!
 * So it replaces all unsafe characters, no matter the OS flavor. 
 * Another advantage of doing this, is that it keeps filestores interchangable.
 * IMPORTANT: The given file name should NOT include the file path!
 *
 * @param string $fileName Base name of file. Path excluded!
 * @return string The file name, without dangerous chars.
 */
function replaceDangerousChars( $fileName )
{
    MyLog('-replaceDangerousChars');
    MyLog(" input: $fileName ");
	$dangerousChars = "`~!@#$%^*\\|;:'<>/?\"";
	$safeReplacements = str_repeat( '-', strlen($dangerousChars) );
	$fileName = strtr( $fileName, $dangerousChars, $safeReplacements );
	MyLog(" output: $fileName ");
	return $fileName;
}
	
/**
 * Encodes the given file path respecting the FILENAME_ENCODING setting.
 *
 * @param string $path2encode The file path to encode
 * @return string The encoded file path
 */
function encodePath( $path2encode )
{
  MyLog('-encodePath');
  MyLog(" input: $path2encode ");
  
  setlocale(LC_CTYPE, 'nl_NL');
  $newPath = iconv('UTF-8', "ASCII//TRANSLIT", $path2encode);
  $newPath = preg_replace('/[^A-Za-z0-9\-]/', '', $newPath);
  
  MyLog(" output: $newPath ");
  return $newPath;
}
 
