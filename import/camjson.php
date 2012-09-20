<?php

$deactivated = true;

include("../settings.php");
include("../controller/dbh.php");

// Safety lock
if(!$deactivated){
  $db = new MySQLiHandler(
    $SETTING_DB_HOST,
    $SETTING_DB_USER,
    $SETTING_DB_PASS,
    $SETTING_DB_DATABASE
  );

  $file = "Fronter_20120911_1.json";
  $data = json_decode(file_get_contents($file),true);

  $links = array();
  $stats = array();
  $users = array();

  $i = 0;
  foreach($data as $item){
    if($i++ % 100 == 0) echo "$i entries parsed<br />";
    $links[$item["itemId"]] = array(
      "link" => $item["itemtitle"]
    );
    $users[$item['groupId']] = array(
      "name" => $item['grouptitle'],
      "email" => $item['groupemail'],
      "username" => $item['groupId'],
      "password" => $item['groupId']
    );
    $stats[] = array(
      "user"=>$item['groupId'],
      "link"=>$item['itemtitle'],
      "linkId"=>$item['itemId'],
      "refer"=>$item['guid'],
      "timestamp"=>strtotime($item['dateTime'])
    );
  }

  foreach($users as $userId=>$user){
    $names = explode(" ",$user['name']);
    $surname = array_pop($names);
    $firstname = implode(" ", $names);
    $users[$userId]['id'] = $db->insert("user",array(
      "course"=>"VU",
      "username"=>$user['username'],
      "password"=>$user['password'],
      "firstname"=>$firstname,
      "surname"=>$surname,
      "student"=>1,
      "email"=>$user['email']
    ));
    echo "User ".$users[$userId]['id']." was created.<br />";
  }

  foreach($links as $linkId=>$link){
    $links[$linkId]['id'] = $db->insert("links",array(
      "course"=>"VU",
      "url"=>$link['link'],
      "title"=>$link['link'],
      "type"=>"Web document"
    ));
    echo "Link ".$links[$linkId]['id']." was created.<br />";
  }


  $i = 0;
  foreach($stats as $stat){
    if($i++ % 100 == 0) echo "$i stats imported<br />";
    $db->insert("stats", array(
      "course"=>"VU",
      "user"=>$users[$stat['user']]['id'],
      "link"=>$stat['link'],
      "linkId"=>$links[$stat['linkId']]['id'],
      "refer"=>$stat['refer'],
      "timestamp"=>$stat['timestamp']
    ));
  }
}
?>
