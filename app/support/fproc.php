<html>
<head>
<title>COMS Support</title>
</head>
<body>
<?php
header("Cache-Control: no-cache");

$compname = gethostbyaddr($_SERVER['REMOTE_ADDR']);
$ip_vistor = $_SERVER['REMOTE_ADDR'];
$userid = get_current_user();

$Module = $_POST["Module"];
$Title = $_POST["Title"];
$Feedback = $_POST["Feedback"];
$file_info = $_POST["file_info"];
$Severity = $_POST["Severity"];
$Probability = $_POST["Probability"];
$Priority = $_POST["Priority"];
$file_info = $_POST["file_info"];
$file_info = $_POST["file_info"];
$TrackNumber = $_POST["TrackNumber"];
$body .= "User: ".$userid."\n";
$body .= "Module: ".$Module."\n";
$body .= "Feedback: ".$Feedback."\n";
$body .= "Severity: ".$Severity."\n";
$body .= "Probability: ".$Probability."\n";
$body .= "Priority: ".$Priority."\n";
$body .="\n";


require_once "config_Constants.php";
$serverName = DB_HOST;
$connectionOptions = array("UID"=>DB_USER,"PWD"=>DB_PASSWORD,"Database"=>DB_NAME);
$conn =  sqlsrv_connect( $serverName, $connectionOptions);

$sqlid = "SELECT id FROM COMS_UAT ORDER BY id";
$getid = sqlsrv_query($conn, $sqlid);
while( $row = sqlsrv_fetch_array($getid, SQLSRV_FETCH_ASSOC))
{
$newid = $row['id'] + 1;
}

$tsql = "INSERT INTO COMS_UAT (id,module,feedback,userid,attachment,file_info,compname,ip_vistor,Severity,Probability,Priority,Title,Status,TrackNumber,Type) 
VALUES ($newid,'$Module','$Feedback','$userid','$filename','$file_info','$compname','$ip_vistor','$Severity','$Probability','$Priority','$Title','Open','$TrackNumber','Ticket')";
$postfeedback = sqlsrv_query($conn, $tsql);

echo "<table align='center'><tr><td colspan='2' align='center'><font size='20'>db<font color='#000099'>IT</font>pro</font></td></tr><tr><td colspan='2' align='center'> COMS UAT Support Ticket# ".$newid." </td></tr><tr><td colspan='2'>&nbsp;</td></tr><tr><td><br><br>";

echo "Thank you ".$userid." for providing your information. Your description of the issue is for the <i>".$Module."</i>.<br><br>Title: ".$Title."<br><br>We have that you entered: <i>".$Feedback."</i><br><br>";

echo "Please continue using the <a href='https://coms-uat.dbitpro.com'>COMS Application</a> or click <a href='index.php'>here</a> to create another ticket. <br><br>If you would like to communicate directly with someone, please contact Sean Cassidy at 732.920.8305, or email him at <a href='sean.cassidy@dbitpro.com'>sean.cassidy@dbitpro.com</a>.";

echo "</td></tr></table>";
$To = 'dbitpro@gmail.com,sean.cassidy@dbitpro.com,lferrucci@caci.com,barlowm@gmail.com'; 
$Subject = "COMS UAT Ticket: ".$newid."";
  mail( $To, $Subject, $body, "From: <do_not_reply@dbitpro.com>");
?>
</body>
</html>