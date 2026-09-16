<?php
if(isset($_POST['data'])){
  require_once('proses.php');
}
?>
<!-- <p class="bg-info" style="padding: 15px;">Pastikan ID anda sudah diperbaharui dan disesuaikan dengan ID terbaru.<br>Contoh ID Terbaru : <b>0136080609_ABSBOXCPAR_AVERYIPPS_1</b><br>
ID Terdiri Dari : <b>PO10_ITEM_RECEIVER_SEQUENCE</b><br>
Effective Date: Running Change<br>
Contoh Data Upload QR Terbaru dapat didownload dibawah</p>
<br> 
<p class="bg-danger" style="padding: 15px;">PERUBAHAN FORMAT FILE SB SITE. SILAHKAN DAPAT DI DOWNLOAD DIBAWAH.<br>
<b>ID SAP DAPAT DI ISI SAMA DENGAN ID DI SISTEM LAMA</b><br>
Contoh : <b>0900062482SDFS4012160, 135370708SDFH1019240</b></p>-->
<div class="container">
  <a class="btn btn-primary btn-lg mb-5 mb-lg-2" href="/newqr/ContohScan.xls" role="button">CONTOH DATA UPLOAD</a>
</div>

<form method="POST" action="" enctype="multipart/form-data">
  <div class="form-group">
    <label for="exampleInputEmail1">Data</label>
    <select class="form-control" name="data" id="data" required>
      <option value="additional_label">additional_label</option>
    </select>
  </div>
  <div class="form-group">
    <label for="exampleInputEmail1">Upload File (excel 97-2003 Workbook(*.xls))</label>
    <input type="file" class="form-control" id="file_excel" name="file_excel" placeholder="Upload File">
  </div>
  <button type="submit" class="btn btn-default">Submit</button>
</form>