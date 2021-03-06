<?php
    /**
     * @property Patient $Patient
     *
     */
function PoCD_Cmp($a, $b) {
//    error_log("First = " . json_encode($a) . "; Secondt = " . json_encode($b));
    return strcmp($a["SortKey"], $b["SortKey"]);
}

    class PatientController extends Controller {
        
        function checkForErrors( $errorMsg, $retVal ) {
            $ErrorCode = "";
            $this->set('frameworkErrCodes', $ErrorCode);
            if (null != $retVal && array_key_exists('error', $retVal)) {
                if (is_string($retVal['error'])) {
                    $errorMsg .= " " . $retVal['error'];
                }
                else {
                    foreach ($retVal['error'] as $error) {
                        $errorMsg .= "SQLSTATE: " . $error[ 'SQLSTATE' ] . " code: " . $error[ 'code' ] . " message: " . $error[ 'message' ];
                    }
                }
                return true;
            }
            return false;
        }

        function MedicationSanityCheck( ) {
            // Get all templates from the Master Template
            $query = "select * from Master_Template";
            // Template_ID - should exist in all tables
            // Regimen_ID
            // Cancer_ID
            // Location_ID
            // Cycle_Time_Frame_ID
            // Emotegenic_ID
            // Created_By
            // Patient_ID
            
            
            // for each template walk through all the therapies and look into the Regimen and Medication_Hydration and MH_Infusion tables for squirly data
            // When found produce a list
        }
        
        
        
        function LabInfoResults( $patientId = NULL ) {
            $jsonRecord = array( );
            
            if ( $patientId != NULL ) {
                
                $records = $this->Patient->getLabInfoForPatient( $patientId );
                
                if ( $this->checkForErrors( 'Get Patient Lab Info Failed. ', $records ) ) {
                    $jsonRecord[ 'success' ] = 'false';
                    $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                    $this->set( 'jsonRecord', $jsonRecord );
                    return;
                }
                
                $jsonRecord[ 'success' ] = 'true';
                $jsonRecord[ 'total' ]   = count( $records );
                
                $labInfoResults = array( );
                
                foreach ( $records as $record ) {
                    
                    $results = $this->Patient->getLabInfoResults( $record[ 'ID' ] );
                    // $modResults = array();
                    
                    // MWB - 5/14/2012 - There's only ever one element in the
                    // results array returned from MDWS (why make it an array
                    // then???)
                    if ( isset( $results[ 0 ] ) ) {
                        $result = $results[ 0 ];
                    } else {
                        $result = $results;
                    }
                    if ( '0' == $result[ 'outOfRange' ] ) {
                        $result[ 'outOfRange' ] = false;
                    } else {
                        $result[ 'outOfRange' ] = true;
                    }
                    
                    $record[ 'ResultID' ]    = $result[ 'ID' ];
                    $record[ 'name' ]        = $result[ 'name' ];
                    $record[ 'units' ]       = $result[ 'units' ];
                    $record[ 'result' ]      = $result[ 'result' ];
                    $record[ 'mdwsId' ]      = $result[ 'mdwsId' ];
                    $record[ 'acceptRange' ] = $result[ 'acceptRange' ];
                    $record[ 'site' ]        = $result[ 'site' ];
                    $record[ 'outOfRange' ]  = $result[ 'outOfRange' ];
                    
                    /**
                     * ******** - MWB - 5/14/2012 Original code
                     * foreach($results as $result){
                     * if('0' == $result['outOfRange']){
                     * $result['outOfRange'] = false;
                     * }else {
                     * $result['outOfRange'] = true;
                     * }
                     * }
                     *
                     * array_push($modResults, $result);
                     * $record['results'] = $modResults;
                     * **************
                     */
                    
                    array_push( $labInfoResults, $record );
                }
                
                $jsonRecord[ 'records' ] = $labInfoResults;
                
                $this->set( 'jsonRecord', $jsonRecord );
            } else {
                $jsonRecord[ 'success' ] = 'false';
                $jsonRecord[ 'msg' ]     = 'No Patient ID provided.';
                $this->set( 'jsonRecord', $jsonRecord );
            }
        }
        
        function History( $id = NULL ) {
            if ( $id != NULL ) {
                $this->set( 'history', $this->Patient->selectHistory( $id ) );
                $this->set( 'frameworkErr', 'Removed several columns from Patient History so the query has been removed.' );
            } else {
                $this->set( 'history', null );
                $this->set( 'frameworkErr', 'No Patient ID provided. - 123' );
            }
        }
        
        function Template( $id = NULL ) {
            if ( $id != NULL ) {
                $patientTemplate = $this->Patient->getPriorPatientTemplates( $id );
                if ( $this->checkForErrors( 'Get Patient Template Failed. ', $patientTemplate ) ) {
                    return;
                }
                $this->set( 'patientTemplate', $patientTemplate );
                $this->set( 'frameworkErr', null );
            } else {
                $this->set( 'frameworkErr', 'No Patient ID provided.' );
            }
        }
        
        /* Get all templates assigned to a patient (past and current) */
        function Templates( $Patient_ID = NULL ) {
            $jsonRecord = array( );
            
            if ( NULL === $Patient_ID ) {
                $jsonRecord[ 'success' ] = 'false';
                $jsonRecord[ 'msg' ]     = 'No Patient ID provided.';
                $this->set( 'jsonRecord', $jsonRecord );
                return;
            }
            $details = $this->Patient->getCurrentAndHistoricalPatientTemplates( $Patient_ID );
            if ( $this->checkForErrors( 'Get Patient Details Failed. ', $details ) ) {
                $jsonRecord[ 'success' ] = 'false';
                $jsonRecord[ 'msg' ]     = 'Get Patient Details Failed.';
                $this->set( 'jsonRecord', $jsonRecord );
                return;
            }
            $currentTemplate     = array( );
            $historicalTemplates = array( );
            foreach ( $details as $detail ) {
                $current = strtotime( $detail[ "DateEnded" ] ) > strtotime( date( "m/d/Y" ) );
                if ( "" === $detail[ "DateEndedActual" ] && $current ) {
                    $currentTemplate[ ] = $detail;
                } else {
                    $historicalTemplates[ ] = $detail;
                }
            }
            $records                 = array( );
            $records[ 'current' ]    = $currentTemplate;
            $records[ 'historical' ] = $historicalTemplates;
            
            
            $jsonRecord[ 'success' ] = 'true';
            $jsonRecord[ 'total' ]   = count( $details );
            $jsonRecord[ 'records' ] = $records;
            $this->set( 'jsonRecord', $jsonRecord );
        }
        
        
        
        
        
        
        function PrintOrders( $patientID ) {
            $hasErrors = false;
            
            if ( $patientID != NULL ) {
                // Call the "selectByPatientId" function of the Patient model to retrieve all the basic Patient Info
                $patientData = $this->Patient->selectByPatientId( $patientID );
                if ( $this->checkForErrors( 'Get Patient Details Failed. ', $patientData ) ) {
                    $this->set( 'templatedata', null );
                    $hasErrors = true;
                    return;
                }
                $this->set( 'PatientInfo', $patientData[ 0 ] );
                
                $patientAllergies = $this->Allergies( $patientID );
                if ( $this->checkForErrors( 'Get Patient Allergies Failed. ', $patientAllergies ) ) {
                    return;
                }
                
                $this->set( 'patientAllergies', $patientAllergies );
                $this->set( 'frameworkErr', null );
                
                
                $patientTemplate = $this->Patient->getPriorPatientTemplates( $patientID );
                if ( $this->checkForErrors( 'Get Patient Template Failed. ', $patientTemplate ) ) {
                    return;
                }
                
                $this->set( 'patientTemplate', $patientTemplate );
                $this->set( 'frameworkErr', null );
                
                // Function is also used by the OEM controller function to retrieve all the current OEM Data
                $this->genOEMData( $patientID );
                $PatientDetails = $this->Patient->getPatientDetailInfo( $patientID );
                if ( $this->checkForErrors( 'Get Patient Details Failed. ', $PatientDetails ) ) {
                    $this->set( 'templatedata', null );
                    $hasErrors = true;
                    return;
                }
                
                if ( !empty( $PatientDetails[ 0 ] ) ) {
                    $detail = $this->TreatmentStatus( $PatientDetails[ 0 ] );
                    if ( $detail[ "TreatmentStatus" ] == 'Ended' ) {
                        $detail = null;
                    }
                    $patientDetailMap[ $patientID ] = $detail;
                } else {
                    $patientDetailMap[ $patientID ] = $PatientDetails;
                }
                $this->set( 'PatientDetailMap', $patientDetailMap );
            } else {
                $this->set( 'frameworkErr', 'No Patient ID provided.' );
            }
        }
        
        
        
        /**
         * savePatientTemplate action
         */
        public function savePatientTemplate( ) {
            $formData = json_decode( file_get_contents( 'php://input' ) );
            $this->Patient->beginTransaction();
            $returnVal = $this->Patient->savePatientTemplate( $formData );
            if ( $this->checkForErrors( 'Apply Patient Template Failed. ', $returnVal ) ) {
                $this->set( 'patientTemplateId', null );
                $this->Patient->rollbackTransaction();
                return;
            }
            $this->Patient->endTransaction();
            $AssignedByUser = $_SESSION["AccessCode"];
            if (!$_SESSION[ 'Preceptee' ]) {
                $ApprovedByUser = $_SESSION["AccessCode"];
                $this->createOEMRecords( $formData );
            }
            $this->set( 'patientTemplateId', $returnVal[ 0 ][ 'id' ] );
            $this->set( 'frameworkErr', null );
        }
        
        /**
         * 
         * @param array $detail
         * @return array
         */
        public function TreatmentStatus( $detail ) {
            $startDate     = new DateTime( $detail[ 'TreatmentStart' ] );
            $endDate       = new DateTime( $detail[ 'TreatmentEnd' ] );
            $actualEndDate = ( !empty( $detail[ 'TreatmentEndActual' ] ) ) ? new DateTime( $detail[ 'TreatmentEndActual' ] ) : null;
            $today         = new DateTime( "now" );
            
            if ( ( !empty( $actualEndDate ) && $today > $actualEndDate ) || $today > $endDate ) {
                $status = "Ended";
            } else if ( $today < $startDate ) {
                $status = "Applied";
            } else {
                $admindate = $this->Patient->isAdminDate( $detail[ 'TemplateID' ], $today->format( 'm/d/Y' ) );
                if ( count( $admindate ) > 0 ) {
                    $status = "On-Going - Admin Day";
                } else {
                    $status = "On-Going - Rest Day";
                }
            }
            $detail[ "TreatmentStatus" ] = $status;
            return $detail;
        }
        
        function savePatient( ) {
            $form_data = json_decode( file_get_contents( 'php://input' ) );
            
            $this->Patient->beginTransaction();
            
            $retVal = $this->Patient->savePatient( $form_data );
            
            if ( $this->checkForErrors( 'Insert into Patient_History failed.', $retVal ) ) {
                $this->Patient->rollbackTransaction();
                return;
            }
            
            $this->set( 'patientId', $retVal );
            $this->set( 'frameworkErr', null );
            
            $this->Patient->endTransaction();
        }
        
        
        
        
        function viewall( $id = NULL ) {
// error_log("viewall Entry Point");
            if ( $id == NULL ) {
                $retVal = $this->Patient->selectAll();
            } else {
                $retVal = $this->Patient->selectAll( $id );
            }
            if ( $this->checkForErrors( 'Get Patients Failed. ', $retVal ) ) {
                $this->set( 'templatedata', null );
                return;
            }
            
            $patients         = $retVal;
            $patientDetailMap = array( );
            $measurements     = array( );
            $modPatients      = array( );
// error_log("Patients Count - " . count($patients));
            foreach ( $patients as $patient ) {
                if ( ( NULL === $id ) || ( NULL !== $id && $patient[ 'ID' ] == $id ) ) {
                    $lookup = new LookUp();
                    
                    
                    $amputations = $lookup->getLookupDescByNameAndType( $patient[ 'ID' ], '30' );
                    if ( $this->checkForErrors( 'Get Patient Amputations Failed. ', $amputations ) ) {
                        $this->set( 'templatedata', null );
                        return;
                    }
                    $tmpAmputations = array( );
                    foreach ( $amputations as $amputation ) {
                        array_push( $tmpAmputations, $amputation );
                    }
                    $patient[ 'amputations' ] = $tmpAmputations;
                    array_push( $modPatients, $patient );
                    
                    
                    $retVal = $this->Patient->getPatientDetailInfo( $patient[ 'ID' ] );
                    
                    if ( $this->checkForErrors( 'Get Patient Details Failed. ', $retVal ) ) {
                        $this->set( 'templatedata', null );
                        return;
                    }
                    $patientBSA = $this->_getBSA( $patient[ 'ID' ] );
                    
                    $details = $retVal;
// error_log("Details Count - " . count($details));
                    if ( count( $details ) > 0 ) {
                        foreach ( $details as $d ) {
                            $detail = $this->TreatmentStatus( $d );
                            if ( isset( $patientBSA ) && count( $patientBSA ) > 0 ) {
                                $a                         = $patientBSA[ 0 ][ "WeightFormula" ];
                                $b                         = $patientBSA[ 0 ][ "BSAFormula" ];
                                $detail[ "WeightFormula" ] = $a;
                                $detail[ "BSAFormula" ]    = $b;
                            }
                            if ( $detail[ "TreatmentStatus" ] != 'Ended' ) {
                                $patientDetailMap[ $patient[ 'ID' ] ] = $detail;
                            }

// error_log("viewAll - The Details - " . json_encode($detail));
                        }
                    } else {
// error_log("No Details, so build one");
                        $detail                          = array( );
                        $detail[ "TemplateName" ]        = "";
                        $detail[ "TemplateDescription" ] = "";
                        $detail[ "TemplateID" ]          = "";
                        $detail[ "TreatmentStart" ]      = "";
                        $detail[ "TreatmentEnd" ]        = "";
                        $detail[ "TreatmentStatus" ]     = "";
                        $detail[ "ConcurRadTherapy" ]    = "";
                        $detail[ "AssignedByUser" ]      = "";
                        $detail[ "ApprovedByUser" ]      = "";
                        $detail[ "Goal" ]                = "";
                        $detail[ "ClinicalTrial" ]       = "";
                        $detail[ "PAT_ID" ]              = "";
                        $detail[ "PerformanceStatus" ]   = "";
                        
                        if ( isset( $patientBSA ) && count( $patientBSA ) > 0 ) {
// error_log("Got BSA Data from BSA_Info Table");
                            $a                         = $patientBSA[ 0 ][ "WeightFormula" ];
                            $b                         = $patientBSA[ 0 ][ "BSAFormula" ];
                            $detail[ "WeightFormula" ] = $a;
                            $detail[ "BSAFormula" ]    = $b;
                        }
                        $patientDetailMap[ $patient[ 'ID' ] ] = $detail;
                    }
                }
            }
            
            $this->set( 'patients', $modPatients );
            $this->set( 'templatedetails', $patientDetailMap );
        }
        
        /**
         *
         * @param array $formData            
         * @param array $preHydrationRecord            
         * @return string
         *
         * @todo Move this into a model
         */
        private function _insertOrderStatus( $formData, $preHydrationRecord, $GUID, $infusionMap, $templateIdMT ) {
            //echo "|||formData||| ";
            //var_dump($formData);
            //echo "|||preHydrationRecord||| ";
            //var_dump($preHydrationRecord);
            //echo "|||infusionMap||| ";
            //var_dump($infusionMap);
            //echo "|||templateIdMT||| ";
            //var_dump($templateIdMT);
            
// error_log("_insertOrderStatus infusionMap - " . $this->varDumpToString($infusionMap));
            
            $iterator = new RecursiveIteratorIterator( new RecursiveArrayIterator( $templateIdMT ) );
            foreach ( $iterator as $key => $value ) {
                $templateIdMT = $value;
                $queryTIDMT   = "SELECT Template_ID
					,Admin_Day
					,Admin_Date
					,Patient_ID
					,Date_Entered
				FROM Master_Template
				WHERE Template_ID = '$templateIdMT'";
                $GetMT        = $this->Patient->query( $queryTIDMT );
                foreach ( $GetMT as $row ) {
                    $Admin_Date2 = $row[ 'Admin_Date' ];
                }
                
            }
            
            $Admin_Date = $Admin_Date2->format( 'Ymd' );
            
            $templateId        = $formData->TemplateID;
            $DateApplied       = $formData->DateApplied;
            $DateStarted       = $formData->DateStarted;
            $DateEnded         = $formData->DateEnded;
            $DateEndedActual   = $formData->DateEndedActual;
            $patientid         = $formData->PatientID;
            $Goal              = $formData->Goal;
            $ClinicalTrial     = $formData->ClinicalTrial;
            $PerformanceStatus = $formData->PerformanceStatus;
            $WeightFormula     = $formData->WeightFormula;
            $BSAFormula        = $formData->BSAFormula;
            $drugName          = $preHydrationRecord[ 'drug' ];
            $phid              = $preHydrationRecord[ 'id' ];
            
            $AdminDay = "";
            if ( array_key_exists( 'adminDay', $preHydrationRecord ) ) {
                $AdminDay = $preHydrationRecord[ 'adminDay' ];
            }
            $Sequence = "";
            if ( array_key_exists( 'Sequence', $preHydrationRecord ) ) {
                $Sequence = $preHydrationRecord[ 'Sequence' ];
            }
            
            $iSequence = "";
            if ( array_key_exists( 'sequence', $preHydrationRecord ) ) {
                $iSequence = $preHydrationRecord[ 'sequence' ];
            }
            if ( empty( $Sequence ) ) {
                $pSequence = $iSequence;
            } else {
                $pSequence = $Sequence;
            }
            
            $amt     = "";
            $regdose = "";
            if ( array_key_exists( 'regdose', $preHydrationRecord ) ) {
                $amt = $preHydrationRecord[ 'regdose' ];
            }

            if ($infusionMap) {
                try {
                    $iamt = $infusionMap[ $phid ][ 0 ]->data[ 'amt' ];
                }
                catch ( Exception $e ) {
                }
            }
            if ( empty( $amt ) ) {
                $pamt = $iamt;
            } else {
                $pamt = $amt;
            }
            
            $route = "";
            if ( array_key_exists( 'route', $preHydrationRecord ) ) {
                $route = $preHydrationRecord[ 'route' ];
            }
            if ($infusionMap) {
                try {
                    $iroute = $infusionMap[ $phid ][ 0 ]->data[ 'type' ];
                }
                catch ( Exception $e ) {
                }
            }
            if ( empty( $route ) ) {
                $proute = $iroute;
            } else {
                $proute = $route;
            }
            
            $unit = "";
            if ( array_key_exists( 'regdoseunit', $preHydrationRecord ) ) {
                $unit = $preHydrationRecord[ 'regdoseunit' ];
            }
            if ($infusionMap) {
                try {
                    $iunit = $infusionMap[ $phid ][ 0 ]->data[ 'unit' ];
                }
                catch ( Exception $e ) {
                }
            }
            if ( empty( $route ) ) {
                $punit = $iunit;
            } else {
                $punit = $unit;
            }
            
            $flowRate = "";
            if ( array_key_exists( 'flowRate', $preHydrationRecord ) ) {
                $flowRate = $preHydrationRecord[ 'flowRate' ];
            }
            if ($infusionMap) {
                try {
                    $iflowRate = $infusionMap[ $phid ][ 0 ]->data[ 'flowRate' ];
                }
                catch ( Exception $e ) {
                }
            }
            if ( empty( $flowRate ) ) {
                $pflowRate = $iflowRate;
            } else {
                $pflowRate = $flowRate;
            }
            $flvol = "";
            if ( array_key_exists( 'flvol', $preHydrationRecord ) ) {
                $flvol = $preHydrationRecord[ 'flvol' ];
            }
            if ($infusionMap) {
                $iflvol = $infusionMap[ $phid ][ 0 ]->data[ 'fluidVol' ];
            }
            if ( empty( $flvol ) ) {
                $pflvol = $iflvol;
            } else {
                $pflvol = $flvol;
            }
            
            $id = "";
            if ( array_key_exists( 'id', $preHydrationRecord ) ) {
                $DrugID = $preHydrationRecord[ 'id' ];
            }
            $regdosepct = "";
            if ( array_key_exists( 'regdosepct', $preHydrationRecord ) ) {
                $RegDosePct = $preHydrationRecord[ 'regdosepct' ];
            }
            $regreason = "";
            if ( array_key_exists( 'regreason', $preHydrationRecord ) ) {
                $RegReason = $preHydrationRecord[ 'regreason' ];
            }
            $patientdose = "";
            if ( array_key_exists( 'patientdose', $preHydrationRecord ) ) {
                $PatientDose = $preHydrationRecord[ 'patientdose' ];
            }
            $patientdoseunit = "";
            if ( array_key_exists( 'patientdoseunit', $preHydrationRecord ) ) {
                $PatientDoseUnit = $preHydrationRecord[ 'patientdoseunit' ];
            }
            $flunit = "";
            if ( array_key_exists( 'flunit', $preHydrationRecord ) ) {
                $flunit = $preHydrationRecord[ 'flunit' ];
            }
            $infusion = "";
            if ( array_key_exists( 'infusion', $preHydrationRecord ) ) {
                $infusion = $preHydrationRecord[ 'infusion' ];
            }
            $bsaDose = "";
            if ( array_key_exists( 'bsaDose', $preHydrationRecord ) ) {
                $bsaDose = $preHydrationRecord[ 'bsaDose' ];
            }
            $Reason = "";
            if ( array_key_exists( 'Reason', $preHydrationRecord ) ) {
                $Reason = $preHydrationRecord[ 'Reason' ];
            }
            $orderType   = ( empty( $preHydrationRecord[ 'type' ] ) ) ? 'Therapy' : $preHydrationRecord[ 'type' ];
            $orderStatus = "Ordered";
            $Notes       = "Line 552, PatientController";
            




$query = "select PAT_ID FROM Patient_Assigned_Templates where Patient_ID = '$patientid' and Template_ID = '$templateId' and Date_Ended_Actual is null";
$this->Patient->query( $query );

// error_log("PatientController.Getting PAT_ID to insert into Order_Status table");
// error_log($query);
            $result = $this->Patient->query($query);
            if (! empty($result[0]['PAT_ID'])) {
                $PAT_ID = $result[0]['PAT_ID'];
// error_log("Got PAT_ID = $PAT_ID");
            }
//          else {
// error_log("No PAT_ID - ERROR - ERROR - ERROR - ERROR - ERROR - ERROR - ERROR");
//          }














            
            $query = "
            INSERT INTO Order_Status (
                Template_ID, 
                Template_IDMT, 
                Order_Status,
				Order_ID,				
                Drug_Name,
				Drug_ID,
                Order_Type, 
                Patient_ID,
                PAT_ID,
				Notes,
				Amt,
				iAmt,
				Sequence,
				Route,
				AdminDay,
				Unit,
				RegDosePct,
				FlowRate,
				flvol,
				RegReason,
				PatientDoseUnit,
				PatientDose,
				flunit,
				infusion,
				bsaDose,
				Reason,
				Admin_Date,
				DateApplied,
				DateStarted,
				DateEnded,
				DateEndedActual,
				Goal,
				ClinicalTrial,
				PerformanceStatus,
				WeightFormula,
				BSAFormula
            ) VALUES (
                '$templateId',
                '$templateIdMT',
                '$orderStatus',
                '$GUID',
                '$drugName',
                '$DrugID',
                '$orderType',
                '$patientid',
                '$PAT_ID',
                '$Notes',
                '$pamt',
                '$iamt',
				'$pSequence',
                '$proute',
                '$AdminDay',
				'$punit',
				'$RegDosePct',
				'$pflowRate',
				'$pflvol',
				'$RegReason',
				'$PatientDoseUnit',
				'$PatientDose',
				'$flunit',
				'$infusion',
				'$bsaDose',
				'$Reason',
				'$Admin_Date',
				'$DateApplied',
				'$DateStarted',
				'$DateEnded',
				'$DateEndedActual',
				'$Goal',
				'$ClinicalTrial',
				'$PerformanceStatus',
				'$WeightFormula',
				'$BSAFormula'
            )
         ";
// error_log("PatientController._insertOrderStatus - Query = $query");
            $this->Patient->query( $query );
            return $GUID;
        }
        
        /**
         *
         * @param unknown_type $therapies            
         * @param unknown_type $infusionMap            
         * @param unknown_type $dateStarted            
         * @param unknown_type $template            
         * @param unknown_type $cycle            
         * @param unknown_type $patientId            
         * @param stdClass $formData            
         *
         * @todo Move this into a model
         */
        private function _insertTherapyOrders( $therapies, $infusionMap, $dateStarted, $template, $cycle, $patientId, $formData ) {
// error_log( "_insertTherapyOrders - Entry Point - " . count( $therapies ) );
// error_log( "_insertTherapyOrders - " . json_encode($infusionMap));

            foreach ( $therapies as $therapy ) {
                $adminDays = $therapy[ "adminDay" ];
                
                $dashPos  = strpos( $adminDays, '-' );
                $commaPos = strpos( $adminDays, ',' );
                
                $commaValArray      = array( );
                $dashValArray       = array( );
                $finalCommaValArray = array( );
                $finalDashValArray  = array( );
                $daysArray          = array( );
                
                if ( false !== $dashPos && false !== $commaPos ) {
                    
                    $commaValArray = explode( ',', $adminDays );
                    for ( $index = 0; $index < count( $commaValArray ); $index++ ) {
                        
                        $tmpArray = explode( '-', $commaValArray[ $index ] );
                        
                        if ( count( $tmpArray ) > 1 ) {
                            $pos               = count( $daysArray );
                            $daysArray[ $pos ] = $index;
                            
                            $actualNextVal = (int) $tmpArray[ 1 ];
                            $currentVal    = (int) $tmpArray[ 0 ];
                            
                            while ( $currentVal <= $actualNextVal ) {
                                $pos                       = count( $finalDashValArray );
                                $finalDashValArray[ $pos ] = $currentVal;
                                $currentVal++;
                            }
                        }
                    }
                    
                    foreach ( $daysArray as $day ) {
                        unset( $commaValArray[ $day ] );
                    }
                    
                    $finalCommaValArray = $commaValArray;
                } else if ( false === $dashPos && false !== $commaPos ) {
                    
                    $commaValArray      = explode( ',', $adminDays );
                    $finalCommaValArray = $commaValArray;
                } else if ( false !== $dashPos && false === $commaPos ) {
                    
                    $dashValArray = explode( '-', $adminDays );
                    
                    $actualNextVal = (int) $dashValArray[ 1 ];
                    $currentVal    = (int) $dashValArray[ 0 ];
                    
                    while ( $currentVal <= $actualNextVal ) {
                        $pos                       = count( $finalDashValArray );
                        $finalDashValArray[ $pos ] = $currentVal;
                        $currentVal++;
                    }
                } else {
                    $finalCommaValArray[ 0 ] = $adminDays;
                }
                
                if ( count( $finalCommaValArray ) > 0 || count( $finalDashValArray ) > 0 ) {
                    $daysArray = array_merge( $finalCommaValArray, $finalDashValArray );
                    $daysArray = array_unique( $daysArray );
                    sort( $daysArray );
                    $startDate = new DateTime( $dateStarted );
// error_log( "_insertTherapyOrders - Looping DaysArray - " . count( $daysArray ) );
                    for ( $index = 0; $index < count( $daysArray ); $index++ ) {
                        $daysDiff = 0;
                        if ( $index > 0 ) {
                            $daysDiff = ( $daysArray[ $index ] - $daysArray[ $index - 1 ] );
                        } else if ( $daysArray[ $index ] > 1 ) {
                            $daysDiff = ( $daysArray[ $index ] ) - 1;
                        }
                        date_add( $startDate, new DateInterval( 'P' . $daysDiff . 'D' ) );
// error_log( "_insertTherapyOrders - _insertTemplate" );
                        $templateId = $this->_insertTemplate( $template, $daysArray[ $index ], $startDate->format( 'Y-m-d' ), $cycle, $patientId );
                        if ( empty( $templateId ) ) {
                            return;
                        }
                        $query   = "SELECT NEWID()";
                        $GUID    = $this->Patient->query( $query );
                        $GUID    = $GUID[ 0 ][ "" ];
                        $orderId = $this->_insertOrderStatus( $formData, $therapy, $GUID, $infusionMap, $templateId );
                        if ( !empty( $infusionMap ) ) {
                            $this->_insertHydrations( $therapy, $infusionMap, $templateId[ 0 ][ 'lookupid' ], $orderId );
                        } else {
                            $this->_insertRegimens( $therapy, $templateId[ 0 ][ 'lookupid' ], $orderId );
                        }
                    }
// error_log( "_insertTherapyOrders - Looping DaysArray END LOOP" );
                }
            }
// error_log( "_insertTherapyOrders - EXIT Point - " );
        }
        
        /**
         *
         * @param array $regimen            
         * @param string $templateId            
         * @param string $orderId            
         *
         * @todo Move into a model
         */
        private function _insertRegimens( $regimen, $templateId, $orderId ) {
// error_log( "------------------------------------------------ Patient Controller - _insertRegimens() ------------------------------------------");
// error_log( "$regimen, $templateId, $orderId");




            $lookup = new LookUp();

            $data               = new stdClass();
            $data->drugid       = $regimen[ "drugid" ];
            $data->Amt          = $regimen[ "regdose" ];
            $data->Units        = $regimen[ "regdoseunit" ];
            $data->Route        = $regimen[ "route" ];
            $data->Day          = $regimen[ "adminDay" ];
            $data->InfusionTime = $regimen[ "infusion" ];
            $data->FluidVol     = $regimen[ "flvol" ];
            $data->FlowRate     = $regimen[ "flowRate" ];
            $data->Instructions = $regimen[ "instructions" ];
            $data->Status       = $regimen[ "Status" ];
            $data->Sequence     = $regimen[ "sequence" ];
            $data->AdminTime    = $regimen[ "adminTime" ];
            $data->FluidType    = $regimen[ "fluidType" ];
            
            $regimens            = array( );
            $regimens[ 0 ]       = new stdClass();
            $regimens[ 0 ]->data = $data;


            $retVal = $lookup->getLookupInfoById($regimen[ "drugid" ]);
            $DrugName = $retVal[0]["Name"];
            $DrugIEN = $retVal[0]["Lookup_Type_ID"];
            $DrugInfo = str_replace(">",")",str_replace("<","(",$DrugName));
            $DrugInfo1 = "$DrugInfo : $DrugIEN";
            $data->Drug = $DrugInfo1;
// error_log("Patient Controller - _insertRegimens(); Drug Information : $DrugName; $DrugIEN; $DrugInfo; $DrugInfo1");
// error_log("Patient Controller - _insertRegimens(); Regimen - \n\n" . json_encode($regimens[ 0 ]));




// error_log("Patient.Controller._insertRegimens - Save Data");
// error_log(json_encode($data));


            $retVal = $lookup->saveRegimen( $regimens, $templateId, $orderId );
            if ( $this->checkForErrors( 'Insert Template Regimens Failed. ', $retVal ) ) {
                $this->Patient->rollbackTransaction();
                return;
            }
        }
        
        /**
         *
         * @param array $hydration            
         * @param array $infusionMap            
         * @param string $templateId            
         * @param string $orderId            
         *
         * @todo Move into a model
         */
        private function _insertHydrations( $hydration, $infusionMap, $templateId, $orderId ) {
            $data               = new stdClass();
            $data->drugid       = $hydration[ "drug" ];
            $data->description  = $hydration[ "description" ];
            $data->fluidVol     = $hydration[ "fluidVol" ];
            $data->flowRate     = $hydration[ "flowRate" ];
            $data->infusionTime = $hydration[ "infusionTime" ];
            $data->adminDay     = $hydration[ "adminDay" ];
            $data->sequence     = $hydration[ "Sequence" ];
            $data->adminTime    = $hydration[ "adminTime" ];
            $data->infusions    = $infusionMap[ $hydration[ 'id' ] ];
            
            $hydrations            = array( );
            $hydrations[ 0 ]       = new stdClass();
            $hydrations[ 0 ]->data = $data;

// error_log("Patient.Controller._insertHydrations - Save Data");
// error_log(json_encode($data));
            
            $lookup = new LookUp();
            $retVal = $lookup->saveHydrations( $hydrations, $hydration[ "type" ], $templateId, $orderId );
            
            if ( $this->checkForErrors( 'Insert ' . $hydration[ "type" ] . ' Therapy Failed. ', $retVal ) ) {
                $this->Patient->rollbackTransaction();
                return;
            }
        }
        
        /**
         *
         * @param unknown_type $template            
         * @param unknown_type $adminDay            
         * @param unknown_type $adminDate            
         * @param unknown_type $cycle            
         * @param unknown_type $patientId            
         * @return void Ambigous multitype:string >
         */
        private function _insertTemplate( $template, $adminDay, $adminDate, $cycle, $patientId ) {
            $data                     = new stdClass();
            $data->Disease            = $template[ 'Disease' ];
            $data->DiseaseStage       = $template[ 'DiseaseStage' ];
            $data->CycleLength        = $template[ 'length' ];
            $data->CycleLengthUnit    = $template[ 'CycleLengthUnitID' ];
            $data->ELevel             = $template[ 'emoID' ];
            $data->FNRisk             = $template[ 'fnRisk' ];
            $data->PostMHInstructions = $template[ 'postMHInstruct' ];
            $data->PreMHInstructions  = $template[ 'preMHInstruct' ];
            $data->RegimenInstruction = $template[ 'regimenInstruction' ];
            $data->CourseNumMax       = $template[ 'CourseNumMax' ];
            $data->AdminDay           = $adminDay;
            $data->AdminDate          = $adminDate;
            $data->Cycle              = $cycle;
            $data->PatientID          = $patientId;
            
            $lookup     = new LookUp();
            $templateId = $lookup->saveTemplate( $data, $template[ 'RegimenId' ] );
            
            if ( $this->checkForErrors( "Insert Master Template (in Patient Controller) Failed. (id=$templateId)", $templateId ) ) {
                $this->Patient->rollbackTransaction();
                return;
            }
            
            return $templateId;
        }
        
        /**
         *
         * @param stdClass $formData            
         *
         * @todo Seems this method should really be inside a model
         * @todo Get Hydrations, Infusions, Regimens, etc, from a model not a view
         */
        private function createOEMRecords( $formData ) {
// error_log( "---------------------------------------------------- Patient Controller - createOEMRecords - Entry ----------------------------------------------------" );
// error_log( json_encode($formData));


            $templateId = $formData->TemplateID;
            $lookup     = new LookUp();
            $templates  = $lookup->getTopLevelTemplateDataById( $templateId, '' );
            $template   = $templates[ 0 ];

// error_log( "---------------------------------------------------- Patient Controller - createOEMRecords - Pre/Post Hydrations via TemplateID = $templateId ----------------------------------------------------" );
            $this->Hydrations( 'pre', $templateId );
            $preHydrations  = $this->get( 'prehydrations' );
            $preInfusionMap = $this->get( 'preinfusions' );
            
            $this->Hydrations( 'post', $templateId );
            $postHydrations  = $this->get( 'posthydrations' );
            $postInfusionMap = $this->get( 'postinfusions' );

// error_log( "---------------------------------------------------- Patient Controller - createOEMRecords - Regimens via TemplateID = $templateId ----------------------------------------------------" );
            $this->Regimens( $templateId );
            $regimens = $this->get( 'regimens' );
// error_log( "---------------------------------------------------- Patient Controller - createOEMRecords - Regimens ----------------------------------------------------" );
// error_log( json_encode($regimens));


            $dateStarted = $formData->DateStarted;
            $patientId   = $formData->PatientID;
            
// error_log( "createOEMRecords - Cycles = " . $template[ 'CourseNumMax' ] );
            for ( $cycle = 1; $cycle <= $template[ 'CourseNumMax' ]; $cycle++ ) {
                if ( !$this->checkForErrors( 'Failed to get prehydrations', $preHydrations ) && !empty( $preHydrations ) ) {
// error_log( "createOEMRecords - InsertTherapyOrders - Pre" );
                    $this->_insertTherapyOrders( $preHydrations, $preInfusionMap, $dateStarted, $template, $cycle, $patientId, $formData );
                }
                if ( !$this->checkForErrors( 'Failed to get posthydrations', $postHydrations ) && !empty( $postHydrations ) ) {
// error_log( "createOEMRecords - InsertTherapyOrders - Post" );
                    $this->_insertTherapyOrders( $postHydrations, $postInfusionMap, $dateStarted, $template, $cycle, $patientId, $formData );
                }
                if ( !$this->checkForErrors( 'Failed to get regimens', $regimens ) && !empty( $regimens ) ) {
// error_log( "---------------------------------------------------- Patient Controller - createOEMRecords - _insertTherapyOrders() ----------------------------------------------------" );
                    $this->_insertTherapyOrders( $regimens, null, $dateStarted, $template, $cycle, $patientId, $formData );
                }
                $dateStarted = $this->_formatDate( $dateStarted, $template[ "CycleLengthUnit" ], $template[ "length" ] );
            }
        }
        
        /**
         * Formats a date according to the given time frame
         *
         * @param string $date            
         * @param string $timeFrameUnit            
         * @param string $daysDiff            
         * @return string
         */
        private function _formatDate( $date, $timeFrameUnit, $daysDiff ) {
            $startDate = new DateTime( $date );
            
            if ( 'Days' === $timeFrameUnit ) {
                date_add( $startDate, new DateInterval( 'P' . $daysDiff . 'D' ) );
            } else if ( 'Weeks' === $timeFrameUnit ) {
                $daysDiff *= 7;
                date_add( $startDate, new DateInterval( 'P' . $daysDiff . 'D' ) );
            } else if ( 'Months' === $timeFrameUnit ) {
                date_add( $startDate, new DateInterval( 'P' . $daysDiff . 'M' ) );
            } else if ( 'Years' === $timeFrameUnit ) {
                date_add( $startDate, new DateInterval( 'P' . $daysDiff . 'Y' ) );
            }
            return $startDate->format( 'Y-m-d' );
        }
        
        /**
         * $id = Patient GUID used in the Patient Assigned Templates Table
         *
         *  Get Template currently assigned to this patient
         *  Get the disease this patient has
         *  
         **/
        function genOEMData( $id ) {
            $lookup     = new LookUp();
            $templateId = $this->Patient->getTemplateIdByPatientID( $id );
            if ( $this->checkForErrors( 'Template ID not available in Patient_Assigned_Templates. ', $templateId ) ) {
                $this->set( 'masterRecord', null );
                return;
            }
            
            if ( 0 == count( $templateId ) ) {
                $this->set( 'oemsaved', null );
                $this->set( 'oemrecords', null );
                $this->set( 'masterRecord', null );
                $this->set( 'frameworkErr', null );
error_log("No Records for  - $id");
error_log(json_encode($templateId));
                return;
            }

error_log("Records for  - $id");
error_log(json_encode($templateId));
			$tID0 = $templateId[ 0 ];

			
            $masterRecord = $this->Patient->getTopLevelPatientTemplateDataById( $id, $tID0[ 'id' ] );
            
            if ( $this->checkForErrors( 'Get Top Level Template Data Failed. ', $masterRecord ) ) {
                $this->set( 'masterRecord', null );
error_log( "Get Top Level Template Data Failed." );
                return;
            }

			$mr0 = $masterRecord[ 0 ];
            $lookup                            = new LookUp();
            $mr0[ "emodetails" ] = $lookup->getEmoData( $mr0[ "emoLevel" ] );
            $mr0[ "fnrDetails" ] = $lookup->getNeutroData( $mr0[ "fnRisk" ] );

error_log("masterRecords for  - $id");
error_log(json_encode($masterRecord));
            
            // Add Disease Info record for use in PrintOrders - MWB - 12/23/2013
            $lookup  = new LookUp();
            $Disease = $lookup->selectByNameAndDesc( 'DiseaseType', $mr0[ 'Disease' ] );
            if ( $this->checkForErrors( 'Get Disease Info Failed. ', $Disease ) ) {
                $this->set( 'templatedata', null );
// error_log( "Get Disease Info Failed. $Disease" );
                return;
            }

			$mr0[ 'DiseaseRecord' ] = $Disease;
            $this->set( 'masterRecord', $masterRecord );
            
            $oemrecords = $this->Patient->getTopLevelOEMRecords( $id, $tID0[ 'id' ] );
            if ( $this->checkForErrors( 'Get Top Level OEM Data Failed. ', $oemrecords ) ) {
                $this->set( 'oemrecords', null );
// error_log( "Get Top Level OEM Data Failed. $oemrecords" );
                return;
            }
            $this->set( 'oemrecords', $oemrecords );

error_log("oemrecords for  - $id");
error_log(json_encode($oemrecords));


            $oemMap = array( );
            foreach ( $oemrecords as $oemrecord ) {
// error_log( "Patient Controller - genOEMData() - OEM Record - ");
// error_log( json_encode($oemrecord) );

// echo "ARRAY:<br>" . json_encode($oemDetails) . "<br><br><br>";
                $oemDetails          = array( );
                $oemRecordTemplateID = $oemrecord[ 'TemplateID' ];
                
                $retVal = $this->Hydrations( 'pre', $oemrecord[ 'TemplateID' ] );
                if ( $this->checkForErrors( 'Get Pre Therapy Failed. ', $retVal ) ) {
                    $this->set( 'oemrecords', null );
// error_log( "Get Pre Therapy Failed. - $retVal" );
                    return;
                }
                $oemDetails[ 'PreTherapy' ]          = $this->get( 'prehydrations' );
                $oemDetails[ 'PreTherapyInfusions' ] = $this->get( 'preorigInfusions' );
                
                $retVal = $this->Hydrations( 'post', $oemrecord[ 'TemplateID' ] );
                if ( $this->checkForErrors( 'Get Post Therapy Failed. ', $retVal ) ) {
                    $this->set( 'oemrecords', null );
// error_log("Get Post Therapy Failed. - $retVal");
                    return;
                }
                
                $oemDetails[ 'PostTherapy' ]          = $this->get( 'posthydrations' );
                $oemDetails[ 'PostTherapyInfusions' ] = $this->get( 'postorigInfusions' );
                
                $retVal = $this->Regimens( $oemrecord[ 'TemplateID' ] );
                if ( $this->checkForErrors( 'Get Therapy Failed. ', $retVal ) ) {
                    $this->set( 'oemrecords', null );
// error_log("Get Therapy Failed. - $retVal");
                    return;
                }
                
                $oemDetails[ 'Therapy' ]              = $this->get( 'regimens' );
                $oemMap[ $oemrecord[ 'TemplateID' ] ] = $oemDetails;
// error_log( "Patient Controller - genOEMData() - OEM Details - ");
// error_log( json_encode($oemDetails) );

            }
            
            $this->set( 'oemMap', $oemMap );
            // $this->set( 'oemsaved', null );
            $this->set( 'frameworkErr', null );
        }
        
        
        function getOEMData( $PatientID ) {
            $this->genOEMData( $PatientID );
            $this->buildJsonObj4Output();
            return $this->get( 'jsonRecord' );
        }
        
        
        /*********************************************************/
        function Therapy( $regimens ) {
			if ($regimens && count($regimens) > 0 ) {
				$Therapy = array( );
				foreach ( $regimens as $regimen ) {
					$TherapyRecord                   = array( );
					$status                          = $regimen[ "Status" ] ? $regimen[ "Status" ] : "";
					$reason                          = ( "Test - Communication" !== $regimen[ "Reason" ] ) ? $regimen[ "Reason" ] : "";
					$TherapyRecord[ "id" ]           = $regimen[ "id" ];
					$TherapyRecord[ "Order_ID" ]     = $regimen[ "Order_ID" ];
					$TherapyRecord[ "Order_Status" ] = $regimen[ "Order_Status" ];
					$TherapyRecord[ "Med" ]          = $regimen[ "drug" ];
					$TherapyRecord[ "Dose" ]         = $regimen[ "regdose" ];
					$TherapyRecord[ "MedID" ]        = $regimen[ "drugid" ];
					$TherapyRecord[ "DoseUnits" ]    = $regimen[ "regdoseunit" ];
					$TherapyRecord[ "AdminMethod" ]  = $regimen[ "route" ];
					$TherapyRecord[ "FluidType" ]    = $regimen[ "fluidType" ];
					$TherapyRecord[ "BSA_Dose" ]     = $regimen[ "bsaDose" ];
					$TherapyRecord[ "FluidVol" ]     = $regimen[ "flvol" ];
					$TherapyRecord[ "FlowRate" ]     = $regimen[ "flowRate" ];
					$TherapyRecord[ "Instructions" ] = $regimen[ "instructions" ];
					$TherapyRecord[ "Status" ]       = $status;
					$TherapyRecord[ "Reason" ]       = $reason;
					$TherapyRecord[ "AdminTime" ]    = $regimen[ "adminTime" ];
					$TherapyRecord[ "InfusionTime" ] = $regimen[ "infusion" ];
					$Therapy[ ]                      = $TherapyRecord;
				}
	// error_log("Patient.Controller.Therapy - Therapy Data");
	// error_log(json_encode($Therapy));

				return $Therapy;
			}
error_log("Patient.Controller.Therapy - NO Therapy Data passed");
			return null;
        }
        
        function PrePostTherapy( $hydrations, $infusions ) {
// error_log("PrePostTherapy() - Getting Hydration Status - " . json_encode($hydrations));
// error_log("---------------------------");
			if ($hydrations && count($hydrations) > 0) {
				$HydrationArray = array( );
				foreach ( $hydrations as $hydration ) {
	// error_log("PrePostTherapy() - Getting Hydration Status - " . json_encode($hydration));
	// error_log("---------------------------");
					$HydrationRecord = array( );
					$status          = $hydration[ "Status" ] ? $hydration[ "Status" ] : "";
					
					$reason = "";
					if ( isset( $hydration[ "Reason" ] ) ) {
						$reason = ( "Test - Communication" !== $hydration[ "Reason" ] ) ? $hydration[ "Reason" ] : "";
					}
					
					$HydrationRecord[ "id" ]           = $hydration[ "id" ];
					$HydrationRecord[ "Order_ID" ]     = $hydration[ "Order_ID" ];
					// $HydrationRecord["Order_Status"] = isset($hydration["Order_Status"]) ? $hydration["Order_Status"] : "";
					$HydrationRecord[ "Instructions" ] = $hydration[ "description" ];
					$HydrationRecord[ "XStatus" ]      = $status;
					$HydrationRecord[ "Order_Status" ] = $this->getOrderStatus( $hydration[ "Order_ID" ] );
					$HydrationRecord[ "Status" ]       = $this->getOrderStatus( $hydration[ "Order_ID" ] );
					
					
					
					
					$HydrationRecord[ "Reason" ]    = $reason;
					$HydrationRecord[ "Med" ]       = $hydration[ "drug" ];
					$HydrationRecord[ "MedID" ]     = $hydration[ "drugid" ];
					$HydrationRecord[ "AdminTime" ] = $hydration[ "adminTime" ];
					
					$myinfusions  = $infusions[ $hydration[ 'id' ] ];
					$numInfusions = count( $myinfusions );
					
					if ( $numInfusions == 0 ) {
						$HydrationRecord[ "Dose1" ]         = "";
						$HydrationRecord[ "DoseUnits1" ]    = "";
						$HydrationRecord[ "AdminMethod1" ]  = "";
						$HydrationRecord[ "BSA_Dose1" ]     = "";
						$HydrationRecord[ "FluidType1" ]    = "";
						$HydrationRecord[ "FluidVol1" ]     = "";
						$HydrationRecord[ "FlowRate1" ]     = "";
						$HydrationRecord[ "InfusionTime1" ] = "";
						$HydrationRecord[ "Dose2" ]         = "";
						$HydrationRecord[ "DoseUnits2" ]    = "";
						$HydrationRecord[ "AdminMethod2" ]  = "";
						$HydrationRecord[ "BSA_Dose2" ]     = "";
						$HydrationRecord[ "FluidType2" ]    = "";
						$HydrationRecord[ "FluidVol2" ]     = "";
						$HydrationRecord[ "FlowRate2" ]     = "";
						$HydrationRecord[ "InfusionTime2" ] = "";
					} else if ( $numInfusions == 1 ) {
						$bsa_dose                           = null == $myinfusions[ 0 ][ "bsaDose" ] ? "" : $this->numberFormater( $myinfusions[ 0 ][ "bsaDose" ] );
						$HydrationRecord[ "Dose1" ]         = $this->numberFormater( $myinfusions[ 0 ][ "amt" ] );
						$HydrationRecord[ "DoseUnits1" ]    = $myinfusions[ 0 ][ "unit" ];
						$HydrationRecord[ "AdminMethod1" ]  = $myinfusions[ 0 ][ "type" ];
						$HydrationRecord[ "BSA_Dose1" ]     = $this->numberFormater( $bsa_dose );
						$HydrationRecord[ "FluidType1" ]    = $myinfusions[ 0 ][ "fluidType" ];
						$HydrationRecord[ "FluidVol1" ]     = $myinfusions[ 0 ][ "fluidVol" ];
						$HydrationRecord[ "FlowRate1" ]     = $myinfusions[ 0 ][ "flowRate" ];
						$HydrationRecord[ "InfusionTime1" ] = $myinfusions[ 0 ][ "infusionTime" ];
						$HydrationRecord[ "Dose2" ]         = "";
						$HydrationRecord[ "DoseUnits2" ]    = "";
						$HydrationRecord[ "AdminMethod2" ]  = "";
						$HydrationRecord[ "BSA_Dose2" ]     = "";
						$HydrationRecord[ "FluidType2" ]    = "";
						$HydrationRecord[ "FluidVol2" ]     = "";
						$HydrationRecord[ "FlowRate2" ]     = "";
						$HydrationRecord[ "InfusionTime2" ] = "";
					} else {
						$infusionCount = 1;
						foreach ( $myinfusions as $infusion ) {
							$bsa_dose                                        = null == $infusion[ "bsaDose" ] ? "" : $infusion[ "bsaDose" ];
							$HydrationRecord[ "Dose$infusionCount" ]         = $infusion[ "amt" ];
							$HydrationRecord[ "DoseUnits$infusionCount" ]    = $infusion[ "unit" ];
							$HydrationRecord[ "AdminMethod$infusionCount" ]  = $infusion[ "type" ];
							$HydrationRecord[ "BSA_Dose$infusionCount" ]     = $bsa_dose;
							$HydrationRecord[ "FluidType$infusionCount" ]    = $infusion[ "fluidType" ];
							$HydrationRecord[ "FluidVol$infusionCount" ]     = $infusion[ "fluidVol" ];
							$HydrationRecord[ "FlowRate$infusionCount" ]     = $infusion[ "flowRate" ];
							$HydrationRecord[ "InfusionTime$infusionCount" ] = $infusion[ "infusionTime" ];
							$infusionCount++;
						}
					}
					$HydrationArray[ ] = $HydrationRecord;
				}
				return $HydrationArray;
			}
// error_log("Patient.Controller.PrePostTherapy - HydrationArray Data");
// error_log(json_encode($HydrationArray));
// error_log("Patient.Controller.PrePostTherapy - NO Hydration Data passed");
		return null;

        }
        
        
        function CountAdminDaysPerCycle( $oemRecords ) {
            // Perform REAL calculation of the # of Admin Days in a Given cycle.
            // Previously incorrect data pulled in the echo below:
            //    "\"AdminDaysPerCycle\":\"".$masterRecord[0]['length']."\", \"OEMRecords\":[";
            // the "length" is the # of <units> per cycle (where units is days, weeks, etc)
            // What's needed is the # of Admin Days per cycle (which can be anything from 1 up to the # of days in a cycle
            $AdminDaysPerCycle = 0;
            foreach ( $oemRecords as $oemrecord ) {
                if ( 1 == $oemrecord[ "CourseNum" ] ) {
                    $AdminDaysPerCycle++;
                } else {
                    break;
                }
            }
            return $AdminDaysPerCycle;
        }
        
        function buildJsonObj4Output( ) {
            $oemrecords   = $this->get( 'oemrecords' );
            $oemsaved     = $this->get( 'oemsaved' );
            $masterRecord = $this->get( 'masterRecord' );
            $oemMap       = $this->get( 'oemMap' );


// error_log("oemrecords vvvvvvvvvvvvvvvvvvvvvvvvvvvvv");
// error_log(json_encode($oemrecords));

// error_log("oemsaved ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^");
// error_log(json_encode($oemsaved));

// error_log("masterRecord ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^");
// error_log(json_encode($masterRecord));

// error_log("oemMap ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^");
// error_log(json_encode($oemMap));
// error_log("^^^^^^^^^^^^^^^^^^^^^^^^^^^^^ DONE ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^");



            
            $jsonRecord = array( );
            if ( !is_null( $oemrecords ) && is_null( $oemsaved ) ) {
                $numeoemrecords = count( $oemrecords );
                $numtemplates   = count( $masterRecord );
                
                if ( $numeoemrecords ) {
                    $AdminDaysPerCycle       = $this->CountAdminDaysPerCycle( $oemrecords );
                    $jsonRecord[ "success" ] = true;
                    $jsonRecord[ "total" ]   = $numtemplates;
                    
                    $preRecord         = array( );
                    $preRecord[ "id" ] = $masterRecord[ 0 ][ "id" ];
                    
                    
                    $preRecord[ "FNRisk" ]                    = $masterRecord[ 0 ][ "fnRisk" ];
                    $preRecord[ "NeutropeniaRecommendation" ] = $masterRecord[ 0 ][ "fnrDetails" ];
                    $preRecord[ "ELevelID" ]                  = $masterRecord[ 0 ][ "emoID" ];
                    $preRecord[ "ELevelName" ]                = $masterRecord[ 0 ][ "emoLevel" ];
                    $preRecord[ "ELevelRecommendationASCO" ]  = $masterRecord[ 0 ][ "emodetails" ];
                    $preRecord[ "ELevelRecommendationNCCN" ]  = "Unknown";
                    
                    $preRecord[ "numCycles" ]         = $masterRecord[ 0 ][ "CourseNumMax" ];
                    $preRecord[ "Goal" ]              = $masterRecord[ 0 ][ "Goal" ];
                    $preRecord[ "ClinicalTrial" ]     = $masterRecord[ 0 ][ "ClinicalTrial" ];
                    $preRecord[ "Status" ]            = $masterRecord[ 0 ][ "Status" ];
                    $preRecord[ "PerformanceStatus" ] = $masterRecord[ 0 ][ "PerfStatus" ];
                    $preRecord[ "AdminDaysPerCycle" ] = $AdminDaysPerCycle;
                    
                    $allRecords = array( );
                    foreach ( $oemrecords as $oemrecord ) {
                        $oemDetails                      = $oemMap[ $oemrecord[ 'TemplateID' ] ];
                        $OneRecord                       = array( );
                        $OneRecord[ "id" ]               = $oemrecord[ "TemplateID" ];
                        $OneRecord[ "Cycle" ]            = $oemrecord[ "CourseNum" ];
                        $OneRecord[ "Day" ]              = $oemrecord[ "Day" ];
                        $OneRecord[ "AdminDate" ]        = $oemrecord[ "AdminDate" ];
                        $OneRecord[ "PreTherapyInstr" ]  = $oemrecord[ "PreTherapyInstr" ];
                        $OneRecord[ "TherapyInstr" ]     = $oemrecord[ "TherapyInstr" ];
                        $OneRecord[ "PostTherapyInstr" ] = $oemrecord[ "PostTherapyInstr" ];
                        $OneRecord[ "PreTherapy" ]       = $this->PrePostTherapy( $oemDetails[ "PreTherapy" ], $oemDetails[ "PreTherapyInfusions" ] );
// error_log("OneRecord vvvvvvvvvvvvvvvvvvvvvvvvvvvvv");
// error_log(json_encode($OneRecord["PreTherapy"]));
// error_log("OneRecord ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^");
                        
                        
                        
                        $OneRecord[ "Therapy" ]     = $this->Therapy( $oemDetails[ "Therapy" ] );
                        $OneRecord[ "PostTherapy" ] = $this->PrePostTherapy( $oemDetails[ "PostTherapy" ], $oemDetails[ "PostTherapyInfusions" ] );
                        
                        
// error_log("ALL RECORDS vvvvvvvvvvvvvvvvvvvvvvvvvvvvv");
// error_log(json_encode($OneRecord));
// error_log("ALL RECORDS ^^^^^^^^^^^^^^^^^^^^^^^^^^^^^");
                        $allRecords[ ] = $OneRecord;
                    }
                    $preRecord[ "OEMRecords" ] = $allRecords;
                    $jsonRecord[ "records" ]   = array(
                         $preRecord 
                    );
                } else {
                    $jsonRecord[ "success" ] = false;
                    $jsonRecord[ "msg" ]     = "No records found.";
                }
            } else if ( !is_null( $oemsaved ) ) {
                $jsonRecord[ "success" ] = true;
                $jsonRecord[ "msg" ]     = "OEM Record updated.";
                $jsonRecord[ "records" ] = array( );
            } else {
                $jsonRecord[ "success" ]      = false;
                $jsonRecord[ "msg" ]          = "No records found.";
                $jsonRecord[ "frameworkErr" ] = $this->get( 'frameworkErr' );
            }
            $this->set( 'jsonRecord', $jsonRecord );

			$this->set( 'oemrecords', null );
            $this->set( 'oemsaved', null );
            $this->set( 'masterRecord', null );
            $this->set( 'oemMap', null );

        }
        /********************************************************/
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        function OEM( $id = null ) {
            $form_data = json_decode( file_get_contents( 'php://input' ) );
            
            if ( $id != NULL ) { // This assumes command is a GET, ignores PUT/DELETE
                if ( "GET" == $_SERVER[ 'REQUEST_METHOD' ] ) {
                    $this->genOEMData( $id );
                }
            } else if ( $form_data ) {
                $this->Patient->beginTransaction();
                $this->set( 'oemrecords', null );
// error_log("PatientController.OEM - Begin Transaction");
// error_log(json_encode($form_data));

                $jsonRecord                   = array( );
                $jsonRecord[ "success" ]      = false;



				/* Update Template_Regimen (for Therapy meds) or Medication_Hydration and MH_Infusion (for Pre/Post therapy meds) */
                $retVal = $this->Patient->updateOEMRecord( $form_data );
// error_log("PatientController.OEM - UpdatedOEMRecord - (null is GOOD) ");
// error_log(json_encode($retVal));

                if ( null != $retVal && array_key_exists( 'apperror', $retVal ) ) {
                    $errorMsg = $retVal[ 'apperror' ];
                    $this->set( 'oemsaved', null );
                    $this->Patient->rollbackTransaction();
                    $jsonRecord[ "msg" ]          = $retVal["apperror"];
                    $jsonRecord[ "frameworkErr" ] = $errorMsg;
// error_log("PatientController.OEM - UpdatedOEMRecord - " . $retVal["apperror"]);
                    $this->set( 'jsonRecord', $jsonRecord );
                    $this->set( 'frameworkErr', null );
                    return;
                }

                if ( $this->checkForErrors( 'Update OEM Record Failed. ', $retVal ) ) {
                    $this->set( 'oemsaved', null );
                    $this->Patient->rollbackTransaction();
                    $jsonRecord[ "msg" ]          = "Update Records Fail.";
                    $jsonRecord[ "frameworkErr" ] = $this->get( 'frameworkErr' );
// error_log("PatientController.OEM - UpdatedOEMRecord - Update OEM Record Failed");
                    $this->set( 'jsonRecord', $jsonRecord );
                    $this->set( 'frameworkErr', null );
                    return;
                }




                if ( !empty( $form_data->Reason ) && !empty( $form_data->Order_ID ) ) {
error_log("PatientController.OEM - Workflow");
                    if ( $form_data->Reason == Workflow::REASON_CANCELLED ) {
                        $this->Patient->updateOrderStatus( $form_data->Order_ID, Orders::STATUS_CANCELLED );
                    } else {
                        $workflow = new Workflow();
                        $workflow->OEMeditWorkflow( $form_data );
                        
                        // Update order status of this order if number of steps for
                        // the given reason is greater than 1
                        $workflows = $workflow->getWorkflowsByReasonNo( $form_data->Reason );

                        if ( !empty( $workflows[ 0 ][ 'NoSteps' ] ) && $workflows[ 0 ][ 'NoSteps' ] > 1 ) {
                            $this->Patient->updateOrderStatus( $form_data->Order_ID, Orders::STATUS_INCOORDINATION );
                        }

                        // Update order status for all instances of this drug for
                        // this patient if route is 'Oral'
                        $patientIds = $this->Patient->getPatientIdByOrderId( $form_data->Order_ID );
                        if ( !empty( $form_data->InfusionMethod ) && !empty( $patientIds[ 0 ][ 'Patient_ID' ] ) && $form_data->InfusionMethod == 'Oral' ) {
                            $this->Patient->updateOrderStatusByPatientIdAndDrugName( $patientIds[ 0 ][ 'Patient_ID' ], $form_data->Med, Orders::STATUS_INCOORDINATION, $form_data->Order_ID );
                        }
                    }
error_log("PatientController.OEM - update Order Status Done");
                }




                $this->Patient->endTransaction();
                
                $this->set( 'oemsaved', '' );
                $this->set( 'frameworkErr', null );
            } else {
                $this->set( 'frameworkErr', 'No Template ID provided.' );
            }

// error_log("PatientController.OEM - buildJsonObj4Output()");
			// $this->set( 'masterRecord', null );
			// $this->set( 'oemMap', null );
            $this->buildJsonObj4Output();
        }
        
        function Regimens( $id = null ) {
            $lookup   = new LookUp();
            $regimens = $lookup->getRegimens( $id );
            if ( null != $regimens && array_key_exists( 'error', $regimens ) ) {
// error_log("Patient Controller - Regimens() - We have an error");
                return $regimens;
            }
// error_log("Patient.Controller.Regimens - SET Regimens - ");
// error_log(json_encode($regimens));
            $this->set( 'regimens', $regimens );
        }
        
        function Vitals( $id = null, $dateTaken = null ) {
            $jsonRecord              = array( );
            $jsonRecord[ "success" ] = true;
            $records                 = array( );
            $msg                     = "Disease type and stage have been saved";

            $form_data = json_decode( file_get_contents( 'php://input' ) );
            
            if ( $id != NULL ) {
                
                $records = $this->Patient->getMeasurements_v1( $id, $dateTaken );
                
                if ( $this->checkForErrors( 'Get Patient Vitals Failed. ', $records ) ) {
                    $jsonRecord[ "success" ] = false;
                    $msg                     = "Get Patient Vitals Failed. ";
                    $jsonRecord[ "msg" ]     = $msg . $this->get( "frameworkErr" );
                    $this->set( "jsonRecord", $jsonRecord );
                    return;
                }
                
                $jsonRecord = array( );
                
                $jsonRecord[ 'success' ] = true;
                $jsonRecord[ 'total' ]   = count( $records );
                
                $jsonRecord[ 'records' ] = $records;
                
                $this->set( 'jsonRecord', $jsonRecord );
                $this->set( 'frameworkErr', null );
            } else if ( $form_data ) {
                $this->Patient->beginTransaction();
                $saveVitals = $this->Patient->saveVitals( $form_data, null );
// error_log("Model call to saveVitals - " . json_encode($saveVitals));

                if ( null != $saveVitals && array_key_exists( 'apperror', $saveVitals ) ) {
                    $errorMsg                = $saveVitals[ 'apperror' ];
                    $jsonRecord[ 'success' ] = false;
                    $jsonRecord[ 'msg' ]     = $errorMsg;
                    $this->set( 'jsonRecord', $jsonRecord );
                    $this->set( 'frameworkErr', null );
                    $this->Patient->rollbackTransaction();
                    return;
                }
                
                if ( $this->checkForErrors( 'Save Patient Vitals Failed. ', $saveVitals ) ) {
                    $jsonRecord[ 'success' ] = false;
                    $jsonRecord[ 'message' ] = $this->get( 'frameworkErr' );
                    $this->set( 'jsonRecord', $jsonRecord );
                    $this->set( 'frameworkErr', null );
                    $this->Patient->rollbackTransaction();
                    return;
                }

                $jsonRecord = array( );
                $jsonRecord[ 'success' ] = true;
                $jsonRecord[ 'msg' ]     = 'Vitals Record Saved';
                $jsonRecord[ 'records' ] = '';

                $this->set( 'jsonRecord', $jsonRecord );
                $this->set( 'frameworkErr', null );
                $this->Patient->endTransaction();
            } else {
                $this->set( 'frameworkErr', 'No Patient ID provided.' );
            }
        }
        
        
        function getVitals( $PatientID = null, $dateTaken = null ) {
            $this->Vitals( $PatientID );
            return $this->get( 'jsonRecord' );
        }
        
        function Allergies( $patientId = null ) {
            $jsonRecord = array( );
            
            if ( $patientId != NULL ) {
                
                $records = $this->Patient->getAllergies( $patientId );
                
                if ( $this->checkForErrors( 'Get Patient Allergies Failed. ', $records ) ) {
                    $jsonRecord[ 'success' ] = 'false';
                    $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                    $this->set( 'jsonRecord', $jsonRecord );
                    return;
                }
                $jsonRecord[ 'success' ] = 'true';
                $jsonRecord[ 'total' ]   = count( $records );
                $jsonRecord[ 'records' ] = $records;
                $this->set( 'jsonRecord', $jsonRecord );
            }
        }
        
        function getOrderStatus( $orderID = null ) {
            if ( $orderID ) {
                $query       = "select Order_Status from Order_Status where Order_ID = '$orderID'";
                $OrderStatus = $this->Patient->query( $query );
                try {
                    return $OrderStatus[ 0 ][ "Order_Status" ];
                } catch (Exception $e) {
// error_log("PatientController.getOrderStatus query = $query");
                    return "";
                }
            }
            return "";
        }
        
        function Hydrations( $type = null, $id = null ) {
// error_log("Patient Controller - Hydrations() - $type; $id");

            $lookup = new LookUp();
            $hydrations = $lookup->getHydrations( $id, $type );
            if ( null != $hydrations && array_key_exists( 'error', $hydrations ) ) {
                return $hydrations;
            }

            $infusionMap     = array( );
            $origInfusionMap = array( );
            if ( null !== $hydrations) {
    // error_log("Hydrations() - Getting Hydration Status - " . json_encode($hydrations));
    // error_log("---------------------------");
                foreach ( $hydrations as $hydration ) {
                    $infusions = $lookup->getMHInfusions( $hydration[ 'id' ] );
                    if ( null != $infusions && array_key_exists( 'error', $infusions ) ) {
                        return $infusions;
                    }
                    $myinfusions = array( );
                    $origInfusionMap[ $hydration[ 'id' ] ] = $infusions;
                    for ( $i = 0; $i < count( $infusions ); $i++ ) {
                        $myinfusion                   = array( );
                        $myinfusion[ 'amt' ]          = $infusions[ $i ][ 'amt' ];
                        $myinfusion[ 'unit' ]         = $infusions[ $i ][ 'unit' ];
                        $myinfusion[ 'type' ]         = $infusions[ $i ][ 'type' ];
                        $myinfusion[ 'flowRate' ]     = $infusions[ $i ][ 'flowRate' ];
                        $myinfusion[ 'fluidVol' ]     = $infusions[ $i ][ 'fluidVol' ];
                        $myinfusion[ 'fluidType' ]    = $infusions[ $i ][ 'fluidType' ];
                        $myinfusion[ 'infusionTime' ] = $infusions[ $i ][ 'infusionTime' ];
                        $myinfusion[ 'Order_ID' ]     = $infusions[ $i ][ 'Order_ID' ];
                        $myinfusion[ 'Order_Status' ] = $this->getOrderStatus( $myinfusion[ 'Order_ID' ] );
                        $myinfusions[ $i ]->{'data'}  = $myinfusion;
                    }
                    $infusionMap[ $hydration[ 'id' ] ] = $myinfusions;
                }
            }
            $this->set( $type . 'hydrations', $hydrations );
            $this->set( $type . 'infusions', $infusionMap );
            $this->set( $type . 'origInfusions', $origInfusionMap );
        }
        
        // /sic's Code
        function OEM_AllOrdersToday( ) {
            $jsonRecord = array( );
            
            $records = $this->Patient->getOEMrecordsToday();
            
            if ( $this->checkForErrors( 'Get OEM Records Failed. ', $records ) ) {
                $jsonRecord[ 'success' ] = false;
                $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                $this->set( 'jsonRecord', $jsonRecord );
                return;
            }
            
            $jsonRecord[ 'success' ] = true;
            $jsonRecord[ 'total' ]   = count( $records );
            
            $jsonRecord[ 'records' ] = $records;
            
            $this->set( 'jsonRecord', $jsonRecord );
            
            // $templateIds = $this->Patient->getTemplateIds();
        }
        
        function OEM_PatientOrdersToday( ) {
            $jsonRecord = array( );
            
            $records = $this->Patient->getOEMPatientOrdersToday( $matchvalue );
            
            if ( $this->checkForErrors( 'Get OEM Patient Records for Today Failed. ', $records ) ) {
                $jsonRecord[ 'success' ] = false;
                $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                $this->set( 'jsonRecord', $jsonRecord );
                return;
            }
            
            $jsonRecord[ 'success' ] = true;
            $jsonRecord[ 'total' ]   = count( $records );
            
            $jsonRecord[ 'records' ] = $records;
            
            $this->set( 'jsonRecord', $jsonRecord );
            
            // $templateIds = $this->Patient->getTemplateIds();
        }
        
        function sendCPRSOrder( ) {
            $jsonRecord = array( );
            
            $TID     = '2C987ADB-F6A0-E111-903E-000C2935B86F';
            $records = $this->Patient->sendCPRSOrder( $TID );
            
            if ( $this->checkForErrors( 'Place CPRS Order Failed. ', $records ) ) {
                $jsonRecord[ 'success' ] = false;
                $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                $this->set( 'jsonRecord', $jsonRecord );
                return;
            }
            
            $jsonRecord[ 'success' ] = true;
            $jsonRecord[ 'total' ]   = count( $records );
            
            $jsonRecord[ 'records' ] = $records;
            
            $this->set( 'jsonRecord', $jsonRecord );
            
            // $templateIds = $this->Patient->getTemplateIds();
        }
        
        
        /**
         * $id = Record ID in specific table
         * $type = Determines which table to update ("Pre", "Post", "Therapy")
         *         Pre uses Medication_Hydration Table and ID maps to 'MH_ID'
         *         Post uses Medication_Hydration Table and ID maps to 'MH_ID'
         *         Therapy uses Template_Regimen Table and ID maps to 'Patient_Regimen_ID'
         * $status = Status to set - "Hold", "Cancel", "Clear"
         **/
        function HoldCancel( $id = null, $type = null, $status = null ) {
            $jsonRecord              = array( );
            $jsonRecord[ 'success' ] = true;
            if ( !$id ) {
                $jsonRecord[ 'success' ] = false;
                $jsonRecord[ 'msg' ]     = "Missing Record ID for Hold";
                $this->set( 'jsonRecord', $jsonRecord );
                return;
            }
            
            if ( "Pre" === $type || "Post" === $type || "Therapy" === $type ) {
                if ( "Hold" === $status || "Cancel" === $status || "Clear" === $status || null === $status ) {
                    if ( null === $status || "Clear" === $status ) {
                        $status = "";
                    }
                    if ( "PUT" == $_SERVER[ 'REQUEST_METHOD' ] ) {


// If we have Treatment Data
//    Update the status of the Regimen (Medication_Hydration OR Template_Regimen) table
//    Update the status of the Order_Status table
//


                        $table = "Medication_Hydration";
                        $key   = "MH_ID";
                        if ( "Therapy" == $type ) {
                            $table = "Template_Regimen";
                            $key   = "Patient_Regimen_ID";
                        }
                        $query         = "select * from $table where $key = '$id'";
                        $TreatmentData = $this->Patient->query( $query );
error_log("Patient Controller - HoldCancel() - ID = $id; Type = $type; Status = $status");
error_log("Patient Controller - HoldCancel() - Query = $query");


                        if ( 0 == count( $TreatmentData ) ) {
                            $jsonRecord[ 'success' ] = 'false';
                            $jsonRecord[ 'msg' ]     = "No Record Matches $id";
error_log("Patient Controller - HoldCancel() - FAILURE - No TreatmentData = " . $jsonRecord[ 'msg' ] );
                        } else {
                            if ( $this->checkForErrors( 'Set Hold/Cancel Status FAILED ', $TreatmentData ) ) {
                                $jsonRecord[ 'success' ] = 'false';
                                $jsonRecord[ 'msg' ]     = $frameworkErr;
                                $this->set( 'frameworkErr', null );
error_log("Patient Controller - HoldCancel() - FAILURE - TreatmentData Error = " . $jsonRecord[ 'msg' ] );
                            } else {

error_log("Patient Controller - HoldCancel() - TreatmentData = " . json_encode($TreatmentData));

/**
                                $TID           = $TreatmentData[ 0 ][ "Template_ID" ];
                                $Drug_ID       = $TreatmentData[ 0 ][ "Drug_ID" ];
                                $lookup        = new LookUp();
                                $Drug_Name     = $lookup->getLookupNameByIdAndType( $Drug_ID, 2 );

error_log("Patient Controller - HoldCancel() - Drug_Name = " . json_encode($Drug_Name));
                                $Drug_Name     = $Drug_Name[0]["Name"];
error_log("Patient Controller - HoldCancel() - Drug_Name = $Drug_Name");
**/

                                $query  = "update $table set Status = '$status' where $key = '$id'";
                                $retVal = $this->Patient->query( $query );
                                if ( $this->checkForErrors( 'Set Hold/Cancel Status FAILED ', $retVal ) ) {
                                    $jsonRecord[ 'success' ] = 'false';
                                    $jsonRecord[ 'msg' ]     = $frameworkErr;
                                    $this->set( 'frameworkErr', null );
                                }


                                $query2  = "SELECT Order_ID FROM $table WHERE $key = '$id'";
                                $resultq = $this->Patient->query( $query2 );
                                if ( $this->checkForErrors( 'Set Hold/Cancel Status FAILED ', $resultq ) ) {
                                    $jsonRecord[ 'success' ] = 'false';
                                    $jsonRecord[ 'msg' ]     = $frameworkErr;
                                    $this->set( 'frameworkErr', null );
                                }
                                $Order_ID = $resultq[0]['Order_ID'];

error_log("Patient Controller - HoldCancel() - query2 = $query2");
error_log("Patient Controller - HoldCancel() - Result = " . json_encode($resultq));
error_log("Patient Controller - HoldCancel() - Order_ID = $Order_ID");

                                $query3 = "UPDATE Order_Status SET Order_Status = '$status' WHERE Order_ID = '$Order_ID'";
                                $retVal = $this->Patient->query( $query3 );
                                if ( $this->checkForErrors( 'Set Hold/Cancel Status FAILED ', $retVal ) ) {
                                    $jsonRecord[ 'success' ] = 'false';
                                    $jsonRecord[ 'msg' ]     = $frameworkErr;
                                    $this->set( 'frameworkErr', null );
                                }
                            }
                        }
                    } else {
                        $jsonRecord[ 'success' ] = false;
                        $jsonRecord[ 'msg' ]     = "Invalid COMMAND - " . $_SERVER[ 'REQUEST_METHOD' ] . " expected a PUT";
                    }
                } else {
                    $jsonRecord[ 'success' ] = false;
                    $jsonRecord[ 'msg' ]     = "Invalid COMMAND - $status, expected a Hold/Cancel or Clear";
                }
            } else {
                $jsonRecord[ 'success' ] = false;
                $jsonRecord[ 'msg' ]     = "Invalid Therapy Type = $type expected Pre/Post/Therapy";
            }
            $this->set( 'jsonRecord', $jsonRecord );
        }
        
        function Amputations( $patientID = null ) {
            $jsonRecord              = array( );
            $jsonRecord[ 'success' ] = true;
            if ( !$patientID ) {
                $jsonRecord[ 'success' ] = false;
                $jsonRecord[ 'msg' ]     = "Missing Patient ID for saving Amputations";
                $this->set( 'jsonRecord', $jsonRecord );
                return;
            }
            $data      = file_get_contents( 'php://input' );
            $form_data = json_decode( $data );
            if ( !$form_data ) {
                $jsonRecord[ 'success' ] = false;
                $jsonRecord[ 'msg' ]     = "No information available to save Amputations";
                $this->set( 'jsonRecord', $jsonRecord );
                return;
            }
            
            if ( "POST" == $_SERVER[ 'REQUEST_METHOD' ] ) {
                $query         = "delete from LookUp where Lookup_Type = 30 and Name = '$patientID'";
                $TreatmentData = $this->Patient->query( $query );
// error_log("Deleting old records");
// error_log($query);
// error_log(json_encode($TreatmentData));
                
                $Amputations = $form_data->Amputations;
                $this->Patient->beginTransaction();
                foreach ( $Amputations As $Amputation ) {
                    $query  = "insert into LookUp (Lookup_Type, Name, Description) values (30, '$patientID', '$Amputation')";
                    $retVal = $this->Patient->query( $query );
                    if ( $this->checkForErrors( 'Saving Amputation Record Failed. ', $retVal ) ) {
                        $jsonRecord[ 'success' ] = 'false';
                        $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                        $this->set( 'jsonRecord', $jsonRecord );
                        return;
                    }
                }
                $this->Patient->endTransaction();
                $jsonRecord[ 'msg' ] = count( $Amputations ) . " Amputation records saved";
                $this->set( 'jsonRecord', $jsonRecord );
            } else {
                $jsonRecord[ 'success' ] = false;
                $jsonRecord[ 'msg' ]     = "Incorrect method for saving Amputations (expected a POST got a " . $_SERVER[ 'REQUEST_METHOD' ];
                $this->set( 'jsonRecord', $jsonRecord );
            }
        }
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        
        /**
         * BSA Information
         * Can be a GET request passing a PatientID
         * or a POST request passing a PatientID and a form containing the following:
         *      { "WeightFormula" : "Something", "BSAFormula": "Method of", "UserName" : "Mike Barlow" }
         *
         *  Any existing BSA info for the patentID given will be set to inactive and the date of the change
         *  This allows us to track who changes the BSA info and when it is changed.
         *  Note that if doing a post any previously active BSA info is marked as inactive based on PatientID
         *  Uses "Patient_BSA" table
         *
         *  Sample Patient ID = 'FC7C048A-19C2-E111-A7F5-000C2935B86F'
         **/
        public function _getBSA( $patientID ) {
            $query = "select * from Patient_BSA where Patient_ID = '$patientID' AND Active = 1";
            return $this->Patient->query( $query );
        }


        function BSA( $patientID = null ) {
            $jsonRecord              = array( );
            $jsonRecord[ 'success' ] = true;
            if ( !$patientID ) {
                $jsonRecord[ 'success' ] = false;
                $jsonRecord[ 'msg' ]     = "Missing Patient ID for getting/saving BSA Info";
                $this->set( 'jsonRecord', $jsonRecord );
                return;
            }
            
            $records = $this->_getBSA( $patientID );
            if ( $this->checkForErrors( "Retrieving Patient BSA Info", $records ) ) {
                $jsonRecord[ 'success' ] = false;
                $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                $this->set( 'jsonRecord', $jsonRecord );
                return;
            }
            
            if ( "GET" == $_SERVER[ 'REQUEST_METHOD' ] ) {
                $jsonRecord[ 'success' ] = true;
                $jsonRecord[ 'total' ]   = count( $records );
                $jsonRecord[ 'records' ] = $records;
                $this->set( 'jsonRecord', $jsonRecord );
                return;
            }
            
            if ( "POST" == $_SERVER[ 'REQUEST_METHOD' ] ) {
                $data      = file_get_contents( 'php://input' );
                $form_data = json_decode( $data );
                if ( !$form_data ) {
                    $jsonRecord[ 'success' ] = false;
                    $jsonRecord[ 'msg' ]     = "No information available to save BSA Info";
                    $this->set( 'jsonRecord', $jsonRecord );
                    return;
                }
                $wt   = $form_data->WeightFormula;
                $BSAFormula = $form_data->BSAFormula;
                $usr  = $form_data->UserName;
                $this->Patient->_setBSA($patientID, $wt, $BSAFormula, $usr);
            } else {
                $jsonRecord[ 'success' ] = false;
                $jsonRecord[ 'msg' ]     = "Incorrect method for saving BSA Info (expected a POST got a " . $_SERVER[ 'REQUEST_METHOD' ];
                $this->set( 'jsonRecord', $jsonRecord );
            }
        }
        
        
        
        /**
         * DischargeInstructions - This service call manages the Patient Discharge Instruction records
         * Each time a set of Discharge Instructions are generated for a particular patient a record is maintained consisting of the following:
         *  A Lookup Table (DischargeInfoLookup) containing
         *      PAT ID
         *      Template ID
         *      Date Discharge Instructions were generated.
         *      A Unique ID for this record which is used as a link into the DischargeInformation table which contains a separate record for each instruction saved in a single Discharge Instruction Form
         *
         * It makes use of the Patient Discharge Instructions Lookup to retrieve the Record ID, 
         * GET Call
         *
         * POST Call
         *
         * PUT Call
         *
         * DELETE Call
         *
         * Table Definition
         *
         *    
         *    USE [COMS_TEST_2]
         *    GO
         *    CREATE TABLE [dbo].[DischargeInstructionsLink](
         *        [DischargeID] [uniqueidentifier] NOT NULL,
         *        [PatientID] [uniqueidentifier] NOT NULL,
         *        [date] [date]  NOT NULL,
         *    ) ON [PRIMARY]
         *    GO
         *
         *
         *    USE [COMS_TEST_2]
         *    GO
         *    CREATE TABLE [dbo].[DischargeInstructions](
         *        [DischargeID] [uniqueidentifier] NOT NULL,
         *        [fieldName] [varchar](255) NOT NULL,
         *        [value] [varchar](255)
         *    ) ON [PRIMARY]
         *    GO
         *
         **/
        
        function DischargeInstructions( $PAT_ID = null, $dischargeRecordID = null ) {
            $jsonRecord              = array( );
            $jsonRecord[ 'success' ] = true;
            $query                   = "";
            $DischargeLinkTable      = "DischargeInstructionsLink";
            $DischargeInfoTable      = "DischargeInstructions";
            $GUID                    = "";
            $this->Patient->beginTransaction();
            $Date2 = date( "F j, Y" );
            
            $ErrMsg = "";
            if ( "GET" == $_SERVER[ 'REQUEST_METHOD' ] ) {
                if ( $PAT_ID ) {
                    if ( $dischargeRecordID ) {
                        $query = "select 
                        di.fieldName as fieldName,
                        di.value as value,
                        CONVERT(varchar,dil.date,101) as date
                        from $DischargeInfoTable di
                        join $DischargeLinkTable dil on dil.DischargeID = di.DischargeID
                        where di.dischargeID = '$dischargeRecordID'";
                    } else {
                        $query = "
                        SELECT DischargeID, PatientID, 
                        CONVERT(varchar,date,101) as date
                        FROM $DischargeLinkTable where PatientID = '$PAT_ID' order by date desc";
                    }
                }
// error_log("DischargeInstructions Query - $query");
                $jsonRecord[ 'msg' ] = "No records to find";
                $ErrMsg              = "Retrieving Records";
            } else if ( "POST" == $_SERVER[ 'REQUEST_METHOD' ] ) {
                $query = "SELECT NEWID()";
                $GUID  = $this->Patient->query( $query );
                $GUID  = $GUID[ 0 ][ "" ];
                
                $query  = "
            INSERT INTO $DischargeLinkTable (
                DischargeID,
                PatientID, 
                Date
            ) VALUES (
                '$GUID',
                '$PAT_ID',
                '$Date2'
            )";
                $retVal = $this->Patient->query( $query );
                if ( $this->checkForErrors( $ErrMsg, $retVal ) ) {
                    $this->Patient->rollbackTransaction();
                    $jsonRecord[ 'success' ] = false;
                    $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                    $this->set( 'jsonRecord', $jsonRecord );
                    return;
                } else {
                    foreach ( $_POST as $key => $value ) {
                        if ( "" !== $value ) {
                            $value  = $this->escapeString( $value );
                            $query  = "INSERT INTO $DischargeInfoTable (
                            DischargeID,
                            fieldName,
                            value
                        ) VALUES (
                            '$GUID',
                            '$key',
                            '$value'
                        )";
                            $retVal = $this->Patient->query( $query );
                            if ( $this->checkForErrors( $ErrMsg, $retVal ) ) {
                                $this->Patient->rollbackTransaction();
                                $jsonRecord[ 'success' ] = false;
                                $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                                $this->set( 'jsonRecord', $jsonRecord );
                                return;
                            }
                        }
                    }
                }
                $query = "";
                /* Reset query so we don't run it again in the final step */
            } else if ( "PUT" == $_SERVER[ 'REQUEST_METHOD' ] ) {
                $GUID   = $dischargeRecordID;
                $query  = "delete from $DischargeLinkTable WHERE DischargeID = '$dischargeRecordID'";
                $retVal = $this->Patient->query( $query );
                $query  = "
            INSERT INTO $DischargeLinkTable (
                DischargeID,
                PatientID, 
                Date
            ) VALUES (
                '$dischargeRecordID',
                '$PAT_ID',
                '$Date2'
            )";
                $retVal = $this->Patient->query( $query );
                if ( $this->checkForErrors( $ErrMsg, $retVal ) ) {
                    $this->Patient->rollbackTransaction();
                    $jsonRecord[ 'success' ] = false;
                    $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                    $this->set( 'jsonRecord', $jsonRecord );
                    return;
                } else {
                    $query  = "delete from $DischargeInfoTable where DischargeID = '$dischargeRecordID'";
                    $retVal = $this->Patient->query( $query );
                    if ( $this->checkForErrors( $ErrMsg, $retVal ) ) {
                        $this->Patient->rollbackTransaction();
                        $jsonRecord[ 'success' ] = false;
                        $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                        $this->set( 'jsonRecord', $jsonRecord );
                        return;
                    } else {
                        parse_str( file_get_contents( "php://input" ), $post_vars );
                        foreach ( $post_vars as $key => $value ) {
                            if ( "" !== $value ) {
                                $value  = $this->escapeString( $value );
                                $query  = "INSERT INTO $DischargeInfoTable (
                                DischargeID,
                                fieldName,
                                value
                            ) VALUES (
                                '$GUID',
                                '$key',
                                '$value'
                            )";
                                $retVal = $this->Patient->query( $query );
                                if ( $this->checkForErrors( $ErrMsg, $retVal ) ) {
                                    $this->Patient->rollbackTransaction();
                                    $jsonRecord[ 'success' ] = false;
                                    $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                                    $this->set( 'jsonRecord', $jsonRecord );
                                    return;
                                }
                            }
                        }
                    }
                }
                $query = "";
                /* Reset query so we don't run it again in the final step */
                /* For the current DischargeInfoID delete all records in the DischargeInfoTable with that ID then add anew... */
            } else if ( "DELETE" == $_SERVER[ 'REQUEST_METHOD' ] ) {
                $query = "";
                /* Reset query so we don't run it again in the final step */
            } else {
                $jsonRecord[ 'success' ] = false;
                $jsonRecord[ 'msg' ]     = "Incorrect method called for DischargeInstructions Service (expected a GET got a " . $_SERVER[ 'REQUEST_METHOD' ];
                $this->Patient->rollbackTransaction();
                $this->set( 'jsonRecord', $jsonRecord );
                return;
            }
            
            if ( "" !== $query ) {
// error_log("DischargeInstructions Query - Performing Lookup");
                $retVal = $this->Patient->query( $query );
// error_log("DischargeInstructions Query - Lookup Complete");
                if ( $this->checkForErrors( $ErrMsg, $retVal ) ) {
// error_log("DischargeInstructions Query - Lookup generated an Error");
                    $jsonRecord[ 'success' ] = false;
                    $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                    $this->Patient->rollbackTransaction();
                } else {
// error_log("DischargeInstructions Query - Lookup did NOT generate an Error");
                    $jsonRecord[ 'success' ] = 'true';
                    
                    if ( "GET" == $_SERVER[ 'REQUEST_METHOD' ] && $dischargeRecordID ) {
// error_log("DischargeInstructions Query - Getting More Data");
                        /* Get Patient Name for display on PrintOut */
                        $patInfoQuery = "SELECT 
                        PAT.PAT_ID, 
                        PAT.Patient_ID
                        /* lu1.Last_Name, 
                        lu1.First_Name */
                        FROM Patient_Assigned_Templates PAT
                        JOIN Patient lu1 ON lu1.Patient_ID = PAT.Patient_ID
                        WHERE PAT.PAT_ID = '$PAT_ID'";
// error_log("DischargeInstructions Query - Getting More Data - $patInfoQuery");
                        $patInfo = $this->Patient->query( $patInfoQuery );
                        if ( $this->checkForErrors( $ErrMsg, $patInfo ) ) {
// error_log("DischargeInstructions Query - Lookup generated an Error");
                            $jsonRecord[ 'success' ] = false;
                            $jsonRecord[ 'msg' ]     = "Patient Information Unavailable - " . $this->get( 'frameworkErr' );
                        } else {
// error_log("DischargeInstructions Query - Lookup did NOT generate an Error");
// error_log("$patInfoQuery");
// error_log("Patient Info - " . json_encode( $patInfo[0]["First_Name"] . " " . $patInfo[0]["Last_Name"] ));
                            /* Parse data into Proper Form Input structure */
                            if ( count( $retVal ) > 0 ) {
                                $data                  = array( );
                                // $data[ "PatientName" ] = $patInfo[ 0 ][ "First_Name" ] . " " . $patInfo[ 0 ][ "Last_Name" ];
                                $data[ "date" ]        = "";
                                foreach ( $retVal as $record ) {
                                    if ( "" === $data[ "date" ] ) {
                                        $data[ "date" ] = $record[ "date" ];
                                    }
                                    $data[ $record[ "fieldName" ] ] = $record[ "value" ];
                                }
                                $jsonRecord[ "data" ] = $data;
                                unset( $jsonRecord[ 'msg' ] );
                                $GUID = "";
                            } else {
                                $jsonRecord[ 'success' ]      = 'false';
                                $jsonRecord[ 'errorMessage' ] = 'No Records found';
                            }
                        }
                    } else {
                        if ( count( $retVal ) > 0 ) {
                            unset( $jsonRecord[ 'msg' ] );
                            $jsonRecord[ 'total' ]   = count( $retVal );
                            $jsonRecord[ 'records' ] = $retVal;
                        }
                    }
                }
            }
            
            $this->Patient->endTransaction();
            if ( "" !== $GUID ) {
                $jsonRecord[ 'dischargeInfoID' ] = "$GUID";
            }
            $this->set( 'jsonRecord', $jsonRecord );
            return;
        }

        /**
         * Cumulative Dose Tracking - This service call manages the Patient Cumulative Dose History records
         * Each time a Patient Cumulative Dose History ("cdhRecord") record is generated for a particular patient a record is maintained consisting of the following:
         * ...
         *
         * GET Call
         *
         * POST Call
         *
         * PUT Call
         *
         * DELETE Call
         *
         * Table Definition
         *
         Initialize Lookup Table for Cumulative Dose Medications
         Cumulative Dose Medications are stored in the Lookup Table with a Lookup_Type_ID = 60.
         Note that Lookup_Types between 40 and 60 are used for other purposes than standard lookups.
         
         INSERT INTO [COMS_TEST_2].[dbo].[LookUp]
         ([Lookup_Type],[Lookup_Type_ID],[Name],[Description])
         VALUES
         (0, 60, 'Cumulative Dosing Meds', 'Medication ID')
         
         
         // 7/1/2014 - MWB - Cumulative Dose Tracking SQL Table
         USE [COMS_TEST_2]
         CREATE TABLE [dbo].[Patient_CumulativeDoseHistory](
         [ID] [uniqueidentifier] NOT NULL,
         [Patient_ID] [uniqueidentifier] NOT NULL,
         [MedID] [uniqueidentifier] NOT NULL,
         [CumulativeDoseAmt] [varchar](30) NOT NULL,
         [CumulativeDoseUnits] [varchar](30) NOT NULL,
         [Date_Changed] [datetime] DEFAULT (getdate()),
         [Author] [varchar](30) NULL
         ) ON [PRIMARY]
         
         
         
         INSERT INTO [COMS_TEST_2].[dbo].[Patient_CumulativeDoseHistory]
         (ID, Patient_ID, MedID, CumulativeDoseAmt, CumulativeDoseUnits, Author)
         VALUES
         ('B9D985FA-C493-46AC-A388-E42997AA2629', 'C4A968D0-06F3-E311-AC08-000C2935B86F', '7D95474E-A99F-E111-903E-000C2935B86F', 300, 'ml', 'Mike Barlow')
         
         
         SAMPLE GET for testing
         All records for patient:
         http://coms-mwb.dbitpro.com:355/Patient/CumulativeDoseTracking/C4A968D0-06F3-E311-AC08-000C2935B86F
         Specific record for patient:
         http://coms-mwb.dbitpro.com:355/Patient/CumulativeDoseTracking/C4A968D0-06F3-E311-AC08-000C2935B86F/B9D985FA-C493-46AC-A388-E42997AA2629
         
         
         SAMPLE POST for testing (Note: MedID is for 'ACYCLOVIR INJ'
         URL: 
         http://coms-mwb.dbitpro.com:355/Patient/CumulativeDoseTracking/C4A968D0-06F3-E311-AC08-000C2935B86F
         http://coms-mwb.dbitpro.com:355/Patient/CumulativeDoseTracking
         Content-Type:application/x-www-form-urlencoded; charset=UTF-8
         
         Data:
         Medication=7D95474E-A99F-E111-903E-000C2935B86F
         
         SAMPLE PUT for testing
         URL: http://coms-mwb.dbitpro.com:355/Patient/CumulativeDoseTracking/C4A968D0-06F3-E311-AC08-000C2935B86F/C4A968D0-06F3-E311-AC08-000C2935B86F
         Content-Type:application/json
         Data:
         {
         "MedID":"C4A968D0-06F3-E311-AC08-000C2935B86F",
         "CumulativeDoseAmt" : "399",
         "CumulativeDoseUnits" : "MiliRoentgens",
         "Author" : "Someone"
         }
         *
         **/
        
        function _AddRecordMed2CumList($CumDoseMeds, &$aMed) {
            foreach($CumDoseMeds as $aCumMed) {
                if ($aCumMed['MedID'] == $aMed['MedID']) {
// error_log(json_encode($aCumMed));
                    $aMed['MaxCumulativeDoseAmt'] = $aCumMed['CumulativeDoseAmt'];
                    $aMed['MaxCumulativeDoseUnits'] = $aCumMed['CumulativeDoseUnits'];
                    return;
                }
            }
        }

        function CumulativeDoseTracking( $PatientID = null, $cdhRecordID = null ) {
            $jsonRecord              = array( );
            $jsonRecord[ 'success' ] = true;
            $query                   = "";
            
            $DataTable = "Patient_CumulativeDoseHistory";
            $GUID      = "";
            $this->Patient->beginTransaction();
            $Date2 = date( "F j, Y" );
            $ErrMsg = "";
            if ( "GET" == $_SERVER[ 'REQUEST_METHOD' ] ) {
            $controller = 'LookupController';
            $lookupController = new $controller('Lookup', 'lookup', null);
            $query = $lookupController->_getAllCumulativeDoseMeds(null);
            $CumDoseMeds = $this->Patient->query( $query );
// error_log("PatientController.CumulativeDoseTracking - Cumulative Meds List");
// error_log(json_encode($CumDoseMeds));
// error_log("CumulativeDoseTracking - GET");
                if ( $PatientID ) {
                    $partialQuery = "SELECT 
                   dt.CumulativeDoseAmt, 
                   dt.CumulativeDoseUnits, 
                   dt.Source,
                   dt.MedID,
                   dt.Author,
                   lu1.Name as MedName,
                   lu2.Name as Units,
                   CONVERT(varchar,dt.Date_Changed,101) as Date_Changed
                   from Patient_CumulativeDoseHistory dt
                   join LookUp lu1 on lu1.Lookup_ID = dt.MedID
                   join LookUp lu2 on lu2.Lookup_ID = dt.CumulativeDoseUnits
                   where Patient_ID = '$PatientID'";
                    if ( $cdhRecordID ) {
                        $query = $partialQuery . " and dt.ID = '$cdhRecordID' order by Name asc";
                    } else {
                        $query = $partialQuery;
                    }
                }
// error_log("CumulativeDoseTracking Query - $query");
                $jsonRecord[ 'msg' ] = "No records to find";
                $ErrMsg              = "Retrieving Records";

                $PatientsCumMeds = $this->Patient->query( $query );
                if ( $this->checkForErrors( $ErrMsg, $PatientsCumMeds ) ) {
                    $jsonRecord[ 'success' ] = false;
                    $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                    $this->Patient->rollbackTransaction();
                    $this->set( 'jsonRecord', $jsonRecord );
                    return;
                }


                $jsonRecord[ 'success' ] = 'true';
                $CumMedsList = array( );
                if ( count( $PatientsCumMeds ) > 0 ) {
// error_log("Patient.Controller.CumulativeDoseTracking.Walking Patients Meds - ");
                    $Lifetime = 0;
                    $LastKey = "";
                    foreach ($PatientsCumMeds as $aMed) {
// error_log("Patient. Med - ");
// error_log(json_encode($aMed));
                        $rec = $aMed;
                        $this->_AddRecordMed2CumList($CumDoseMeds, $rec);
// error_log("Array Key does NOT exist - ");
// error_log(json_encode($CumMedsList));
                        if (!array_key_exists ( $aMed['MedID'] , $CumMedsList )) {
                            if("" != $LastKey) {
                                $CumMedsList[$LastKey]['LifetimeAmt'] += $Lifetime;
                            }
                            $LastKey = $aMed['MedID'];
                            $Lifetime = 0;
                            $CumMedsList[$aMed['MedID']] = array();
                            $CumMedsList[$aMed['MedID']]['MedName'] = $rec['MedName'];
                            $CumMedsList[$aMed['MedID']]['MedMaxDose'] = $rec['MaxCumulativeDoseAmt'];
                            $CumMedsList[$aMed['MedID']]['MaxCumulativeDoseAmt'] = $rec['MaxCumulativeDoseAmt'];
                            $CumMedsList[$aMed['MedID']]['MaxCumulativeDoseUnits'] = $rec['MaxCumulativeDoseUnits'];
                            $CumMedsList[$aMed['MedID']]['LifetimeAmt'] = 0;
                        }
                        $newRec = array();
                        $Lifetime += $rec['CumulativeDoseAmt'];
                        $newRec['Amt'] = $rec['CumulativeDoseAmt'];
                        $newRec['Src'] = $rec['Source'];
                        $newRec['Author'] = $rec['Author'];
                        $CumMedsList[$aMed['MedID']]['Patient'][] = $newRec;
                        // $CumMedsList[$aMed['MedID']]['Patient']['Src'] =
                        // $CumMedsList[$aMed['MedID']]['Patient']['Author'] = $rec['Author'];
// error_log("A specific Med - ");
// error_log(json_encode($rec));

//                        $CumMedsList[] = $rec;
                    }
                    if("" != $LastKey) {
                        $CumMedsList[$LastKey]['LifetimeAmt'] += $Lifetime;
// error_log("Patient. LastKey = $LastKey; Lifetime Total = $Lifetime");
                    }
                    unset( $jsonRecord[ 'msg' ] );
                    $recs = array();
                    foreach ( $CumMedsList as $key => $aMed ) {
                        $aMed['ID'] = $key;
                        $recs[] = $aMed;
                    }
                    $jsonRecord[ 'total' ]   = count( $CumMedsList );
                    $jsonRecord[ 'records' ] = $recs;
                }
                else {
// error_log("Patient.Controller.CumulativeDoseTracking.NO Patients Meds - ");
                }
// error_log("CumMedsList");
// error_log(json_encode($CumMedsList));

                $this->set( 'jsonRecord', $jsonRecord );
                return;

            } else if ( "POST" == $_SERVER[ 'REQUEST_METHOD' ] ) {
// error_log("Patient Controller - CumulativeDoseTracking - POST - 2415");
                $MedID                 = "";
                $MedName               = "";
                $Source                = "";
                $CumulativeDoseUnits   = "";
                $CumulativeDoseUnitsID = "";

                parse_str( file_get_contents( "php://input" ), $post_vars );
                $lookup = new LookUp();
// error_log("Input - " . json_encode($post_vars));

                if ( isset( $post_vars[ "MedName" ] ) ) {
                    $MedName = $post_vars[ "MedName" ];
                    $MedName = $this->NTD_StripLeadingFromDrugName($MedName);

                    $retVal = $lookup->getLookupIdByNameAndType($MedName, 2);
// error_log(json_encode($retVal));
                    $MedID = $retVal[0]["id"];
                }
// error_log("Grabbing MedName 1 - MedID = $MedID, MedName = $MedName");

/*** MedID is wrong, doesn't match Lookup Table value. **/
                if ( isset( $post_vars[ "value" ] ) ) {
                    $MedID = $post_vars[ "value" ];
                }
// error_log("Grabbing MedName 2 - MedID = $MedID, MedName = $MedName");

                if ( isset( $post_vars[ "LifetimeDose" ] ) ) {
                    $CumulativeDoseAmt = $post_vars[ "LifetimeDose" ];
                }

                if ( isset( $post_vars[ "UnitName" ] ) ) {
                    $CumulativeDoseUnits = $post_vars[ "UnitName" ];
                }
                if ( isset( $post_vars[ "Units" ] ) ) {
                    $CumulativeDoseUnitsID = $post_vars[ "Units" ];
                }
                if ("" !== $CumulativeDoseUnits && "" === $CumulativeDoseUnitsID) {
                    $retVal = $lookup->getLookupIdByNameAndType($CumulativeDoseUnits, 11);
// error_log("Lookup for CumulativeDoseUnits - $CumulativeDoseUnits; returns: " . json_encode($retVal));
                    $CumulativeDoseUnitsID = $retVal[0]["id"];
// error_log("Units = $CumulativeDoseUnits; ID = $CumulativeDoseUnitsID");
                }
                else {
// error_log("Have CumulativeDoseUnitsID = $CumulativeDoseUnitsID");
                }

                if ( isset( $post_vars[ "Source" ] ) ) {
                    $Source = $post_vars[ "Source" ];
                }
            



// error_log("CumulativeDoseTracking - POST");
                /*********************************************************************************
                Sample POST
                URL: http://coms-mwb.dbitpro.com:355/Patient/CumulativeDoseTracking/C4A968D0-06F3-E311-AC08-000C2935B86F
                MEthod: POST
                Headers: Content-Type:application/x-www-form-urlencoded; charset=UTF-8
                Data: value=7A95474E-A99F-E111-903E-000C2935B86F&LifetimeDose=500&Units=32FC87C5-9C38-E111-9B9C-000C2935B86F&Source=Something
                
                Data Collection Method: parse_str(file_get_contents("php://input"),$post_vars);
                Field Access Method: $MedID = $post_vars["value"];
                *********************************************************************************/
                $GUID = $this->Patient->newGUID();
                
                $query = "INSERT INTO $DataTable (ID, Patient_ID, MedID, CumulativeDoseAmt, CumulativeDoseUnits, Source)
            VALUES (
                '$GUID',
                '$PatientID',
                '$MedID',
                '$CumulativeDoseAmt',
                '$CumulativeDoseUnitsID',
                '$Source'
            )";

// error_log("Patient.Controller.CumulativeDoseTracking - POST - $CumulativeDoseUnitsID - QUERY - $query");
                $retVal = $this->Patient->query( $query );
                if ( $this->checkForErrors( $ErrMsg, $retVal ) ) {
                    $this->Patient->rollbackTransaction();
                    $jsonRecord[ 'success' ] = false;
                    $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                    $this->set( 'jsonRecord', $jsonRecord );
                    return;
                }
                $query = "";
                /* Reset query so we don't run it again in the final step */
            } else if ( "PUT" == $_SERVER[ 'REQUEST_METHOD' ] ) {

                parse_str( file_get_contents( "php://input" ), $post_vars );
                if ( isset( $post_vars[ "value" ] ) ) {
                    $MedID = $post_vars[ "value" ];
                }
                if ( isset( $post_vars[ "LifetimeDose" ] ) ) {
                    $CumulativeDoseAmt = $post_vars[ "LifetimeDose" ];
                }

                if ( isset( $post_vars[ "UnitName" ] ) ) {
                    $CumulativeDoseUnits = $post_vars[ "UnitName" ];
                } else if ( isset( $post_vars[ "Units" ] ) ) {
                    $CumulativeDoseUnits = $post_vars[ "Units" ];
                }
                if ("" !== $CumulativeDoseUnits) {
                    $lookup = new LookUp();
                    $retVal = $lookup->getLookupIdByNameAndType($CumulativeDoseUnits, 11);
// error_log(json_encode($retVal));
                    $CumulativeDoseUnitsID = $retVal[0]["id"];
// error_log("Units = $CumulativeDoseUnits; ID = $CumulativeDoseUnitsID");
                }

                if ( isset( $post_vars[ "Source" ] ) ) {
                    $Source = $post_vars[ "Source" ];
                }
                

                /* Update table record */
                $query = "
                UPDATE $DataTable
                   SET 
                    MedID = '$MedID', 
                    CumulativeDoseAmt = '$CumulativeDoseAmt', 
                    CumulativeDoseUnits = '$CumulativeDoseUnitsID'
            ";
                if ( isset( $Source ) ) {
                    $query .= ",Source = '$Source'";
                }
                $query .= " WHERE ID = '$cdhRecordID'";
                
                $retVal = $this->Patient->query( $query );
                
                /* Check for errors */
                if ( $this->checkForErrors( $ErrMsg, $retVal ) ) {
                    $this->Patient->rollbackTransaction();
                    $jsonRecord[ 'success' ] = false;
                    $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                    $this->set( 'jsonRecord', $jsonRecord );
                    return;
                }
                $query = "";
                /* Reset query so we don't run it again in the final step */
            } else if ( "DELETE" == $_SERVER[ 'REQUEST_METHOD' ] ) {
                $query  = "DELETE from $DataTable WHERE ID = '$cdhRecordID'";
                $retVal = $this->Patient->query( $query );
                
                /* Check for errors */
                if ( $this->checkForErrors( $ErrMsg, $retVal ) ) {
                    $this->Patient->rollbackTransaction();
                    $jsonRecord[ 'success' ] = false;
                    $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                    $this->set( 'jsonRecord', $jsonRecord );
                    return;
                }
                $query = "";
                /* Reset query so we don't run it again in the final step */
            } else {
                $jsonRecord[ 'success' ] = false;
                $jsonRecord[ 'msg' ]     = "Incorrect method called for CumulativeDoseTracking Service (expected a GET/POST/PUS/DELETE got a " . $_SERVER[ 'REQUEST_METHOD' ];
                $this->Patient->rollbackTransaction();
                $this->set( 'jsonRecord', $jsonRecord );
                return;
            }
            
            if ( "" !== $query ) {
                $retVal = $this->Patient->query( $query );
                if ( $this->checkForErrors( $ErrMsg, $retVal ) ) {
                    $jsonRecord[ 'success' ] = false;
                    $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                    $this->Patient->rollbackTransaction();
                } else {
                    $jsonRecord[ 'success' ] = 'true';
                    if ( count( $retVal ) > 0 ) {
                        unset( $jsonRecord[ 'msg' ] );
                        $jsonRecord[ 'total' ]   = count( $retVal );
                        $jsonRecord[ 'records' ] = $retVal;
                    }
                }
            }
            $this->Patient->endTransaction();
            $this->set( 'jsonRecord', $jsonRecord );
            return;
        }
        



        function UpdateAdminDate( $Template_ID, $Admin_Date) {
            $formData = json_decode( file_get_contents( 'php://input' ) );
            $fAdmin_Date        = $formData->Admin_Date;
            // $fOrder_ID          = $formData->Order_ID;
            $fOrders            = $formData->Orders;
// error_log("UpdateAdminDate - Template_ID = $Template_ID, Admin_Date = $Admin_Date, FormAdmin_Date = $fAdmin_Date");
// error_log("UpdateAdminDate - Orders = ");
// error_log(json_encode($fOrders));

            $Pre = $fOrders->PreTherapy;
            $Therapy = $fOrders->Therapy;
            $Post = $fOrders->PostTherapy;

            $this->Patient->UpdateOrderStatusAdminDate($Pre, $fAdmin_Date, "Pre");
            $this->Patient->UpdateOrderStatusAdminDate($Therapy, $fAdmin_Date, "Therapy");
            $this->Patient->UpdateOrderStatusAdminDate($Post, $fAdmin_Date, "Post");

            $jsonRecord = array( );
            $records = array();
            $records    = $this->Patient->UpdateAdminDateMT( $Template_ID, $Admin_Date );
            if ( $this->checkForErrors( 'Update Admin Date Failed. ', $records ) ) {
                $jsonRecord[ 'success' ] = 'false';
                $jsonRecord[ 'msg' ]     = $this->get( 'frameworkErr' );
                $this->set( 'jsonRecord', $jsonRecord );
                return;
            }
            $jsonRecord[ 'success' ] = true;
            $jsonRecord[ 'total' ]   = count( $records );
            $jsonRecord[ 'records' ] = $records;
            $this->set( 'jsonRecord', $jsonRecord );
        }
        
        
        
        
        /***
         * URL: http://coms-mwb.dbitpro.com:355/Patient/MedReminders/36DCBFD9-8FC7-45E7-A2DD-DD63E26A701F
         * Header: Content-Type:application/json
         * Data: {"MR_ID":"","TemplateID":"","Title":"kjgh kjhg ","Description":"kjhg kjhg ","ReminderWhenCycle":"Before","ReminderWhenPeriod":"Cycle","id":null}
         */
        
        
        
        function MedReminders( $TemplateID = null, $MR_ID = null ) {
            $inputData = file_get_contents( 'php://input' );
            $post_vars = json_decode( $inputData );
// error_log( "MedReminders Input - $inputData" );
// error_log( "MedReminders post_vars - " . $this->varDumpToString( $post_vars ) );
            
            
            
            
            $jsonRecord              = array( );
            $jsonRecord[ "success" ] = true;
            $tmpRecords              = array( );
            
            if ( "GET" == $_SERVER[ "REQUEST_METHOD" ] ) {
                $records    = $this->Patient->getMedReminders( $TemplateID, $MR_ID );
                $msg        = "Get";
                $tmpRecords = $records;
            } else if ( "POST" == $_SERVER[ "REQUEST_METHOD" ] ) {
                if ( !$MR_ID ) {
                    $MR_ID = $this->Patient->newGUID();
                }
                $records          = $this->Patient->setMedReminders( $TemplateID, $MR_ID, $post_vars );
                $msg              = "Create";
                $MRRec            = array( );
                $MRRec[ "MR_ID" ] = $MR_ID;
                $tmpRecords[ ]    = $MRRec;
            } else if ( "PUT" == $_SERVER[ "REQUEST_METHOD" ] ) {
                if ( $MR_ID ) {
                    $records          = $this->Patient->updateMedReminders( $TemplateID, $MR_ID, $post_vars );
                    $msg              = "Update";
                    $MRRec            = array( );
                    $MRRec[ "MR_ID" ] = $MR_ID;
                    $tmpRecords[ ]    = $MRRec;
                }
            } else if ( "DELETE" == $_SERVER[ "REQUEST_METHOD" ] ) {
            }
            
            if ( $this->checkForErrors( "$msg Medication Reminder Failed. ", $records ) ) {
                $jsonRecord[ "success" ] = false;
                $jsonRecord[ "msg" ]     = $this->get( "frameworkErr" );
                $this->set( "jsonRecord", $jsonRecord );
                return;
            }
            $jsonRecord[ "total" ]   = count( $tmpRecords );
            $jsonRecord[ "records" ] = $tmpRecords;
            $this->set( "jsonRecord", $jsonRecord );
        }
        
        
        
        /******************
         *
         *
         * CREATE TABLE [dbo].[EditLockout](
         *  [id] [uniqueidentifier] DEFAULT NEWSEQUENTIALID(),
         *  [Patient_ID] [uniqueidentifier] NOT NULL,
         *  [Section] [nvarchar](max) NULL,
         *  [User] [nvarchar](max) NULL,
         *  [dtLocked] [datetime] NULL,
         *  [dtUnLocked] [datetime] NULL
         *  
         *  GET: - http://coms-mwb.dbitpro.com:355/Patient/Lock
         *  Get info for all locked sites
         * 
         *  GET: - http://coms-mwb.dbitpro.com:355/Patient/Lock/<Patient_ID>/<SECTION_NAME>
         *  Example: - http://coms-mwb.dbitpro.com:355/Patient/Lock/8C77FF35-1D9D-46CA-8082-190D81559EB0/MySite2313
         *  Get Lock State (true/false) for site specified by Patient_ID = 8C77FF35-1D9D-46CA-8082-190D81559EB0 and Site = MySite2313
         * 
         *  PUT: - http://coms-mwb.dbitpro.com:355/Patient/Lock/<R_ID>
         *  Example: - http://coms-mwb.dbitpro.com:355/Patient/Lock/B9925432-1B69-E411-8A4E-000C2935B86F
         *  Unlock site specified by record_id = B9925432-1B69-E411-8A4E-000C2935B86F
         * 
         *  POST: - http://coms-mwb.dbitpro.com:355/Patient/Lock/<Patient_ID>/<SECTION_NAME>
         *  Example: - http://coms-mwb.dbitpro.com:355/Patient/Lock/8C77FF35-1D9D-46CA-8082-190D81559EB0/MySite2313
         *  Lock site specified by Patient_ID = 8C77FF35-1D9D-46CA-8082-190D81559EB0 and Site = MySite2313
         *  Returns true and record if site is locked
         *  Returns false and record info of who currently has site locked.
         * 
         ******************/
        
        /* returns records indicating site section is locked */
        /* if return is one or more records then that indicates those sections are locked. */
        function _getLockedState( $Patient_ID = null, $Section = null ) {
            $query = "select 
        el.id, 
        el.Patient_ID, 
        el.Section, 
        el.UserName, 
        CONVERT(varchar,el.dtLocked,100) as dtLocked, 
        CONVERT(varchar,el.dtUnLocked,100) as dtUnLocked 
        from EditLockout el where Patient_ID = '$Patient_ID' 
        and Section = '$Section' 
        and dtLocked is not null 
        and dtUnLocked is null";
            
// error_log( "_getLockedState = $query" );
            $retVal = $this->Patient->query( $query );
            return $retVal;
        }
        
        function _isSiteLocked( $Patient_ID = null, $Section = null ) {
            $retVal = $this->_getLockedState( $Patient_ID, $Section );
// error_log( "_isSiteLocked = " . count( $retVal ) );
            return ( count( $retVal ) > 0 );
        }
        
        function _unlockSite( $rid ) {
            $query   = "Update EditLockout set dtUnLocked = GETDATE() where id = '$rid'";
            $records = $this->Patient->query( $query );
        }
        
        function Lock( $Patient_ID = null, $Section = null, $State = null ) {
            
// error_log( "Lock = $Patient_ID, $Section, $State" );
            
            
            
            $jsonRecord              = array( );
            $jsonRecord[ "success" ] = true;
            $records                 = array( );
            $msg                     = "";
            if ( "GET" == $_SERVER[ "REQUEST_METHOD" ] ) {
                /* Additional check for "" takes care of just a trailing slash at the end of the request URI */
                if ( ( null === $Patient_ID || "" === $Patient_ID ) && null === $Section && null === $State ) {
// error_log( "Get info on all locked records" );
/***********************
                    $query = "select 
                el.id, 
                el.Patient_ID, 
                el.Section, 
                el.UserName, 
                CONVERT(varchar,el.dtLocked,100) as dtLocked, 
                CONVERT(varchar,el.dtUnLocked,100) as dtUnLocked, 
                ISNULL(p.Last_Name, '') as Last_Name,
                ISNULL(p.First_Name, '') as First_Name,
                ISNULL(p.Middle_Name, '') as Middle_Name,
                ISNULL(p.Prefix, '') as Prefix, 
                ISNULL(p.Suffix, '') as Suffix, 
                p.Match
                from EditLockout el 
                join Patient p on p.Patient_ID = el.Patient_ID
                where el.dtLocked is not null 
                and el.dtUnLocked is null";
***********************/

                    $query = "select 
                el.id, 
                el.Patient_ID, 
                el.Section, 
                el.UserName, 
                CONVERT(varchar,el.dtLocked,100) as dtLocked, 
                CONVERT(varchar,el.dtUnLocked,100) as dtUnLocked, 
                DFN as Patient_DFN
                from EditLockout el 
                join Patient p on p.Patient_ID = el.Patient_ID
                where el.dtLocked is not null 
                and el.dtUnLocked is null";


                    $records                 = $this->Patient->query( $query );
                    $jsonRecord[ "success" ] = true;
                    $jsonRecord[ "total" ]   = count( $records );
                    $jsonRecord[ "records" ] = $records;
                    $this->set( "jsonRecord", $jsonRecord );
                    return;
                }
                
                if ( null === $State ) { // Get current state
                    $msg                       = "Get Lock State for specified section";
                    $LockState                 = $this->_isSiteLocked( $Patient_ID, $Section );
                    $State                     = $LockState ? "Locked" : "Unlocked";
                    $jsonRecord[ "success" ]   = true;
                    $jsonRecord[ "LockState" ] = $State;
                    $this->set( "jsonRecord", $jsonRecord );
                    return;
                }
            } else if ( "POST" == $_SERVER[ "REQUEST_METHOD" ] ) {
// error_log( "Lock = POST" );
                $LockState = $this->_isSiteLocked( $Patient_ID, $Section );
                
                if ( $LockState ) {
                    /* Site is already locked, return info on who has it locked */
// error_log( "Site is locked" );
                    $records                 = $this->_getLockedState( $Patient_ID, $Section );
                    $jsonRecord[ "success" ] = false;
                    $jsonRecord[ "total" ]   = count( $records );
                    $jsonRecord[ "records" ] = $records;
                    $this->set( "jsonRecord", $jsonRecord );
                    return;
                }
                
// error_log( "Site is NOT locked" );
                $User  = $_SESSION[ "dname" ];
                $MR_ID = $this->Patient->newGUID();
                $query = "INSERT INTO EditLockout
(id, Patient_ID, Section, UserName, dtLocked)
VALUES
('$MR_ID','$Patient_ID', '$Section', '$User', GETDATE())";
// error_log( "POST - $query" );
                $records = $this->Patient->query( $query );
                
// error_log( "Lock - POST - " . $this->varDumpToString( $records ) );
                
                $query   = "select * from EditLockout where id = '$MR_ID'";
                $records = $this->Patient->query( $query );
                
// error_log( "Lock - getLocked - " . $this->varDumpToString( $records ) );
                
                
                $jsonRecord[ "success" ] = true;
                $jsonRecord[ "total" ]   = count( $records );
                $jsonRecord[ "records" ] = $records;
                
                $this->set( "jsonRecord", $jsonRecord );
                return;
            } else if ( "PUT" == $_SERVER[ "REQUEST_METHOD" ] ) {
// error_log( "Unlocking locked record" );
                if ( null === $Patient_ID || "" === $Patient_ID ) {
                    $jsonRecord[ "success" ] = false;
                    $jsonRecord[ "msg" ]     = "No record ID to unlock";
                    $this->set( "jsonRecord", $jsonRecord );
                    return;
                }
                $retVal                  = $this->_unlockSite( $Patient_ID );
                $jsonRecord[ "success" ] = true;
                $jsonRecord[ "msg" ]     = "Specified section has been unlocked";
                $this->set( "jsonRecord", $jsonRecord );
                return;
            } else {
                $jsonRecord[ "success" ] = false;
                $jsonRecord[ "msg" ]     = "Invalid Request - " . $_SERVER[ "REQUEST_METHOD" ];
                $this->set( "jsonRecord", $jsonRecord );
                return;
            }
            
            if ( $this->checkForErrors( "$msg Edit Lock Failed. ", $records ) ) {
                $jsonRecord[ "success" ] = false;
                $jsonRecord[ "msg" ]     = $this->get( "frameworkErr" );
                $this->set( "jsonRecord", $jsonRecord );
                return;
            }
            
            $jsonRecord[ "total" ]   = count( $records );
            $jsonRecord[ "records" ] = $records;
            $this->set( "jsonRecord", $jsonRecord );
        }




