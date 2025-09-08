<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

trait ImageManagerTrait
{
    protected function storeImage(\Illuminate\Http\UploadedFile $file, string $folder, string $prefix): string
    {
        // 1. Configurar el disco personalizado
        $disk = 'public_direct';
        
        // 2. Generar nombre único seguro
        $filename = $prefix . '_' . time() . '_' . Str::random(8) . '.' . $file->getClientOriginalExtension();
        
        // 3. Guardar en public/{folder}
        $path = Storage::disk($disk)->putFileAs(
            $folder,
            $file,
            $filename
        );
        
        return "/{$folder}/{$filename}";
    }

    protected function storeBase64File(string $base64File, string $folder): string
    {
        // Extrae el tipo de archivo y los datos base64
        $mimeType = null;
        $fileData = null;

        if (preg_match('/^data:([a-zA-Z0-9\/\.\-\+\;]+);base64,/', $base64File, $matches)) {
            $mimeType = $matches[1];
            $fileData = base64_decode(substr($base64File, strpos($base64File, ',') + 1));
        } else {
            // Si no tiene el prefijo data, asumimos que es base64 puro y tratamos de detectar el tipo después
            $fileData = base64_decode($base64File);
        }

        // Determina la extensión a partir del mime type si está disponible
        $extensions = [
            'image/jpeg' => '.jpg',
            'image/png' => '.png',
            'image/gif' => '.gif',
            'image/webp' => '.webp',
            'image/bmp' => '.bmp',
            'image/svg+xml' => '.svg',
            'application/pdf' => '.pdf',
            'application/msword' => '.doc',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => '.docx',
            'application/vnd.ms-excel' => '.xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => '.xlsx',
            'application/vnd.ms-powerpoint' => '.ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => '.pptx',
            'text/plain' => '.txt',
            'application/zip' => '.zip',
            'application/x-rar-compressed' => '.rar',
            'audio/mpeg' => '.mp3',
            'audio/wav' => '.wav',
            'video/mp4' => '.mp4',
            // Agrega más tipos si es necesario
        ];

        $ext = '.bin'; // Valor por defecto

        if ($mimeType && isset($extensions[$mimeType])) {
            $ext = $extensions[$mimeType];
        } elseif (!$mimeType && function_exists('finfo_open')) {
            // Si no se detectó el mimeType, intenta detectarlo con finfo
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $detectedMime = finfo_buffer($finfo, $fileData);
            finfo_close($finfo);
            if (isset($extensions[$detectedMime])) {
                $ext = $extensions[$detectedMime];
            }
        }

        // Genera un nombre de archivo único
        $filename = uniqid('', true) . $ext;
        $path = $folder . '/' . $filename;

        // Define la ruta de almacenamiento en la carpeta 'public'
        $filePath = public_path($path);

        // Asegúrate de que el directorio exista
        $directory = dirname($filePath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Guarda el archivo en el disco
        file_put_contents($filePath, $fileData);

        return "/{$path}";
    }
}
