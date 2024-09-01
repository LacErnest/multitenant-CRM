<?php

namespace App\Utils;

use Barryvdh\DomPDF\Facade\Pdf;
use DOMDocument;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\Process;
use Illuminate\Support\Str;

class ImageUtils
{

    /**
     * @var string $htmlContent
     * @return string
     */
    public static function convertHtmlToBase64($htmlContent): string
    {
        $htmlContent = static::replaceImageUris($htmlContent);
        $randomaFileName = Str::random(20);
        $pdfOutputFileName = $randomaFileName . '.pdf';
        $imageOutputFileName = $randomaFileName . '.png';

        // Convert HTML content to PDF file
        $pdfOutputFilePath = Storage::disk('public')->path($pdfOutputFileName);
        $pdf = Pdf::loadHTML($htmlContent);
        $pdf->save($pdfOutputFilePath);

        // Set the output filename
        $imageOutputFilePath = Storage::disk('public')->path($imageOutputFileName);

        // Build the Ghostscript command
        $ghostscriptCommand = sprintf(
            'gs -dSAFER -dNOPAUSE -dBATCH -sDEVICE=png16m -r300 -sOutputFile=%s %s',
            escapeshellarg($imageOutputFilePath),
            escapeshellarg($pdfOutputFilePath)
        );

        // Execute the Ghostscript command using Symfony Process component
        $process = Process::fromShellCommandline($ghostscriptCommand);

        try {
            // Run the Ghostscript command
            $process->mustRun();
            // Read the image file contents
            $imageContents = file_get_contents($imageOutputFilePath);

            // Encode the image contents to base64
            $base64Image = base64_encode($imageContents);

            // Delete unsed files
            Storage::disk('public')->delete($pdfOutputFileName);
            Storage::disk('public')->delete($imageOutputFileName);

            return $base64Image;
        } catch (ProcessFailedException $exception) {
            throw $exception;
        }
    }

    public static function replaceImageUris($html)
    {
        if (config('app.env') === 'production') {
            return $html;
        }

        // Get the APP_FRONTEND_URL from the environment
        $frontendUrl = env('APP_FRONTEND_URL').'/storage';

        // Initialize DOMDocument and load HTML
        $dom = new DOMDocument;
        libxml_use_internal_errors(true); // Suppress errors due to invalid HTML
        $dom->loadHTML($html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        // Get all image tags
        $images = $dom->getElementsByTagName('img');
        // Iterate over each image and replace the src attribute based on your condition
        foreach ($images as $img) {
            $src = $img->getAttribute('src');

            if (filter_var($src, FILTER_VALIDATE_URL) && strpos($src, $frontendUrl) === 0) {
                // Extract the relative path from the URL
                $relativePath = str_replace($frontendUrl, '', $src);
                $path = Storage::disk('design-template')->path($relativePath);
                // Retrieve the image content
                $imageContent = static::getImageContent($path);

                // Convert the image to base64 if content is retrieved
                if ($imageContent) {
                    $base64 = base64_encode($imageContent);
                    $type = static::getMimeType($path);
                    $img->setAttribute('src', "data:image/$type;base64,$base64");
                }
            }
        }

        // Save and return the modified HTML
        return $dom->saveHTML();
    }

     // Function to get image content from storage
    private static function getImageContent($path)
    {
        if (File::exists($path)) {
            return file_get_contents($path);
        }
        return null;
    }

     // Function to determine mime type based on file extension
    private static function getMimeType($path)
    {
        return pathinfo($path, PATHINFO_EXTENSION);
    }
}
