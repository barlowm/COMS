<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class MdwsBase
{
    public function MDWS_Setup($roles)
    {
        $sitelist = $_SESSION['sitelist'];
        $AC = $_SESSION['AC'];
        $VC = $_SESSION['VC'];
        $_SESSION['MDWS_Status'] = '';
        $_SESSION['MDWS_Type'] = '';
        $_SESSION['MDWS_Msg'] = '';
        $_SESSION['MDWS_Suggestion'] = '';
            //echo "<br>MDWS Dump:".var_dump($_SESSION)."<br>";

            try {
                $client = new SoapClient("http://".$_SESSION['mdws']."/EmrSvc.asmx?WSDL");
                if (isset($client->fault)) {
                    $this->MDWsCrashReport($client, "SoapClient", false);

                    return;
                }

/*
                    $addDataSource = $client->addDataSource(array('id'=>'355','name'=>'vaphsdb04','datasource'=>'54.243.40.32','port'=>'9210','modality'=>'HIS','protocol'=>'VISTA','region'=>'355'));
                    if (isset($connect->connectResult->fault)) {
                            $this->MDWsCrashReport($connect->connectResult, "Connect", false);
                            return null;
                    }
*/
                    // $addDataSource = $client->addDataSource(array('id'=>'355','name'=>'I20355VSTLNX','datasource'=>'54.83.44.110','port'=>'9200','modality'=>'HIS','protocol'=>'VISTA','region'=>'355'));
                    $addDataSource = $client->addDataSource(array('id' => '355', 'name' => 'I20355VSTLNX', 'datasource' => '54.83.44.110', 'port' => '9300', 'modality' => 'HIS', 'protocol' => 'VISTA', 'region' => '355'));
                if (isset($connect->connectResult->fault)) {
                    $this->MDWsCrashReport($connect->connectResult, "Connect", false);

                    return;
                }

                $connect = $client->connect(array('sitelist' => $sitelist));
                if (isset($connect->connectResult->fault)) {
                    $this->MDWsCrashReport($connect->connectResult, "Connect", false);

                    return;
                }

                $login = $client->login(array('username' => $AC, 'pwd' => $VC, 'context' => ''));
                if (isset($login->loginResult->fault)) {
                    $this->MDWsCrashReport($login->loginResult, "Login", false);

                    return;
                }

                if (isset($client->fault)) {
                    $this->MDWsCrashReport($client, "Client", false);

                    return;
                }

                return $client;
            } catch (Exception $e) {
                // echo ("Error: $e->getMessage()");
            }

        return;
    }

    public function MDWS_Disconnect($client)
    {
        $disconnect = $client->disconnect();
        if (isset($disconnect->disconnectResult->fault)) {
            $this->MDWsCrashReport($disconnect->disconnectResult, "Disconnect", false);

            return;
        }

        $disconnect = $client->disconnectRemoteSites();
        if (isset($disconnect->disconnectRemoteSitesResult->fault)) {
            $this->MDWsCrashReport($disconnect->disconnectRemoteSitesResult, "Disconnect", false);

            return;
        }
    }

    public function MDWS_TimestampConvert($date)
    {
        // YYYYMMDD.HHMMSS
            // 012345678901234
            // 000000000011111
            $year = substr($date, 0, 4);
        $mon = substr($date, 4, 2);
        $day = substr($date, 6, 2);
        $hr = substr($date, 9, 2);
        $min = substr($date, 11, 2);
        $sec = substr($date, 13, 2);
        $parsedDate = "$mon/$day/$year $hr:$min:$sec";
            // echo "$date - $year - $mon - $day - $hr - $min - $sec<br />";
            return ($parsedDate);
    }

    public function MDWS_Convert2JSON($dfn, $rawData)
    {
        $array = (array) $rawData;

            //Format JSON
            $jsonRecord = array();
        $jsonRecord['success'] = 'true';
        $jsonRecord['total'] = count($rawData);

        $recordArray = array();
        $recordArray['id'] = $dfn;
        $recordArray['data'] = $rawData;

        $jsonRecord['records'] = $recordArray;

        return json_encode($jsonRecord);
    }

    public function MDWs_ShowData($dfn, $rawData, $patientId)
    {
        $recordArray = array();
        $recordArray['id'] = $patientId;
        $recordArray['dfn'] = $dfn;
        $recordArray['data'] = $rawData;

        $data = json_encode($recordArray);
        echo $data;
    }

    public function MDWsCrashReport($result, $type, $displayReport)
    {
        if (isset($result->fault)) {
            $fault = $result->fault;
            $_SESSION['MDWS_Status'] = 'Crashed';
            $_SESSION['MDWS_Msg'] = $fault->message;
            $_SESSION['MDWS_Suggestion'] = $fault->suggestion;
            $_SESSION['MDWS_Type'] = $type;
                    //$_SESSION['cprsUsername'] = $cprsUsername;
                    $this->MDWSCrashed($displayReport);

            return;
        }

        return ($result);
    }

    public function MDWSCrashed($displayReport)
    {
        $errMsg = "";
        if ("" !== $_SESSION['MDWS_Type']) {
            $errMsg .= "MDWS Type: ".$_SESSION['MDWS_Type']."; ";
        }
        if ("" !== $_SESSION['MDWS_Msg']) {
            $errMsg .= "MDWS Msg: ".$_SESSION['MDWS_Msg']."; ";
        }
        if ("" !== $_SESSION['MDWS_Suggestion']) {
            $errMsg .= "MDWS Suggestion: ".$_SESSION['MDWS_Suggestion']."; ";
        }
        if ("" === $errMsg) {
            $errMsg .= "Unknown MDWS Error; No further details to report";
        }

        return ($errMsg);
        /********
            if ("" !== $_SESSION['MDWS_Status'] && $displayReport) {
                    echo "<table border=1>";
                    echo "<tr><th colspan=2><h1>MDWs Crashed</h1></th></tr>";
                    echo "<tr><th>MDWs Error</th><td>" . $_SESSION['MDWS_Msg'] . "</td></tr>";
                    echo "<tr><th>Suggestion</th><td>" . $_SESSION['MDWS_Suggestion'] . "</td></tr>";
            }
         ********/
    }

    public function objectToArray($d)
    {
        if (is_object($d)) {
            // Gets the properties of the given object
                    // with get_object_vars function
                    $d = get_object_vars($d);
        }

        if (is_array($d)) {
            /*
                    * Return array converted to object
                    * Using __FUNCTION__ (Magic constant)
                    * for recursive call
                    */
                    return array_map(__FUNCTION__, $d);
        } else {
            // Return array
                    return $d;
        }
    }

    public function MDWS_Login_Check($AccessCode, $VerifyCode)
    {
        //set variables
            $sitelist = $_SESSION['sitelist'];
        $AC = $_SESSION['AC'];
        $VC = $_SESSION['VC'];
        $_SESSION['MDWS_Status'] = '';
        $_SESSION['MDWS_Type'] = '';
        $_SESSION['MDWS_Msg'] = '';
        $_SESSION['MDWS_Suggestion'] = '';

        try {
            $client = new SoapClient("http://".$_SESSION['mdws']."/EmrSvc.asmx?WSDL");
            if (isset($client->fault)) {
                $this->MDWsCrashReport($client, "SoapClient", false);

                return;
            }

            $addDataSource = $client->addDataSource(array('id' => '355', 'name' => 'vaphsdb04', 'datasource' => '54.243.40.32', 'port' => '9210', 'modality' => 'HIS', 'protocol' => 'VISTA', 'region' => '355'));

            if (isset($connect->connectResult->fault)) {
                $this->MDWsCrashReport($connect->connectResult, "Connect", false);

                return;
            }
            $connect = $client->connect(array('sitelist' => $sitelist));
            if (isset($connect->connectResult->fault)) {
                $this->MDWsCrashReport($connect->connectResult, "Connect", false);

                return;
            }

            $login = $client->login(array('username' => $AC, 'pwd' => $VC, 'context' => ''));
            if (isset($login->loginResult->fault)) {
                $this->MDWsCrashReport($login->loginResult, "Login", false);

                return;
            }

            if (isset($client->fault)) {
                $this->MDWsCrashReport($client, "Client", false);

                return;
            }

            return $client;
        } catch (Exception $e) {
            // echo ("Error: $e->getMessage()");
        }

        return;
    }
}
