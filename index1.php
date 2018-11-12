<?php 

if( isset($_POST['submit']) && isset($_FILES["csvfile"]) ) 
{

    $servername = "localhost";
    $username = "root";
    $password = "admin";
    $source_file = $_FILES["csvfile"]["name"];
    $dbname = $_POST['dbname'];
    $tblname = $_POST['tblname'];


    try {
        CreateDatabaseTable($servername,$username,$password,$dbname,$tblname);
    }
    catch(PDOException $e)
    {
        echo     $e->getMessage();
    }

    prettyVarDump(getCustomCSV($source_file),"10 rows");

    
}


// create database and table
function CreateDatabaseTable($servername,$username,$password,$dbname,$tblname)
{
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    $conn->exec($sql);
    $sql = "use $dbname";
    $conn->exec($sql);
    $sql = "CREATE TABLE IF NOT EXISTS $tblname (
                ID int(11) AUTO_INCREMENT PRIMARY KEY)";
    $conn->exec($sql);
    echo "DataBase Created Successfully \n";
}


// display Beatiful vardump function 
function prettyVarDump($data, $title="", $background="#EEEEEE", $color="#000000")
{
        
    echo "<pre style='background:$background; color:$color; padding:10px 20px; border:2px inset $color'>";
    echo    "<h2>$title</h2>";
            var_dump($data); 
    echo "</pre>";
}



// replace space character to underline
function cleanseHeaderRow($header_row)
{
    $new_header_row = array();
    foreach($header_row as $key => $a_row)
    {
        $new_header_row[$key] = strtolower(str_replace(" ", "_", preg_replace("/[^ \w]+/", "_", trim($a_row))));
    }
    return $new_header_row;
}


// get 10 Rows of csv file that is not empty
function getCustomCSV($file, $lenght = 10, $skipEmptyLines = true) 
{
    $numberRow = 0; 
    $output = array(); 
        if (($handle = fopen($file, "r")) !== FALSE)  
        { 
            while (($data = fgetcsv($handle, 10000, ",")) !== FALSE)  
            {
                if($numberRow == 0)
                {
                    $data = cleanseHeaderRow($data);
                    foreach($data as $key => $value){
                        $header_row[$key] = $value;
                    }
                    prettyVarDump($header_row,"Cleans Header Row"); 
                }
                else
                {
                    if($lenght) 
                    { 
                        $chk = true; 
                        foreach($data AS $row) 
                        { 
                            if(empty($row) && $skipEmptyLines == true) 
                            {
                                $chk = false; 
                            }
                        } 
                        if($chk) 
                        { 
                            $output[] = $data; 
                            $lenght--; 
                        } 
                    } 
                    else
                    {
                        break; 
                    }
                }
                $numberRow++;
            } 
            fclose($handle); 
        } 
    return $output; 
}


?>