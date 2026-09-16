<!-- CODE BUILDING X FOR B-GRADE -->
	<button class="btn-success" onclick = "window.print()" > PRINTX </button>
	<?php
	if ($cek_data && $cek_data["DATA"]=="tl") {
		$zero_qty = mysqli_fetch_array(mysqli_query($db, "SELECT COUNT(*) as c FROM DATA_LABEL_TL WHERE UPLOAD_VERSION='$version' AND QTY=0"));
		if ($zero_qty['c'] > 0) {
			echo '<div class="alert-warning-box">⚠️ PERINGATAN: Terdapat ' . $zero_qty['c'] . ' item dengan QTY = 0. Periksa kembali data sebelum mencetak!</div>';
		}
	}
	?>
	<div>
		<table id="" class="table table-bordered print-table-full">
			<tbody>
			<?php
			require_once(BASE_PATH . '/phpqrcode/qrlib.php');
			$kolom = 4;
			$i=1;
			if ($cek_data && $cek_data["DATA"]=="tl") {
				foreach($rows as $r) {

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

						if ($r['BUILDING']!="") {
							$bg = "bg-".$r["BUILDING"];
						}else{
							$bg = "bg-B-GRADE";
						}
						?>

						<td class="text-center" style="vertical-align: bottom;/*height: 36mm;*/padding: 0;width:283px">
							<?php if ($r['ID']!="" AND $r['PO']!="") {
								?>
								<div class="row" style="margin:0px;/*height:70px/*">
									<div class="col-xs-4" style="padding:0">
										<?php if ($r['ID']!="") {?> <img src="<?=BASE_URL?>/QR/<?= htmlspecialchars($r['ID'], ENT_QUOTES, 'UTF-8') ?>.png" width="45px"><?php } ?>
										<?php if (strpos($r['ID'], "_") !== false) {
											echo "<br><p class='bg-new-id' style='margin:0'>TL</p>";
										}
										?>
									</div>
									<div class="col-xs-8" style="padding:0;text-align: left;margin-top: 8px;">
										<h6 class="item">START<text style="color:white !important">-</text>: <?=formatDateDisplay($r['START'])?></h6>
										<h6 class="item">SDD<text style="color:white !important">----</text>: <?=formatDateDisplay($r['SDD'])?></h6>
										<h6 class="item">QTY<text style="color:white !important">----</text>: <?php if ($r['QTY']!="") { echo number_format($r['QTY']); }?></h6>
										<h6 class="item">SAP<text style="color:white !important">-</text>: <?= htmlspecialchars($r['SAP'], ENT_QUOTES, 'UTF-8') ?></h6>
									</div>
								</div>
								<div class="row <?=$bg?>" style="margin:0px">
									<h6 class="item_po <?=$bg?>"><?= htmlspecialchars($r['PO'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($r['BUILDING'], ENT_QUOTES, 'UTF-8') ?>.<?= htmlspecialchars($r['CELL'], ENT_QUOTES, 'UTF-8') ?>)</h6>
									<h6 class="item_po <?=$bg?>"><?= htmlspecialchars($r['COUNTRY'], ENT_QUOTES, 'UTF-8') ?></h6>
								</div>
							<?php } ?>
						</td>

						<?php
						//substr($r['CELL'],1,2) 
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
		margin-bottom: 0;
		font-weight: 400;/*700*/
		white-space: nowrap;
		overflow: hidden;
	}
	.item_po {
		margin-top: 5px;
		margin-bottom: 5px;
		font-weight: 400;/*700*/
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
	.bg-X{
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
			height:297mm;
			position:absolute;
		}
	</style>
