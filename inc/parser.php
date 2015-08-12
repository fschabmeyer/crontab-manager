<?php
namespace exporter\parser;
use exporter\regex;


class parser {

    private $ServerToTest = Array();

    /**
     *
     * @return array
     */
    private function getIniSettings(){
        $filename = "config.ini";
        $array = parse_ini_file($filename);
        $this->ServerToTest = $array;
    }

    /**
     * @param $serveriptotest
     * @return resource
     */
    private function getsshConnection($serveriptotest){
        $connection = ssh2_connect($serveriptotest, 22);
        ssh2_auth_password($connection, 'root', 'test');
        return $connection;
        }

    /**
     * @param $connection
     * @param $commandtotest
     * @return string
     */
    private function getsshStreamData($connection, $commandtotest){
        $stream = ssh2_exec($connection, "tail /var/spool/cron/crontabs/root");
        stream_set_blocking($stream, true);
        $data = "";
        while ($buf = fread($stream, 4096)) {
            $data .= $buf;
        }
        fclose($stream);
        return $data;
    }

    /**
     *
     */
    private function getCrontabFromRemoteServer(){
        foreach ($this->ServerToTest["serverip"] as $serveriptotest) {
            $connection = $this->getsshConnection($serveriptotest);
            $data = $this->getsshStreamData($connection,"tail /var/spool/cron/crontabs/root");
            $splitdata=explode("\n",$data);
           foreach ($splitdata as $crontabline){
               if (substr($crontabline,0,1) == '#') {
                   echo $crontabline."\n";
               }
               else {
                   $parsedline = $this->parseLine($crontabline);
                   if ($parsedline['state']== 1) {
                       echo $crontabline."\n";
                   }
               }

            }

        }
    }


    /**
     * @param $line
     *
     * @return bool
     */
    public function parseLine($line) {
        $regex = '/^(' . regex::$regexmin . ')\s+(' . regex::$regexhrs. ')\s+(' . regex::$regexdom . ')\s+(' . regex::$regexmon . ')\s+(' . regex::$regexdow . ')\s+(.+)$/';
        if (preg_match($regex,$line,$matches)) {
            return array('state' => true, 'matches' => $matches);
        }
        return array('state' => false);
    }



}