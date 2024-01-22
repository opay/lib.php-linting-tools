<?php

declare(strict_types=1);

namespace Opay\OpaySniffs\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class ForbiddenDumpsSniff implements Sniff
{
    private array $forbiddenDumps = ['var_dump', 'd', 'dd', 'exit', 'die'];

    public function register(): array
    {
        return [T_EXIT, T_STRING];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['type'] !== 'T_STRING' && $tokens[$stackPtr]['type'] !== 'T_EXIT') {
            return;
        }

        $functionName = strtolower($tokens[$stackPtr]['content']);

        if (in_array($functionName, $this->forbiddenDumps, true) === false) {
            return;
        }

        $error = 'Detected '
            . $tokens[$stackPtr]['content']
            . '(). Avoid using '
            . $tokens[$stackPtr]['content']
            . '() in production code.';

        $phpcsFile->addError($error, $stackPtr, 'FoundFunctionCall');
    }
}