/********************
use COMS_TEST_6
alter table Patient
add constraint PK_Patient_ID Primary key(Patient_ID)
GO
alter table LookUp
add constraint PK_Lookup_ID Primary key(Lookup_ID)
GO
alter table DiseaseStaging
add constraint PK_ID Primary key(ID)
GO

CREATE TABLE PatientDiseaseHistory(
    PDH_ID uniqueidentifier not null default newsequentialid() constraint PK_PDH_ID PRIMARY KEY,
    Patient_ID uniqueidentifier NOT NULL constraint FK_Patient_ID REFERENCES Patient (Patient_ID),
    Date_Assessment datetime NOT NULL default getdate(),
    Author varchar(30) NULL,
    Disease_ID uniqueidentifier NOT NULL constraint FK_Disease_ID REFERENCES LookUp(Lookup_ID),
    DiseaseStage_ID uniqueidentifier NULL constraint FK_ID REFERENCES DiseaseStaging (ID)
)
 ********************/
        function Query4ListOfCancers( $Patient_ID = null) {
            $query = "select 
                PDH_ID,
                CONVERT(varchar,pdh.Date_Assessment,101) as date,
                pdh.Author,
                lu.Name as DiseaseName,
                case when ds.Stage is null then '' else ds.Stage end as DiseaseStage
                from PatientDiseaseHistory pdh
                join LookUp lu on lu.Lookup_ID = pdh.Disease_ID
                left outer join DiseaseStaging ds on ds.ID = pdh.DiseaseStage_ID
                where Patient_ID = '$Patient_ID'
                order by date DESC";

            if (null == $Patient_ID) {
                $jsonRecord[ "success" ] = false;
                $msg                     = "Query into Patient Disease History failed, Missing Patient ID";
                $jsonRecord[ "msg" ]     = $msg;
                $this->set( "jsonRecord", $jsonRecord );
                return null;
            }
            $retVal = $this->Patient->query( $query );
            if ( $this->checkForErrors( 'Query into Patient Disease History failed', $retVal ) ) {
                $jsonRecord[ "success" ] = false;
                $msg                     = "Query into Patient Disease History failed";
                $jsonRecord[ "msg" ]     = $msg . $this->get( "frameworkErr" );
                $this->set( "jsonRecord", $jsonRecord );
                return null;
            }
            return $retVal;
        }

        function Cancer( $Patient_ID = null, $Disease_ID = null, $DiseaseStage_ID = null ) {
            $jsonRecord              = array( );
            $jsonRecord[ "success" ] = true;
            $records                 = array( );
            $msg                     = "Disease type and stage have been saved";
            $Author                  = $_SESSION[ 'dname' ];

            if ( "GET" == $_SERVER[ "REQUEST_METHOD" ] ) {
                $retVal = $this->Query4ListOfCancers($Patient_ID);
            }
            else if ( "POST" == $_SERVER[ "REQUEST_METHOD" ] ) {
                $form_data = json_decode( file_get_contents( 'php://input' ) );
                $Patient_ID = $form_data->Patient_ID;
                $Disease_ID = $form_data->Disease_ID;
                $DiseaseStage_ID = $form_data->DiseaseStage_ID;

                $this->Patient->beginTransaction();
                if ('' == $DiseaseStage_ID) {
                    $query = "INSERT INTO PatientDiseaseHistory 
    (Patient_ID,Author,Disease_ID) 
VALUES 
    ('$Patient_ID','$Author','$Disease_ID')";
                }
                else {
                    $query = "INSERT INTO PatientDiseaseHistory 
    (Patient_ID,Author,Disease_ID,DiseaseStage_ID) 
VALUES 
    ('$Patient_ID','$Author','$Disease_ID','$DiseaseStage_ID')";
                }

                $retVal = $this->Patient->query( $query );

                if ( $this->checkForErrors( 'Insert into Patient_History failed.', $retVal ) ) {
                    $this->Patient->rollbackTransaction();

                    $jsonRecord[ "success" ] = false;
                    $msg                     = "Disease type and stage have NOT been saved ";
                    $jsonRecord[ "msg" ]     = $msg . $this->get( "frameworkErr" );
                    $this->set( "jsonRecord", $jsonRecord );
                    return;
                }
                $this->Patient->endTransaction();
                $retVal = $this->Query4ListOfCancers($Patient_ID);
            }
            else if ( "DELETE" == $_SERVER[ "REQUEST_METHOD" ] ) {
                $PDH_ID = $Disease_ID;
                $this->Patient->beginTransaction();
                if ('' !== $PDH_ID) {
                    $query = "DELETE FROM PatientDiseaseHistory where PDH_ID = '$PDH_ID'";
// error_log("Delete Record from PDH - $query");
                    $retVal = $this->Patient->query( $query );

                    if ( $this->checkForErrors( 'DELETE from Patient_History failed.', $retVal ) ) {
                        $this->Patient->rollbackTransaction();
                        $jsonRecord[ "success" ] = false;
                        $msg                     = "Disease has NOT been deleted from Patient Record";
                        $jsonRecord[ "msg" ]     = $msg . $this->get( "frameworkErr" );
                        $this->set( "jsonRecord", $jsonRecord );
                        return;
                    }
                }
                $this->Patient->endTransaction();
                $retVal = $this->Query4ListOfCancers($Patient_ID);
            }
            else {
                $jsonRecord[ 'success' ] = false;
                $jsonRecord[ 'msg' ]     = "Incorrect method for saving Cancer Type (expected a GET/POST got a " . $_SERVER[ 'REQUEST_METHOD' ];
                $this->set( 'jsonRecord', $jsonRecord );
                return;
            }
            $this->set( 'frameworkErr', null );
            $jsonRecord[ "total" ]   = count( $retVal );
            $jsonRecord[ "records" ] = $retVal;
            $this->set( "jsonRecord", $jsonRecord );
        }


















