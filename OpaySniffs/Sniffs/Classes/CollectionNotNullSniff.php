<?php

declare(strict_types=1);

namespace Opay\OpaySniffs\Sniffs\Classes;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;
class CollectionNotNullSniff implements Sniff
{
    protected array $collectionClasses = [
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
        $tokens = $phpcsFile->getTokens();

        $varPtr = $phpcsFile->findNext(T_VARIABLE, $stackPtr + 1);
        if ($varPtr === false) {
            return;
        }

        $nullablePtr = $phpcsFile->findNext(T_NULLABLE, $stackPtr + 1, $varPtr);

        $typeString = '';
        for ($i = $stackPtr + 1; $i < $varPtr; $i++) {
            if (in_array($tokens[$i]['code'], [T_STRING, T_NS_SEPARATOR, T_TYPE_UNION, T_NULLABLE], true)) {
                $typeString .= $tokens[$i]['content'];
            }
        }

        if (empty($typeString)) {
            return;
        }

        // Check 1: Short nullable format (?Collection)
        if ($nullablePtr !== false) {
            $typeName = ltrim(substr($typeString, 1), '\\');
            if ($this->isCollectionType($typeName)) {
                $this->addError($phpcsFile, $stackPtr,'NullablePrefixFound', $typeName);
            }
        }

        // Check 2: PHP 8.0+ Union type (Collection|null or null|Collection)
        if (str_contains($typeString, '|')) {
            $types = explode('|', $typeString);
            $hasNull = in_array('null', array_map('strtolower', $types), true);

            if ($hasNull) {
                foreach ($types as $type) {
                    $cleanType = ltrim($type, '\\');
                    if ($this->isCollectionType($cleanType)) {
                        $this->addError($phpcsFile, $stackPtr,'UnionNullFound', $typeString);
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
            'Property type "?%s" is not allowed. Collections should never be nullable; return an empty Collection instead.',
            $propertyName
        );
        $phpcsFile->addError($error, $stackPtr, $errorCode);
    }
}