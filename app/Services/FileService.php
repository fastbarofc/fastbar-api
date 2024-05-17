<?php

namespace App\Services;

use App\Models\File;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;


class FileService
{

  public static function saveImage($item, $column, $path, $fileData)
    {

      /* $filePath = 'products/6647a820e2e80.png';

      if (Storage::disk('public')->exists($filePath)) {
          $fileContents = Storage::disk('public')->get($filePath);
          dd($fileContents);
      } */
        $filePath = "/{$path}/";
        $fileName = uniqid('', false) . '.png';

        @list($type, $fileData) = explode(':', $fileData);
        @list(, $fileData) = explode(';', $fileData);
        
        if ($fileData !== '' && Storage::disk('public')->put($filePath . $fileName, base64_decode($fileData))) {
            if (!empty($item->$column)) {
                Storage::disk('public')->delete(self::clearStoragePath($item->$column));
            }

            $file = File::create([
                'name'      => $fileName,
                'path'      => $filePath . $fileName,
                'extension' => 'png',
            ]);

            $item->update([$column => '/storage' . $filePath . $fileName]);

            return $file;
        }

        throw new \Exception('Failed to save image');
    }

    private static function clearStoragePath($path)
    {
        return str_replace('/storage', '', $path);
    }
  public static function upload($base64Data, $folder)
  {
    $base64Data = trim($base64Data);

    // Verifica se a string base64 tem um cabeçalho válido
    if (preg_match('/^data:image\/(\w+);base64,/', $base64Data, $type)) {
        // Remove o cabeçalho da string base64
        $base64Data = substr($base64Data, strpos($base64Data, ',') + 1);
        // Obtém a extensão do arquivo a partir do tipo MIME
        $extension = strtolower($type[1]);
    } else {
        // Se não houver cabeçalho, lança uma exceção
        throw new \Exception('Invalid base64 image data 01');
    }

      // Decodifica a string base64 para obter os dados binários do arquivo
      $imageData = base64_decode($base64Data);

      if ($imageData === false) {
          // Retorna um erro se a decodificação falhar
          throw new \Exception('Invalid base64 image data 02');
      }

      // Crie um nome de arquivo único usando um hash MD5 dos dados binários
      $filename = strtolower(md5(uniqid())) . '.' . $extension;

      // Salva os dados da imagem no diretório de destino
      $path = $folder . '/' . $filename;
      Storage::put('public/' . $path, $imageData);

      // Cria uma entrada de arquivo no banco de dados
      return File::create([
          'name'      => $filename,
          'path'      => $path,
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
}