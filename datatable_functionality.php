<?php	include_once 'includes/mainHeader.php'; ?>
<script src="../global_assets/js/plugins/forms/selects/bootstrap_multiselect.js"></script>
<script type="text/javascript" src="https://momentjs.com/downloads/moment.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
<script src="../global_assets/js/plugins/tables/datatables/datatables.min.js"></script>
<style type="text/css">
	.card{
		margin-bottom: 0.25rem !important
	}
	.travel_date_range{
		width: 100%;
	}
	.travel_date_range > span {
/*		font-size: 10px;*/
	}

	.dataTables_info,.dataTables_paginate {
		margin-top: 15px;
	}

	/*table {
		font-size: 11px !important;
	}*/

	.travel_pending_table thead {
		background: #A294F9;
	}

	.travel_pending_table tfoot {
		background:#CDC1FF;
	}

	.travel_pending_table tfoot tr th{
		font-weight: bold;
	}

	small {
		font-size: 12px;
	}
</style>
	<!-- Page content -->
	<div class="page-content">
    <?php	include_once 'includes/sideNav.php'; ?>

		<!-- Main content -->
		<div class="content-wrapper">

			<!-- Inner content -->
			<div class="content-inner">
				<!-- Content area -->
				<div class="content">
							<form id="reportFilter" autocomplete="false" method="post" action="genReceiptTrackingReport.php">
								<div class="row">
									<div class="col-xl-12 col-lg-12 col-md-12 col-sm-12">
										<div class="card">
											<div class="card-header bg-info">
												<h7>
													<a data-toggle="collapse" class="text-white card-title" href="#collapsible-styled-group3">Travel Details Report</a>
												</h7>
											</div>
											<div id="collapsible-styled-group3" class="collapse show">
												<div class="card-body">
													<div class="row filterby_emp_details emp_details_filter">

														<div class="col-xl-4 col-lg-4 col-md-4 col-sm-12">
															<div class="form-group">
						                                        <input type="hidden" name="tr_from_date" id="tr_from_date"
					                                                value="2024-11-20">
					                                            <input type="hidden" name="tr_to_date" id="tr_to_date"
					                                                value="<?=date('Y-m-t')?>">
					                                            <div class="form-group">
					                                                <label class="d-block">Travel from-to date : <small class="text-danger">( Punched report details are available from the 20-11-2024 )</small></label>

					                                                <button type="button" class="btn btn-sm btn-warning travel_date_range">
					                                                    <i class="icon-calendar22 mr-2"></i>
					                                                    <span></span>
					                                                </button>
					                                            </div>
															</div>
														</div>
														<div class="col-xl-2 col-lg-2 col-md-2 col-sm-12">
															<div class="form-group">
																<label class="d-block">Business division:</label>
																<select class="form-control form-control-sm multiselect business_division" multiple="multiple" data-button-class="btn btn-sm" data-fouc name="business_division[]">
																</select>
															</div>
														</div>
														
														
														<div class="col-xl-2 col-lg-2 col-md-2 col-sm-12">
															<div class="form-group">
																<label class="d-block">Department:</label>
																<select class="form-control form-control-sm multiselect Department" multiple="multiple" data-button-class="btn btn-sm" data-fouc name="Department[]">
																
																</select>
															</div>
														</div>
														<div class="col-xl-2 col-lg-2 col-md-2 col-sm-12">
															<div class="form-group">
																<label class="d-block">Designation:</label>
																<select class="form-control form-control-sm multiselect Designation" multiple="multiple" data-button-class="btn btn-sm" data-fouc name="Designation[]">
																
																</select>
															</div>
														</div>
														<div class="col-xl-1 col-lg-2 col-md-2 col-sm-2 text-center mt-auto mb-auto">
															<button type="button" class="btn btn-success btn-sm filter_btn">Submit</button>
															<!-- <button type="reset" class="btn btn-danger btn-sm">Reset</button> -->
														</div>
													</div>




													<!-- table data -->

														<div class="row">
															<div class="col-lg-12">
																<div class="card">
															
																	<div class="card-body">
																		<ul class="nav nav-tabs nav-tabs-highlight">
																			<li class="nav-item tabNav pending-tab" data-tbl="pending"><a href="#pill-badges-tab1"
																					class="nav-link secondary active" data-toggle="tab">
																					Consolidated Report</a>
																			</li>

																		</ul>
																		<div class="tab-content">
																			<div class="tab-pane fade show active" id="pill-badges-tab1">
																				<table class="table table-bordered travel_pending_table" data-loaded="no" style="font-size: 10px;width: 100%;"> 
																					<thead class="text-white">
																						<tr>
																							<th>Division</th>
																							<th>Punched Count</th>
																							<th>Punched (%)</th>
																							<th>Not Punched Count</th>
																							<th>Not Punched (%)</th>
																							<th>Grand Total</th>
																						</tr>
																					</thead>
																					<tfoot>
	        																				<tr>
	            																				<th>Grand Total</th>
	            																				<th></th>
	            																				<th></th>
	            																				<th></th>
	            																				<th></th>
	            																				<th></th>
	        																				</tr>
    																				</tfoot>
																				</table>
																			</div>

																		</div>
																	</div>
																</div>
															</div>
														</div>


												</div>
											</div>
										</div>
									</div>
								</div>

							</form>



				</div>
			</div>
			<!-- /inner content -->

		</div>
		<!-- /main content -->

	</div>
	<!-- /page content -->

