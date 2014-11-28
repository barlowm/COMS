Ext.define('COMS.store.InventoryConsumption', {
				extend : 'Ext.data.Store',
				fields:["ID", "iReport_ID", "Drug", "Total", "Unit", "StartDate"],
				proxy: {
					type: 'rest',
					url : "/Reports/Inventory",
					reader: {
						type: 'json',
						root : 'records'
					}
				}
			});