Ext.define("COMS.controller.NewPlan.NewPlanTab", {
    extend : "Ext.app.Controller",
    puWinAmputations : null,
    puWinBSASelection : null,

    stores : [
    "Patients"	// Used by the "SelectPatient", "PatientInfo" views
    , "PatientHistory"
    , "LabInfo"
    , "TemplateSources"
    , "DiseaseType"
    , "DiseaseStage"
    , "Templates"
    , "CTOS"
    , "PerfStatStore"
    , "TemperatureLocation"
    , "DeliveryMechanism"
	, "IDEntry"
    ],



	models : ["LabInfo", "AllTemplatesApplied2Patient", "IDEntry"],

    views : [
    "NewPlan.NewPlanTab"
	,"NewPlan.PatientSelection"
	,"NewPlan.SelectPatient"
    ,"NewPlan.PatientInfo"
    ,"NewPlan.PatientInfoTable"
    ,"NewPlan.PatientTemplates"
    ,"NewPlan.PatientHistory"
    ,"NewPlan.LabInfo"
    // MWB - 12/12/2011
    // Removed - During 09-Dec customer meeting, client said DiagImage & Pharmacy were not needed
    // Also removed from PatientInfor view
    // "NewPlan.DiagImage",
    // "NewPlan.Pharmacy",
    ,"NewPlan.OEM"	// MWB - 16-Jan-2012 - Added new view


	,"NewPlan.CTOS"
    ,"NewPlan.CTOS.PatientSummary"	// MWB 27-Jan-2012 - Added new view
//    ,"NewPlan.CTOS.FlowSheet"		// MWB 27-Jan-2012 - Added new view
    ,"NewPlan.CTOS.NursingDocs"		// MWB 27-Jan-2012 - Added new view
    ,"NewPlan.CTOS.KnowledgeBase"	// MWB 27-Jan-2012 - Added new view


    ,'Common.Search4Template'
    ,"Common.selCTOSTemplate"
    ,"Common.selTemplateType"
    ,"Common.selDiseaseAndStage"
    ,"Common.selDisease"
    ,"Common.selDiseaseStage"
    ,"Common.selTemplate"
	,"Common.VitalSignsHistory"
	,"Common.puWinSelCancer"
    ,"NewPlan.dspTemplateData"
    ,"NewPlan.AskQues2ApplyTemplate"
    ,"NewPlan.AmputationSelection"
    ,"NewPlan.BSASelection"
	,"NewPlan.EndTreatmentSummary"
    ],

    refs: [
		{ ref: "NewPlanTab",					selector: "NewPlanTab"},

		{ ref: "CTOS",							selector: "NewPlanTab CTOS"},
		{ ref: "ApplyTemplateBtn",				selector: "NewPlanTab CTOS button[name=\"Apply\"]"},
		{ ref: "EditTemplateBtn",				selector: "NewPlanTab CTOS button[name=\"Edit\"]"},
		{ ref: "What2DoBtns",				    selector: "NewPlanTab CTOS [name=\"NewPlan_What2Do_Btns\"]"},
		{ ref: "NewPlan_CTOS_Form",			    selector: "NewPlanTab CTOS form[name=\"NewPlan_CTOS_Form\"]"},


		{ ref: "PatientInfo",					selector: "NewPlanTab PatientInfo"},
		{ ref: "ResetButton",					selector: "NewPlanTab PatientInfo CTOS selCTOSTemplate button[title=\"ResetFilter\"]"},

		{ ref: "PatientInfoTable",				selector: "NewPlanTab PatientInfo PatientInfoTable"},
		{ ref: "PatientInfoTableInformation",	selector: "NewPlanTab PatientInfo PatientInfoTable container[name=\"PatientInfoTable\"]"},
		{ ref: "PatientInfoTableBSACalcs",		selector: "NewPlanTab PatientInfo PatientInfoTable container[name=\"BSAInfoTable\"]"},

		{ ref: "PatientTemplates",				selector: "NewPlanTab PatientInfo PatientTemplates"},
		{ ref: "PatientHistory",				selector: "NewPlanTab PatientInfo PatientHistory"},
		{ ref: "LaboratoryInfo",				selector: "NewPlanTab PatientInfo LabInfo"},

		{ ref: "selTemplateType",				selector: "NewPlanTab PatientInfo CTOS selTemplateType"},
		{ ref: "DiseaseAndStage",				selector: "NewPlanTab PatientInfo CTOS selCTOSTemplate selDiseaseAndStage"},
		{ ref: "AllTemplatesShownMsg",			selector: "NewPlanTab PatientInfo CTOS selCTOSTemplate [name=\"AllTemplatesShownMsg\"]"},

		{ ref: "Disease",						selector: "NewPlanTab PatientInfo CTOS selCTOSTemplate selDiseaseAndStage selDisease"},
		{ ref: "DiseaseStage",					selector: "NewPlanTab PatientInfo CTOS selCTOSTemplate selDiseaseAndStage selDiseaseStage"},
		{ ref: "Template",						selector: "NewPlanTab PatientInfo CTOS selTemplate[name=\"AllTemplates\"]"},
		{ ref: "MyTemplates",					selector: "NewPlanTab PatientInfo CTOS selTemplate[name=\"MyTemplates\"]"},
		{ ref: "CTOSDataDsp",					selector: "NewPlanTab PatientInfo CTOS dspTemplateData"},

		{ ref: "VitalSigns",					selector: "NewPlanTab PatientHistory VitalSignsHistory"},

		{ ref: "selCTOSTemplate",				selector: "NewPlanTab selCTOSTemplate"},
		{ ref: "SelectPatientSection",			selector: "NewPlanTab SelectPatient"},
		{ ref: "PatientSelectionPanel",			selector: "NewPlanTab PatientSelection"},
		{ ref: "SelectPatient",					selector: "NewPlanTab SelectPatient combobox"},
		{ ref: "ConfirmPatient",				selector: "NewPlanTab SelectPatient container[name=\"Confirm\"]"},

		{ ref: "AuthoringTab",					selector: "AuthoringTab"},
		{ ref: "NavigationTabs",				selector: "NavigationTabs"},

		{ ref: "NDGI_VitalSigns",				selector: "NursingDocs_GenInfo VitalSignsHistory"}
    ],


    init: function() {
        wccConsoleLog("Initialized New Plan Tab Panel Navigation Controller!");
        this.application.btnEditTemplatClicked=false;
        this.control({
            "NewPlanTab fieldcontainer radiofield[name=\"NewPlan_What2Do\"]" : {
                change : this.TemplateTypeSelected
            },

			"NewPlanTab PatientSelection textfield[name=\"CPRS_QueryString\"]" : {
				specialkey : this.QSEnter
			},
            "NewPlanTab SelectPatient combobox" : {
                select : this.PatientSelected
            },

            "NewPlanTab PatientInfo CTOS selTemplateType" : {
                select : this.onTemplateTypeChange
            },
            "NewPlanTab PatientInfo CTOS selDisease" : {
                select : this.DiseaseSelected,
                collapse: this.collapseCombo,
                expand: this.loadCombo
            },
            //KD - 01/23/2012 - Added collapse and expand handlers for disease stage combo
            //This was done to handle the loading issues when going back and forth between
            //CTOS and Template Authoring and random Loading issues.
            "NewPlanTab PatientInfo CTOS selDiseaseStage" : {
                select : this.onDiseaseStageChange,
                collapse: this.collapseCombo,
                expand : this.loadCombo
            },
            "NewPlanTab PatientInfo CTOS selTemplate" : {
                select : this.selTemplateChange,
                collapse: this.collapseCombo,
                expand : this.loadCombo
            },
            "NewPlanTab CTOS button[name=\"Apply\"]" : {
                click: this.applyTemplateToPatient
            },
            "NewPlanTab CTOS button[name=\"Edit\"]" : {
                click: this.editTemplate
            },
            "NewPlanTab PatientInfo CTOS selCTOSTemplate button[title=\"ResetFilter\"]" : {
                click: this.resetTemplateFilter
            },
            "NewPlanTab PatientSelection" : {
                afterrender: this.handlePatientSelectionRender
            },
            "NewPlanTab PatientInfo PatientInfoTable" : {
                afterrender: Ext.togglePanelOnTitleBarClick
            },
            "NewPlanTab PatientInfo PatientTemplates" : {
                afterrender: Ext.togglePanelOnTitleBarClick
            },
            "NewPlanTab PatientInfo PatientHistory" : {
                afterrender: Ext.togglePanelOnTitleBarClick
            },
            "NewPlanTab PatientInfo LabInfo" : {
                afterrender: Ext.togglePanelOnTitleBarClick
            },
            "PatientHistory [name=\"AddVitals\"] button[text=\"Save\"]" : {
                click: this.SaveVitals
            }
       });
	   this.InitIntelligentDataElementStore();
       wccConsoleLog("New Plan Tab Panel Navigation Controller Initialization complete!");
    },


	InitIntelligentDataElementStore : function() {
		var theStore = this.getStore("IDEntry");
		theStore.load({
			scope: this,
			callback: function(records, operation, success) {
				var IDE = [], i, len = records.length, rec;
				for (i = 0; i < len; i++) {
					rec = records[i].getData();
					IDE.push(rec);
				}
				this.application.IntelligentDataElements = IDE;
			}
		});
	},

    cancelApply: function(button){
		wccConsoleLog("CancelApplication of Template");
    },



    SaveVitals : function() {
        var theController = this.getController("NewPlan.CTOS.NursingDocs.GenInfoTab");
        if (theController) {
            theController.SaveVitals("PatientHistory");
        }
    },


    handlePatientSelectionRender : function( panel ) {
        var Btns = Ext.select("button.anchor.QueryCPRS4Patient");
        if (Btns) {
            Btns.on("click", this.handlePatientSelectionClickEvent, this);
        }
        Ext.togglePanelOnTitleBarClick(panel);
    },


    togglePanelOnTitleBarClick : function(panel) {
        panel.header.el.on('click', function() {
            if (panel.collapsed) {
                panel.expand();
            }
            else {
                panel.collapse();
            }
        });
    },
/**/
	resetPatientInfoPanel: function(thisCtl) {
		var PatientInformationPanel = thisCtl.getPatientInfo();
		PatientInformationPanel.collapse();
	},

	resetTRSPanel: function(thisCtl, numTemplates) {
		var TRSPanel = thisCtl.getPatientTemplates(),
			buf =  "Treatment Regimens & Summaries";
		if (numTemplates && "" !== numTemplates) {
			buf += "<span class='LabInfoTitleInfo' style='margin-left: 3em; font-size: smaller;'>(" + numTemplates + ")</span>";
		}
		TRSPanel.collapse();
		TRSPanel.setTitle(buf);
	},

	resetVitalsPanel: function(thisCtl, numVitals) {
		var VitalsPanel = thisCtl.getPatientHistory(),
			buf =  "Patient Vitals ";
		if (numVitals && "" !== numVitals) {
			buf += "<span class='LabInfoTitleInfo' style='margin-left: 3em; font-size: smaller;'>(" + numVitals + ")</span>";
		}
		VitalsPanel.collapse();
		VitalsPanel.setTitle(buf);
	},

	resetLabInfoPanelPanel: function(thisCtl, numLabResults) {
		var LabInfoPanel = thisCtl.getLaboratoryInfo(),
			buf =  "Laboratory Information ";
		if (numLabResults && "" !== numLabResults) {
			buf += "<span class='LabInfoTitleInfo' style='margin-left: 3em; font-size: smaller;'>(" + numLabResults + ")</span>";
		}
		LabInfoPanel.collapse();
		LabInfoPanel.setTitle(buf);
	},

	resetCTOSPanel: function(thisCtl) {
		var CTOSPanel = thisCtl.getCTOS();
		CTOSPanel.setActiveTab(0);
        try {   /* One or more of the controls may not be available based on role of user */
            thisCtl.getNewPlan_CTOS_Form().getForm().reset();
            // Ext.ComponentQuery.query("NewPlanTab selCTOSTemplate")[0].hide();

            // Ext.ComponentQuery.query("NewPlanTab selTemplateType")[0].hide();
            // Ext.ComponentQuery.query("NewPlanTab selDiseaseAndStage")[0].hide();
            // Ext.ComponentQuery.query("NewPlanTab selTemplate")[0].hide();

            Ext.ComponentQuery.query("NewPlanTab dspTemplateData")[0].hide();
            Ext.ComponentQuery.query("NewPlanTab button[name=\"Apply\"]")[0].hide();
            Ext.ComponentQuery.query("NewPlanTab button[name=\"Edit\"]")[0].hide();
        }
        catch (err) {
        }
	},

	resetPanels: function(thisCtl, numTemplates, numVitals, numLabResults) {
		this.resetPatientInfoPanel(thisCtl);
		this.resetTRSPanel(thisCtl, numTemplates);
		this.resetVitalsPanel(thisCtl, numVitals);
		this.resetLabInfoPanelPanel(thisCtl, numLabResults);
		this.resetCTOSPanel(thisCtl);
	},




	HandleTemplateBtnClicks : function (event, element) {
		wccConsoleLog("HandleTemplateBtnClicks - PatientInfoTable!");
		var templateName, templateID, CTOSTabs, gender, height, weight, Amputee, DateTaken;
		var tab2switch2 = element.getAttribute("tabtype");
		var btnName = element.getAttribute("name");
		var Patient = this.application.Patient;
		var fncName = "Unknown ";


		switch (btnName) {
			case "ShowTemplateDetails":
				fncName = "Show Details";
				this.application.Patient.TD = {};
				this.application.Patient.TD_TemplateID = element.getAttribute("templateid");
				this.application.Patient.TD_TemplateName = element.getAttribute("templatename");
				this.application.Patient.TD_ID = element.getAttribute("EotsID");
				this.application.Patient.TD_Type = "Show";

				Ext.widget("TreatmentDetails");

//				templateName = element.getAttribute("templatename");
//				templateID = element.getAttribute("templateid");
//				this.CTOS_DataLoad2(templateID);
//				CTOSTabs = this.getCTOS();
//				CTOSTabs.setActiveTab(0);		// Show the CTOS Tab

				fncName = "";
				break;
			case "GenerateEoTS":
                fncName = "Generate End of Treatment Summary";
                this.application.Patient.EoTS_TemplateID = element.getAttribute("templateid");
                this.application.Patient.EoTS_TemplateName = element.getAttribute("templatename");
                // Have TemplateID = this.application.Patient.AppliedTemplateID
                // TemplateName = this.application.Patient.AppliedTemplate.Description
                this.application.Patient.EoTS_Type = "Generate";
                Ext.widget("EndTreatmentSummary");
                fncName = "";
                break;

			case "ShowEoTS":
				fncName = "Show End of Treatment Summary";
				this.application.Patient.EoTS_TemplateID = element.getAttribute("templateid");
				this.application.Patient.EoTS_TemplateName = element.getAttribute("templatename");
				this.application.Patient.EoTS_ID = element.getAttribute("EotsID");
				this.application.Patient.EoTS_Type = "Show";
		        this.application.loadMask("Loading End of Treatment Summary Information...");
				delete(this.application.Patient.EoTS);	// Clear out any previous EoTS info just in case...

				Ext.Ajax.request({
					scope : this,
					url: Ext.URLs.EoTS + "/" + this.application.Patient.EoTS_ID,
					success: function( response, opts ){
						this.application.unMask();
						var text = response.responseText;
						var resp = Ext.JSON.decode( text );
						if (resp.success) {
							if (resp.records[0]) {
								this.application.Patient.EoTS = resp.records[0];
								Ext.widget("ViewEndTreatmentSummary");
							}
							else {
								alert("No records available for this EoTS");
							}
						}
						else {
							alert("load EoTS - Error");
						}
					},
					failure : function( response, opts ) {
						this.application.unMask();
						alert("EoTS Data Load Failed...");
					}
				});


				fncName = "";
				break;
		}
		if ("" !== fncName) {
			alert(fncName + " - NewPlanTab.js-HandleTemplateBtnClicks() function not yet available");
		}
	},


    doBSASelection : function() {
        if (!this.puWinBSASelection) {
            var form = Ext.widget('form', {
                layout: {
                    type: 'vbox',
                    align: 'stretch'
                },
                border: false,
                bodyPadding: 10,

                fieldDefaults: {
                    labelAlign: 'top',
                    labelWidth: 100,
                    labelStyle: 'font-weight:bold'
                },
                defaults: {
                    margins: '0 0 10 0'
                },

                items: [{ xtype : "BSASelection" }],

                buttons: [
                {
                    text: 'Save',
                    scope : this,
                    handler: function(btn, event) {
                        var theForm = btn.up('form').getForm();
						var thisCtl = this.getController("NewPlan.NewPlanTab");
						var Patient = this.application.Patient;

						if (theForm.isValid()) {
                            var theData = theForm.getValues();
                            var patient_id = this.application.Patient.id;

                            Ext.Ajax.request({
                                url: "/Patient/BSA/" + patient_id,
                                method : "POST",
                                jsonData : theData,
                                success: function( response, opts ){
                                    var text = response.responseText;
                                    var resp = Ext.JSON.decode( text );
                                    if (!resp.success) {
                                        Ext.MessageBox.alert("Saving Error", "NewPlanTab - BSA Selection, Save Error - " + resp.msg );
                                    }
									else {
										Patient.WeightFormula = theData.WeightFormula;
										Patient.BSAFormula = theData.BSAFormula;
										Patient.BSA_Method = theData.BSAFormula;
										var piTableInfo = thisCtl.getPatientInfoTableInformation();
										piTableInfo.update(Patient);
									}
                                },
                                failure : function( response, opts ) {
                                    var text = response.responseText;
                                    var resp = Ext.JSON.decode( text );
                                    Ext.MessageBox.alert("Saving Error", "NewPlanTab - BSA Selection, Save Error - " + "e.message" + "<br />" + resp.msg );
                                }
                            });

                            theForm.reset();
                            btn.up('window').hide();
                            Ext.MessageBox.alert('Thank you!', 'Patient BSA Determination has been saved');
                        }
                    }
                },
                {
                    text: 'Cancel',
                    handler: function(btn, event) {
                        btn.up('form').getForm().reset();
                        btn.up('window').hide();
                    }
                }]
            });

            this.puWinBSASelection = Ext.widget('window', {
                title: "Body Surface Area Method Selection",
                closeAction: 'hide',
                width: 430,
                height: 270,
                minHeight: 270,
                layout: 'fit',
                resizable: true,
                modal: true,
                items: form
            });
        }
        var theForm = this.puWinBSASelection.query("form")[0].form;
        var BSA_Info = {};
        BSA_Info["WeightFormula"] = this.application.Patient.WeightFormula;
        BSA_Info["BSAFormula"] = this.application.Patient.BSAFormula;

        theForm.setValues(BSA_Info);
        this.puWinBSASelection.show();
    },

	doCancerSelection : function() {
		this.puWinCancer = Ext.widget("puWinSelCancer");
		this.puWinCancer.show();
	},
/**
 * Amputation information is stored in the Lookup table in the following manner:
 *  Lookup_Type = 30
 *  Lookup_Type_ID = null
 *  Name = Patient GUID
 *  Description = Amputation (e.g. "Left Foot", "Lower Left Arm", etc) One Amputation per record
 *  Use Patient Controller
 **/
    doAmputationSelection : function() {
        if (!this.puWinAmputations) {
            var form = Ext.widget('form', {
                layout: {
                    type: 'vbox',
                    align: 'stretch'
                },
                border: false,
                bodyPadding: 10,

                fieldDefaults: {
                    labelAlign: 'top',
                    labelWidth: 100,
                    labelStyle: 'font-weight:bold'
                },
                defaults: {
                    margins: '0 0 10 0'
                },

                items: [{ xtype : "AmputationSelection" }],

                buttons: [
                {
                    text: 'Save',
                    scope : this,
                    handler: function(btn, event) {
                        var theForm = btn.up('form').getForm();
                        if (theForm.isValid()) {
                            var theData = theForm.getValues();
                            var postData = [], dataEl = [], patientAmputations = [];

                            for (var key in theData) {
                                if (theData.hasOwnProperty(key)) {
									var el = [];
                                    el["description"] = key;
                                    patientAmputations.push(el);
                                    postData.push(key);
                                }
                            }
                            var params = {"Amputations" : postData };
                            this.application.Patient.Amputations = patientAmputations;
                            var AmputationDisplay = Ext.get("PatientInformationTableAmputations");
                            postData = postData.join("<br>");
                            AmputationDisplay.setHTML(postData);

                            var patient_id = this.application.Patient.id;
							this.application.loadMask("Updating Patient Amputations");
							Ext.Ajax.request({
								url: Ext.URLs.Amputations + "/" + patient_id,
								method : "POST",
								jsonData : params,
								scope : this,
								success: function( response, opts ){
									this.application.unMask();
									var text = response.responseText;
									var resp = Ext.JSON.decode( text );
									theForm.reset();
									btn.up('window').hide();
									if (!resp.success) {
										Ext.MessageBox.alert("Saving Error", "NewPlanTab - AmputationSelection, Save Error - " + resp.msg );
										this.application.Patient.Amputations = "";
									}
									else {
										Ext.MessageBox.alert('Thank you!', 'Patient amputation records have been saved');
									}
								},
								failure : function( response, opts ) {
									this.application.unMask();
									var text = response.responseText;
									var resp = Ext.JSON.decode( text );
									Ext.MessageBox.alert("Saving Error", "NewPlanTab - AmputationSelection, Save Error - " + "e.message" + "<br />" + resp.msg );
									theForm.reset();
									btn.up('window').hide();
								}
							});
						}
					}
				},
                {
                    text: 'Cancel',
                    handler: function(btn, event) {
                        btn.up('form').getForm().reset();
                        btn.up('window').hide();
                    }
                }]
            });

            this.puWinAmputations = Ext.widget('window', {
                title: "Patient Amputations",
                closeAction: 'hide',
                width: 360,
                height: 270,
                minHeight: 270,
                layout: 'fit',
                resizable: true,
                modal: true,
                items: form
            });
        }
        var theForm = this.puWinAmputations.query("form")[0].form;
        var theAmputations = this.application.Patient.Amputations;
        var i, fldAmputations = {};
        for (i = 0; i < theAmputations.length; i++) {
            var x = theAmputations[i];
            var y = x.description;
            fldAmputations[y] = "on";
        }
        theForm.setValues(fldAmputations);
        this.puWinAmputations.show();
    },

	//-------------------------------------------------------------------------
	// MWB 25 Jan 2012 - Event handler for the anchor onclick events in the PatientTemplate Table.
	// When the user clicks on one of the anchors in the list of templates applied to a patient
	// an event is fired off up the stack, passing the name of the template, and the tab the template should be displayed in
	// e.g. OEM or CTOS
	// The event itself should then be captured in either the CTOS or the OEM controller and processed accordingly.
	//
	// MWB 27 Jan 2012 - Added additional functionality
	// MWB 30 Jan 2012 - Added additional functionality
	// MWB 31 Jan 2012 - Added control for the BSA Anchor
	// MWB 09 Feb 2012 - Added additional param - DateTaken
    // MWB 08 Apr 2014 - Added functionality for Add/Edit BSA and Amputations
    //
	HandleAnchorClicks : function (event, element) {
		wccConsoleLog("HandleAnchorClicks - PatientInfoTable - " + element.getAttribute("tabtype"));
		var templateName, templateID, CTOSTabs, gender, height, weight, Amputee, DateTaken;

		var xx, yy, tmpData, tempBSA, DataEl, OEMData, OEM_Data_Record;
		var PatientInfo;
		var PatientData;

		var tab2switch2 = element.getAttribute("tabtype");

		var Patient = this.application.Patient;


		// PatientData = "<div style=\"margin-left: 1em;\"><ul>" + this.getPatientDataAsString() + "</ul></div>";
		// wccConsoleLog(PatientData);
		// PatientData = "";

		if("DoBSACalcs" === tab2switch2 || "ShowBSACalcs" === tab2switch2) {
			tempBSA = Patient.BSA;
			this.application.Patient.BSA_Reduction = 0;
			PatientData = Ext.ShowBSACalcs(Patient, true, null, null);

			Ext.MessageBox.show({
				title : "Body Surface Area Calculations",
				msg : PatientData,
				buttons : Ext.MessageBox.OK
			});

			this.PatientDataLoadComplete("Update BSA");	// Use this call to update the BSA in the PatientInfoTable.
			if ("DoBSACalcs" === tab2switch2 && tempBSA !== Patient.BSA) {
				// wccConsoleLog("Saving Updated BSA Info - " + tempBSA + " - " + Patient.BSA);
				this.SaveBSAInfo();		// POSTs the BSA Calculations and formula as a Patient Vitals Record.
			}
		} else if("ShowAllPatientData" === tab2switch2) {
			PatientInfo = Patient;
			// PatientData = "<div style=\"margin-left: 1em;\"><ul>" + this.getPatientDataAsString() + "</ul></div>";
			var htmlData = prettyPrint( Patient, { maxDepth : 5 } ).innerHTML;
			Ext.create('Ext.window.Window', {
			    title: 'Patient Info',
			    height: 800,
			    width: 950,
				autoScroll : true,
			    html : htmlData
			}).show();

		} else if("BSA" === tab2switch2) {
			gender = element.getAttribute("gender");
			height = element.getAttribute("height");
			weight = element.getAttribute("weight");
			Amputee = element.getAttribute("amputee");
			DateTaken = element.getAttribute("date");	// MWB 09 Feb 2012 - Added additional param - DateTaken
			this.application.fireEvent("CalculateBSA", {gender : gender, height : height, weight : weight, amputee : Amputee, date : DateTaken }); // MWB 09 Feb 2012 - Added additional param - DateTaken
		} else if ("CTOS" === tab2switch2) {
			templateName = element.getAttribute("templatename");
			templateID = element.getAttribute("templateid");
			this.CTOS_DataLoad2(templateID);
			CTOSTabs = this.getCTOS();
	        CTOSTabs.setActiveTab(0);		// Show the CTOS Tab
		} else if ("AddEditAmputation" === tab2switch2) {
            this.doAmputationSelection();
		} else if ("AddEditBSA" === tab2switch2) {
			this.doBSASelection();
		} else if ("AddEditCancer" === tab2switch2) {
            this.doCancerSelection();
		} else if ("Show Details" === tab2switch2 || "Edit" === tab2switch2) {
			alert("Function not yet available");
		} else {
			templateName = element.getAttribute("templatename");
			templateID = element.getAttribute("templateid");
			this.application.fireEvent("TemplateSelected", {tabType : tab2switch2, templateName : templateName, templateID : templateID});
		}
	},


    //KD - 01/23/2012 - This is shared function between Disease stage combo and Select Templates combo
    loadCombo : function(picker, eOpts){

        var originalHiddenVal=null;
        picker.hiddenValue = picker.getRawValue();
        picker.clearValue();

        var URI,id;

        if("MyTemplates" == picker.name){
            URI = Ext.URLs.Templates + "/Patient/";
            id = this.application.Patient.id;
        }else if("AllTemplates" == picker.name){
            if(this.application.ResetClicked){
                URI = Ext.URLs.Templates;
                id = null;
                originalHiddenVal = picker.hiddenValue;
            }else{
                URI = Ext.URLs.Templates + "/Cancer/";
                id = this.application.Patient.Disease.id;
            }
        }else if("Select Disease Stage Control" == picker.name){
            URI = Ext.URLs.DiseaseStage + "/";
            if(eOpts.length && eOpts.length > 0){
                id = eOpts;
            }else{
                id = this.application.Patient.Disease.id;
            }
        } else if (picker.name == "selDisease"){
            if(eOpts.length && "Refresh" === eOpts){
                URI = Ext.URLs.DiseaseType;
                id = '';
            }else if(null != this.application.Patient.TemplateType.id){
                URI = Ext.URLs.DiseaseType + "/Source/";
                id = this.application.Patient.TemplateType.id;
            }
        }

        picker.getStore().load({
            params: {
                URL : URI,
                ID  : id
            },
            callback: function(records,operation,success){
                if(success){
                    if(null!=originalHiddenVal){
                        picker.setRawValue(originalHiddenVal);
                    }
                }
            }
        });

    },

    collapseCombo : function(picker,eOpts){
        if(picker.getValue() == null && picker.hiddenValue != null){
            picker.setRawValue(picker.hiddenValue);		// MWB 15 Feb 2012 - Added missing ";" as per JSLint
        }

    },

	resetTemplateFilter : function(button){

        if(null != this.application.Patient.Template){
            this.getTemplate().setRawValue(this.application.Patient.Template.description);
        }

        this.application.ResetClicked=true;
        this.loadCombo(this.getTemplate());
        this.loadCombo(this.getDisease(),"Refresh");

		this.getDiseaseAndStage().hide();
        this.getTemplate().show();
		button.hide();
		this.getAllTemplatesShownMsg().show();

//        Ext.MessageBox.alert('Success', 'Template filters have been removed. <br />All available Templates will be displayed. ');

    },


    editTemplate : function(button){
        this.application.loadMask("Edit Template");

        this.application.btnEditTemplatClicked=true;

        var disease = this.getDisease();

        var diseaseRecord = disease.getStore().getById(disease.getValue());

        if(0 == this.getSelTemplateType().getStore().count()){
            this.getSelTemplateType().getStore().load();
        }


        if(null === diseaseRecord){
            var newPlanCtl = this.getController("NewPlan.NewPlanTab");
            disease.getStore().load({
                params: {
                        URL: Ext.URLs.DiseaseType + "/",
                        ID: disease.getValue()
                },
                callback: function (records, operation, success) {
                        if (success) {
                            var diseaseRecord = disease.getStore().getById(disease.getValue());
                            newPlanCtl.afterFindDisease(diseaseRecord);
                        }else{
                            Ext.MessageBox.alert('Failure', 'Cancer type could not be found for this template. ');
                        }
                }
            });

        }else {
            this.afterFindDisease(diseaseRecord);
        }


    },

    afterFindDisease : function(diseaseRecord){
        var thisCtl = this.getController("Authoring.AuthoringTab");
        var existingTemplate = Ext.ComponentQuery.query('AuthoringTab fieldcontainer radiofield[name=\"Authoring_SelectTemplateType\"]')[0];


        var template=null;
        var templateTypeModel, templateType = this.getSelTemplateType();

        if(Ext.ComponentQuery.query('NewPlanTab fieldcontainer radiofield[name=\"NewPlan_What2Do\"]')[0].getValue()){
            /*
             * Assigning the template type to a Local Template. Not correct logic
             * but just something to get past the error when editing a template.
             */
            templateTypeModel = this.getSelTemplateType().getStore().getAt(1);
            templateType.setValue(templateTypeModel);
            template = this.getMyTemplates();
        }else if(Ext.ComponentQuery.query('NewPlanTab fieldcontainer radiofield[name=\"NewPlan_What2Do\"]')[1].getValue()){
            template = this.getTemplate();
        }

        if(null == template){
            /*
             * Assigning the template type to a Local Template. Not correct logic
             * but just something to get past the error when editing a template.
             */
            templateTypeModel = this.getSelTemplateType().getStore().getAt(1);
            templateType.setValue(templateTypeModel);
            template = this.getMyTemplates();
        }
        existingTemplate.setValue(true);

        var rbtn = Ext.create(Ext.form.Radio,{
            inputValue : 0
        });

        thisCtl.AuthorTypeSelected(rbtn,true,null,null);
        thisCtl.getPatientNameField().setValue(this.application.Patient.name);
        thisCtl.getPatientNameField().show();

        var templateSourceRecord = this.getSelTemplateType().getStore().findBy(

            function (record, id) {

                    if(record.data.id == templateType.getValue()){
                        return true;
                    }

                    return false;
            });

        var templateSourceRecords = [];

        var tmp = templateType.getStore().getAt(templateSourceRecord);
        templateSourceRecords.push(tmp);
        thisCtl.getTemplateSource().setValue(tmp);
        thisCtl.onTemplateTypeChange(null,templateSourceRecords,null);

        var diseaseRecords = [];
        diseaseRecords.push(diseaseRecord);
        //thisCtl.getExistingDisease().getStore().load();

        thisCtl.getExistingDisease().setRawValue(diseaseRecord.data.name);
        //thisCtl.getExistingDisease().setValue(diseaseRecord);
        thisCtl.DiseaseSelected(thisCtl.getExistingDisease(),diseaseRecords,null);

        var diseaseStage = this.getDiseaseStage();
        var diseaseStageRecord = diseaseStage.getStore().getById(diseaseStage.value.id);
        var diseaseStageRecords = [];


        if(null != diseaseStageRecord){
	        diseaseStageRecords.push(diseaseStageRecord);
			thisCtl.getExistingDiseaseStage().setRawValue(diseaseStageRecord.data.name);
		    thisCtl.onDiseaseStageChange(thisCtl.getExistingDiseaseStage(),diseaseStageRecords,null);
        }else{
            thisCtl.getExistingDiseaseStage().setRawValue('');
        }


        //var templateRecord = template.getStore().getById(template.getValue());
        var templateRecord;
        if(null!=this.application.Patient.AppliedTemplateID){
            templateRecord = template.getStore().getById(this.application.Patient.AppliedTemplateID);
        }else{
            templateRecord = template.getStore().getById(this.application.Patient.Template.id);
        }

        template.clearValue();
        //diseaseStage.clearValue();

        if(null==templateRecord){
            var newPlanCtl = this.getController("NewPlan.NewPlanTab");
            template.getStore().load({
                params: {
                    URL: Ext.URLs.Templates + "/",
                    ID: (null == this.application.Patient.Template) ? this.application.Patient.AppliedTemplateID : this.application.Patient.Template.id
                },
                callback: function(records,operation,success){
                    if(success){
                        templateRecord = this.getAt(0);
                        newPlanCtl.afterFindTemplate(templateRecord,thisCtl,template);
                    }
                }
            });

        }else{
            this.afterFindTemplate(templateRecord,thisCtl,template);
        }
    },
	afterFindTemplate : function(templateRecord,controller,template){
        var templateRecords = [];

        this.collapseCombo(template,null);
        this.collapseCombo(this.getDiseaseStage(),null);

        templateRecords.push(templateRecord);

        controller.getTemplate().setRawValue(templateRecord.data.description);

        controller.selTemplateChange(controller.getTemplate(),templateRecords,null);

		//        this.getNavigationTabs().setActiveTab(1);
		// MWB - 5/29/2012 - With the addition of the "Orders" tab and the fact that the Orders tab might NOT be visible to all users
		// we need to get the index of the "Template Authoring" tab by walking the titles of the tabs.
		var allTabs = this.getNavigationTabs().items;
		allTabs.findBy(function(aTabPanel) {
			if ("Template Authoring" === aTabPanel.title) {
				this.getNavigationTabs().setActiveTab(aTabPanel);
			}
		},
		this );
    },


    ShowAskQues2ApplyTemplate : function(records, operation, success) {
        var i, itemsInGroup = [];	// new Array();
        for (i = 0; i < records.length; i++ ){
            var record = records[i];
            if(record.data.value !== '5' ){
                itemsInGroup.push({
                    boxLabel : record.data.value + ' - ' + record.data.description,
                    name : 'PerfStatus',
                    inputValue : record.data.id,
                    width : 450
                });
            }
        }

		if(this.application.Patient.TemplateID){
			Ext.MessageBox.show({
				title: 'Information',
				msg: 'Template already applied. Would you like to archive existing template and apply current selection?',
				width:300,
				buttons: Ext.MessageBox.OKCANCEL,
                scope: this,
				fn: function(buttonId) {
					if("ok" === buttonId) {
                        try {
                            var fncName = "Generate End of Treatment Summary";
                            this.application.Patient.EoTS_TemplateID = this.application.Patient.AppliedTemplate.id;
                            this.application.Patient.EoTS_TemplateName = this.application.Patient.AppliedTemplate.Description;
                            this.application.Patient.EoTS_Type = "Generate";
                            Ext.widget("EndTreatmentSummary", { widget : "AskQues2ApplyTemplate", itemsInGroup: itemsInGroup, ChangeTemplate: true });
                            fncName = "";
                        }
                        catch (err) {
                            alert("Failure to Add Date Widget");
                        }
					}
				}
			});
        }
		else{
			var theWidget = Ext.widget('AskQues2ApplyTemplate',{itemsInGroup: itemsInGroup, ChangeTemplate: false});
		}
    },

    applyTemplateToPatient : function(button){
        var startDate = new Date(this.application.Patient.TreatmentStart);
        var dateEnded = new Date(this.application.Patient.TreatmentEnd);
        var today = new Date();

        this.getStore('PerfStatStore').load({ 
            scope : this,
            callback : this.ShowAskQues2ApplyTemplate
        });
    },

    clearCTOS : function(button){
        if(this.getCTOSDataDsp().hidden==false){
            this.getCTOSDataDsp().hide();
            if ("1" === SessionTemplateAuthoring) {
                this.getApplyTemplateBtn().hide();
                this.getEditTemplateBtn().hide();
            }
            this.getDiseaseAndStage().hide();
            this.getResetButton().hide();
            this.getDisease().setValue('');
            this.getDiseaseStage().setValue('');
            this.application.selTemplate=null;
            this.getMyTemplates().hiddenValue=null;
            this.getTemplate().hiddenValue=null;
            this.getDiseaseStage().hiddenValue=null;

            if(!button){
                Ext.ComponentQuery.query('NewPlanTab fieldcontainer radiofield[name=\"NewPlan_What2Do\"]')[0].setValue(false);
                Ext.ComponentQuery.query('NewPlanTab fieldcontainer radiofield[name=\"NewPlan_What2Do\"]')[1].setValue(false);
                this.getTemplate().hide();
                this.getMyTemplates().hide();
                this.getTemplate().setValue('');
                this.getMyTemplates().setValue('');
                this.getSelCTOSTemplate().hide();
            }else if("0" === button){
                this.getTemplate().hide();
                this.getTemplate().setValue('');
            }else if("1" === button){
                this.getMyTemplates().setValue('');
                this.getMyTemplates().hide();
            }else if("2" === button){
                Ext.ComponentQuery.query('NewPlanTab fieldcontainer radiofield[name=\"NewPlan_What2Do\"]')[0].setValue(false);
                Ext.ComponentQuery.query('NewPlanTab fieldcontainer radiofield[name=\"NewPlan_What2Do\"]')[1].setValue(false);
                this.getTemplate().hide();
                this.getMyTemplates().hide();
                this.getTemplate().setValue('');
                this.getMyTemplates().setValue('');
                this.getSelCTOSTemplate().hide();
                if(this.getPatientInfo().hidden == false){
                    this.getLaboratoryInfo().hide();
                    this.getPatientHistory().hide();
                    this.getPatientTemplates().hide();
                    this.getPatientInfoTable().hide();
                    this.getPatientInfo().hide();
                    this.getSelectPatient().setValue('');
                    this.getSelectPatient().getStore().removeAll();
                    this.getSelectPatient().lastQuery = null;
                    this.getCTOS().hide();
                }
            }

        }

    },

    TemplateTypeSelected : function(rbtn, newValue, oldValue, eOpts ) {
        wccConsoleLog("What to do has been selected");
        //var set0 = this.getAppliedTemplateList();
        var set0 = this.getMyTemplates();
        var set1 = this.getSelCTOSTemplate();
        this.application.Patient.AppliedTemplateID = null;

        var What2Do = rbtn.inputValue;
        if( newValue ) {
            if ("0" === What2Do) {
                this.clearCTOS(What2Do);
                set0.show();
                set1.hide();
            }
            else {
                this.clearCTOS(What2Do);
                set0.hide();
                this.getSelTemplateType().setValue('');
                set1.show();
            }
        }
    },







	// Called to complete the "TemplateApplied" process. Called from the success event of the patientTemplate.save() AJAX call in the "ApplyTemplate()" function above.
	PatientModelLoadSQLPostTemplateApplied : function( PatientGUID, TreatmentID ) {
		var pModel = this.getModel("PatientInfo");
		var pModelParam = PatientGUID;
		this.application.PatientID = PatientGUID;	// Not yet used... MWB - 5/25/2012
		this.application.TreatmentID = TreatmentID;
		this.application.PAT_ID = TreatmentID;		// PAT_ID and TreatmentID are the same thing, just set differently in different places.

		pModel.load(pModelParam, {
			scope : this,
			success : function( patientInfo, response ) {

				this.application.Patient.Amputations = patientInfo.data.Amputations;
				this.application.Patient.BSA = patientInfo.data.BSA;
				this.application.Patient.BSAFormula = patientInfo.data.BSAFormula;		// This should really be the string of the formula for calculating the BSA
				this.application.Patient.BSA_Method = patientInfo.data.BSAFormula;		// but the Framework returns the name of the method (e.g. DuBois) as the BSAFormula
				this.application.Patient.BSA_Weight = patientInfo.data.BSA_Weight;
				this.application.Patient.TemplateDescription = patientInfo.data.TemplateDescription;
				this.application.Patient.TemplateID = patientInfo.data.TemplateID;
				this.application.Patient.TemplateName = patientInfo.data.TemplateName;
				this.application.Patient.TreatmentEnd = patientInfo.data.TreatmentEnd;
				this.application.Patient.TreatmentStart = patientInfo.data.TreatmentStart;
				this.application.Patient.TreatmentStatus = patientInfo.data.TreatmentStatus;
				this.application.Patient.TreatmentID = this.application.TreatmentID;
				this.application.Patient.WeightFormula = patientInfo.data.WeightFormula;

				this.application.loadMask("Loading Patient Records");
				this.application.DataLoadCount = 5;		// Count of # of modules to load
				this.loadMDWSData();					// module 1
				this.loadTemplates("Templates");					// module 5
                this.loadAllTemplatesApplied2Patient("PatientModelLoadSQLPostTemplateApplied");
				this.loadOrderRecords();				// module 6
                if (this.application.Patient.TemplateID) {
                    this.LoadSpecifiedTemplate(this.application.Patient.TemplateID);
                }
                else {
					this.application.DataLoadCount--;
					this.PatientDataLoadComplete("No Current Template Applied to patient to load");
                }

				var theRealID = this.application.Patient.id;
				this.LoadAllData4PatientByMDWSGUID( theRealID );
			},
			failure : function (record, operation) {
				this.application.unMask();
				wccConsoleLog("Patient Info failed to load properly from MDWS");
			}
		});
	},

	//----------------------------------------------------------------------------------------------------------------
	//
	//	Patient Selection via Admin Dates or entering Patient ID (First Letter of Last Name followed by last 4 of SSN
	//
	//----------------------------------------------------------------------------------------------------------------
	PatientModelLoadSQL : function( query ) {
		var PatientStore = COMS.store.Patients.create();
		this.application.loadMask("One moment please, retrieving Patient Information for " + query + "...");
		PatientStore.load({ scope : this, callback :
			function( records, operation, status) {
				this.application.unMask();
// wccConsoleLog("PatientModelLoadSQL - Load Complete");
				var CPRS_QueryString = Ext.ComponentQuery.query("NewPlanTab PatientSelection [name=\"CPRS_QueryString\"]")[0];
				var query = "";
				var SSN= "";
				var PatientInfo = {}, Patient_ID, Patient_Name;
				if (CPRS_QueryString) {
					query = CPRS_QueryString.getValue();
				}

				if ("" !== query) {
					SSN = query.substring(1);
					var i, nRecs = records.length, flag = false, tmpSSN;

					for (i = 0; i < nRecs; i++) {
						tmpSSN = records[i].get("DFN");
						tmpSSN = tmpSSN.substring(2);
						if (SSN === tmpSSN) {
							flag = true;
							Patient_ID = records[i].get("id");
							Patient_Name = records[i].get("name");
							this.application.TempPatient = records[i];
						}
					}

					// Additional code here to perform proper query in MDWS for data
					var thisCtl = this.getController("NewPlan.NewPlanTab");
					var SelectPatientSection = thisCtl.getSelectPatientSection();
					var SelectPatient = thisCtl.getSelectPatient();
					var ConfirmPatient = thisCtl.getConfirmPatient();
					SelectPatientSection.show();
					SelectPatient.hide();
					if (flag) {
						PatientInfo.Patient_Name = Patient_Name;
						PatientInfo.Patient_ID = Patient_ID;
						ConfirmPatient.update( PatientInfo );
						// CPRS_QueryString.setValue("");
						ConfirmPatient.show();
						ConfirmPatient.el.select("button").on("click", this.ConfirmPatientClick, this);
					}
					else {
						SelectPatient.show();
					}
				}
			}
		});
	},

	PatientModelLoadMDWS : function(query) {
        // Load the Patient Information
        var pModel = this.getModel("PatientInfoMDWS");
        var pModelParam = query;
		this.application.PatientSSN_ID = query;
		this.application.loadMask("One moment please, retrieving Patient Information for " + query + "...");

        pModel.load(pModelParam, {
            scope : this,
            success : function( patientInfo, response ) {
// wccConsoleLog("PatientModelLoadMDWS - Load Complete");
                wccConsoleLog("Patient Info Loaded - Processing");
				this.application.unMask();
				var CPRS_QueryString = Ext.ComponentQuery.query("NewPlanTab PatientSelection [name=\"CPRS_QueryString\"]")[0];
				var query = "";
				var SSN= "";
				var PatientInfo = {}, Patient_ID, Patient_Name;
				if (CPRS_QueryString) {
					query = CPRS_QueryString.getValue();
				}

				if ("" !== query) {
					var record = patientInfo.data;
					Patient_ID = record.id;
					Patient_Name = record.name;
					var data = record;
					this.application.TempPatient = record;

					// Additional code here to perform proper query in MDWS for data
					var thisCtl = this.getController("NewPlan.NewPlanTab");
					var SelectPatientSection = thisCtl.getSelectPatientSection();
					var SelectPatient = thisCtl.getSelectPatient();
					var ConfirmPatient = thisCtl.getConfirmPatient();
					SelectPatientSection.show();
					SelectPatient.hide();
					PatientInfo.Patient_Name = Patient_Name;
					PatientInfo.Patient_ID = Patient_ID;
					ConfirmPatient.update( PatientInfo );
					ConfirmPatient.show();
					ConfirmPatient.el.select("button").on("click", this.ConfirmPatientClick, this);
				}
            },
            failure : function (record, operation) {
				this.application.unMask();
	            Ext.MessageBox.alert('MDWS Error', 'Patient Info failed to load properly from MDWS.<br />' + operation.error);
                wccConsoleLog("Patient Info failed to load properly from MDWS");
            }
        });
	},



	// Get here by either clicking on the "Query CPRS for Patient" button or hitting the "Enter" key in the SSN Field.
	PatientStoreQuery : function( ) {
		// alert("PatientStoreQuery");

		var thisCtl = this.getController("NewPlan.NewPlanTab");
		thisCtl.getPatientInfo().hide();


		var CPRS_QueryString = Ext.ComponentQuery.query("NewPlanTab PatientSelection [name=\"CPRS_QueryString\"]")[0];
		var query = "";
		if (CPRS_QueryString) {
			query = CPRS_QueryString.getValue();
		}
		if ("" === query) {
			return;
		}

		Ext.ComponentQuery.query('NewPlanTab PatientSelection [name=\"from_date\"]')[0].setValue("");
		Ext.ComponentQuery.query('NewPlanTab PatientSelection [name=\"to_date\"]')[0].setValue("");

// MWB - 2/12/2014 - UseNewQueryMethod is not used anyplace else... So it's a global intended for future use
		if (UseNewQueryMethod) {
			this.PatientModelLoadMDWS( query );
		}
		else {
			this.PatientModelLoadSQL( query );
		}
	},

	// Event handler for pressing the "Enter" key in the "Enter Patient Identification" field.
	QSEnter : function( fld, e, eOpts ) {
		// alert("QSEnter");
		if (e.ENTER === e.getKey()) {
			this.PatientStoreQuery();
		}
	},


	LoadAllData4PatientByMDWSGUID : function(patientMDWSGUID) {

// console.log("Loading Patient Data for ");
// console.log("Patient GUID - " + patientMDWSGUID );

		var pModel = this.getModel("PatientInfo");
		this.application.loadMask("Loading Patient Records... After selecting template");

		pModel.load(patientMDWSGUID, {
			scope : this,
			success : function( patientInfo, response ) {
				var recs = [];
				recs[0] = { data : patientInfo.data };

				var thisCtl = this.getController("NewPlan.NewPlanTab");
				var NewPlanTab = thisCtl.getNewPlanTab();
				var PatientSelection = thisCtl.getPatientSelectionPanel();
				PatientSelection.collapse();

				this.resetPanels(thisCtl, "", "", "");

				var ConfirmPatient = thisCtl.getConfirmPatient();
				ConfirmPatient.hide();

				this.PatientSelected(null, recs, null);

				// Attach event handler to the "Update" and "Show" MDWS Data buttons (styled to look like links) in "view\NewPlan\PatientInfo.js"
		//		{ xtype : "container", html : "<button class=\"anchor\" name=\"UpdateMDWSData\">Update</button> Patient Info from MDWS" },
		//		{ xtype : "container", html : "<button class=\"anchor\" name=\"DisplayMDWSData\">Show</button> Updated Patient Info from MDWS" },

				var Btns = Ext.ComponentQuery.query("NewPlanTab PatientInfo")[0].el.select("button.anchor");
				Btns.on("click", this.handleShowUpdateMDWSClickEvent, this);
				Ext.ComponentQuery.query("NewPlanTab PatientInfo container[name=\"MDWSStatus\"]")[0].show();
				Ext.ComponentQuery.query("NewPlanTab PatientInfo container[name=\"UpdateMDWSDataContainer\"]")[0].hide();
				Ext.ComponentQuery.query("NewPlanTab PatientInfo container[name=\"DisplayMDWSDataContainer\"]")[0].hide();
			},
			failure : function (record, operation) {
				this.application.unMask();
				// Ext.MessageBox.alert('MDWS Error', 'Patient Info failed to load properly from MDWS.<br />' + operation.error);
				wccConsoleLog("Patient Info failed to load properly from MDWS");
			}
		});
	},

	ConfirmPatientClick : function(evt, btn) {
		var patientMDWSGUID = btn.getAttribute("pid");
		this.LoadAllData4PatientByMDWSGUID( patientMDWSGUID );
	},

	handleShowUpdateMDWSClickEvent : function( evt, btn ) {
		wccConsoleLog("handleShowUpdateMDWSClickEvent - PatientInfoTable!");

		var PatientInfo = this.application.Patient;
		var btnName = btn.getAttribute("name");
		var Update = Ext.ComponentQuery.query("NewPlanTab PatientInfo container[name=\"UpdateMDWSDataContainer\"]")[0];
		var Display = Ext.ComponentQuery.query("NewPlanTab PatientInfo container[name=\"DisplayMDWSDataContainer\"]")[0];
		if ("UpdateMDWSData" == btnName) {
			this.application.DataLoadCount = 1;
			this.loadMDWSData();
		}
		else if ("DisplayMDWSData" == btnName) {
			Ext.ComponentQuery.query("NewPlanTab PatientInfo container[name=\"MDWSStatus\"]")[0].hide();
			Ext.ComponentQuery.query("NewPlanTab PatientInfo container[name=\"UpdateMDWSDataContainer\"]")[0].show();
			Ext.ComponentQuery.query("NewPlanTab PatientInfo container[name=\"DisplayMDWSDataContainer\"]")[0].hide();
		}
	},










	handlePatientSelectionClickEvent : function( evt, theBtn ) {
        console.log("handlePatientSelectionClickEvent");
		wccConsoleLog("handlePatientSelectionClickEvent - PatientInfoTable!");

		//---------------------------------
		//
		//	This block of code is in place till we can do a reliable query for Patient Information from MDWS
		//	At that point we will have to create a COMS Service which will query MDWS and return either a single Patient Record
		//	OR a list of Patient Records to be used as the Store for the "SelectPatient" combo box.
		//	If a SINGLE record is returned then no combo box is required, just a single link/button to "Accept" and Use the returned Patient Record.
		//
		//---------------------------------
		// alert( "User clicked - " + theBtn.name );
		if ("SelectPatientAdminRange" === theBtn.name ) {
			// Note: For some reason MS-IE 8 receives this event even though the SelectPatientAdminRange button isn't clicked.

			// alert("Select Patient Admin Range");
			// Additional code here to perform proper query in MDWS for data
			var thisCtl = this.getController("NewPlan.NewPlanTab");
			var SelectPatientSection = thisCtl.getSelectPatientSection();
			var SelectPatient = thisCtl.getSelectPatient();
			var ConfirmPatient = thisCtl.getConfirmPatient();
			// Ext.ComponentQuery.query("NewPlanTab PatientSelection [name=\"CPRS_QueryString\"]")[0].setValue("");
			SelectPatientSection.show();
			ConfirmPatient.hide();
			SelectPatient.show();
			thisCtl.getPatientInfo().hide();
		}
		else if ("QueryCPRS4Patient" === theBtn.name ) {
			// alert("Query CPRS 4 Patient");
			// Load the "Patients" Store.
			// Upon load, find the patient looked for by DFN
			this.PatientStoreQuery();
		}
	},


	loadMDWSData : function() {
		var PatientInfo = this.application.Patient;
		var URLParam = "/DFN/" + PatientInfo.DFN;
		if (this.application.PatientSSN_ID) {
			URLParam = "/SSN/" + this.application.PatientSSN_ID;
		}

		Ext.Ajax.request({
			scope : this,
			url: Ext.URLs.MegaMDWS + URLParam,
			success: function( response, opts ){
// wccConsoleLog("MDWS Data - Load Complete");
				var text = response.responseText;
				var resp = Ext.JSON.decode( text );
				if (resp.success) {
					Ext.ComponentQuery.query("NewPlanTab PatientInfo container[name=\"MDWSStatus\"]")[0].hide();
					Ext.ComponentQuery.query("NewPlanTab PatientInfo container[name=\"UpdateMDWSDataContainer\"]")[0].hide();
					Ext.ComponentQuery.query("NewPlanTab PatientInfo container[name=\"DisplayMDWSDataContainer\"]")[0].hide();
					this.application.DataLoadCount--;
					this.PatientDataLoadComplete("MDWS Mega Call");
				}
				else {
					alert("loadMDWSData() - Error");
				}
			},
			failure : function( response, opts ) {
				this.application.DataLoadCount--;
				this.PatientDataLoadComplete("MDWS Mega Call");
				alert("MDWS Data Load Failed...");
			}
		});
	},


//------------------------------------------------------------------------------------------------------------------------------
//
//	Start of data loading section.
//	The 5 "Load" functions here load the various pieces of Patient Data Asynchronously.
//	After each function finishes loading it's particular data set a call is made to the PatientDataLoadComplete() function
//	This function will execute and completion process (e.g. unmask and finish rendering data and managing event handlers)
//
//------------------------------------------------------------------------------------------------------------------------------

    loadAllergyInfo : function() {
        var liModel = this.getModel("Allergies");
        var liModelParam = this.application.Patient.id;
        liModel.load(liModelParam, {
            scope : this,
            success : function( patientInfo, response ) {
                wccConsoleLog("Allergy Info Loaded - Processing");
// wccConsoleLog("Allergies Model - Load Complete");
                var rawData = Ext.JSON.decode(response.response.responseText);
				var tmp = "0 Records";
				this.application.Patient.Allergies = rawData.records;

					// MWB - 5/16/2012 - Used to make sure all data sets are loaded before continuing
				this.application.DataLoadCount--;
				this.PatientDataLoadComplete("Allergy Info");

            },
            failure : function (err, response) {
                wccConsoleLog("Allergy Info failed to load properly");
				this.application.DataLoadCount--;
				this.PatientDataLoadComplete("Allergy Info - FAILED Loading");
            }
        });
    },


    loadLabInfo : function() {
        var liModel = this.getModel("LabInfo");
		var theStore = Ext.getStore("LabInfo");
		var prox = liModel.proxy;
		var uri = prox.url;
		theStore.proxy.url = uri + "/" + this.application.Patient.id;
		theStore.groupField = "specimen";
		theStore.load( {
            scope : this,
			callback: function(records, operation, success) {
// wccConsoleLog("LabInfo Model - Load Complete");
				var thisCtl, LaboratoryInfo, tmp, len;
				if (success) {
	                wccConsoleLog("Laboratory Info Loaded - Processing");
					this.application.Patient.History = records;

						//------------------
						//
						// Grabbing Serum Creatinine (for AUC Calculations) needed data from Labs on loading.
						//
						//


						// MWB - 5/16/2012 - Used to make sure all data sets are loaded before continuing
					this.application.DataLoadCount--;
					this.PatientDataLoadComplete("Lab Info");
				}
				else {
	                wccConsoleLog("Laboratory Info failed to load properly");
					this.application.DataLoadCount--;
					this.PatientDataLoadComplete("Lab Info - FAILED Loading");
				}
		    }
		});
    },


	loadVitals : function(RetCode) {
        var pVitalsModel = this.getModel("Vitals"), pVitalsModelParam = this.application.Patient.id;

        pVitalsModel.load(pVitalsModelParam, {
            scope : this,
            success : function( patientInfo, response ) {
                var rawData = Ext.JSON.decode(response.response.responseText);
                var aVital, vBSA_Weight = "", vWeightFormula = "", vBSA_Method = "", vBSA = "", vHeight = "", vWeight = "";
                var HaveAllCount = 6;
				if (rawData) {
					var i, h, w, t, rec, el;
					if (rawData.total >= 0) {
						for (i = 0; i < rawData.total; i++) {
							rec = rawData.records[i];
							for (el in rec) {
								if (rec.hasOwnProperty(el)) {
								if (null === rec[el]) {
									rec[el] = "";
								}
							}
							}
							h = rawData.records[i].Height;
							t = h.split(" ");
							if (t.length > 1) {
								rawData.records[i].Height = t[0];
							}
							w = rawData.records[i].Weight;
							t = w.split(" ");
							if (t.length > 1) {
								rawData.records[i].Weight = t[0];
							}
						}
						this.application.Patient.Vitals = rawData.records;
						this.application.Patient.Height = "";
						this.application.Patient.Weight = "";

						// We need to get the most recent vitals needed for BSA into the Patient Object (specifically height/weight) if available.
						var Vitals = this.application.Patient.Vitals,
							vLen = Vitals.length;

						for (i = 0; (i < vLen) && (HaveAllCount > 0); i++) {
							aVital = Vitals[i];
							if ("" === vBSA_Weight && "" !== aVital.BSA_Weight) {
								vBSA_Weight = aVital.BSA_Weight;
								HaveAllCount--;
							}
							if ("" === vWeightFormula && "" !== aVital.WeightFormula) {
								vWeightFormula = aVital.WeightFormula;
								HaveAllCount--;
							}
							if ("" === vBSA_Method && "" !== aVital.BSA_Method) {
								vBSA_Method = aVital.BSA_Method;
								HaveAllCount--;
							}
							if ("" === vBSA && "" !== aVital.BSA) {
								vBSA = aVital.BSA;
								HaveAllCount--;
							}
							if ("" === vHeight && "" !== aVital.Height) {
								vHeight = aVital.Height;
								HaveAllCount--;
							}
							if ("" === vWeight && "" !== aVital.Weight) {
								vWeight = aVital.Weight;
								HaveAllCount--;
							}
						}
						if (HaveAllCount > 0) {
							var errMsg = [];
							if ("" === vBSA_Weight) {
								errMsg.push("BSA Weight");
							}
							if ("" === vBSA_Method) {
								errMsg.push("BSA Method");
							}
							if ("" === vWeightFormula) {
								errMsg.push("BSA Weight Formula");
							}
							if ("" === vBSA) {
								errMsg.push("BSA");
							}
							if ("" === vHeight) {
								errMsg.push("Patient Height");
							}
							if ("" === vWeight) {
								errMsg.push("Patient Weight");
							}
							var errMsgStr = errMsg.join(", ");
						}
						this.application.Patient.Height = vHeight;
						this.application.Patient.Weight = vWeight;
					}
				}

					// MWB - 5/16/2012 - Used to make sure all data sets are loaded before continuing
				this.application.DataLoadCount--;
				this.PatientDataLoadComplete(RetCode);

            },
			failure : function (err, response) {
                wccConsoleLog("Patient Vitals can not be accessed.");
				this.application.DataLoadCount--;
				this.PatientDataLoadComplete(RetCode + " - FAILED Loading");
            }
        });
	},


    /**
     *
     * Gets the current and historical templates applied to the patient from the "Patient_Assigned_Templates", "Master_Template", "EoTS" and "Lookup" tables
     *
     **/
    loadAllTemplatesApplied2Patient : function() {
        // console.log("loadAllTemplatesApplied2Patient Entry Point");
        var phModel = this.getModel("AllTemplatesApplied2Patient");
        var phModelParam = this.application.Patient.id;
        phModel.load(phModelParam, {
            scope : this,
            success : function( AllTemplatesApplied2Patient, response ) {
                this.application.Patient.AllTemplatesApplied2Patient = AllTemplatesApplied2Patient;
                this.application.DataLoadCount--;
                this.PatientDataLoadComplete("All Templates Applied");
                var current = AllTemplatesApplied2Patient.get("current");
                if (current && current[0]) {
                    current = current[0];
                    if (current.TemplateID) {
                        this.LoadSpecifiedTemplate(current.TemplateID);
                    }
                    else {
                        this.application.DataLoadCount--;
                        this.PatientDataLoadComplete("No Current Template Applied to patient to load");
                    }
                }
            },
            failure : function (err, response) {
                // console.log("loadAllTemplatesApplied2Patient - FAILURE");
                this.application.DataLoadCount--;
                this.PatientDataLoadComplete("Templates - Failed to load - " + response.error);
            }
        });
    },




	loadTemplates : function() {
        var phModel = this.getModel("PatientTemplates");
        var phModelParam = this.application.Patient.id;
        phModel.load(phModelParam, {
            scope : this,
            success : function( patientInfo, response ) {
// wccConsoleLog("PatientTemplates Model - Load Complete");
				var rawData = Ext.JSON.decode(response.response.responseText);
                // First take all the data received and put it into a local JSON object for the TPL to process
                wccConsoleLog("Patient Templates Loaded - Processing");

				this.application.Patient.TemplateHistory = rawData.records;


					// MWB - 5/16/2012 - Used to make sure all data sets are loaded before continuing
				this.application.DataLoadCount--;
				this.PatientDataLoadComplete("Templates");


            },
            failure : function (err, response) {
// wccConsoleLog("PatientTemplates Model - Load FAILED - " + response.error);
                wccConsoleLog("PatientHistory store failed to load properly - " + response.error);
				this.application.DataLoadCount--;
				this.PatientDataLoadComplete("Templates - Failed to load - " + response.error);
            }
        });
//
//	List of Templates for patient
//
	},


	getFNRiskInfo : function(FNRisk) {
		var FNLevelInfo = FNRisk < 10 ? "Low Risk" : FNRisk <= 20 ? "Intermediate Risk" : "High Risk";
		var URL = Ext.URLs.MedRisks + "/Type/" + (FNRisk < 10 ? "Neutropenia-1" : FNRisk <= 20 ? "Neutropenia-2" : "Neutropenia-3");
		Ext.Ajax.request({
			scope : this,
			url: URL,
			success: function( response, opts ){
				var text = response.responseText;
				var resp = Ext.JSON.decode( text );
				resp = Ext.util.Format.htmlDecode(resp);
				this.application.Patient.OEMRecords.NeutropeniaRecommendation = resp;
				this.application.Patient.OEMRecords.FNRiskDetails = resp;
				this.application.unMask();
			},
			failure : function( response, opts ) {
				var text = response.responseText;
				var resp = Ext.JSON.decode( text );
				this.application.unMask();
				Ext.MessageBox.alert("Retrieve Error", "Error attempting to retrieve information on Neutropenia Level - " + e.message + "<br />" + resp );
			}
		});
	},


	getEmoLevelInfo : function(ELevel) {
		var eLevel1 = ELevel.split(" ")[0];
		var x = "";
		switch (eLevel1) {
			case "Low":
				x = "Emesis-1";
				break;
			case "Medium":
				x = "Emesis-2";
				break;

			case "Moderate":
				x = "Emesis-3";
				break;
			case "High":
				x = "Emesis-4";
				break;
			case "Very":
				x = "Emesis-5";
				break;
		}

		var URL = Ext.URLs.MedRisks + "/Type/" + x;
		Ext.Ajax.request({
			scope : this,
			url: URL,
			success: function( response, opts ){
				var text = response.responseText;
				var resp = Ext.JSON.decode( text );
				resp = Ext.util.Format.htmlDecode(resp);
				this.application.unMask();
				this.application.Patient.OEMRecords.ELevelRecommendation = resp;
			},
			failure : function( response, opts ) {
				var text = response.responseText;
				var resp = Ext.JSON.decode( text );
				this.application.unMask();
				Ext.MessageBox.alert("Retrieve Error", "Error attempting to retrieve information on Emetogenic Level - " + e.message + "<br />" + resp );
			}
		});
	},
	loadOrderRecords : function( ) {
		var PatientID = this.application.Patient.id;
		var CTOSModel = this.getModel("OEMRecords");		// MWB 21 Feb 2012 - Loading new model for retrieving the records direct from the DB rather than generating them
		CTOSModel.load( PatientID, {
			scope: this,
			success: function (TemplateData, response) {
// wccConsoleLog("OEMRecords Model - Load Complete");
				try {
					wccConsoleLog("Template Data Loaded - Processing");
					var theData = TemplateData.data;
					theData.PatientName = this.application.Patient.name;
					theData.RegimenName = this.application.Patient.TemplateName;
					theData.RegimenDescription = this.application.Patient.TemplateDescription;

					theData.ELevelRecommendationASCO = EmesisRisk[theData.ELevelID].ASCO;
					theData.ELevelRecommendationNCCN = EmesisRisk[theData.ELevelID].NCCN;

					this.application.Patient.OEMRecords = theData;
					this.getEmoLevelInfo(theData.ELevelName);
					this.getFNRiskInfo(theData.FNRisk);



				}
				catch (err) {
					var errMsg1 = "ERROR in parsing data for Template " + this.application.Patient.TemplateName;
					alert("ERROR in Loading Order Entry Management Record Data for Template : " + this.application.Patient.TemplateName);
					wccConsoleLog(errMsg1);
					wccConsoleLog(err.message + " @ Line# " + err.lineNo);
				}

					// MWB - 5/16/2012 - Used to make sure all data sets are loaded before continuing
				this.application.DataLoadCount--;
				this.PatientDataLoadComplete("OEM Records");


			},
			failure: function (err) {
				wccConsoleLog("Template Data failed to load properly");
				// alert("Warning - No Order Information available for Patient " + this.application.Patient.name);
				this.application.DataLoadCount--;
				this.PatientDataLoadComplete("Templates - Failed to load");
			}
		});
	},

    //-------------------------------------------------------------------------
    //
    //	Patient Selected - Phase 1 for this panel
    //	Causes Patient Info
    //	(including Patient History, Laboratory Information and CTOS Tabset) to be displayed
    //
	//	MWB 10 Feb 2012 - Made several minor changes for code cleanup and
	//		sorted the PatientMeasurements for disply of most recent measurements first
	//
	//	MWB 22 Mar 2012 - This is the only point where the Select Event for the Combo Box is trapped.
	//		The end of this event handler fires off a "PatientSelected" event which is intercepted throughout the application
	//		When we change from a Combo Box to an Edit Field to enter Patient ID this should be the only place which needs to get changed.
	//
    PatientSelected : function(combo, recs, eOpts) {
		wccConsoleLog("NewPlanTab - Patient Selected has changed or been refreshed");
		if(null === recs){		// MWB 10 Feb 2012 - If the recs come back as null then something's wrong, exit the function
			return;
		}

		// The recs data comes from either selecting an entry in a combo box (SelectPatient), or by virtue of a query
		// (via either the PatientModelLoadMDWS() or PatientModelLoadSQL()) into the recs array.
		var piData;
		if (recs[0].data) {
			piData = recs[0].data;
		}
		else {
			piData = recs[0];
		}


		this.application.Patient = piData;

		// Get a handle to the frameset itself
        var thisCtl = this.getController("NewPlan.NewPlanTab");
        var fs = thisCtl.getPatientInfo();

        // Update the legend (via the setTitle method) of the Frameset and expand it
        fs.setTitle("<h2>Patient Information for - " + this.application.Patient.name + "</h2>");
        fs.show();
        fs.expand();

        // Display the selected patient's info in the table via it's template
		Ext.ComponentQuery.query("NewPlanTab PatientInfo container[name=\"UpdateMDWSDataContainer\"]")[0].show();
		Ext.ComponentQuery.query("NewPlanTab PatientInfo container[name=\"DisplayMDWSDataContainer\"]")[0].hide();

			// MWB 02 Feb 2012 - Clear out the CTOS Tab when changing the patient
		var piTable = thisCtl.getPatientInfoTable();
        piTable.update("");
		piTable.collapse();

		var piTable1 = thisCtl.getPatientInfoTableInformation();
        piTable1.update("");

		if ("1" === SessionTemplateAuthoring) {
			var CTOSData = thisCtl.getCTOSDataDsp();
			CTOSData.update("");
			CTOSData.hide();
			this.getApplyTemplateBtn().hide();
			this.getEditTemplateBtn().hide();
		}


		this.application.PatientSelectedRecs = recs;
		this.application.PatientSelectedOpts = eOpts;

		this.application.loadMask("Loading Patient Records... For selected patient");
		this.application.DataLoadCount = 8;		// Count of # of modules to load

// wccConsoleLog("Loading Patient Records");
		this.loadMDWSData();					// module 1
		this.loadLabInfo();						// module 2
		this.loadAllergyInfo();					// module 3
		this.loadVitals("Vitals");						// module 4
		this.loadTemplates("Templates");					// module 5
        this.loadAllTemplatesApplied2Patient("PatientSelected");
		this.loadOrderRecords();				// module 6
        if (this.application.Patient.TemplateID) {
            this.LoadSpecifiedTemplate(this.application.Patient.TemplateID);
        }
        else {
            this.application.DataLoadCount--;
            this.PatientDataLoadComplete("No Current Template Applied to patient to load");
        }
    },
    //
    //
    //	END Patient Selected
    //
    //-------------------------------------------------------------------------




	UpdateOEMRecords : function(aRec, bRec) {
		try {
			var oemEditRec = {
				"TemplateID" : this.application.Patient.OEMRecords.id,
				"OEMRecordID" : aRec.id,
				"Order_ID" : bRec.Order_ID,
				"TherapyID" : bRec.id,
				"TherapyType" : "Therapy",		// Because we're only looking at the aRec.Therapy array (first if() of the initial for(a = 0; a < oRecLen; a++) loop above
				"Instructions" : bRec.Instructions,
				"AdminTime" : bRec.AdminTime,
				"MedID" : bRec.MedID,
				"Med" : bRec.Med,
				"Reason" : (bRec.Reason || ""),		// This variable may not be set initially
				"Dose" : bRec.Dose,
				"BSA_Dose" : bRec.BSA_Dose,
				"Units" : bRec.DoseUnits,
				"InfusionMethod" : bRec.AdminMethod,
				"FluidType" : bRec.FluidType,
				"FluidVol" : bRec.FluidVol,
				"FlowRate" : bRec.FlowRate,
				"InfusionTime" : bRec.InfusionTime,
					// These variables aren't needed for a Therapy record as there's no "optional" dosing allowed for a "Therapy", only for Pre/Post.
				"Dose2" : "",
				"BSA_Dose2" : "",
				"Units2" : "",
				"InfusionMethod2" : "",
				"FluidType2" : "",
				"FluidVol2" : "",
				"FlowRate2" : "",
				"InfusionTime2" : ""
			};

			var oemRec = Ext.create(Ext.COMSModels.Edit_OEMRecord, oemEditRec);		// Create an instance of this model with the data specified
			oemRec.save();
		}
		catch (ee) {
			var ErrorObj = ee;
			var errMsg = "";
			var o;
			for (o in ee) {
				if (ee.hasOwnProperty(o)) {
					errMsg += o + "\n";
				}
			}
			alert("Error - Saving updated OEM Record in NewPlan Controller - " + ee.message + "\n" + errMsg );
		}
	},

	reAddHandlers : function() {
		// console.log("Re-Assigning event handlers in 30 seconds");
		Ext.Function.defer( this.AssignBtnHandlers, 30000, this );
	},

	buildTemplateInfo : function(thisCtl, Patient, comeFrom) {
        var TemplateInfo, 
            patientTemplates = thisCtl.getPatientTemplates(),
            currentTemplates = this.application.Patient.CurrentTemplatesApplied2Patient,
            historicalTemplates = this.application.Patient.HistoricalTemplatesApplied2Patient,
            numRecords = 0;
        
        if (currentTemplates) {
            numRecords += currentTemplates.length;
        }
        if (historicalTemplates) {
            numRecords += historicalTemplates.length;
        }

        TemplateInfo = {};
        TemplateInfo.Historical = this.application.Patient.HistoricalTemplatesApplied2Patient;
        TemplateInfo.Current = this.application.Patient.CurrentTemplatesApplied2Patient;

        // Render # of templates for initial display of the panel - MWB - 11/11/2013
        patientTemplates.update( TemplateInfo );

        var strRecs = "No Records Available";
        if (1 === numRecords ) {
            strRecs = numRecords + " Record";
        }
        else if (numRecords > 1) {
            strRecs = numRecords + " Records";
        }
        this.resetTRSPanel(thisCtl, strRecs);
        return patientTemplates;
	},

	PatientDataLoadComplete : function(Loaded) {
		wccConsoleLog("PatientDataLoadComplete");
        // console.log("PatientDataLoadComplete");
		var DataLoadCount = this.application.DataLoadCount;
		var thisCtl = this.getController("NewPlan.NewPlanTab");
		var Patient = this.application.Patient;
		var piTableInfo, patientTemplates, dspVSHTemplateData, VSHTemplateDataBtns;

        if ("All Templates Applied" === Loaded) {
            var historical = this.application.Patient.AllTemplatesApplied2Patient.get("historical"),
                current = this.application.Patient.AllTemplatesApplied2Patient.get("current");
            this.application.Patient.CurrentTemplatesApplied2Patient = current;
            this.application.Patient.HistoricalTemplatesApplied2Patient = historical;
            // this.application.Patient.History = historical; "History" is really LabResults
            if (current && current[0]) {
                current = current[0];
                // Needed for the 
                if (!this.application.Patient.AppliedTemplate) {
                    this.application.Patient.AppliedTemplate = {};
                }
                this.application.Patient.AppliedTemplate.id = current.TemplateID; 
                this.application.Patient.AppliedTemplate.Description = current.TemplateDescription;
                this.application.Patient.AppliedTemplate.Name = current.TemplateName;

                this.application.Patient.AppliedTemplateID = current.TemplateID;
                this.application.Patient.TemplateDescription = current.TemplateDescription;
                this.application.Patient.TemplateName = current.TemplateName;
                this.application.Patient.TemplateID = current.TemplateID;
                this.application.Patient.TreatmentStart = current.DateStarted;
                this.application.Patient.TreatmentEnd = current.DateEnded;
            }
            patientTemplates = this.buildTemplateInfo(thisCtl, Patient, "PatientDataLoadComplete Update Templates Loaded");
			COMS.Patient = this.application.Patient;
        }





		if ("Update BSA" === Loaded) {
			piTableInfo = thisCtl.getPatientInfoTableInformation();
			piTableInfo.update(Patient);
			// console.log("Update BSA process complete, assign button handlers in 2 sec");
			Ext.Function.defer( this.AssignBtnHandlers, 2000, this );
			return;
		}

		if ("Update Vitals" === Loaded) {
			var ND_VitalSignsHistory = Ext.ComponentQuery.query("NursingDocs_GenInfo fieldset[title=\"Vital Signs - Historical\"] VitalSignsHistory")[0];
			if (ND_VitalSignsHistory) {
				ND_VitalSignsHistory.update(Patient);
			}

			PatientHistoryVitalStats = thisCtl.getVitalSigns();
			PatientHistoryVitalStats.update(Patient);

			PatientHistory = thisCtl.getPatientHistory();

			tmp = "No Records Available";
			if (Patient.Vitals) {
			len = Patient.Vitals.length;
				if (len > 0) {
				tmp = len + " Record";
				tmp += (1 === len) ? "" : "s";
				}
			}
//			PatientHistory.setTitle("Patient Vitals <span class='LabInfoTitleInfo' style='margin-left: 3em; font-size: smaller;'>(" + tmp + ")</span>");
			this.resetVitalsPanel(thisCtl, tmp);

			// MWB - 6/27/2012 - Handle the OnClick Event for the BSA Button in the Vital Signs table
			dspVSHTemplateData = this.getVitalSigns();
			VSHTemplateDataBtns = dspVSHTemplateData.el.select("button.dspVSHDoseCalcs");
			VSHTemplateDataBtns.on("click", this.HandleVSHCalcDoseButtons, this);

			dspVSHTemplateData = this.getNDGI_VitalSigns();
			if (dspVSHTemplateData && dspVSHTemplateData.rendered) {		// Make sure the Vital Signs in the ND/GenInfo tab are rendered before trying to attach.
				VSHTemplateDataBtns = dspVSHTemplateData.el.select("button.dspVSHDoseCalcs");
				VSHTemplateDataBtns.on("click", this.HandleVSHCalcDoseButtons, this);
			}

			COMS.Patient = this.application.Patient;
			return;
		}

//		if ("Update Templates" === Loaded) {
//			var patientTemplates = this.buildTemplateInfo(thisCtl, Patient, "PatientDataLoadComplete Update Templates Loaded");
//			patientTemplates.show();
//			return;
//		}


		wccConsoleLog("DataLoadCount - " + DataLoadCount + " - " + Loaded);
        // console.log("DataLoadCount - " + DataLoadCount + " - " + Loaded);
		if (DataLoadCount <= 0) {		// All remote data for this patient has been loaded
			var len, tmp;
			var piTable;
			var LaboratoryInfo;
			var PatientHistoryVitalStats, PatientHistory;

			// MWB - 5/29/2012 - If today is the day the treatment starts, then it should have a status of "Template Applied"
			// NOT "On-Going - Admin Day"
			if (Ext.Date.isEqual(new Date(Patient.TreatmentStart), new Date(new Date().toDateString()))) {
				// However, we should distingush between an Admin Day and a Rest Day
				var PostStatus = " - Rest Day";
				if (Patient.TreatmentStatus.search("Admin Day") >= 0) {
					PostStatus = " - Admin Day";
				}
				Patient.TreatmentStatus = "Template Applied" + PostStatus;
			}

			this.application.unMask();
			this.getCTOS().show();

			thisCtl.getPatientInfo().expand();		// MWB - 22 Feb 2012 TESTING

			piTable = thisCtl.getPatientInfoTable();	// MWB - 10 Feb 2012 - This is the overall container for Patient Info, it contains everything...
			piTable.show();

				// Allergies
			piTableInfo = thisCtl.getPatientInfoTableInformation();
			piTableInfo.update(Patient);

			var HTML = piTableInfo.tpl.apply(Patient);

			piTableInfo.show();

				// Lab Info - Store is loaded directly into a Grid
			LaboratoryInfo = thisCtl.getLaboratoryInfo();
			tmp = "No Records Available";

			/***************** THERE'S A BUG in the LAB INFO RETRIEVAL DUE TO INCONSISTENT DATA STRUCTURE IN MDWS !!! - MWB - 5/31/2012 ***/
			// LAB INFO IS SOMETIMES RETURNED AS STRAIGHT DATA, OTHER TIMES AS AN ARRAY OTHER TIMES AS AN ARRAY WITHIN AN ARRAY, ETC
			if (Patient.History) {
			len = Patient.History.length;
				if (len > 0) {
				tmp = len + " Record";
				tmp += (1 === len) ? "" : "s";
				}
			}
			// LaboratoryInfo.setTitle("Laboratory Information<span class='LabInfoTitleInfo' style='margin-left: 3em; font-size: smaller;'>(" + tmp + ")</span>");
			this.resetLabInfoPanelPanel(thisCtl, tmp);


				// History (e.g. Vitals)
			PatientHistoryVitalStats = thisCtl.getVitalSigns();
			PatientHistoryVitalStats.update(Patient);

			PatientHistory = thisCtl.getPatientHistory();

			tmp = "No Records Available";
			if (Patient.Vitals) {
			len = Patient.Vitals.length;
				if (len > 0) {
				tmp = len + " Record";
				tmp += (1 === len) ? "" : "s";
				}
			}
			// PatientHistory.setTitle("Patient Vitals <span class='LabInfoTitleInfo' style='margin-left: 3em; font-size: smaller;'>(" + tmp + ")</span>");
			this.resetVitalsPanel(thisCtl, tmp);

			// MWB - 6/27/2012 - Handle the OnClick Event for the BSA Button in the Vital Signs table
			dspVSHTemplateData = this.getVitalSigns();
			VSHTemplateDataBtns = dspVSHTemplateData.el.select("button.dspVSHDoseCalcs");
			VSHTemplateDataBtns.on("click", this.HandleVSHCalcDoseButtons, this);

			dspVSHTemplateData = this.getNDGI_VitalSigns();
			if (dspVSHTemplateData && dspVSHTemplateData.rendered) {		// Make sure the Vital Signs in the ND/GenInfo tab are rendered before trying to attach.
				VSHTemplateDataBtns = dspVSHTemplateData.el.select("button.dspVSHDoseCalcs");
				VSHTemplateDataBtns.on("click", this.HandleVSHCalcDoseButtons, this);
			}

			patientTemplates = this.buildTemplateInfo(thisCtl, Patient, "PatientDataLoadComplete AND DataLoadCount < 0");
			patientTemplates.show();

			// If BSA_Dose is empty then calculate it for each record and save that record back.
			// BUT we need to calculate the BSA value and BSA_Weight before we load the records...
			// Then walk through theData.OEMRecords;
			var a, b, c, aRec, bRec, bRecUnits, calcDose, updateRecord = false, tmpDose, Dose, Units,
				theRecords, oRecLen,
				tRecords, oTherapyLen;

			if (this.application.Patient && this.application.Patient.OEMRecords && this.application.Patient.OEMRecords.OEMRecords) {
				theRecords = this.application.Patient.OEMRecords.OEMRecords;
				oRecLen = theRecords.length;
				for (a = 0; a < oRecLen; a++) {
					aRec = theRecords[a];
					if (aRec.Therapy) {
						oTherapyLen = aRec.Therapy.length;
						for (b = 0; b < oTherapyLen; b++) {
							bRec = aRec.Therapy[b];
							bRecUnits = bRec.DoseUnits.toUpperCase();
							calcDose = false;

							if (bRecUnits.search("M2") > 0 || bRecUnits.search("KG") > 0 || bRecUnits.search("AUC") >= 0 ) {
								calcDose = true;
							}

							if (calcDose) {
								if ("" === bRec.BSA_Dose || "NaN mg" === bRec.BSA_Dose) {
									// wccConsoleLog("No Dose for " + bRec.Med + " - " + bRec.Dose + " " + bRec.DoseUnits);

									if (bRecUnits.search("M2") > 0) {
										Dose = bRec.Dose * Patient.BSA;
										Dose = Ext.GeneralRounding2Digits(Dose);
										Units = bRec.DoseUnits.substr(0, bRecUnits.search("/"));
										bRec.BSA_Dose = Dose + " " + Units;
										updateRecord = true;
									}
									else if	(bRecUnits.search("KG") > 0) {
										Dose = bRec.Dose * Patient.BSA_Weight;
										Dose = Ext.GeneralRounding2Digits(Dose);
										Units = bRec.DoseUnits.substr(0, bRecUnits.search("/"));
										bRec.BSA_Dose = Dose + " " + Units;
										updateRecord = true;
									}
									else if (bRecUnits.search("AUC") >= 0) {
										Dose = Ext.CalcAUCDose(Patient, bRec.Dose);
										bRec.BSA_Dose = Dose;
										updateRecord = true;
									}
								}
								else {
									// MWB - 7/12/2012 - Fix to update Dosage Calculations every time patient info is loaded.
									// DO NOT IMPLEMENT until further notice...
									// Implement as per SIC's e-mail - 7/12/2012 08:56 AM
									/**********************************************/
									if (bRecUnits.search("M2") > 0) {
										Dose = bRec.Dose * Patient.BSA;
										Dose = Ext.GeneralRounding2Digits(Dose);
										Units = bRec.DoseUnits.substr(0, bRecUnits.search("/"));
										tmpDose = Dose + " " + Units;
										if (tmpDose != bRec.BSA_Dose) {
											bRec.BSA_Dose = tmpDose;
											updateRecord = true;
										}
									}
									else if	(bRecUnits.search("KG") > 0) {
										Dose = bRec.Dose * Patient.BSA_Weight;
										Dose = Ext.GeneralRounding2Digits(Dose);
										Units = bRec.DoseUnits.substr(0, bRecUnits.search("/"));
										tmpDose = Dose + " " + Units;
										if (tmpDose != bRec.BSA_Dose) {
											bRec.BSA_Dose = tmpDose;
											updateRecord = true;
										}
									}
									else if (bRecUnits.search("AUC") >= 0) {
										Dose = Ext.CalcAUCDose(Patient, bRec.Dose);
										tmpDose = Dose;
										if (tmpDose != bRec.BSA_Dose) {
											bRec.BSA_Dose = tmpDose;
											updateRecord = true;
										}
									}
									/***********************************************/
								}
							}
							if (updateRecord) {
							this.UpdateOEMRecords(aRec, bRec);
							}
						}
					}
					else {
						oTherapyLen = aRec.Therapy.length;
						for (b = 0; b < oTherapyLen; b++) {
							bRec = aRec.Therapy[b];
							this.UpdateOEMRecords(aRec, bRec);
						}
					}
				}
			}
			// Order Entry Records - No functions at this time.
			// console.log("Assigning button handlers in 2 seconds");
			Ext.Function.defer( this.AssignBtnHandlers, 2000, this );

			// MWB - 7/1/2012 Should this process be called here or in the AssignBtnHandlers() which is where it was originally????
			// Let other controllers know that this event has occurred
			this.application.fireEvent("PatientSelected", this.application.PatientSelectedRecs, this.application.PatientSelectedOpts);	// MWB 10 Feb 2012 - Added additional parameters
		}
	},


	HandleVSHCalcDoseButtons : function( event, element ) {
	/******* Button definition in view\Common\VitalSignsHistory.js
				return ("<button class=\"anchor dspVSHDoseCalcs\" name=\"dspVSHDoseCalcs\" title=\"Show Dosage Calculation\" " +
					"weight=\"" + data.Weight + "\" " +
					"height=\"" + data.Height + "\" " +
					"weightFormula=\"" + data.WeightFormula + "\" " +
					"bsa_Weight=\"" + data.BSA_Weight + "\" " +
					"bsa_Method=\"" + data.BSA_Method + "\" " +
				">" + data.BSA + "</button> m<sup>2</sup>");
	********/
		var btnTitle = element.getAttribute("title");
		if ("Show Dosage Calculation" === btnTitle) {
			var Patient = this.application.Patient;
			var params = {};
			params.Weight = element.getAttribute("weight");
			params.Height = element.getAttribute("height");
			params.WeightFormula = element.getAttribute("weightFormula");
			params.BSA_Weight = element.getAttribute("bsa_Weight");
			params.BSA_Method = element.getAttribute("bsa_Method");
			params.Gender = Patient.Gender;
			params.Amputations = Patient.Amputations;

			params.BSA = Ext.BSA_Calc(params);


			var PatientData = Ext.ShowBSACalcs(params, false, null, null);

			Ext.MessageBox.show({
				title : "Body Surface Area Calculations",
				msg : PatientData,
				buttons : Ext.MessageBox.OK
			});
		}
	},



























	AssignBtnHandlers : function() {
		try {
			var thisCtl = this.getController("NewPlan.NewPlanTab");
			var Patient = this.application.Patient;
			Ext.Patient = this.application.Patient;		// MWB - 5/30/2012 - Need this so that the Patient Info can be accessed within xTemplates

			var patientTemplates = thisCtl.getPatientTemplates();
			var piTableInfo = thisCtl.getPatientInfoTableInformation();

			var btns1 = patientTemplates.el.select("button");
			var btns2 = piTableInfo.el.select("button");

			btns1.removeAllListeners();
			btns2.removeAllListeners();

			btns1.on("click", this.HandleTemplateBtnClicks, this);
			btns2.on("click", this.HandleAnchorClicks, this);
		}
		catch (e) {
			wccConsoleLog("Error in AssignBtnHandlers");
		}
		Ext.Function.defer( this.reAddHandlers, 3000, this );
	},


    //-------------------------------------------------------------------------
    //
    //	Template Source (National/Local/Personal) Selected - Phase 1 of the CTOS Tab
    //
    //
    onTemplateTypeChange : function(combo, recs, eOpts) {
        wccConsoleLog("Select Template Type");
        this.application.Patient.TemplateType = recs[0].data;
        var thisCtl = this.getController("NewPlan.NewPlanTab");
        var obj = thisCtl.getDiseaseAndStage();
        obj.show();
        this.getResetButton().show();
    },

	//
    //
    //	END Template Source Selected
    //
    //-------------------------------------------------------------------------


    //-------------------------------------------------------------------------
    //
    //	Disease Type Selected - Phase 2 of the CTOS Tab
    //
    //
    DiseaseSelected : function(combo, recs, eOpts) {
		debugger;
        wccConsoleLog("Disease Type has been selected");

        if(this.application.Patient.Disease != recs[0].data){
            this.application.ResetClicked=false;
        }

        this.application.Patient.Disease = recs[0].data;

        var obj = this.getTemplate();	// MWB 19 Jan 2012 - Added per customer request to not require selecting Disease Stage before displaying list of templates
        obj.show();
        // this.getResetButton().show();

    },
    //-------------------------------------------------------------------------
    //
    //	Disease Stage Selected - Phase 2 of the CTOS Tab
    //
    //
    onDiseaseStageChange : function(combo, recs, eOpts) {
        wccConsoleLog("Disease Type and Stage has been selected");

        this.application.Patient.DiseaseStage = recs[0].data;

        combo.hiddenValue = recs[0].data.name;

        var thisCtl = this.getController("NewPlan.NewPlanTab");
        var obj = thisCtl.getTemplate();
        obj.show();
        // this.getResetButton().show();
    },



	/**************************************************
	 *
	 *	MWB 30 Jan 2012 - Modified to break out the Loading of the Template from the Select Tag
	 *	This was done to make use of the loading function by the "HandleClickEvents" function above
	 *
	 **************************************************/
// Load the selected template - Called when user clicks on the "Show Template" in the Patient Info Table via the "HandleAnchorClicks - PatientInfoTable!" function above.
// This template is one which is currently applied to the patient.
	CTOS_DataLoad2 : function(TemplateID) {
	        this.application.loadMask("CTOS DataLoad2"); // MWB 19 Jan 2012 - Mask the screen
			var CTOSModel = this.getModel("CTOS");
			var CTOSModelParam = TemplateID;

			wccConsoleLog("Template Params = " + CTOSModelParam);

			this.clearCTOS();


	        CTOSModel.load(CTOSModelParam, {
				scope: this,
				success: function (CTOSTemplateData, response) {
					wccConsoleLog("CTOS Loaded - Processing");
					this.application.Patient.AppliedTemplateID = TemplateID;

					var thisCtl = this.getController("NewPlan.NewPlanTab");
					var CTOSData = thisCtl.getCTOSDataDsp();

// MEB - 6/7/2012 - Need to add Template Timing info to the data object
					CTOSTemplateData.data.ELevelRecommendation = CTOSTemplateData.data.ELevel[0].details;
					CTOSData.update(CTOSTemplateData.data);
					this.getDisease().setValue(CTOSTemplateData.data.Disease);
					this.loadCombo(this.getDiseaseStage(),CTOSTemplateData.data.Disease);

					if(this.getDiseaseStage().getStore().count()==0){
						this.loadCombo(this.getDiseaseStage(),this.getDisease().getValue());
					}

					this.getDiseaseStage().setValue(CTOSTemplateData.data.DiseaseStage[0].name);

					CTOSData.show();
			if ("1" === SessionTemplateAuthoring) {
					this.getApplyTemplateBtn().disable();	// Template is already applied to patient
					this.getApplyTemplateBtn().hide();	// so no need to have it available.
					this.getEditTemplateBtn().show();
			}
					this.application.CurrentTemplate = CTOSData;	// MWB - 5/21/2012 - Hang onto the current template data for use in calculating the proper end date when applying the template.
					this.application.unMask(); // MWB 19 Jan 2012 - Unmask the screen

					wccConsoleLog("CTOS Loaded - Rendering complete");
				},
				failure: function (err) {
					wccConsoleLog("CTOS Data failed to load properly");
					var thisCtl = this.getController("NewPlan.NewPlanTab");
					var CTOSData = thisCtl.getCTOSDataDsp();
					CTOSData.update("<h2 class='errMsg'>No information available for Template " + this.application.Patient.Template.name + "</h2>");
					Ext.MessageBox.alert("Template Load Error", "Unknown Error in loading Template " + this.application.Patient.Template.name + " - CTOS_DataLoad2" );

					this.application.unMask();	// MWB 19 Jan 2012 - Unmask the screen
				}
	        });
	},




// Load the selected template - This is done by browsing through the available templates and selecting one from the drop down.
	CTOS_DataLoad : function(TemplateID) {
        this.application.loadMask("Loading Selected Template");	// MWB 19 Jan 2012 - Mask the screen

        var CTOSModel = this.getModel("CTOS");
        var CTOSModelParam = TemplateID;
        wccConsoleLog("Template Params = " + CTOSModelParam );

        CTOSModel.load(CTOSModelParam, {
            scope : this,
            success : function( CTOSTemplateData, response ) {
                wccConsoleLog("CTOS Loaded - Processing");
                var thisCtl = this.getController("NewPlan.NewPlanTab");
                var CTOSData = thisCtl.getCTOSDataDsp();


				CTOSTemplateData.data.ELevelRecommendation = CTOSTemplateData.data.ELevel[0].details;
                CTOSData.update( CTOSTemplateData.data );

                this.getDisease().setValue(CTOSTemplateData.data.Disease);

                if(Ext.ComponentQuery.query('NewPlanTab fieldcontainer radiofield[name=\"NewPlan_What2Do\"]')[0].getValue()){
                    this.loadCombo(this.getDiseaseStage(),CTOSTemplateData.data.Disease);
                }

                if(this.getDiseaseStage().getStore().count()==0){
                    this.loadCombo(this.getDiseaseStage(),this.getDisease().getValue());
                }

                this.getDiseaseStage().setValue(CTOSTemplateData.data.DiseaseStage[0].name);
                if(CTOSData.hidden==true){
                    CTOSData.show();
                }

                var patientAppliedTemplates = Ext.ComponentQuery.query('NewPlanTab fieldcontainer radiofield[name=\"NewPlan_What2Do\"]')[0];

				if ("1" === SessionTemplateAuthoring) {
	                if(patientAppliedTemplates.getValue()){
						this.getApplyTemplateBtn().disable();
	                }else{
						this.getApplyTemplateBtn().enable();
					}
					this.getApplyTemplateBtn().show();
					this.getEditTemplateBtn().show();
                }
				this.application.CurrentTemplate = CTOSData;	// MWB - 5/21/2012 - Hang onto the current template data for use in calculating the proper end date when applying the template.
				this.application.unMask();	// MWB 19 Jan 2012 - Unmask the screen

            },
            failure : function (err, response) {
                wccConsoleLog("Laboratory Info failed to load properly");
                var thisCtl = this.getController("NewPlan.NewPlanTab");
                var CTOSData = thisCtl.getCTOSDataDsp();
                CTOSData.update( "<h2 class='errMsg'>No information available for Template " + this.application.Patient.Template.name + "</h2>" );
				this.application.unMask();	// MWB 19 Jan 2012 - Unmask the screen
            }
        });
	},



	LoadSpecifiedTemplate : function(TemplateID) {
			var CTOSModel = this.getModel("CTOS");
			var CTOSModelParam = TemplateID;
			this.clearCTOS();
	        CTOSModel.load(CTOSModelParam, {
				scope: this,
				success: function (CTOSTemplateData, response) {
					this.application.Patient.AppliedTemplateID = TemplateID;
					CTOSTemplateData.data.ELevelRecommendation = CTOSTemplateData.data.ELevel[0].details;
					this.application.Patient.AppliedTemplate = CTOSTemplateData.data;
					this.application.DataLoadCount--;
					this.PatientDataLoadComplete("Current Applied Template Loaded");

				},
	            failure : function (err, response) {
					this.application.DataLoadCount--;
					this.PatientDataLoadComplete("Current Applied Template - Failed to load - " + response.error);
					Ext.MessageBox.alert("Loading Template Error", "NewPlanTab - Current Applied Template - Failed to load - " + response.error);
				}
	        });
	},

	selTemplateChange : function(combo, recs, eOpts) {
		wccConsoleLog("Template has been selected");

		this.application.Patient.Template = recs[0].data;
		combo.hiddenValue = this.application.Patient.Template.description;
		this.CTOS_DataLoad(this.application.Patient.Template.id);

	},


	SaveBSAInfo : function() {	// Used to update the BSA if it's recalculated
		var Patient = this.application.Patient;
		var ThisAdminDay = this.application.Patient.ThisAdminDay;		// This is the OEM Record for a specific Admin Day -
		// { id, AdminDate, Cycle, Day, PostTherapy, PostTherapyInstr, PreTherapy, PreTherapyInstr, Therapy, TherapyInstr }

		var dt = new Date();
		var record = {};
		if (ThisAdminDay) {
			record.Cycle = ThisAdminDay.Cycle;
			record.Day = ThisAdminDay.Day;
		}
		else {	// This is NOT an AdminDay for this Regimen
			record.Cycle = "";
			record.Day = "";
		}
		record.PatientID = Patient.id;
		record.DateTaken = Ext.Date.format(dt, "m/d/Y H:i:s");
		record.Height = String(Patient.Height);
		record.Weight = String(Patient.Weight);
		record.BSA = String(Patient.BSA);
		record.WeightFormula = Patient.WeightFormula;
		record.BSA_Method = Patient.BSA_Method;
		record.BSA_Weight = Patient.BSA_Weight;


		record.BP = "";
		record.Systolic = "";
		record.Diastolic = "";
		record.Temperature = "";
		record.Pulse = "";
		record.Respiration = "";
		record.Pain = 0;
		record.SPO2 = "";


		record.DateTaken = Ext.Date.format(dt, "m/d/Y H:i:s");

		record.Height = String(Patient.Height);
		record.Weight = String(Patient.Weight);
		record.WeightFormula = Patient.WeightFormula;
		record.BSA_Method = Patient.BSA_Method;
		record.BSA_Weight = Patient.BSA_Weight;
		record.BSA = String(Patient.BSA);

		record.BP = "";
		record.Diastolic = 0;
		record.Systolic = 0;

		record.Cycle = "";
		record.Day = "";
		record.Pain = null;
		record.Pulse = 0;
		record.Respiration = "";
		record.SPO2 = "";
		record.Temperature = "";
		record.PatientID = Patient.id;

















		var params = Ext.encode(record);

		Ext.Ajax.request({
			url: Ext.URLs.AddVitals,
			method : "POST",
			jsonData : params,
			success: function( response, opts ){
				var text = response.responseText;
				var resp = Ext.JSON.decode( text );

				if (!resp.success) {
					Ext.MessageBox.alert("Saving Error", "ND - GenInfo - Vitals Information Section, Save Error - " + resp.msg );
				}
			},
			failure : function( response, opts ) {
				var text = response.responseText;
				var resp = Ext.JSON.decode( text );
				Ext.MessageBox.alert("Saving Error", "ND - GenInfo - Vitals Information Section, Save Error - " + "e.message" + "<br />" + resp.msg );
			}
		});
		return (true);
	}


	/**************************************************
	 *
	 *	MWB 30 Jan 2012 - End of changes
	 *
	 **************************************************/

});

