<?php 

if( isset($_POST['submit']) && isset($_FILES["csvfile"]) ) {

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

    populateRows($source_file);
    
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
    echo "DB created successfully \n";
}


function prettyVarDump($data){
    echo "<pre>" ;
    var_dump($data);
    echo "</pre>";
}


function populateRows($source_file)
{
    $numberRow = 0;
    $header_row = array();

    if( $handle = fopen($source_file, "r") ){

        while( $data = fgetcsv($handle, 10000, ",") ){

            if ( $numberRow == 0 ) {
                $data = cleanseHeaderRow($data);
                foreach($data as $key => $value){
                    $header_row[$key] = $value;
                }
                prettyVarDump($header_row);
            } else {
                echo 'else <br>';
                
                prettyVarDump($data);
            }
            $numberRow++;   
        }

    } else {
        echo 'Err: can not open csv file.';
    }
    
}



// replace space to underline
function cleanseHeaderRow($header_row)
{
    echo "cleans";
    $new_header_row = array();
    foreach($header_row as $key => $a_row){
        $new_header_row[$key] = strtolower(str_replace(" ", "_", preg_replace("/[^ \w]+/", "_", trim($a_row))));
    }
    return $new_header_row;
}






?>