<?php

if (isset($_POST['submit'])) 
{
    $servername = "localhost";
    $username = "root";
    $password = "admin";

    $dbname = $_POST['dbname'];
    $numberOfDB = $_POST['number_of_db'];


    for ($i=1; $i <= $numberOfDB; $i++) 
    { 
        $source_file = $_FILES["csvfile$i"]["name"];

        $tblname = str_replace(".csv","", $source_file);
        
        $headerRow = getHeaderRow($source_file);

        $get10rows = getCustomCSV($source_file);
    
        $dataTypes = analysisDataTypes($get10rows);
    
        $csvColumns = createCsvColumns($headerRow, $dataTypes);
    
        $columnsDataTime = setColumnsDataTime($dataTypes); 
    
        try {
            CreateDatabaseTable($csvColumns, $servername, $username, $password, $dbname, $tblname);
            loadCsvToMysql($source_file, $headerRow, $columnsDataTime, $servername, $username, $password, $dbname, $tblname);
        } catch (PDOException $e) {
            echo     $e->getMessage();
        }
    }


}


function setColumnsDataTime($dataTypes)
{
    $columnsDataTime = array();
    foreach ($dataTypes as $key => $value) {
        if( $value == "DATETIME"){
            $columnsDataTime[] = $key;
        }
    }

    return $columnsDataTime;
}


// create database and table
function CreateDatabaseTable($csvColumns, $servername, $username, $password, $dbname, $tblname1)
{
    // create database
    $conn = new PDO("mysql:host=$servername", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    $conn->exec($sql);
    $sql = "use $dbname";
    $conn->exec($sql);
    $sql = "CREATE TABLE IF NOT EXISTS $tblname1 (
                ID int(11) AUTO_INCREMENT PRIMARY KEY,
                $csvColumns
                )";
    $conn->exec($sql);
    echo "DataBase Created Successfully \n";
    $conn = null;
}


// Merg header name & data type $ size column to one string
function createCsvColumns($headerRow, $dataTypes)
{
    $csvColumns = array();
    $sizeColumn = 0;

    for ($i = 0 ; $i < sizeof($headerRow) ; $i++) {
        if ($dataTypes[$i] == 'INT') {
            $sizeColumn = 20;
        } elseif ($dataTypes[$i] == 'TINYINT') {
            $sizeColumn = 1;
        } elseif ($dataTypes[$i] == 'VARCHAR') {
            $sizeColumn = 255;
        } elseif ($dataTypes[$i] == 'DATETIME') {
            $sizeColumn = 6;
        }
        $csvColumns[] = "$headerRow[$i] " . strtoupper($dataTypes[$i]) . "({$sizeColumn})";
    }
    $csvColumns = join(', ', $csvColumns);

    return $csvColumns;
}


// Load csv to mysql by one query
function loadCsvToMysql($file, $headerRow, $columnsDataTime, $servername, $username, $password, $dbname, $tblname1){
    $cons= mysqli_connect("$servername", "$username","$password","$dbname") or die(mysql_error());

    $result1=mysqli_query($cons,"select count(*) count from $tblname1");
    $r1=mysqli_fetch_array($result1);
    $count1=(int)$r1['count'];

    $atHeaderRow = createHeaderString($headerRow, $columnsDataTime);
    $setString = createSetString($headerRow, $columnsDataTime);
    // echo $setString;
    $atHeaderRow = join(', ', $atHeaderRow);
    // echo $atHeaderRow;
    
    $q = ' LOAD DATA LOCAL INFILE "'.$file.'"
    INTO TABLE '.$tblname1.'
    FIELDS TERMINATED by \',\'
    LINES TERMINATED BY \'\n\'
    IGNORE 1 ROWS
    (' .$atHeaderRow. ') 
    '.$setString.';';

    // echo $q;

    mysqli_query($cons, $q)or die(mysql_error());
    
    $result2=mysqli_query($cons,"select count(*) count from $tblname1");
    $r2=mysqli_fetch_array($result2);
    $count2=(int)$r2['count'];

    $count=$count2-$count1;
    if($count>0)
    echo "<br>Success";
    echo "<b> total $count records have been added to the table $tblname1 </b> ";
    
}


// create string for @ header
function createHeaderString($headerRow, $columnsDataTime){

    foreach ($headerRow as $key => $value) {
        foreach ($columnsDataTime as $k) {
            if($key == $k){
                $headerRow[$key] = '@'.$value;
            }
        }
    }

    return $headerRow;

}


// create string for set query | STR_TO_DATE
function createSetString($headerRow, $columnsDataTime){
    $setString = '';
    $i = 0;
    $chk = false;
    foreach ($headerRow as $key => $value) {
        foreach ($columnsDataTime as $k) {
            if($key == $k){
                $chk = true;
                if( $i > 0){
                    $setString .= ', ';
                }
                $setString .= $value . ' = STR_TO_DATE(' . $headerRow[$key] . ', "%m/%d/%Y")';
                $i++;
            }
        }
    }
    $allSetString = '';
    if($chk){
        $allSetString = 'SET ' . $setString;
    }
    
    return $allSetString;
}



