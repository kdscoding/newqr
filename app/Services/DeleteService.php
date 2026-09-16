<?php
class DeleteService {
    public static function execute($db, $version, $dataType) {
        $tables = [
            'inhouse' => 'DATA_LABEL',
            'sbsite' => 'DATA_LABEL_SBSITE',
            'paxar' => 'DATA_LABEL_SL',
            'supplier' => 'DATA_LABEL_SP',
            'tl' => 'DATA_LABEL_TL',
            'additional_label' => 'data_label_add',
        ];
        
        if (array_key_exists($dataType, $tables)) {
            $table = $tables[$dataType];
            
            $stmt1 = $db->prepare("DELETE FROM $table WHERE UPLOAD_VERSION = ?");
            $stmt1->bind_param('s', $version);
            $stmt1->execute();
            $stmt1->close();
            
            $stmt2 = $db->prepare("DELETE FROM version WHERE UPLOAD_VERSION = ?");
            $stmt2->bind_param('s', $version);
            $stmt2->execute();
            $stmt2->close();
            
            Flash::set('deleted', []);
            header("Location: /newqr/data-qr");
            exit;
        }
        
        Flash::set('error', []);
        header("Location: /newqr/data-qr");
        exit;
    }
}
