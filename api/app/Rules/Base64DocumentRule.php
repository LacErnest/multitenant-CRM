<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use ZipArchive;

class Base64DocumentRule implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // Check if the value contains the base64 prefix for DOCX and strip it
        if (strpos($value, 'data:application/vnd.openxmlformats-officedocument.wordprocessingml.document;base64,') === 0) {
            $value = substr($value, strpos($value, ',') + 1);
        }

        // Check if the value contains the base64 prefix for DOC and strip it
        if (strpos($value, 'data:application/msword;base64,') === 0) {
            $value = substr($value, strpos($value, ',') + 1);
        }

        // Decode the base64 string
        $fileData = base64_decode($value);

        // Check if decoding was successful
        if ($fileData === false) {
            return false;
        }

        // Create a temporary file to store the decoded content
        $tmpFilePath = tempnam(sys_get_temp_dir(), 'docx');
        file_put_contents($tmpFilePath, $fileData);

        $isValid = $this->isValidDocx($tmpFilePath) || $this->isValidDoc($tmpFilePath);

        // Clean up the temporary file
        unlink($tmpFilePath);

        // Validate the MIME type
        return $isValid;
    }

    /**
     * Validate the DOCX file by checking its internal structure.
     *
     * @param  string  $filePath
     * @return bool
     */
    private function isValidDocx($filePath)
    {
        $zip = new ZipArchive();

        if ($zip->open($filePath) === true) {
            // Check for the presence of '[Content_Types].xml' which is mandatory in DOCX files
            $isValid = $zip->locateName('[Content_Types].xml') !== false;
            $zip->close();
            return $isValid;
        }

        return false;
    }

    /**
     * Validate the DOC file by checking its header.
     *
     * @param  string  $filePath
     * @return bool
     */
    private function isValidDoc($filePath)
    {
        // Read the first 8 bytes of the file to check the DOC signature
        $fileHandle = fopen($filePath, 'rb');
        if ($fileHandle) {
            $header = fread($fileHandle, 8);
            fclose($fileHandle);

            // Check for the DOC file signature
            // DOC files start with D0 CF 11 E0 A1 B1 1A E1 in hexadecimal
            $docSignature = chr(0xD0) . chr(0xCF) . chr(0x11) . chr(0xE0) . chr(0xA1) . chr(0xB1) . chr(0x1A) . chr(0xE1);
            return $header === $docSignature;
        }

        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The file must be a file of type: docx,doc.';
    }
}
