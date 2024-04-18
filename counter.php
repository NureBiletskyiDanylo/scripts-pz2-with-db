<?php

//Important initial sql requests:
$sqlCreateDataBase = "CREATE DATABASE IF NOT EXISTS `php_counter` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */ /*!80016 DEFAULT ENCRYPTION='N' */";
$sqlCreateTable = "CREATE TABLE IF NOT EXISTS `user_activity` (
   `user_ip` varchar(50) NOT NULL,
   `date` datetime DEFAULT NULL,
   `todays_count` int DEFAULT NULL,
   `total_count` int DEFAULT NULL,
   PRIMARY KEY (`user_ip`)
 ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci";

$connectionToDb = new mysqli("localhost:3306", "root", "", ""); //Insert here your credentials for your mysql

// Check if db is created (if not then create db and corresponding table)
$connectionToDb->execute_query($sqlCreateDataBase);
$connectionToDb->select_db("php_counter");
$connectionToDb -> execute_query($sqlCreateTable);

// get basic important info
$currentDate = new DateTime('now');
$currentDateStr = $currentDate->format('Y-m-d H:i:s');
$userIp = $_SERVER['REMOTE_ADDR'];

//Check if the user's ip is in db
$sql ="SELECT user_ip FROM php_counter.user_activity WHERE user_ip=?";
$stmt = $connectionToDb->prepare($sql);
$stmt -> bind_param("s", $userIp);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();
//if not then create a new field for user
if (gettype($result) == "NULL")
{
    unset($sql);
    $sql = "INSERT INTO php_counter.user_activity (user_ip, date, todays_count, total_count)
    VALUES ( ?, NOW(), 1, 1)";
    $stmt = $connectionToDb->prepare($sql);
    $stmt -> bind_param("s",$userIp);
    
    $stmt->execute();
}
else
{
    unset($sql);

    $sql = "SELECT date FROM php_counter.user_activity WHERE user_ip = ?";
    $stmt = $connectionToDb->prepare($sql);
    $stmt -> bind_param("s",$userIp);
    $stmt->execute();
    $lastDateStr = $stmt->get_result();
    if ($lastDateStr -> num_rows > 0)
    {
        $row = $lastDateStr->fetch_assoc();
        $lastDateStr = $row['date'];
        $lastDateStamp = strtotime($lastDateStr);
        $lastDate = new DateTime('now');
        if ($lastDateStamp)
        {
            $lastDate->setTimestamp($lastDateStamp);
        }
        else
        {
            echo "Request was not successful";
        }
    }
    else
    {
        echo "Request was not successful so last date is being set up to today's date";
        $lastDate = new DateTime('now');
    }
    // updating todays and total counts i.e being dependent on last visit
    if ($currentDate->format("%d") != $lastDate->format("%d"))
    {
        $sql = "UPDATE php_counter.user_activity SET date = NOW(), todays_count = 1, total_count = total_count + 1 WHERE user_ip = ?";
        $stmt  = $connectionToDb->prepare($sql);
        $stmt->bind_param("s",$userIp);
        $stmt->execute();
    }
    else
    {
        $sql = "UPDATE php_counter.user_activity SET date = NOW(), todays_count = todays_count + 1, total_count = total_count + 1 WHERE user_ip = ?";
        $stmt  = $connectionToDb->prepare($sql);
        $stmt->bind_param("s",$userIp);
        $stmt->execute();
    }
    
    //output info
    
    $sqlSelectUserData = "SELECT * FROM php_counter.user_activity WHERE user_ip = ?";
    $stmt = $connectionToDb->prepare($sqlSelectUserData);
    $stmt->bind_param("s",$userIp);
    $stmt->execute();
    $userData = $stmt->get_result();
    if ($userData->num_rows > 0)
    {
        $userData = $userData->fetch_assoc();
        echo "<b>Info about the following user:</b> <br>
        ip - " . $userData['user_ip'] . "<br>";
        echo "last attendance -"  . $userData['date'] . "<br>";
        echo "todays count -" . $userData['todays_count'] ."<br>";
        echo "total count -" . $userData['total_count'] . "<br>";
    }
}
