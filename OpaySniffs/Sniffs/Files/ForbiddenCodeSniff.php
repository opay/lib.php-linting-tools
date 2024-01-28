<?php

declare(strict_types=1);

namespace Opay\OpaySniffs\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class ForbiddenCodeSniff implements Sniff
{
    // phpcs:ignore OpaySniff.Files.ForbiddenCode
    public array $forbiddenCode = [
        '<?=' => '<?php',
    ];

    private array $errorsOnLines = [];

    public function register(): array
    {
        return [T_OPEN_TAG];
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function process(File $phpcsFile, $stackPtr): void
    {
        if (empty($this->forbiddenCode) === true) {
            return;
        }

        $file = file($phpcsFile->getFilename());

        $tokens = $phpcsFile->getTokens();
        foreach ($this->forbiddenCode as $forbiddenCode => $recommendedCode) {
            if (is_int($forbiddenCode) === true) {
                $forbiddenCode = $recommendedCode;
                $recommendedCode = null;
            }

            foreach ($tokens as $key => $token) {
                $line = $token['line'] - 1;
                $lineContent = $file[$line];
                if (str_contains($lineContent, $forbiddenCode) === false) {
                    continue;
                }

                if ($token['column'] !== 1 || array_key_exists($line, $this->errorsOnLines)) {
                    continue;
                }

                $errorMessage = $recommendedCode !== null
                    ? sprintf('Forbidden code "%s" found. Use "%s" instead.', $forbiddenCode, $recommendedCode)
                    : sprintf('Forbidden code "%s" found.', $forbiddenCode);

                $phpcsFile->addError($errorMessage, $key, 'ForbiddenCodeFound');
            }
        }
    }
}
