<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Storage;

class FileService
{
  public static function upload($file, $folder)
  {
    $fullFileName = $file->getClientOriginalName();
    $filename     = explode('.', $fullFileName)[0];
    $extension    = $file->getClientOriginalExtension();
    $path         = $folder . '/' . strtolower(md5($filename)) . '.' . $extension;
    $file->storeAs('public/' . $folder, strtolower(md5($filename)) . '.' . $extension);
    return File::create([
      'name'          => $filename,
      'path'          => $path,
      'extension'     => $extension,
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
}