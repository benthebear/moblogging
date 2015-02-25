<?php

class email {
  var $server;
  var $port;
  var $user;
  var $password;
  var $lastresult;
  var $lastresulttxt;
  var $connection;
  var $list;
  var $mails;
  var $path;
  
  function email($server, $port, $user, $password, $path){
    $this->server = $server;
    $this->port = $port;
    $this->user = $user;
    $this->password = $password;
    $this->path = $path;
  }
  
  function email_open(){
    $this->connection = imap_open("{". $this->server.":".$this->port."/pop3}INBOX", $this->user, $this->password);    
  }
  
  function email_close(){
    imap_close($this->connection);
  }
  
  function get_all_mail(){
    $this->email_open();
    $number_of_messages = imap_num_msg($this->connection);
    //echo $number_of_messages;
    $counter = 1;
    while ($number_of_messages>=$counter){
      $this->get_one_mail($counter);
      $counter++;
    }
    $this->email_close();
  }
  
  function get_one_mail($number){
    $mail = imap_headerinfo($this->connection, $number);
    //echo "<hr/>";
    //echo "mail nummer: ".$number."<br/>";
    //echo "date: ".$mail->date."<br/>";
    //echo "subject: ".$mail->subject."<br/>";
    //echo "from-mailbox: ".$mail->from[0]->mailbox."<br/>";
    //echo "from-host: ".$mail->from[0]->host."<br/>";
    $this->mails[$number]["date"]["raw"] = $mail->date;
    $zwischenspeicher = split(" ", $this->mails[$number]["date"]["raw"]);
    //echo "Zwischenspeicher[2]: ".$zwischenspeicher[2]."<br/>";
    switch($zwischenspeicher[1]){
      
      case "Jan":
      $month="01";
      break;
      
      case "Feb":
      $month="02";
      break;
      
      case "Mar":
      $month="03";
      break;
      
      case "Apr":
      $month="04";
      break;
      
      case "May":
      $month="05";
      break;
      
      case "Jun":
      $month="06";
      break;
      
      case "Jul":
      $month="07";
      break;
      
      case "Aug":
      $month="08";
      break;
      
      case "Sep":
      $month="09";
      break;
      
      case "Oct":
      $month="10";
      break;
      
      case "Nov":
      $month="11";
      break;
      
      case "Dec":
      $month="12";
      break;
    
    }
    $uhrzeit = split(":", $zwischenspeicher[3]);
    $this->mails[$number]["date"]["year"] = $zwischenspeicher[2];
    $this->mails[$number]["date"]["month"] = $month;
    if ($zwischenspeicher[0]<10){
      $zwischenspeicher[0] = "0".$zwischenspeicher[0];
    }
    $this->mails[$number]["date"]["day"] = $zwischenspeicher[0];
    $this->mails[$number]["date"]["hour"] = $uhrzeit[0];
    $this->mails[$number]["date"]["minute"] = $uhrzeit[1];
    $cleansubject = ereg_replace("=\?ISO-8859-1\?Q\?", "", $mail->subject);
    $cleansubject = ereg_replace("\?=", "", $cleansubject);
    $cleansubject = ereg_replace("_", " ", $cleansubject);
    $this->mails[$number]["subject"] = $cleansubject;
    $this->mails[$number]["mailbox"] = $mail->from[0]->mailbox;
    $this->mails[$number]["host"] = $mail->from[0]->host;
    // Der Body der email wird geholt
    $body = imap_fetchbody($this->connection, $number, "1");
    if ($body!=""){
      $this->mails[$number]["text"] = $body;
    }
    $struct = imap_fetchstructure($this->connection, $number);
    //print_r($struct);
    $counter=2;
    // Diese Schleife läuft durch alle Anhänge
    while (imap_fetchbody($this->connection, $number, $counter)!=""){
      	//Die base64 codierten Bilder aus dem anhang werden geholt
    	$image = imap_fetchbody($this->connection, $number, $counter);
      	$this->mails[$number]["image"][$counter]["data"] = $image;
      	$parts=$counter-1;
      	// Der Dateiname wird aus der Struct Variable geholt
      	$this->mails[$number]["image"][$counter]["name"] = $struct->parts[$parts]->dparameters[0]->value;
      	// Aus dem in base64 codierten Bild wird eine Datei gemacht
      	$this->email_base64_to_file($number, $counter);
      	$counter++;    
    }
  }
  
  /*
  *		Diese Funktion gibt alle emails aus
  *
  *		Diese Funktion gibt alle emails in einem Postfach in HTML aus.
  *		Die Funktion dient nur de:Bugging Zwecken.  
  */
  
  
  private function  show_all_mail(){
    $this->get_all_mail();
    $counter =1;
    while ($this->mails[$counter]["date"]!=""){
      echo "<hr/>";
      echo "date: ".$this->mails[$counter]["date"]["raw"]."<br/>";
      echo "year: ".$this->mails[$counter]["date"]["year"]."<br/>";
      echo "month: ".$this->mails[$counter]["date"]["month"]."<br/>";
      echo "day: ".$this->mails[$counter]["date"]["day"]."<br/>";
      echo "hour: ".$this->mails[$counter]["date"]["hour"]."<br/>";
      echo "minute: ".$this->mails[$counter]["date"]["minute"]."<br/>";
      echo "subject: ".$this->mails[$counter]["subject"]."<br/>";
      echo "mailbox: ".$this->mails[$counter]["mailbox"]."<br/>";
      echo "host: ".$this->mails[$counter]["host"]."<br/>";
      echo "text: ".$this->mails[$counter]["text"]."<br/>";
      $pic = 2;
      while ($this->mails[$counter]["image"][$pic]["data"]!=""){
        $pic--;
        echo "Bild Nr.".$pic."<br/>";
        $pic++;
        echo "Bild Name: <a href=\"pics/".$this->mails[$counter]["image"][$pic]["name"]."\">".$this->mails[$counter]["image"][$pic]["name"]."</a><br/>";
        //$this->email_base64_to_file($counter, $pic);
        //echo "image ".$pic.": ".$this->mails[$counter]["image"][$pic]["data"]."<br/>";
        $pic++;
      }      
      $counter++;    
    }
  }
  
  
  
  function email_base64_to_file($counter, $pic){
    $f_pic = fopen($this->path."wp-content/uploads/moblog-w880i/".$this->mails[$counter]["image"][$pic]["name"], "w+");
    fwrite ($f_pic, base64_decode($this->mails[$counter]["image"][$pic]["data"]));
    fclose($f_pic);
  }
  
  function delete_all_mail(){
    $this->email_open();
    $counter=1;
    while ($this->mails[$counter]["date"]!=""){
      imap_delete($this->connection, $counter);
      $counter++;
    }
    imap_expunge($this->connection);
    $this->email_close();
  }
  
}


?>