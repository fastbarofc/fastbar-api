<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Storage;
use mikehaertl\tmp\File as MikeFile;
use mikehaertl\wkhtmlto\Pdf;
use Illuminate\Support\Facades\File as FileFacade;
use Illuminate\Support\Str;

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

  public static function saveBase64File(string $path, string $fileData, ?string $originalName = null): File
  {
    if (!preg_match('/^data:(.*?);base64,(.*)$/', $fileData, $matches)) {
      throw new \InvalidArgumentException('Formato de arquivo inválido.');
    }

    $mimeType = $matches[1];
    $base64Content = base64_decode($matches[2]);

    if ($base64Content === false) {
      throw new \InvalidArgumentException('Não foi possível decodificar o arquivo.');
    }

    $extension = self::getExtensionFromMime($mimeType);

    if (!$extension) {
      throw new \InvalidArgumentException('Tipo de arquivo não suportado.');
    }

    $fileDirectory = "/{$path}/";
    $fileName = self::resolveFileName($originalName, $extension);

    Storage::disk('public')->put($fileDirectory . $fileName, $base64Content);

    return File::create([
      'name'      => $fileName,
      'path'      => $fileDirectory . $fileName,
      'extension' => $extension,
    ]);
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

  private static function getExtensionFromMime(string $mimeType): ?string
  {
    $map = [
      'image/png' => 'png',
      'image/jpeg' => 'jpg',
      'image/jpg' => 'jpg',
      'application/pdf' => 'pdf',
      'application/msword' => 'doc',
      'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
    ];

    return $map[$mimeType] ?? null;
  }

  private static function resolveFileName(?string $originalName, string $extension): string
  {
    if ($originalName) {
      $nameWithoutExtension = pathinfo($originalName, PATHINFO_FILENAME);
      $sanitized = Str::slug($nameWithoutExtension);

      if (!empty($sanitized)) {
        return $sanitized . '-' . uniqid('', false) . '.' . $extension;
      }
    }

    return uniqid('', false) . '.' . $extension;
  }
}
