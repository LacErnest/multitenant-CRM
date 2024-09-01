<?php

namespace App\Services\Imports;

use App\Models\Setting;
use Http\Discovery\Exception\NotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use Spatie\MediaLibrary\HasMedia;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Maatwebsite\Excel\HeadingRowImport;

class BaseImport
{
    protected ?HasMedia $mediaModel;
    protected array $matches;
    protected array $fileColumnIndex;
    public bool $haveImportError = false;
    public array $notValidFileRows = [];
    public array $importData = [];

    public function __construct()
    {
        $this->mediaModel = (new Setting())->first();
    }

    public function import($request)
    {
        if (!$this->getFile($request->id)) {
            abort(404, 'File id not found');
        }

        $this->setMatches($request->matches);
        $this->setIndexes($request->id);
        Excel::import($this, $this->getFile($request->id)->getPath());
        $this->getFile($request->id)->delete();
        return $this;
    }

    public function saveFile(Request $request)
    {
        $filename = Str::uuid()->toString();

        if (!$request->file) {
            throw new BadRequestException();
        }

        if (!$this->mediaModel->addMediaFromBase64($request->file)->setFileName($filename.'.csv')->setName($filename)->toMediaCollection($this->mediaCollection)->save()) {
            throw new BadRequestException();
        }

        return response()->json(['id' => $filename,'columns' => $this->getFileColumns($filename),'properties' => $this->getProperties()]);
    }

    private function getFile($file_name)
    {
        return $this->mediaModel->getMedia($this->mediaCollection)->where('name', $file_name)->first();
    }


    protected function getFileColumns(string $filename): array
    {
        $countParams = 1;
        $headers     = $this->getFileHeaders($filename);
        $body        = $this->getFileBody($filename);

        foreach ($headers as $headersName) {
            $result[] = [
            'name' => $headersName,
            'examples' => [],
            ];
        }
        foreach ($body as $bodyValues) {
            if ($countParams++ > 5) {
                break;
            }

            foreach ($bodyValues as $key => $columnValue) {
                if ($columnValue === null) {
                    continue;
                }
                $result[$key]['examples'][] = $columnValue;
            }
        }
        return $result ?? [];
    }

    protected function getFileHeaders(string $filename)
    {
        return (new HeadingRowImport)->toArray($this->getFile($filename)->getPath())[0][0];
    }

    private function getFileBody(string $filename)
    {
        return  Excel::toCollection((new ExampleImport()), $this->getFile($filename)->getPath())->first();
    }

    protected function setMatches($matches)
    {
        foreach ($matches as $match) {
            $this->matches[$match['property']] = $match['column'];
        }
    }

    protected function setIndexes($fileName)
    {
        $headers = $this->getFileHeaders($fileName);
        $this->fileColumnIndex = array_flip($headers);
    }

    protected function getColumnValue($columnName, $row)
    {
        if (!isset($this->matches[$columnName])) {
            return null;
        }

        if (!isset($this->fileColumnIndex[$this->matches[$columnName]])) {
            return null;
        }

        if (!isset($row[$this->fileColumnIndex[$this->matches[$columnName]]])) {
            return null;
        }
        return $row[$this->fileColumnIndex[$this->matches[$columnName]]];
    }

    protected function setNotValidFileRows(array $row, array $messages): void
    {
        if (count($row) && count($messages)) {
            foreach ($messages as $key => $message) {
                $this->notValidFileRows[] = $this->getColumnValue($key, $row);
            }
            if (count($this->notValidFileRows)) {
                $this->haveImportError = true;
            }
        }
    }
}
