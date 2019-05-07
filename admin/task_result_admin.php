<?php
require 'admin_startup.php';

function get_task_results($link, $tasPk) {
    $query = "SELECT * FROM `tblResultFile` WHERE `tasPk` = $tasPk ORDER BY `refTimestamp`";
    $result = mysqli_query($link, $query) or die('Error ' . mysqli_errno($link) . ' Result retreval failed: ' . mysqli_connect_error());
    return $result;
}

// Main Code Begins HERE //

$comPk = reqival('comPk');
$tasPk = reqival('tasPk');
$file = __FILE__;
$usePk = auth('system');
$link = db_connect();
$message = '';
$content = '';

# We check if there is modification request
if ( reqexists('chvisible') )
{
    $refPk = reqival('refPk');
    change_visible($link, $refPk);
}
elseif (reqexists('delete'))
{
    $refPk = reqival('refPk');

    # Check if I am admin for the Comp
    if (!check_admin('admin', $usePk, $comPk))
    {
        $message .= "You cannot delete results for that competition (ID = $comPk) <br /> \n";
    }
    else
    {
        $query = "DELETE FROM `tblResultFile` WHERE `refPk`=$refPk";
        mysqli_query($link, $query) or die('Error ' . mysqli_errno($link) . ' Result deletion failed: ' . mysqli_connect_error());

        $message .= "Result succesfully deleted (ID = $refPk) <br /> \n";
    }
}
elseif ( reqexists('chstatus') )
{
    $refPk = reqival('refPk');
    $status = reqsval('status');
    change_status($link, $refPk, $status);
}




# Get result files for the task
$result = get_task_results($link, $tasPk);

# Check if we have registered pilots
if ( mysqli_num_rows($result) == 0 )
{
    $message .= "There are no results for this Task yet <br /> \n";
}
else
{
    # Create Tables
    $out = '';
    $dtable = [];
    $xtable = [];
    $count = 1;

    $out = 'There are ' . mysqli_num_rows($result) . ' results available';
    $dtable[] = array('', '<strong>Created on</strong>', '<strong>File</strong>', '<strong>Status</strong>', '', '<strong>Visible</strong>');
    while ( $row = mysqli_fetch_assoc($result) )
    {
        $refPk = $row['refPk'];
        $created = gmdate('Y-m-d H:i:s', ($row['refTimestamp']));
        $json = $row['refJSON'];
        $status = $row['refStatus'];
        $active = $row['refVisible'];

        #check if JSON file exists
        if (file_exists($json)) {
            $file = "<a href='../test_task_result.php?refPk=$refPk' target='_blank'>".basename($json)."</a>";
        }
        else {
            $file = "<strong style='color:red'>".basename($json)."</strong>";
        }

        $dtable[] = array($count, $created, $file, "<form enctype='multipart/form-data' action='task_result_admin.php?comPk=$comPk&tasPk=$tasPk&refPk=$refPk' method='post' name='res-$refPk' id='res-$resPk'>".fin('status', $status, 14), fis('chstatus', 'Change Status', 12), fic("active$refPk", " ", $active, "onchange='this.form.submit()'"), fis('del', 'DELETE', 10) ."</form>");
        $count++;

    }

    // # Create Bulk IGC ZIP Submit
    // $btable[] = array(" <form enctype=\"multipart/form-data\" action=\"tasktrack_admin.php?tasPk=$tasPk&comPk=$comPk\" method=\"post\">
    //                         <input type=\"hidden\" name=\"MAX_FILE_SIZE\" value=\"1000000000\">",
    //                         "<input name=\"userfile\" type=\"file\">",  "<input type=\"submit\" name=\"foo\" value=\"Send Tracklog\"></form>");
    //
    // # Create XContest Tracks Submit
    // $xtable[] = array(" <form action=\"tasktrack_admin.php?tasPk=$tasPk&comPk=$comPk\" name=\"xcontest\" method=\"post\"> \n".
    //                         fbut('submit', 'getxcontest', $tasPk, 'Get Tracks fron XContest').
    //                         "\n</form>\n");
}


//initializing template header
$query = "SELECT * FROM `tblTaskView` WHERE `tasPk`=$tasPk";
$result = mysqli_query($link, $query);
$row = mysqli_fetch_assoc($result);
$taskname = $row['tasName'];

tpadmin($link, $file, $row);

echo "<h3> $taskname </h3>" . PHP_EOL;
echo "<h4> Result Management </h4>" . PHP_EOL;
if ( $message !== '')
{
    echo "<h4> <span style='color:red'>$message</span> </h4>" . PHP_EOL;
    echo "<br />" . PHP_EOL;
    echo "<hr />" . PHP_EOL;
}
if ( $content !== '')
{
    echo "$content" . PHP_EOL;
    echo "<br />" . PHP_EOL;
    echo "<hr />" . PHP_EOL;
}


echo "<br />" . PHP_EOL;
echo "<hr />" . PHP_EOL;
$out .= ftable($dtable,'class=regdtable', '', '');
echo $out;
echo "<br />" . PHP_EOL;
echo "<hr />" . PHP_EOL;

echo "<a href='task_admin.php?tasPk=$tasPk&comPk=$comPk'>Back to Task Administration</a><br />";

tpfooter($file);

?>