<?php include_once('includes/footer.php') ?>
<script type="text/javascript">
	$(document).ready(function() {
		
		$('.multiselect').multiselect({
			includeSelectAllOption: true,
			enableFiltering: true,
            enableCaseInsensitiveFiltering: true
		});

		var fromDate = $("#tr_from_date").val();
		var toDate = $("#tr_to_date").val();
		$('.travel_date_range span').html(moment(fromDate).format('DD-MMM-YYYY') + ' &nbsp; - &nbsp; ' + moment(toDate).format('DD-MMM-YYYY'));
		var endofLastMonth =  moment(fromDate).subtract(1,'months').endOf('month');
		var startOfLastMonth = moment(endofLastMonth).startOf('month');
		
		var travel_date_rangeOpt = {
        		showDropdowns: true,
                parentEl: '.content-inner',
                alwaysShowCalendars: true,
                locale: {
                    format: 'DD-MMM-YYYY'
                },
                ranges:{
                    'This month': [moment(fromDate), moment(toDate)],
                    'Last month': [startOfLastMonth, endofLastMonth],
                    'Last three month': [moment(startOfLastMonth).subtract(2, 'months'), endofLastMonth],
                    'Last six months': [moment(startOfLastMonth).subtract(5, 'months'), endofLastMonth],
                    'Last one year': [moment(startOfLastMonth).subtract(11, 'months'), endofLastMonth]
                }
            }

            $('.travel_date_range').daterangepicker(travel_date_rangeOpt,
		        function (start, end) {
		            $('.travel_date_range span').html(start.format('DD-MMM-YYYY') + ' &nbsp; - &nbsp; ' + end.format('DD-MMM-YYYY'));
		            $("#tr_from_date").val(start.format('YYYY-MM-DD'));
		            $("#tr_to_date").val(end.format('YYYY-MM-DD'));
		        }
		    );





		getBusinessDivision();
		$("body").on("change",".business_division",function(){
			getDepartment();
		});
		$("body").on("change",".Department",function(){
			getDesignation();
		});

	

	});


		function getBusinessDivision(){
			$.ajax({
				url: '../api/travel_detail_filters.php',
				type: 'POST',
				dataType: 'json',
				data: {action: 'getconsolidatedDivision'},
				context:$("body"),
				success:function(res){
					if(res.status=='ok'){
						var bdiv = res['bdiv'];
						$(".business_division").html('');
						for(var i in bdiv){
							$(".business_division").append('<option selected>'+bdiv[i]+'</option>');
						}
						$(".business_division").multiselect('rebuild');
						getDepartment()
					}
				}
			});			
		}

		function getDepartment(){
			$.ajax({
				url: '../api/travel_detail_filters.php',
				type: 'POST',
				dataType: 'json',
				data: {action: 'getDepartment','bdiv':$(".business_division").val()},
				context:$("body"),
				success:function(res){
					if(res.status=='ok'){
						var Department = res['Department'];
						$(".Department").html('');
						for(var i in Department){
							$(".Department").append('<option selected>'+Department[i]+'</option>');
						}
						$(".Department").multiselect('rebuild');
						getDesignation();
					}
				}
			});			
		}


		function getDesignation(){
			$.ajax({
				url: '../api/travel_detail_filters.php',
				type: 'POST',
				dataType: 'json',
				data: {action: 'getDesignation','bdiv':$(".business_division").val(),'Department':$(".Department").val()},
				context:$("body"),
				success:function(res){
					if(res.status=='ok'){
						var Designation = res['Designation'];
						$(".Designation").html('');
						for(var i in Designation){
							$(".Designation").append('<option selected>'+Designation[i]+'</option>');
						}
						$(".Designation").multiselect('rebuild');
						get_pending_data();
					}
				}
			});			
		}

	

		function get_pending_data(from = ''){
			let destroy_status = false;
			let bdiv           = $('.business_division').val();
			let department     = $('.Department').val();
			let designation    = $('.Designation').val();
			let tr_from_date   = '2024-11-20';
			let tr_to_date 	   = $('#tr_to_date').val();


			if(from == 'filter_btn') {
				destroy_status = true;
				tr_from_date   = $('#tr_from_date').val();
				set_table('pending',destroy_status,bdiv,department,designation,tr_from_date,tr_to_date);
				set_table('submitted',destroy_status,bdiv,department,designation,tr_from_date,tr_to_date);
				return false;
			} else if(from == 'pending-tab') {
				destroy_status = true;
				tr_from_date   = $('#tr_from_date').val();
				set_table('pending',destroy_status,bdiv,department,designation,tr_from_date,tr_to_date);
				return false;
			} else {
				// for onload calling 
				set_table('pending',destroy_status,bdiv,department,designation,tr_from_date,tr_to_date);		
			}


		}

	function set_table(dataFor = 'pending',destroy_status = false,bdivision = '',department = '',designation = '',tr_from_date = '',tr_to_date = '') {
		var cTbl  = $('.travel_'+dataFor+'_table');
		
		if(destroy_status == true) {
			cTbl.DataTable().destroy();
			cTbl.attr('data-loaded','no');
		}

		var loaded = cTbl.attr('data-loaded');
		if(loaded=='no'){
			cTbl.attr('data-loaded','yes');
			var cPostData = [];
			cPostData['action']  = (dataFor == 'pending') ? 'get_travel_consolidated_report' : 'get_submitted_travel_details';
			cPostData['dataFor'] = dataFor;
			cPostData['bdiv']    = bdivision; 
			cPostData['department']   = department; 
			cPostData['designation']  = designation; 
			cPostData['tr_from_date'] = tr_from_date; 
			cPostData['tr_to_date'] = tr_to_date; 

			if(dataFor=='pending'){
			 	cTbl.DataTable({
			        "dom": 'Bfrtip',
			        // "scrollX": true,
			        "searching": true,
			        "ordering":true,
			        order: [[1, 'ASC']],
			        "columnDefs": [
					    { "name": "division","targets": 0},
					    { "name": "punched count","targets": 1 },
					    { "name": "punched percentage","targets": 2 },
					    { "name": "not punched count","targets": 3 },
					    { "name": "not punched percentage","targets": 4 },
					    { "name": "grand total","targets": 5 },
					  ],
					  'lengthMenu': [
							[5, 10, 50, -1],
							[5, 10, 50, "All"]
						],
					     "language": {                
            				"infoFiltered": ""
        				},
			        
			        "bprocessing": true,
			        "serverSide": true,
			        "pageLength": 10,
			        "pagingType":"full_numbers",
			        "ajax": {
			          "url": '../api/travel_detail_filters.php',
			          "type": "POST",
			          "context":cTbl,
			          "data": cPostData
			        },
			        "rowCallback": function(row, data, index) {
			        	if(data[6] == 'division_wise') {
			        		// $(row).attr('style','background: #C5D3E8;');
			        		$(row).attr('style','background: #E5D9F2;font-weight:500;');
			        	}
			        },  
				    "footerCallback": function(row, data, start, end, display) {
				        var api = this.api();

						var totalPunchedCount = totalNotPunchedCount = totalPunchedPercentage = totalNotPunchedPercentage = grandtotalcountsum = 0;
				        
				        for(i of display) {
				        	if(data[i][6] == 'division_wise') {
				        		totalPunchedCount += data[i][1];
				        		totalNotPunchedCount += data[i][3];
				        		grandtotalcountsum += data[i][5];
				        	}
				        }

				        // Total of each column (except for the first column, which is a string)
				        // var totalPunchedCount = api.column(1).data().reduce(function(a, b) {
				        //     return a + b * 1; // Assuming column 1 contains numbers
				        // }, 0);

				        // var totalNotPunchedCount = api.column(3).data().reduce(function(a, b) {
				        //     return a + b * 1; // Assuming column 3 contains numbers
				        // }, 0);

				        // var grandtotalcountsum = api.column(5).data().reduce(function(a, b) {
				        //     return a + b * 1; // Assuming column 3 contains numbers
				        // }, 0);

				        var totalPunchedPercentage = (totalPunchedCount > 0) ? ((totalPunchedCount/grandtotalcountsum)*100).toFixed(2) : 0;

				        var totalNotPunchedPercentage = (totalNotPunchedCount > 0) ? ((totalNotPunchedCount/grandtotalcountsum)*100).toFixed(2) : 0;

				        // Update footer with totals
				        $(api.column(1).footer()).html(totalPunchedCount);
				        $(api.column(3).footer()).html(totalNotPunchedCount);
				        $(api.column(5).footer()).html(grandtotalcountsum);

				        $(api.column(2).footer()).html(totalPunchedPercentage);
				        $(api.column(4).footer()).html(totalNotPunchedPercentage);

				    }
			    });
			} 
		}
	}

	$(document).on('click','.filter_btn',function() {
		get_pending_data('filter_btn');
	});

	// $(document).on('click','.pending-tab',function() {
	// 	get_pending_data('pending-tab');
	// });

</script>
</body>

</html>
