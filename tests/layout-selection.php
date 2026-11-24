<?php
require __DIR__.'/../theme/root/vpscloud-layout.php';
class LayoutSelectionResult {
    private $rows;
    function __construct($rows){$this->rows=$rows;}
    function fetch_assoc(){return array_shift($this->rows);}
    function fetch_row(){$row=$this->fetch_assoc();return $row?array_values($row):null;}
}
class LayoutSelectionDatabase {
    public $nativeRows=[];
    public $optionRows=[];
    public $failNative=false;
    function query($sql){
        if (strpos($sql,'SHOW FULL TABLES')===0)return new LayoutSelectionResult([['name'=>'sis_hotsite','type'=>'BASE TABLE']]);
        if ($sql==='SELECT layout FROM sis_hotsite LIMIT 1')return $this->failNative?false:new LayoutSelectionResult($this->nativeRows);
        if (strpos($sql,"SELECT valor FROM sis_opcao WHERE nome='layhotsite'")===0)return new LayoutSelectionResult($this->optionRows);
        throw new RuntimeException('Consulta inesperada no teste: '.$sql);
    }
}
function selectionCheck($ok,$label){if(!$ok)throw new RuntimeException($label);echo 'PASS '.$label.PHP_EOL;}
$db=new LayoutSelectionDatabase();
$db->optionRows=[['valor'=>'ABGS_econ03_blue']];
selectionCheck(vpscloud_selected_layout($db)==='ABGS_econ03_blue','tabela antiga vazia usa preferência atual');
selectionCheck(vpscloud_install_layout(vpscloud_selected_layout($db))==='layout-vpscloud-whatsapp','ABGS migra para WhatsApp');
$db->optionRows=[['valor'=>'layout-vpscloud-whatsapp']];
selectionCheck(vpscloud_layout_mode($db)==='whatsapp','endpoint lê cadastro com tabela antiga vazia');
$db->nativeRows=[['layout'=>'tema_cliente']];
selectionCheck(vpscloud_selected_layout($db)==='tema_cliente','registro nativo existente mantém precedência');
$db->nativeRows=[];$db->optionRows=[];
selectionCheck(vpscloud_selected_layout($db)==='','ausência de ambas as preferências não quebra endpoint');
$db->failNative=true;$failed=false;
try{vpscloud_selected_layout($db);}catch(RuntimeException $e){$failed=true;}
selectionCheck($failed,'erro SQL real continua explícito');
