<?php

declare(strict_types=1);

namespace App\CodingStandard\Sniffs\Classes;

use Illuminate\Support\Str;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\UseStatement;
use SlevomatCodingStandard\Helpers\UseStatementHelper;

class NonExistingUsesSniff implements Sniff
{
    public array $ignoreNamespaces = [];
    public array $ignoreGlobalNamespaces = [];

    public function register(): array
    {
        return [T_OPEN_TAG];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        collect(UseStatementHelper::getFileUseStatements($phpcsFile))
            ->each(function ($statements) use (&$phpcsFile) {
                collect($statements)->each(function (UseStatement $statement) use (&$phpcsFile) {
                    if ($this->isValidImport($statement->getFullyQualifiedTypeName())) {
                        return;
                    }

                    $phpcsFile->addError(
                        'Namespace "%s" does not exist',
                        $statement->getPointer(),
                        'NonExistingNamespace',
                        [$statement->getFullyQualifiedTypeName()]
                    );
                });
            });
    }

    private function isValidImport(string $namespace): bool
    {
        if (class_exists($namespace) || interface_exists($namespace) || trait_exists($namespace)) {
            return true;
        }

        if (collect($this->ignoreNamespaces)->contains($namespace)) {
            return true;
        }

        return collect($this->ignoreGlobalNamespaces)->contains(Str::of($namespace)->explode('\\')->first());
    }
}
