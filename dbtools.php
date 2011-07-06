<?php

if (file_exists('dbconfig.php')) {
    include 'dbconfig.php';
    if(!isset($db))
        die('dbconfig.php with no $db is stupid\n');
} else {
    $db = new PDO("sqlite:dbfile.sqlite");
}
if(!isset($my_station))
    $my_station = "Berlin";


function timestamp_to_datetime(&$item) {
    $item["time"] = DateTime::createFromFormat('U', "{$item['time']}");
}

function get_current($limit=10, $before=5)
{
    global $db;
    global $my_station;
    $time = new DateTime();
    $time->modify("- $before minutes");
    $time =  (int)$time->format('U');
    $stmt = $db->prepare("
        select station, target, train, line, planed_arrival, drift
        from fahrplan
        where (station = ?)
        and (planed_arrival > ?)
        limit 0, ?");
    if($stmt === FALSE)
        die(print_r($db->errorInfo(), true));
    $stmt->bindParam(1, $my_station);
    $stmt->bindParam(2, $time, PDO::PARAM_INT);
    $stmt->bindParam(3, $limit, PDO::PARAM_INT);
    $stmt->execute() or die(print_r($stmt->errorInfo(), true));
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
    array_walk($items, 'timestamp_to_datetime');
    return $items;
}

function insert_or_update($station, $item)
{
    global $db;
    $stmt = $db->prepare("
        replace into fahrplan(
            station, train, line, 
            target, planed_arrival, drift) VALUES
            (?, ?, ?, ?, ?, ?)");
    if ($stmt == NULL)
        die(print_r($db->errorInfo(), true));
    $time = (int)$item["time"]->format('U');
    $stmt->bindParam(1, $station, PDO::PARAM_STR);
    $stmt->bindParam(2, $item["train"], PDO::PARAM_STR);
    $stmt->bindParam(3, $item["line"], PDO::PARAM_STR);
    $stmt->bindParam(4, $item["target"], PDO::PARAM_STR);
    $stmt->bindParam(5, $time, PDO::PARAM_INT);
    $stmt->bindParam(6, $item["drift"], PDO::PARAM_STR);
    $stmt->execute() or die(print_r($stmt->errorInfo(), true));

}


?>