// insert Data Row line by line in database
// function insertDataRow($file, $headerRow, $servername, $username, $password, $dbname, $tblname1)
// {
//     $conn = new PDO("mysql:host=$servername;dbname=$dbname", $username, $password);
//     $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

//     $headerRow = join(', ', $headerRow);

//     if (($handle = fopen($file, "r")) !== false) {
//         while (($data = fgetcsv($handle, 10000, ",")) !== false) {
//             if ($numberRow == 0) {
//                 // continue;
//             } else {
//                 foreach ($data as $key => $value) {
//                     if (empty($value)) {
//                         $value = 'NULL';
//                     }
//                     if (detectDateTimeType($value)) {
//                         // $value = date('m/d/Y H:i:s', $value);
//                         // $value->format('m/d/Y H:i:s');

//                         // $testtt = DateTime::createFromFormat('Y/m/d H:i', $value);

//                         // $value = date_create_from_format('d/m/Y H:i', $value);
//                         // $value->getTimestamp();

//                         // $date = DateTime::createFromFormat(m/d/Y H:i', $value);
//                         // $value = $date->format('m/d/Y H:i');
                        
//                         // prettyVarDump($value, "datetime");
//                     }
//                 }
                
//                 $data = "'". join("','", $data) ."'";

//                 $sql = "INSERT INTO {$tblname1} ({$headerRow})
//                 VALUES ($data)";
//                 $conn->exec($sql);
//                 // echo "New record created successfully";
//             }
//             $numberRow++;
//         }
//         fclose($handle);
//     }
//     $conn = null;
// }


// replace space character to underline
function cleanseHeaderRow($header_row)
{
    $new_header_row = array();
    foreach ($header_row as $key => $a_row) {
        $new_header_row[$key] = strtolower(str_replace(" ", "_", preg_replace("/[^ \w]+/", "_", trim($a_row))));
    }
    return $new_header_row;
}


// get Header Row
function getHeaderRow($file)
{
    $numberRow = 0;
    $header_row = array();

    if (($handle = fopen($file, "r")) !== false) {
        while (($data = fgetcsv($handle, 10000, ",")) !== false) {
            if ($numberRow == 0) {
                $data = cleanseHeaderRow($data);
                foreach ($data as $key => $value) {
                    $header_row[$key] = $value;
                }
            } else {
                break;
            }
            $numberRow++;
        }
    }
    return $header_row;
}


// get 10 Rows of csv file that is not empty
function getCustomCSV($file, $lenght = 10, $skipEmptyLines = true)
{
    $numberRow = 0;
    $output = array();

    if (($handle = fopen($file, "r")) !== false) {
        while (($data = fgetcsv($handle, 10000, ",")) !== false) {
            if ($numberRow == 0) {
                // continue;
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
    $NumberofCol = 0 ;
    foreach ($get10rows as $key => $value) {
        $NumberofCol = sizeof($value);
        foreach ($value as $cell) {
            if (is_numeric($cell)) {
                if (detectTinyIntType((int)$cell)) {
                    $dataTypes[$key][] = "TINYINT";
                } else {
                    $dataTypes[$key][] = "INT";
                }
            } elseif (detectDateTimeType($cell)) {
                $dataTypes[$key][] = "DATETIME";
            } else {
                $dataTypes[$key][] = "VARCHAR";
            }
        }
    }

    // Sort & Compare Data Types
    $chk = true;
    $DT = array();
    for ($col = 0; $col < $NumberofCol; $col++) {
        for ($row = 1; $row <= 9; $row++) {
            if ($dataTypes[0][$col] == $dataTypes[$row][$col]) {
                $DT[$col] = $dataTypes[0][$col];
            } else {
                $chk = false;
            }
        }
    }
    if ($chk) {
        return $DT;
    } else {
        echo "<pre"."Error : The data type of your CSV file column is not the same!"."</pre>";
    }

    return $dataTypes;
}


// just detect date time type
function detectDateTimeType($val)
{
    if (preg_match('/(.*)([0-9]{2}\/[0-9]{2}\/[0-9]{2,4})(.*)/', $val)) {
        return 1;
    } elseif (preg_match('/(.*)([0-9]{2}\-[0-9]{2}\-[0-9]{2,4})(.*)/', $val)) {
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



// display Beatiful vardump function
function prettyVarDump($data, $title="", $background="#EEEEEE", $color="#000000")
{
    echo "<pre style='background:$background; color:$color; padding:10px 20px; border:2px inset $color'>";
    echo    "<h2>$title</h2>";
    var_dump($data);
    echo "</pre>";
}
