	<button class="btn-success" onclick = "window.print()" > PRINT </button>
	<div>
		<table id="" class="table table-bordered" style="width:100%;/*margin-left: 60px;margin-top:5mm*/">
			<tbody>
				<?php
				require_once('phpqrcode/qrlib.php');
				if ($_GET) {
					$UPLOAD_VERSION = $_GET['version'];
				}elseif (!$_GET) {
					$UPLOAD_VERSION = "";
				}
				$kolom = 2;
				$i=1;
				$cek_data = mysqli_fetch_array(mysqli_query($koneksi, "SELECT * FROM version WHERE UPLOAD_VERSION='$UPLOAD_VERSION'"));
				if ($cek_data["DATA"]=="paxar") {
					$tampil = mysqli_query($koneksi, "SELECT * FROM DATA_LABEL_SL WHERE UPLOAD_VERSION='$UPLOAD_VERSION' ORDER BY NO_URUT ASC");
					while($r=mysqli_fetch_array($tampil)){

						$qrvalue = $r["ID"];

						$tempDir = "QR/"; 
						$codeContents = $qrvalue; 
						$fileName = $qrvalue . '.png'; 
						$pngAbsoluteFilePath = $tempDir.$fileName; 
						$urlRelativeFilePath = $tempDir.$fileName; 
						if (!file_exists($pngAbsoluteFilePath)) { 
							QRcode::png($codeContents, $pngAbsoluteFilePath); 
						}
						if(($i) % $kolom== 1){
							echo "<tr>";
						}
						?>

						<td class="text-center" style="vertical-align: bottom;height: 36mm;padding: 0;width:283px">
							<?php if ($r['ID']!="") {
								?>
								<div class="row" style="margin:0px;height:0px">
									<div class="col-xs-8" style="padding:0;text-align: left;/*padding-left: 50px;*/">
										<h6 class="item"></h6>
										<h6 class="item"><text style="color:white !important">--------------------</text> <?=$r['PRINT_DATE']?></h6>
										<h6 class="item"><text style="color:white !important">--------------------</text> <?=$r['PO']?></h6>
										<h6 class="item"><text style="color:white !important">--------------------</text> <?=$r['CUST']?></h6>
										<h6 class="item"><text style="color:white !important">--------------------</text> <?=$r['ART']?></h6>
										<h6 class="item"><text style="color:white !important">--------------------</text> <?=$r['MODEL_NAME']?></h6>
										<h6 class="item"><text style="color:white !important">--------------------</text> <?=number_format($r['QTY'])?></h6>
										<h6 class="item"><text style="color:white !important">--------------------</text> <?=$r['REMARKS']?></h6>
										<h6 class="item"><text style="color:white !important">--------------------</text> <?=$r['CELL']?></h6>
									</div>
								</div>
								
							<?php } ?>
						</td>

						<?php
						if(($i) % $kolom== 0) {
							echo "</tr>";
						}
						$i++;
					}
				}
				?>
			</tbody>
		</table>
	</div>
	<style type="text/css">
	.table td{
		border-top-width: 0px;
		border: none !important;
	}
	.item {
		margin-top: 0;
		margin-bottom: 20px;
		font-weight: 700;
	}
	.item_po {
		margin-top: 5px;
		margin-bottom: 5px;
		font-weight: 700;
	}
	.bg-A{
		background: rgb(210, 222, 50) !important;
		color: black !important;
	}
	.bg-B{
		background: yellow !important;
		color: black !important;
	}
	.bg-C{
		background: red !important;
		color:  white !important;
	}
	.bg-D{
		background: rgb(178, 178, 178) !important;
		color: black !important;
	}
	.bg-E{
		background: orange !important;
		color: black !important;
	}
	.bg-H{
		background: rgb(0, 169, 255) !important;
		color: white !important;
	}
	.bg-B-GRADE{
		background: rgb(0, 0, 0) !important;
		color: white !important;
	}
	.bg-new-id{
		background: purple !important;
		color: white !important;
	}
	@media print {
		button {
			display :none;
			-webkit-print-color-adjust: exact;
			width: 210mm;
			height:310mm;
			position:absolute;
		}
	</style>