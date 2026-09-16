	<button class="btn-success" onclick = "window.print()" > PRINT </button>
	<?php
	if ($cek_data && $cek_data["DATA"]=="paxar") {
		$zero_qty = mysqli_fetch_array(mysqli_query($db, "SELECT COUNT(*) as c FROM DATA_LABEL_SL WHERE UPLOAD_VERSION='$version' AND QTY=0"));
		if ($zero_qty['c'] > 0) {
			echo '<div class="alert-warning-box">⚠️ PERINGATAN: Terdapat ' . $zero_qty['c'] . ' item dengan QTY = 0. Periksa kembali data sebelum mencetak!</div>';
		}
	}
	?>
	<div>
		<table id="" class="table print-table-full">
			<tbody>
			<?php
			require_once(BASE_PATH . '/phpqrcode/qrlib.php');
			$kolom = 2;
			$i=1;
			if ($cek_data && $cek_data["DATA"]=="paxar") {
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
							$margin="150px";
						}else{
							$margin="135px";
						}


						if ($r['PRINTED_BY']!="") {
							$bg = "bg-" . preg_replace('/[^a-zA-Z0-9_-]/', '', $r["PRINTED_BY"]);
						}
						?>
						<td>
							<?php if ($r['ID']!="" AND $r['PO']!="") {
								?>
								<div class="row itas-x" style="margin-left:<?=$margin?>;    margin-right: 0px;">
									<div class="col-xs-7" style="padding:0">
										<p class="itas"><?=formatDateDisplay($r['PRINT_DATE'])?></p><br>
										<p class="itas"><?= htmlspecialchars($r['PO'], ENT_QUOTES, 'UTF-8') ?></p><br>
									</div>
									<div class="col-xs-5" style="padding:0">
										<?php if ($r['ID']!="") {?> <img src="<?=BASE_URL?>/QR/<?= htmlspecialchars($r['ID'], ENT_QUOTES, 'UTF-8') ?>.png" width="50px"><?php } ?>
									</div>
									<div class="col-xs-12" style="padding:0">
										<p class="itas" style="margin-top:5px"><?= htmlspecialchars($r['CUST'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($r['COUNTRY'], ENT_QUOTES, 'UTF-8') ?>)</p><br>
										<p class="itas"><?= htmlspecialchars($r['ART'], ENT_QUOTES, 'UTF-8') ?></p><br>
										<p class="itas"><?= htmlspecialchars($r['MODEL_NAME'], ENT_QUOTES, 'UTF-8') ?></p><br>
										<p class="itas"><?= htmlspecialchars($r['QTY'], ENT_QUOTES, 'UTF-8') ?></p><br>
										<p class="itas"><?= htmlspecialchars($r['REMARKS'], ENT_QUOTES, 'UTF-8') ?></p><br>
										<p class="itas <?=$bg?>"><?= htmlspecialchars($r['PRINTED_BY'], ENT_QUOTES, 'UTF-8') ?> - <?= htmlspecialchars($r['CELL'], ENT_QUOTES, 'UTF-8') ?></p>
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
		border-bottom-width: 0px;
		border: none !important;
	}
	.itas {
		margin-bottom: -18px;
		font-size: 16px;
	}
	.itas-x {
		margin-top: 60px;
	}

	.bg-h1{
		background: rgb(210, 222, 50) !important;
		color: black !important;
	}
	.bg-h2{
		background: yellow !important;
		color: black !important;
	}
	.bg-h3{
		background: red !important;
		color:  white !important;
	}
	.bg-h4{
		background: rgb(178, 178, 178) !important;
		color: black !important;
	}
	.bg-h5{
		background: orange !important;
		color: black !important;
	}
	.bg-h6{
		background: rgb(0, 169, 255) !important;
		color: white !important;
	}

	@media print {
		button {
			display :none;
			-webkit-print-color-adjust: exact;
			width: 210mm;
			height:355mm;
			position:absolute;
		}
	}
</style>
