<?php
require __DIR__.'/../theme/root/vpscloud-db.php';
class DueRows {
    private $rows;
    function __construct($rows) { $this->rows=$rows; }
    function fetch_assoc() { return array_shift($this->rows); }
}
class DueDb {
    private $rows;
    function __construct($rows) { $this->rows=$rows; }
    function query($sql) { return $this->rows===false?false:new DueRows($this->rows); }
}
$rows=[['nome'=>'dia30','valor'=>'sim'],['nome'=>'dia05','valor'=>null],['nome'=>'dia20','valor'=>'nao'],['nome'=>'dia15','valor'=>'sim'],['nome'=>'dia10','valor'=>'sim'],['nome'=>'dia25','valor'=>'sim'],['nome'=>'dia99','valor'=>'sim']];
if(vpscloud_due_days(new DueDb($rows))!==['10','15','25','30'])throw new Exception('Enabled days mismatch');
if(vpscloud_due_days(new DueDb([]))!==[])throw new Exception('Unexpected fallback days');
try { vpscloud_due_days(new DueDb(false)); throw new LogicException('Query failure ignored'); }
catch(RuntimeException $e) { }
echo "PASS enabled days, ordering, disabled days, empty configuration and query failure\n";
