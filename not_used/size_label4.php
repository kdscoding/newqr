	<button class="btn-success" onclick = "window.print()" > PRINT </button>
	<div>
		<table id="" class="table table-bordered" style="width:100%;border: 1px solid black;/*margin-left: 60px;margin-top:5mm*/">
			<tbody>
				<?php
				require_once('phpqrcode/qrlib.php');
				if ($_GET) { $UPLOAD_VERSION = $_GET['version']; }elseif (!$_GET) { $UPLOAD_VERSION = ""; }
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
							$margin="130px";
						}else{
							$margin="250px";
						}
						?>
						<td>
							<div class="row itas-x" style="margin-left:<?=$margin?>">
								<div class="col-xs-8" style="padding:0">
									<p class="itas"><?=$r['PRINT_DATE']?></p><br>
									<p class="itas"><?=$r['PO']?></p><br>
									<p class="itas"><?=$r['CUST']?></p><br>
								</div>
								<div class="col-xs-4" style="padding:0">
									<?php if ($r['ID']!="") {?> <img src="QR/<?=$r['ID']?>.png" width="50px"><?php } ?>
								</div>

								<div class="col-xs-12" style="padding:0">
								<p class="itas"><?=$r['ART']?><p><br>
								<p class="itas"><?=$r['MODEL_NAME']?><p><br>
								<p class="itas"><?=$r['QTY']?><p><br>
								<p class="itas"><?=$r['REMARKS']?><p><br>
								<p class="itas"><?=$r['CELL']?><p>
								</div>
							</div>
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
	.itas {
		margin-bottom: -20px;
	}
	.itas-x {
		margin-top: 80px;
	}

	@media print {
		button {
			display :none;
			-webkit-print-color-adjust: exact;
			/*width: 210mm;
			height:355mm;*/
			position:absolute;
		}
	</style>