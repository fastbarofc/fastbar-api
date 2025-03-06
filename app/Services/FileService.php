<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Storage;
use mikehaertl\tmp\File as MikeFile;
use mikehaertl\wkhtmlto\Pdf;
use Illuminate\Support\Facades\File as FileFacade;

class FileService
{

  public static function saveImage($path, $fileData)
  {


    $filePath = "/{$path}/";
    $fileName = uniqid('', false) . '.png';

    @list($type, $fileData) = explode(';', $fileData);
    @list(, $fileData) = explode(',', $fileData);

    $fileData = base64_decode($fileData);

    Storage::disk('public')->put($filePath . $fileName, $fileData);

    $file = File::create([
      'name'      => $fileName,
      'path'      => $filePath . $fileName,
      'extension' => 'png',
    ]);

    return $file;
  }

  public static function download(File $file)
  {
    if (Storage::disk('public')->exists($file->path)) {
      $filePath = Storage::disk('public')->path($file->path);
      return response()->download($filePath);
    }
    abort(404, 'File Not Fount');
  }

  public static function delete(File $file)
  {
    Storage::disk('public')->delete($file->path);
    $file->delete();
  }

  public static function generatePdf($viewContent, $fileName, $footerContent = null, $headerContent = null)
  {
    $root = base_path();
    $arch = self::getArchitecture();
    $bin = "{$root}/vendor/bin/wkhtmltopdf-{$arch}";

    $options  = [
      'binary' => $bin,
      'ignoreWarnings' => true
    ];

    if (!empty($footerContent)) {
      $footer = new MikeFile($footerContent, '.html');
      $options['footer-html'] = $footer;
    }

    if (!empty($headerContent)) {
      $header = new MikeFile($headerContent, '.html');
      $options['header-html'] = $header;
    }

    $pdf = new Pdf($options);

    $html = $viewContent->render();
    $pdf->addPage($html);

    $directoryPath = storage_path('app/public/pdfs');

    if (!FileFacade::exists($directoryPath)) {
      FileFacade::makeDirectory($directoryPath, 0755, true);
    }

    $fileName = $fileName . ".pdf";

    $filePath = "{$directoryPath}/{$fileName}";

    if (!$pdf->saveAs($filePath)) {
      throw new \Exception($pdf->getError());
    }

    $pdfUrl = asset("storage/pdfs/{$fileName}");
    return $pdfUrl;
  }

  private static function getArchitecture()
  {

    if (2147483647 == PHP_INT_MAX) {
      $architecture = 'i386';
    } else {
      $architecture = 'amd64';
    }
    return $architecture;
  }
}
