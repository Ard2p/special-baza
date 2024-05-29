<?php

namespace Modules\Orders\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class DocumentConverter
{

    protected string $html, $name, $view = 'document_wrapper';

    protected string $bin = 'chromium';

    protected bool $prefixExecWithExportHome = true;

    public function setData(string $name, string $html): static
    {
        $this->name = $name;
        $this->html = $html;

        return $this;
    }

    public function generatePdf(): ?string
    {
        $htmlContent = view('document_wrapper', [
            'html' => $this->html,
            'name' => $this->name,
        ])->render();

        $tmpName = md5(Str::random(6)) .'.html';
        $fullPath = "uploads/tmp/{$tmpName}";
        Storage::disk('local')->put(
            $fullPath,
            $htmlContent
        );
        $url = 'file://'.Storage::disk('local')->path($fullPath);

        $result = $this->execute($url);

        return $result['pdf_path'];
    }

    protected function execute($url, string $input = ''): array
    {
        $filePath  = $this->getPdfFullPath();
        $outputPath = Storage::disk('local')->path($filePath);

        $cmd = $this->makeCommand($outputPath, $url);

        // Cannot use $_SERVER superglobal since that's empty during UnitUnishTestCase
        // getenv('HOME') isn't set on Windows and generates a Notice.
        if ($this->prefixExecWithExportHome) {
            $home = getenv('HOME');
            if (!is_writable($home)) {
                $cmd = 'export HOME=/tmp && '.$cmd;
            }
        }
        $process = proc_open($cmd, [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);

        if (false === $process) {
            throw new UnprocessableEntityHttpException('Render failed');
        }

        fwrite($pipes[0], $input);
        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);
        $rtn = proc_close($process);

        return [
            'stdout' => $stdout,
            'stderr' => $stderr,
            'return' => $rtn,
            'pdf_path' => $filePath
        ];
    }

    protected function makeCommand(string $outputDirectory,  string $url): string
    {
        $outputDirectory = escapeshellarg($outputDirectory);

        return "{$this->bin} --headless --disable-gpu --run-all-compositor-stages-before-draw --no-sandbox --print-to-pdf-no-header --print-to-pdf={$outputDirectory} {$url}";
    }

    protected function getPdfFullPath(): string
    {
        $fileName = md5(Str::random(6)) .'.pdf';
        return "uploads/tmp/$fileName";
    }
}