<?php

if (isset($_POST['submit']) && isset($_FILES["csvfile"])) {
    $servername = "localhost";
    $username = "root";
    $password = "1";
    $source_file = $_FILES["csvfile"]["name"];
    $dbname = $_POST['dbname'];
    $tblname = $_POST['tblname'];


    try {
        CreateDatabaseTable($servername, $username, $password, $dbname, $tblname);
    } catch (PDOException $e) {
        echo     $e->getMessage();
    }

     prettyVarDump(getCustomCSV($source_file), "10 rows");
    
     // prettyVarDump($get10rows, "Data Type");

    $get10rows = getCustomCSV($source_file);

    prettyVarDump(analysisDataTypes($get10rows), "Data Type");
}


// create database and table
function CreateDatabaseTable($servername, $username, $password, $dbname, $tblname)
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
    foreach ($header_row as $key => $a_row) {
        $new_header_row[$key] = strtolower(str_replace(" ", "_", preg_replace("/[^ \w]+/", "_", trim($a_row))));
    }
    return $new_header_row;
}


// get 10 Rows of csv file that is not empty
function getCustomCSV($file, $lenght = 10, $skipEmptyLines = true)
{
    $numberRow = 0;
    $output = array();
    if (($handle = fopen($file, "r")) !== false) {
        while (($data = fgetcsv($handle, 10000, ",")) !== false) {
            if ($numberRow == 0) {
                $data = cleanseHeaderRow($data);
                foreach ($data as $key => $value) {
                    $header_row[$key] = $value;
                }
                // prettyVarDump($header_row, "Cleans Header Row");
            } else {
                if ($lenght) {
                    $chk = true;
                    foreach ($data as $row) {
                        if (empty($row) && $skipEmptyLines == true) {
                            $chk = false;
                        }
                    }
                    if ($chk) {
                        $output[] = $data;
                        $lenght--;
                    }
                } else {
                    break;
                }
            }
            $numberRow++;
        }
        fclose($handle);
    }
    return $output;
}


// analisis data for detect integer, varchar , datetime type
function analysisDataTypes($get10rows)
{
    $dataTypes = array();
    foreach ($get10rows as $key => $value) {
        $NumberofCol = 0;
        foreach ($value as $cell) {
            $NumberofCol++;
            if (is_numeric($cell)) {
                if (detectTinyIntType((int)$cell)) {
                    $dataTypes[$key][] = "TINYINT";
                } else {
                    $dataTypes[$key][] = "INT";
                }
            } else if (detectDateTimeType($cell)) {
                $dataTypes[$key][] = "DATETIME";
            } else {
                $dataTypes[$key][] = "VARCHAR";
            }
        }
    }
    $chk = true;
    $DT = array();
    for ($col = 0; $col < $NumberofCol; $col++) {
            for ($row = 1; $row <= 9; $row++) {
                if($dataTypes[0][$col] == $dataTypes[$row][$col]){
                    $DT[$col] = $dataTypes[0][$col];
                }
                else {
                    $chk = false;
                }
        }
    }
    if($chk){
        return $DT;
    }
    else {
         echo "<pre"."Error : The data type of your CSV file column is not the same!"."</pre>";
    }



}



// just detect date time type
function detectDateTimeType($val)
{
    if (preg_match('/(.*)([0-9]{2}\/[0-9]{2}\/[0-9]{2,4})(.*)/', $val)) {
        return 1;
    } else if (preg_match('/(.*)([0-9]{2}\-[0-9]{2}\-[0-9]{2,4})(.*)/', $val)) {
        return 1;
    } else {
        return 0;
    }
}

// detect boolean (TINYINT) type
function detectTinyIntType($val)
{
    return (strlen($val) == 1 && ($val == 0 || $val == 1)) ? 1 : 0;
}
