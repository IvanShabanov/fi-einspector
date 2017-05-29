<?php
global $ignore; 

$d = './'; /* Начальная дириктория сканирования */
$f = '__fileinspector.txt'; /* файл для хранения информации о файлах */
$ftmp = substr('bf'. base64_encode( md5( uniqid() ) ),0, rand(13,32) ).'.log'; /* файл для хранения информации о файлах */
$flog = '__fileinspector.log'; /* Файл с отчетами */
$ignore = '/('.$f.')|('.$flog.')|(.*\.(log|jpg|jpeg|bmp|png|gif|ico|pdf))$/'; /* Регулярное выражение какие файлы игнорировать */
$email = ''; /* Email администратора */
$subject = 'Отчет о изменениях в файлах на сайте '.$_SERVER['SERVER_NAME'].' '.$_SERVER['SERVER_ADDR'].' от '.date('Y-m-d H:i:s');
$namearray = array(
                'new' => 'Новые файлы',
                'changed' => 'Измененные файлы',
                'deleted' => 'Удаленные файлы');

/* Нужно ли удалять новые php файлы */
$delete_new_php_files = false; 

/************************************************************/


function FileListinfile($directory, $outputfile) {
  global $ignore;
  if ($handle = opendir($directory)) {
    while (false !== ($file = readdir($handle))) {
      if (is_file($directory.$file)) {
        if ((!preg_match($ignore, $directory.$file)) and ($directory.$file != $directory.$outputfile)) { 
          file_put_contents($outputfile, $directory.$file."\t\t".filesize($directory.$file)." bytes  ".date('Y-m-d H:i:s', filemtime($directory.$file))."\n", FILE_APPEND);
        }
      } elseif ($file != '.' and $file != '..' and is_dir($directory.$file)) {
        FileListinfile($directory.$file.'/', $outputfile);
      }
    }
  }
  closedir($handle);
}

function GetArrayFiles($f) {
  $result = array();
  if (file_exists($f)) {
    $content = file($f);
    foreach ($content as $line) {
      $line = trim($line);
      list($title, $info) = explode("\t\t", $line);
      $result[$title] = $info;
    };
  };
  return $result;
}

function CompareArrays($old, $new) {
  $result = array();
  if (!empty($new)) {
    foreach ($new as $key=>$val) {
      if ($old[$key] == '') {
        $result['new'][$key] = $val;

      } else if ($old[$key] != $val) {
        $result['changed'][$key] = $old[$key].' -> '.$val;
      }
      unset($old[$key]);
    }
  }
  if (!empty($old)) {
    foreach ($old as $key=>$val) {
      $result['deleted'][$key] = $val;
    };
  }
  return $result;
}


$old = GetArrayFiles($f);

FileListinfile($d, $ftmp);


if (!empty($old)) {
  $new = GetArrayFiles($ftmp);
  $res = CompareArrays($old, $new);


  if (!empty($res)) {
    $message = '';
    foreach ($namearray as $key=>$val) {
      if (!empty($res[$key])) {
        $message .= "\n"."------------------------\n".$val."\n";
        foreach ($res[$key] as $filename=>$info) {
          $message .= $filename."\t\t".$info."\n";          
          if ($delete_new_php_files) {
            if ($key == 'new') {
              if (substr(trim($filename),-4) == '.php') {
                @unlink($filename);
              }          
            }
          }
        }
      }
    }
    if (trim($message) != '') {
      if ($flog != '') {
        file_put_contents($flog, $subject.$message."\n\n\n\n\n", FILE_APPEND);
      }
      if ($email != '') {
        @mail($email, $subject, $message);    
      }
    }
  }
}
@unlink($f);
@rename ($ftmp, $f);
?>
