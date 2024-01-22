<?php

namespace Opay\OpaySniffs\Sniffs\Files;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

class ForbiddenDumps implements Sniff
{
    private array $forbiddenDumps = array('var_dump', 'd', 'dd', 'exit', 'die');

    public function register(): array
    {
        return array(T_EXIT, T_STRING);
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['type'] !== 'T_STRING' && $tokens[$stackPtr]['type'] !== 'T_EXIT') {
            return;
        }

        $functionName = strtolower($tokens[$stackPtr]['content']);

        if (in_array($functionName, $this->forbiddenDumps) === false) {
            return;
        }

        $endOfMethod = $phpcsFile->findNext(T_SEMICOLON, $stackPtr);
        $error = 'Detected '
            . $tokens[$stackPtr]['content']
            . '(). Avoid using '
            . $tokens[$stackPtr]['content']
            . '() in production code.';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'FoundFunctionCall');

        if ($fix) {
            $phpcsFile->fixer->beginChangeset();

            $lineStart = $phpcsFile
                    ->findPrevious(T_WHITESPACE, $stackPtr - 1, null, true) + 1;
            $lineEnd = $phpcsFile
                    ->findNext(T_WHITESPACE, $endOfMethod + 1, null, true) - 1;

            for ($i = $lineStart; $i <= $lineEnd; ++$i) {
                $phpcsFile->fixer->replaceToken($i, '');
            }

            $phpcsFile->fixer->endChangeset();
        }
    }
}
