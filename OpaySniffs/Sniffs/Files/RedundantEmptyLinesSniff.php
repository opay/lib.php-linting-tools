<?php

declare(strict_types=1);

namespace Opay\OpaySniffs\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class RedundantEmptyLinesSniff implements Sniff
{
    private array $errorsOnLines = [];

    public function register(): array
    {
        return [T_WHITESPACE];
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function process(File $phpcsFile, $stackPtr): void
    {
        $file = file($phpcsFile->getFilename());

        $lastLineContent = null;

        $tokens = $phpcsFile->getTokens();
        foreach ($tokens as $key => $token) {
            $line = $token['line'] - 1;
            $content = trim($file[$line]);

            if ($lastLineContent === null) {
                $lastLineContent = $content;

                continue;
            }

            if ($token['column'] !== 1 || array_key_exists($line, $this->errorsOnLines)) {
                continue;
            }

            if (empty($content) && empty($lastLineContent)) {
                $this->errorsOnLines[$line] = true;

                $phpcsFile->addError(
                    'Redundant empty line found.',
                    $key,
                    'RedundantEmptyLineFound'
                );
            }

            $lastLineContent = $content;
        }
    }
}
