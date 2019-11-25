<?php

include '../../config/config.php';

// Create connection
if(empty($port) && !$port){$port="3306";}
$conn = new mysqli($servername, $username, $password, $database, $port);
$conn->set_charset('utf8');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

include '../../functions/functions.php';

switch ($seconds) {
    case true:
        $showseconds = ':s';
        break;
    case false:
        $showseconds = '';
        break;
    default:
        $showseconds = '';
        break;
}

switch ($clock) {
    case '24':
        $clock = 'H:i' . $showseconds;
        break;
    case '12':
        $clock = 'g:i' . $showseconds;
        break;
    default:
        $clock = 'g:i' . $showseconds;
        break;
}

    $query = "SELECT pokestop_id, guid, UNIX_TIMESTAMP(CONVERT_TZ(lure_expiration, '+00:00', @@global.time_zone)) as lure_expiration, UNIX_TIMESTAMP(CONVERT_TZ(incident_expiration, '+00:00', @@global.time_zone)) as incident_expiration, quest_type, image, name from pokestop left join trs_quest on pokestop.pokestop_id = trs_quest.guid";
    $result = $conn->query($query);

    if($result && $result->num_rows >= 1 ) {
    $data=[];
    while ($row = $result->fetch_object() ) {
        if(date('Y-m-d ' . $clock, $row->incident_expiration) > date("Y-m-d H:i:s")){$row->rocket='Yes';} else {$row->rocket='No';}
        if($row->quest_type !== NULL){$row->quest='Yes';} else {$row->quest='No';}
        if($row->image == NULL){$row->image='<img src="images/Unknown.png" class="pic" height="46" width="46">';} else {$row->image='<img src="' . $row->image . '" class="pic" height="46" width="46">';}
        if($row->name == NULL){$row->name='Unknown';} else {$row->name='<a href="index.php?page=pokestops&pokestop=' . $row->pokestop_id . '">' . $row->name . '</a>';}
        if(!empty($row->lure_expiration)){$row->lure='Lured until ' . date($clock, $row->lure_expiration);} else {$row->lure='No';}
        $jsonfile->data[]  =  $row;
    }
}
print json_encode($jsonfile,  JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>