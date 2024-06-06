<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Storage;



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
}