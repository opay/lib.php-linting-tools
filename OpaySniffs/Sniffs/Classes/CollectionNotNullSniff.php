<?php

declare(strict_types=1);

namespace Opay\OpaySniffs\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;

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

    public function process(File $phpcsFile, int $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] !== T_VARIABLE) {
            return;
        }

        $this->processTypedProperty($phpcsFile, $stackPtr);
    }

    protected function processTypedProperty(File $phpcsFile, int $stackPtr): void
    {
        $visibilityPtr = $phpcsFile->findPrevious([T_PUBLIC, T_PROTECTED, T_PRIVATE], $stackPtr - 1);
        if ($visibilityPtr === false) {
            return;
        }

        $functionPtr = $phpcsFile->findPrevious(T_FUNCTION, $stackPtr - 1, $visibilityPtr);
        if ($functionPtr !== false) {
            return;
        }

        $tokens = $phpcsFile->getTokens();
        $nullablePtr = false;
        $typeString = $this->getTypeString($visibilityPtr, $stackPtr, $nullablePtr, $tokens);

        if (empty($typeString)) {
            return;
        }

        $this->validateQuestionSymbol($phpcsFile, $stackPtr, $nullablePtr, $typeString);
        $this->validateTypedNull($phpcsFile, $stackPtr, $typeString);
    }

    private function getTypeString(int $visibilityPtr, int $stackPtr, int|false &$nullablePtr, array $tokens): string
    {
        $typeString = '';

        for ($i = $visibilityPtr + 1; $i < $stackPtr; $i++) {
            $code = $tokens[$i]['code'];

            if (in_array($code, [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_STATIC, T_READONLY], true)) {
                continue;
            }

            if ($code === T_NULLABLE) {
                $nullablePtr = $i;
            }

            $typeString .= $tokens[$i]['content'];
        }

        return trim($typeString);
    }

    private function validateQuestionSymbol(
        File $phpcsFile,
        int $stackPtr,
        int|false $nullablePtr,
        string $typeString
    ): void {
        if ($nullablePtr === false && str_starts_with($typeString, '?') === false) {
            return;
        }

        $typeName = ltrim(substr($typeString, 1), '\\');
        if ($this->isCollectionType($typeName)) {
            $this->addError($phpcsFile, $stackPtr, 'NullablePrefixFound', $typeString);
        }
    }

    private function validateTypedNull(File $phpcsFile, int $stackPtr, string $typeString): void
    {
        if (str_contains($typeString, '|') === false) {
            return;
        }
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
            'Property type "%s" is not allowed. Return an empty Collection or array instead.',
            $propertyName
        );
        $phpcsFile->addError($error, $stackPtr, $errorCode);
    }
}
