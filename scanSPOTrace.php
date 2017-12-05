<?php

  date_default_timezone_set("GB-Eire");

  $yesterday = strtotime("yesterday");
  $filedate = date("Ymd",$yesterday);
  $filename = "SPOTracefile_$filedate.log";
  chdir($_ENV["II_LOG"]);
  $totslaves = 0;
  $linecount = 0;
  $queue = 0;
  $nsqueue = 0;
  $nswait = 0;
  $message = "";
  $fp = fopen($filename,"r") or die("Unable to open $filename\r\n");
  while (($line = fgets($fp)) !== false) {
    if (strstr($line,"QueueUse/Depth:")) {
      $linecount++;
      $max = substr($line,55,11);
      $max = trim($max);
      if ($max > 0) {
        $line = trim($line);
//        print "$line\r\n";
        if ($snap == "1") {
          $nsqueue++;
          $nextline = fgets($fp);
          $wait = substr($nextline,58,8);
          $wait = trim($wait);
          if ($wait > $nswait) {
            $nswait = $wait;
          }

        } else {
          $dno = $sarray[$snap];
          $image = $darray[$dno];
          $queue++;
          $message .= "$image\t$line\r\n";
          $nextline = fgets($fp);
          $wait = substr($nextline,58,10);
          $wait = trim($wait);
          $message .= "$wait\r\n";
        }
      }
    }
    if (strstr($line,' TotalSlaves')) {
      $slaves = trim(substr($line,35));
      if ($slaves > $totslaves) {
        $totslaves = $slaves;
      }
    }
    if (strstr($line,"SNAPSHOT")) {
      $snap = substr($line,37);
      $snap = trim($snap);
    }
    if (strstr($line,"CREATING")) {
      if (substr($line,36,1) == "S") {
        $sno = substr($line,37,2);
        $sno = trim($sno);
        $pos1 = strpos($line,"(");
        $pos2 = strpos($line,")");
        $dno  = substr($line,$pos1+2,($pos2-$pos1)-2);
        $sarray[$sno] = $dno;
      } else {
        if (substr($line,37) == "i") {
          $sarray = array("");
          $darray = array("");
        } else {
          $dno = substr($line,37,2);
          $dno = trim($dno);
          $nextline = fgets($fp);
          $image = substr($nextline,36);
          $image = trim($image);
          $darray[$dno] = $image;
        }
      }
    }
  }
  $message = "Total Slaves High Water Mark: $totslaves\r\n\r\n" . $message;
  $message .= "\r\n$nsqueue incidents of name server queuing, max wait $nswait ms\r\n";
  $to = "jruffer@hss.com";
  $to = "DBAs@hss.com";
  $subject = "$queue incidents of queuing found out of $linecount";
  mail($to, $subject, $message);
  fclose($fp);
  file_put_contents("scan.out",$message);
  exit(0);
?>
