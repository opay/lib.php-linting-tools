<?php

declare(strict_types=1);

namespace Opay\OpaySniffs\Sniffs\Classes;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
class CollectionNotNullSniff implements Sniff
{
    protected array $collectionClasses = [
        'array',
        'Collection',
        'Illuminate\Support\Collection',
        'Illuminate\Database\Eloquent\Collection',
    ];

    public function register(): array
    {
        return [
            T_VARIABLE,
            T_DOC_COMMENT_STRING,
        ];
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] === T_VARIABLE) {
            $this->processTypedProperty($phpcsFile, $stackPtr);
        }
    }

    protected function processTypedProperty(File $phpcsFile, int $stackPtr): void
    {
        // 1. Locate property visibility (public, protected, private)
        $visibilityPtr = $phpcsFile->findPrevious([T_PUBLIC, T_PROTECTED, T_PRIVATE], $stackPtr - 1);
        if ($visibilityPtr === false) {
            return;
        }

        // 2. Ignore standard method parameters (where a function keyword sits between visibility and variable)
        $functionPtr = $phpcsFile->findPrevious(T_FUNCTION, $stackPtr - 1, $visibilityPtr);
        if ($functionPtr !== false) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $typeString = '';
        $nullablePtr = false;

        // 3. Build complete type string between visibility modifier and variable name
        for ($i = $visibilityPtr + 1; $i < $stackPtr; $i++) {
            $code = $tokens[$i]['code'];

            // Skip whitespace, comments, and modifiers like readonly / static
            if (in_array($code, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_STATIC, T_READONLY], true)) {
                continue;
            }

            if ($code === T_NULLABLE) {
                $nullablePtr = $i;
            }

            $typeString .= $tokens[$i]['content'];
        }

        $typeString = trim($typeString);

        if (empty($typeString)) {
            return;
        }

        if ($nullablePtr !== false || str_starts_with($typeString, '?')) {
            $typeName = ltrim(substr($typeString, 1), '\\');
            if ($this->isCollectionType($typeName)) {
                $this->addError($phpcsFile, $stackPtr, 'NullablePrefixFound', $typeString);
                return;
            }
        }

        if (str_contains($typeString, '|')) {
            $types = explode('|', $typeString);

            $hasNull = false;
            foreach ($types as $t) {
                if (strtolower(trim($t)) === 'null') {
                    $hasNull = true;
                    break;
                }
            }

            if ($hasNull) {
                foreach ($types as $type) {
                    $cleanType = ltrim(trim($type), '\\');
                    if ($this->isCollectionType($cleanType)) {
                        $this->addError($phpcsFile, $stackPtr, 'UnionNullFound', $typeString);
                        break;
                    }
                }
            }
        }
    }

    protected function isCollectionType(string $type): bool
    {
        foreach ($this->collectionClasses as $target) {
            if ($type === $target || str_ends_with($type, '\\' . $target)) {
                return true;
            }
        }

        return false;
    }

    private function addError(File $phpcsFile, int $stackPtr, string $errorCode, string $propertyName): void
    {
        $error = sprintf(
            'Property type "%s" is not allowed. Collections should never be nullable; return an empty Collection instead.',
            $propertyName
        );
        $phpcsFile->addError($error, $stackPtr, $errorCode);
    }
}