/* Patterns of Care Determination (PCD) List Types of Cancer by Gender */
        function PCD_CancerByGender() {
            $jsonRecord              = array( );
            $jsonRecord[ "success" ] = true;
            $records                 = array( );
            $msg                     = "Patterns of Care Determination (PCD) List Types of Cancer by Gender";

            $query = "select 
 pdh.Patient_ID, 
 pdh.Disease_ID, 
 case when pdh.DiseaseStage_ID is null then null else pdh.DiseaseStage_ID end as Stage_ID,
 lu.name as Cancer,
 case when ds.Stage is null then '' else ds.Stage end as Stage, 
 p.Gender
 from PatientDiseaseHistory pdh
 join LookUp lu on lu.Lookup_ID = pdh.Disease_ID
 join Patient p on p.Patient_ID = pdh.Patient_ID
 left join DiseaseStaging ds on ds.ID = pdh.DiseaseStage_ID
 order by p.Gender";

            $query = "select 
 pdh.Patient_ID, 
 pdh.Disease_ID, 
 case when pdh.DiseaseStage_ID is null then null else pdh.DiseaseStage_ID end as Stage_ID,
 lu.name as Cancer,
 case when ds.Stage is null then '' else ds.Stage end as Stage,
 p.DFN as DFN
 from PatientDiseaseHistory pdh
 join LookUp lu on lu.Lookup_ID = pdh.Disease_ID
 join Patient p on p.Patient_ID = pdh.Patient_ID
 left join DiseaseStaging ds on ds.ID = pdh.DiseaseStage_ID
 order by p.DFN";


            $retVal = $this->Patient->query( $query );
            if ( $this->checkForErrors( 'Patterns of Care Determination failed.', $retVal ) ) {
                $jsonRecord[ "success" ] = false;
                $msg                     = "Patterns of Care Determination can not be determined";
                $jsonRecord[ "msg" ]     = $msg . $this->get( "frameworkErr" );
                $this->set( "jsonRecord", $jsonRecord );
                return;
            }

// error_log("PoCD SQL Query complete - " . count($retVal) . " records");

            $Gender = "";
            $Cancer = "";
            $retRec = array();
            $records = array();
            $controller = 'LookupController';
            $lookupController = new $controller('Lookup', 'lookup', null);
            $rslt1 = array();
            foreach($retVal as $aRecord) {
                $PatientInfo = $lookupController->getPatientInfoFromVistA($aRecord["DFN"]);
                $aRecord["Gender"] = $PatientInfo["Gender"];
                $aCancer = $aRecord["Cancer"];
                if ($aRecord["Stage"] != "") {
                    $aCancer = $aCancer . " / " . $aRecord["Stage"];
                }
                $aRecord["Cancer"] = $Cancer;
                $aRecord["SortKey"] = $PatientInfo["Gender"] . " - " . $aCancer;
                $rslt1[] = $aRecord;

            }
            usort($rslt1, "PoCD_Cmp");
            foreach($rslt1 as $aRecord) {
                $aCancer = $aRecord["Cancer"];
                if ("" == $Gender) {
                    $Gender = $aRecord["Gender"];
                    $Cancer = $aCancer;
                    $ThisCancer = 1;
                }
                else if ($Gender == $aRecord["Gender"]) {
                    if ("" == $Cancer) {
                        $Cancer = $aCancer;
                        $ThisCancer = 1;
                    }
                    else if ($Cancer == $aCancer) {
                        $ThisCancer = $ThisCancer + 1;
                    }
                    else {
                        $retRec["Gender"] = $Gender;
                        $Cancer = $aCancer;
                        $retRec["count"] = $ThisCancer;
                        $records[] = $retRec;
                        $Cancer = $aCancer;
                        $ThisCancer = 1;
                    }
                }
                else {
                    $Gender = $aRecord["Gender"];
                    $Cancer = $aCancer;
                    $ThisCancer = 1;
                }
                $retRec["Gender"] = $Gender;
                $retRec["Cancer"] = $Cancer;
                $retRec["count"] = $ThisCancer;
            }
            $retRec["Gender"] = $Gender;
            $retRec["Cancer"] = $Cancer;
            $retRec["count"] = $ThisCancer;
            $records[] = $retRec;
            $this->set( 'frameworkErr', null );
            $jsonRecord[ "total" ]   = count( $records );
            $jsonRecord[ "records" ] = $records;
            $this->set( "jsonRecord", $jsonRecord );
        }




























        /* Patterns of Care Determination (PCD) */
        /* Cancer Types & Applied Templates */
        function _______________PCD_CancerByGender2() {
            $jsonRecord              = array( );
            $jsonRecord[ "success" ] = true;
            $records                 = array( );
            $msg                     = "Patterns of Care Determination (PCD) List Types of Cancer by Gender";

            $query = "


 select 
lu.Name as name
,case when l3.Name is not null then l3.Description else lu.Description end as description
,l4.Name as Designed4DiseaseName
,mt.Disease_Stage_ID 
,CASE WHEN l5.Name IS NOT NULL THEN l5.Name ELSE '' END AS  Designed4DiseaseStageName
from Master_Template mt
INNER JOIN LookUp lu ON lu.Lookup_ID = mt.Regimen_ID
INNER JOIN LookUp l1 ON l1.Lookup_ID = mt.Cycle_Time_Frame_ID
INNER JOIN LookUp l2 ON l2.Lookup_ID = mt.Emotegenic_ID
INNER JOIN LookUp l4 ON l4.Lookup_ID = mt.Cancer_ID 
LEFT JOIN LookUp l5 ON l5.Lookup_ID = mt.Disease_Stage_ID
LEFT OUTER JOIN LookUp l3 ON l3.Name = convert(nvarchar(max),mt.Regimen_ID)
 
 select pat.Patient_ID, pat.Template_ID, pdh.Disease_ID, 
 case when pdh.DiseaseStage_ID is null then null else pdh.DiseaseStage_ID end as Stage_ID,
 case when ds.Stage is null then '' else ds.Stage end as Stage, 
 lu.name as Cancer
 from Patient_Assigned_Templates pat
 join PatientDiseaseHistory pdh on pdh.Patient_ID = pat.Patient_ID
 join LookUp lu on lu.Lookup_ID = pdh.Disease_ID
 left join DiseaseStaging ds on ds.ID = pdh.DiseaseStage_ID
";
        }



    function VPR($DFN) {
        $jsonRecord              = array( );
        $jsonRecord[ "success" ] = true;
        $records                 = array( );
        $msg                     = "";
        if ( "GET" == $_SERVER[ "REQUEST_METHOD" ] ) {
            $nodevista = new NodeVista();
            $VPR = $nodevista->get("patient/details/$DFN");
            $patient[0]['VPR'] = json_decode($VPR);
            $jsonRecord[ "total" ]   = count( $VPR );
            $jsonRecord[ "records" ] = json_decode($VPR);
        } else {
            $jsonRecord[ "success" ] = false;
            $jsonRecord[ "msg" ]     = "Invalid Request - " . $_SERVER[ "REQUEST_METHOD" ];
        }
        $this->set( "jsonRecord", $jsonRecord );
    }












        function BaseControllerExample( $Patient_ID = null, $Section = null, $State = null ) {
            $jsonRecord              = array( );
            $jsonRecord[ "success" ] = true;
            $records                 = array( );
            $msg                     = "";
            if ( "GET" == $_SERVER[ "REQUEST_METHOD" ] ) {
                if ( $this->checkForErrors( "$msg Edit Lock Failed. ", $records ) ) {
                    $jsonRecord[ "success" ] = false;
                    $jsonRecord[ "msg" ]     = $this->get( "frameworkErr" );
                    $this->set( "jsonRecord", $jsonRecord );
                    return;
                }
                $jsonRecord[ "total" ]   = count( $records );
                $jsonRecord[ "records" ] = $records;
                $this->set( "jsonRecord", $jsonRecord );
            } else if ( "POST" == $_SERVER[ "REQUEST_METHOD" ] ) {
                $records                 = $this->_getLockedState( $Patient_ID, $Section );
                $jsonRecord[ "success" ] = true;
                $jsonRecord[ "total" ]   = count( $records );
                $jsonRecord[ "records" ] = $records;
                $this->set( "jsonRecord", $jsonRecord );
                return;
            } else if ( "PUT" == $_SERVER[ "REQUEST_METHOD" ] ) {
            } else {
                $jsonRecord[ "success" ] = false;
                $jsonRecord[ "msg" ]     = "Invalid Request - " . $_SERVER[ "REQUEST_METHOD" ];
                $this->set( "jsonRecord", $jsonRecord );
                return;
            }
        }

    }
