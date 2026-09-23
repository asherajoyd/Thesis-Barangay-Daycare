<?php 
include "../../api/conn.php";

if ($_SERVER['REQUEST_METHOD'] == 'GET') {

$id = $_GET['id'];

$sql = "DELETE FROM teacher WHERE id = $id";

if ($conn->query($sql) == TRUE){
    header("Location: teachers.php");
            exit();
}

}

?>