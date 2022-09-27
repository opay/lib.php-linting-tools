<?php

declare(strict_types=1);

namespace Opay\PhpLintingTools\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\UseStatementHelper;

class NonExistingUsesSniff implements Sniff
{
    public array $ignoreNamespaces = [];
    public array $ignoreGlobalNamespaces = [
        'OpenApi',
        'JetBrains',
    ];

    public function register(): array
    {
        return [T_OPEN_TAG];
    }

    // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter
    public function process(File $phpcsFile, $stackPtr): void
    {
        $statementsList = UseStatementHelper::getFileUseStatements($phpcsFile);
        foreach ($statementsList as $statements) {
            foreach ($statements as $statement) {
                if ($this->isValidImport($statement->getFullyQualifiedTypeName())) {
                    continue;
                }

                $phpcsFile->addError(
                    'Namespace "%s" does not exist',
                    $statement->getPointer(),
                    'NonExistingNamespace',
                    [$statement->getFullyQualifiedTypeName()]
                );
            }
        }
    }

    private function isValidImport(string $namespace): bool
    {
        if (in_array($namespace, $this->ignoreNamespaces, true)) {
            return true;
        }

        if (in_array(explode('\\', $namespace)[0], $this->ignoreGlobalNamespaces, true)) {
            return true;
        }

        return class_exists($namespace) || interface_exists($namespace) || trait_exists($namespace);
    }
}
