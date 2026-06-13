<?php
require __DIR__ . '/src/config/config.php';
require __DIR__ . '/src/conexion/conexion.php';

$idResultado = 2677;
$stmt = $pdo->prepare("SELECT id, id_cotizacion, id_examen, resultados, adicional_snapshot FROM resultados_examenes WHERE id=:id");
$stmt->execute(['id'=>$idResultado]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$r) { echo "NO_ROW\n"; exit; }

echo "id={$r['id']} cot={$r['id_cotizacion']} examen={$r['id_examen']}\n";

$snap = json_decode((string)$r['adicional_snapshot'], true);
$res = json_decode((string)$r['resultados'], true);
if (!is_array($snap)) $snap = [];
if (!is_array($res)) $res = [];

$norm = function($s){
  $s = trim((string)$s);
  $s = preg_replace('/\s+/u',' ',$s);
  $s = mb_strtolower($s,'UTF-8');
  $a = @iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$s);
  if ($a !== false && $a !== null) $s = $a;
  $s = preg_replace('/[^a-z0-9 ._-]/','',$s);
  return $s;
};

echo "--- SNAP MOVIL/INMOV ---\n";
$byNorm=[];
foreach($snap as $i=>$it){
  $tipo = (string)($it['tipo'] ?? '');
  if (!in_array($tipo,['Parámetro','Campo','Texto Largo'],true)) continue;
  $n = (string)($it['nombre'] ?? '');
  $idp = (string)($it['id_parametro'] ?? '');
  $nl = $norm($n);
  if (strpos($nl,'movil')!==false || strpos($nl,'inmov')!==false) {
    echo "[$i] nombre={$n} norm={$nl} id={$idp}\n";
  }
  if (!isset($byNorm[$nl])) $byNorm[$nl]=[];
  $byNorm[$nl][]=['idx'=>$i,'nombre'=>$n,'id'=>$idp];
}

echo "--- DUPLICADOS POR NOMBRE NORMALIZADO ---\n";
foreach($byNorm as $k=>$arr){
  if (count($arr)>1){
    echo "norm={$k} count=".count($arr)."\n";
    foreach($arr as $x){ echo "  idx={$x['idx']} nombre={$x['nombre']} id={$x['id']}\n"; }
  }
}

echo "--- RESULTADOS KEYS MOVIL/INMOV ---\n";
foreach($res as $k=>$v){
  $nk = $norm((string)$k);
  if (strpos($nk,'movil')!==false || strpos($nk,'inmov')!==false || strpos((string)$k,'id_parametro_')===0){
    if (strpos((string)$k,'id_parametro_')===0 && strpos((string)$v,'')===0){}
    echo $k . " => " . (is_scalar($v)?(string)$v:json_encode($v,JSON_UNESCAPED_UNICODE)) . "\n";
  }
}
