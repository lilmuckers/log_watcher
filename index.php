<?php 

/**
 * This is a class for returning data about a file.
 * 
 * 
 */
class LogReview
{
  /**
   * Array of all watched files
   * 
   * @var array
   */
  protected $_files = array();

  /**
   * Instnatiate the class, load the configuration
   * 
   * @param string $configFile
   * @return void
   */
  public function __construct($configFile = 'config.ini')
  {
    //Make sure the file exists and is readable
    if(!is_readable($configFile)){
      throw new Exception('Unable to read file "'.$configFile.'"');
    }
    
    //read the ini data in the file
    $_configData = parse_ini_file($configFile, true);
    
    //add the files to the file array
    foreach($_configData as $_code => $_file){
      $this->addFile($_code, $_file);
    }
    
  }
  
  /**
   * Add a file to the watcher
   * 
   * @param string $code
   * @param array $fileData
   * @return LogReview
   */
  public function addFile($code, $fileData)
  {
    $this->_files[$code] = $fileData;
  }
  
  /**
   * Get the file data for the given file code
   * 
   * @param string $code
   * @return array
   */
  public function getFile($code)
  {
    //make sure that the file code exists
    if(!array_key_exists($code, $this->_files)){
      throw new Exception('File for code "'.$code.'" does not exist');
    }
    
    return $this->_files[$code];
  }
  
  /**
   * Get the file data
   * 
   * @return array
   */
  public function getFiles()
  {
    return $this->_files;
  }
  
  /**
   * get the last x lines of a file as it currently stands as an array
   * 
   * @param string $code
   * @param int $lines
   * @return array
   */
  public function getLastLines($code, $lines = 50)
  {
    //get the file information
    $_file = $this->getFile($code);
  
    //load up the file into a buffer
    $_lines=array();
    $_fp = fopen($_file['location'], "r");
    
    
    $_readable = is_readable($_file['location']);
    $_all = file_get_contents($_file['location']);
    
    // check that the file opened properly
    if(false == $_fp){
      throw new Exception('Could not open file "'.$code.'"');
    }
    
    //iterate through the lines and build an array of the last $lines
    while(!feof($_fp))
    {
      $_line = trim(fgets($_fp, 4096));
      if(!empty($_line)){
        array_push($_lines, $_line);
        if (count($_lines) > $lines){
          array_shift($_lines);
        }
      }
    }
    fclose($_fp);
    
    //return
    return $_lines;
  }
  
  /**
   * Get the latest line date for the given file
   * 
   * @param string $code
   * @param int $line
   * @return string
   */
  public function getLatestDate($code, $line = 1)
  {
    //get the last line (NB - this will only process the first line of what's returned)
    //this is to account for moments where an inexplained line break might mean the last line
    //doesn't have a timestamp
    $_lines = $this->getLastLines($code, $line);
    
    //get the file information
    $_file = $this->getFile($code);
    
    //get the matches
    $_matches = array();
    
    //do the match
    if(!preg_match($_file['format'], $_lines[0], $_matches)){
      return $this->getLatestDate($code, $line+1);
    }
    
    return $_matches['date'];
  }
  
  /**
   * Get the lines since a given datetime
   * 
   * @param string $code
   * @param string $date
   * @return array
   */
  public function getLinesSince($code, $date)
  {
    //get the last 50 lines
    $_lines = $this->getLastLines($code, 50);
    
    //return lines initiator
    $_returnLines = array();
    
    //get the file info
    $_file = $this->getFile($code);
    
    //this is a date to label non-dated lines with
    $_dateToUse = null;
    
    //iterate through the lines
    foreach($_lines as $_key => $_line) {
      $_matches = array();
      
      //pull out the date information, skipping if it doesn't match
      if(!preg_match($_file['format'], $_line, $_matches) && is_null($_dateToUse)){
        continue;
      } elseif (!preg_match($_file['format'], $_line, $_matches) && !is_null($_dateToUse)){
        $_matches['date'] = $_dateToUse;
      }
      
      //if this entry is the same or earlier than the requested time - remove it and move on
      if(strtotime($_matches['date']) <= strtotime($date)){
        unset($_lines[$_key]);
        continue;
      } else {
        $_returnLines[$_matches['date'].'___'.mt_rand()] = $_line;
      }
      
      //a date to use for non-dated lines
      $_dateToUse = $_matches['date'];
      
    }
    
    return $_returnLines;
  }
  
  /**
   * Render a specified template file
   * 
   * @param string $filename
   * @return string
   */
  public function render($filename = 'index.phtml')
  {
    ob_start();
  	include($filename);
  	$_html = ob_get_clean();
  	return $_html;
  }
  
  /**
   * Process an AJAX request for file updates
   * 
   * @param array $files
   * @return string
   */
  public function process($files)
  {
    $_response = array();
    foreach($files as $_code => $_date){
      $_response[$_code] = $this->getLinesSince($_code, $_date);
    }
    
    //set the JSON header
    header('Content-type: application/json');
    
    //echo out the json string
    return json_encode($_response);
  }
}

$logReview = new LogReview('config.ini');

if(array_key_exists('files', $_POST)){
  echo $logReview->process($_POST['files']);
} else {
  echo $logReview->render('index.phtml');
}


