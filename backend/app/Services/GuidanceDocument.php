<?php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use ZipArchive;

class GuidanceDocument {
 public static function extract(UploadedFile $file):string {
  $extension=strtolower($file->getClientOriginalExtension());
  $path=$file->getRealPath();
  if($extension==='txt')$text=file_get_contents($path);
  elseif($extension==='docx'){
   $zip=new ZipArchive();
   abort_unless($zip->open($path)===true,422,'The Word document could not be opened.');
   $xml=$zip->getFromName('word/document.xml');$zip->close();
   abort_unless($xml,422,'The Word document has no readable text.');
   $xml=preg_replace('/<\/w:p>/',"\n",$xml);
   $text=strip_tags($xml);
   $text=html_entity_decode($text,ENT_QUOTES|ENT_XML1,'UTF-8');
  } elseif($extension==='pdf'){
   $command='pdftotext -layout -enc UTF-8 '.escapeshellarg($path).' -';
   $text=shell_exec($command);
   abort_unless($text!==null,422,'The PDF could not be read. Upload a text-based PDF, Word document, or TXT file.');
  }else abort(422,'Upload a PDF, DOCX, or TXT document.');
  $text=preg_replace('/[\t ]+/u',' ',$text);
  $text=preg_replace('/\n{3,}/u',"\n\n",trim($text));
  abort_unless(mb_strlen($text)>=50,422,'The document has too little readable text. Scanned PDFs need OCR before upload.');
  abort_unless(mb_strlen($text)<=200000,422,'The document contains too much text (maximum 200,000 characters).');
  return $text;
 }
}
