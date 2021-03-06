<?php

declare(strict_types=1);

namespace Symplify\PHPStanExtensions\ErrorFormatter;

use Nette\Utils\Strings;
use PHPStan\Command\AnalysisResult;
use PHPStan\Command\ErrorFormatter\ErrorFormatter;
use PHPStan\Command\Output;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Terminal;
use Symplify\PackageBuilder\Console\ShellCode;
use Symplify\SmartFileSystem\SmartFileInfo;

final class SymplifyErrorFormatter implements ErrorFormatter
{
    /**
     * To fit in Linux/Windows terminal windows to prevent overflow.
     * @var int
     */
    private const BULGARIAN_CONSTANT = 8;

    /**
     * @var SymfonyStyle
     */
    private $symfonyStyle;

    /**
     * @var Terminal
     */
    private $terminal;

    public function __construct(SymfonyStyle $symfonyStyle, Terminal $terminal)
    {
        $this->symfonyStyle = $symfonyStyle;
        $this->terminal = $terminal;
    }

    public function formatErrors(AnalysisResult $analysisResult, Output $output): int
    {
        if ($analysisResult->getTotalErrorsCount() === 0 && $analysisResult->getWarnings() === []) {
            $this->symfonyStyle->success('No errors');
            return ShellCode::SUCCESS;
        }

        $this->reportErrors($analysisResult);

        foreach ($analysisResult->getNotFileSpecificErrors() as $notFileSpecificError) {
            $this->symfonyStyle->warning($notFileSpecificError);
        }

        foreach ($analysisResult->getWarnings() as $warning) {
            $this->symfonyStyle->warning($warning);
        }

        return ShellCode::ERROR;
    }

    private function reportErrors(AnalysisResult $analysisResult): void
    {
        if ($analysisResult->getFileSpecificErrors() === []) {
            return;
        }

        foreach ($analysisResult->getFileSpecificErrors() as $fileSpecificError) {
            $this->separator();

            // clickable path
            $relativeFilePath = $this->getRelativePath($fileSpecificError->getFile());
            $this->symfonyStyle->writeln(' ' . $relativeFilePath . ':' . $fileSpecificError->getLine());
            $this->separator();

            // ignored path
            $regexMessage = $this->regexMessage($fileSpecificError->getMessage());
            $itemMessage = sprintf(" - '%s'", $regexMessage);
            $this->symfonyStyle->writeln($itemMessage);

            $this->separator();
            $this->symfonyStyle->newLine();
        }

        $this->symfonyStyle->newLine(1);

        $errorMessage = sprintf('Found %d errors', $analysisResult->getTotalErrorsCount());
        $this->symfonyStyle->error($errorMessage);
    }

    private function separator(): void
    {
        $separator = str_repeat('-', $this->terminal->getWidth() - self::BULGARIAN_CONSTANT);
        $this->symfonyStyle->writeln(' ' . $separator);
    }

    private function getRelativePath(string $filePath): string
    {
        // remove trait clutter
        $clearFilePath = Strings::replace($filePath, '#(?<file>.*?)(\s+\(in context.*?)?$#', '$1');
        if (! file_exists($clearFilePath)) {
            return $clearFilePath;
        }

        return (new SmartFileInfo($clearFilePath))->getRelativeFilePathFromDirectory(getcwd());
    }

    private function regexMessage(string $message): string
    {
        // remove extra ".", that is really not part of message
        $message = rtrim($message, '.');

        return '#' . preg_quote($message, '#') . '#';
    }
}
