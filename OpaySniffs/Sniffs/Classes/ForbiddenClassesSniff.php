<?php

declare(strict_types=1);

namespace Opay\OpaySniffs\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use SlevomatCodingStandard\Helpers\FixerHelper;
use SlevomatCodingStandard\Helpers\NamespaceHelper;
use SlevomatCodingStandard\Helpers\ReferencedNameHelper;
use SlevomatCodingStandard\Helpers\TokenHelper;
use SlevomatCodingStandard\Helpers\UseStatementHelper;

/**
 * This is duplicate of SlevomatCodingStandard.PHP.ForbiddenClasses
 *
 * It does not fix error if alternative namespace does not exist and formats more informative error when several
 * alternatives exist. This "fix" allows specifying more than one alternative separating it with comma.
 * Everything else works identical to original sniff.
 *
 * Example:
 *  <element key="Old\Class" value="Alternative\One, Alternative\Two"/>
 * Result:
 *  9 | ERROR | Usage of \Old\Class is forbidden, use one of these instead: \Alternative\One, \Alternative\Two.
 */
class ForbiddenClassesSniff implements Sniff
{
    public const CODE_FORBIDDEN_CLASS = 'ForbiddenClass';
    public const CODE_FORBIDDEN_PARENT_CLASS = 'ForbiddenParentClass';
    public const CODE_FORBIDDEN_INTERFACE = 'ForbiddenInterface';
    public const CODE_FORBIDDEN_TRAIT = 'ForbiddenTrait';

    public array $forbiddenClasses = [];
    public array $forbiddenExtends = [];
    public array $forbiddenInterfaces = [];
    public array $forbiddenTraits = [];

    private static array $keywordReferences = ['self', 'parent', 'static'];

    /**
     * @return array<int, (int|string)>
     */
    public function register(): array
    {
        $searchTokens = [];

        if (count($this->forbiddenClasses) > 0) {
            $this->forbiddenClasses = self::normalizeInputOption($this->forbiddenClasses);
            $searchTokens[] = T_NEW;
            $searchTokens[] = T_DOUBLE_COLON;
        }

        if (count($this->forbiddenExtends) > 0) {
            $this->forbiddenExtends = self::normalizeInputOption($this->forbiddenExtends);
            $searchTokens[] = T_EXTENDS;
        }

        if (count($this->forbiddenInterfaces) > 0) {
            $this->forbiddenInterfaces = self::normalizeInputOption($this->forbiddenInterfaces);
            $searchTokens[] = T_IMPLEMENTS;
        }

        if (count($this->forbiddenTraits) > 0) {
            $this->forbiddenTraits = self::normalizeInputOption($this->forbiddenTraits);
            $searchTokens[] = T_USE;
        }

        return $searchTokens;
    }

    public function process(File $phpcsFile, $stackPtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $token = $tokens[$stackPtr];
        $nameTokens = array_merge(TokenHelper::getNameTokenCodes(), TokenHelper::$ineffectiveTokenCodes);

        if (
            $token['code'] === T_IMPLEMENTS
            || ($token['code'] === T_USE && UseStatementHelper::isTraitUse($phpcsFile, $stackPtr))
        ) {
            $this->processImplementationOrTrait($phpcsFile, $stackPtr, $tokens);

            return;
        }

        if (in_array($token['code'], [T_NEW, T_EXTENDS], true)) {
            $this->processInheritanceOrNew($phpcsFile, $stackPtr, $token, $nameTokens);

            return;
        }

        // phpcs:ignore SlevomatCodingStandard.ControlStructures.EarlyExit
        if ($token['code'] === T_DOUBLE_COLON && !$this->isTraitsConflictResolutionToken($token)) {
            $this->processDoubleColon($phpcsFile, $stackPtr, $nameTokens);
        }
    }

    private function processImplementationOrTrait(File $phpcsFile, int|null $stackPtr, array $tokens): void
    {
        $token = $tokens[$stackPtr];

        $endTokenPointer = TokenHelper::findNext(
            $phpcsFile,
            [T_SEMICOLON, T_OPEN_CURLY_BRACKET],
            $stackPtr
        );
        $references = $this->getAllReferences($phpcsFile, $stackPtr, $endTokenPointer);

        if ($token['code'] === T_IMPLEMENTS) {
            $this->checkReferences($phpcsFile, $stackPtr, $references, $this->forbiddenInterfaces);

            return;
        }

        // Fixer does not work when traits contains aliases
        $this->checkReferences(
            $phpcsFile,
            $stackPtr,
            $references,
            $this->forbiddenTraits,
            $tokens[$endTokenPointer]['code'] !== T_OPEN_CURLY_BRACKET
        );
    }

    private function processInheritanceOrNew(File $phpcsFile, int|null $stackPtr, array $token, array $nameTokens): void
    {
        $endTokenPointer = TokenHelper::findNextExcluding($phpcsFile, $nameTokens, $stackPtr + 1);
        $references = $this->getAllReferences($phpcsFile, $stackPtr, $endTokenPointer);

        $this->checkReferences(
            $phpcsFile,
            $stackPtr,
            $references,
            $token['code'] === T_NEW ? $this->forbiddenClasses : $this->forbiddenExtends
        );
    }

    private function processDoubleColon(File $phpcsFile, int|null $stackPtr, array $nameTokens): void
    {
        $startTokenPointer = TokenHelper::findPreviousExcluding($phpcsFile, $nameTokens, $stackPtr - 1);
        $references = $this->getAllReferences($phpcsFile, $startTokenPointer, $stackPtr);

        $this->checkReferences($phpcsFile, $stackPtr, $references, $this->forbiddenClasses);
    }

    private function checkReferences(
        File $phpcsFile,
        int $stackPtr,
        array $references,
        array $forbiddenNames,
        bool $isFixable = true
    ): void {
        $token = $phpcsFile->getTokens()[$stackPtr];
        $details = [
            T_NEW => ['class', self::CODE_FORBIDDEN_CLASS],
            T_DOUBLE_COLON => ['class', self::CODE_FORBIDDEN_CLASS],
            T_EXTENDS => ['as a parent class', self::CODE_FORBIDDEN_PARENT_CLASS],
            T_IMPLEMENTS => ['interface', self::CODE_FORBIDDEN_INTERFACE],
            T_USE => ['trait', self::CODE_FORBIDDEN_TRAIT],
        ];

        foreach ($references as $reference) {
            if (!array_key_exists($reference['fullyQualifiedName'], $forbiddenNames)) {
                continue;
            }

            $alternative = $forbiddenNames[$reference['fullyQualifiedName']];
            [$nameType, $code] = $details[$token['code']];

            /*
             * class_exists($alternative) === false - this was the point of original class copying
             */
            if ($alternative === null || $isFixable === false || class_exists($alternative) === false) {
                $this->addErrorWithoutFixing($phpcsFile, $reference, $nameType, $alternative, $code);

                continue;
            }

            $this->addErrorAndFix($phpcsFile, $reference, $nameType, $alternative, $code);
        }
    }

    /**
     * @param array<string, array<int, int|string>|int|string> $token
     */
    private function isTraitsConflictResolutionToken(array $token): bool
    {
        return is_array($token['conditions']) && array_pop($token['conditions']) === T_USE;
    }

    /**
     * @return array{fullyQualifiedName: string, startPointer: int|null, endPointer: int|null}[]
     */
    private function getAllReferences(File $phpcsFile, int $startPointer, int $endPointer): array
    {
        // Always ignore first token
        $startPointer++;
        $references = [];

        while ($startPointer < $endPointer) {
            $nextComma = TokenHelper::findNext($phpcsFile, [T_COMMA], $startPointer + 1);
            $nextSeparator = min($endPointer, $nextComma ?? PHP_INT_MAX);
            $reference = ReferencedNameHelper::getReferenceName($phpcsFile, $startPointer, $nextSeparator - 1);

            if (
                empty($reference) === false
                && !in_array(strtolower($reference), self::$keywordReferences, true)
            ) {
                $references[] = [
                    'fullyQualifiedName' => NamespaceHelper::resolveClassName($phpcsFile, $reference, $startPointer),
                    'startPointer' => TokenHelper::findNextEffective($phpcsFile, $startPointer, $endPointer),
                    'endPointer' => TokenHelper::findPreviousEffective($phpcsFile, $nextSeparator - 1, $startPointer),
                ];
            }

            $startPointer = $nextSeparator + 1;
        }

        return $references;
    }

    private static function normalizeInputOption(array $option): array
    {
        $forbiddenClasses = [];
        foreach ($option as $forbiddenClass => $alternative) {
            $forbiddenClasses[self::normalizeClassName($forbiddenClass)] = self::normalizeClassName($alternative);
        }

        return $forbiddenClasses;
    }

    private static function normalizeClassName(?string $typeName): ?string
    {
        if (empty($typeName) === true || strtolower($typeName) === 'null') {
            return null;
        }

        return NamespaceHelper::getFullyQualifiedTypeName($typeName);
    }

    private function addErrorWithoutFixing(
        File $phpcsFile,
        array $reference,
        string $nameType,
        string|null $alternative,
        string $code
    ): void {
        if ($alternative === null) {
            $phpcsFile->addError(
                sprintf('Usage of %s %s is forbidden.', $reference['fullyQualifiedName'], $nameType),
                $reference['startPointer'],
                $code
            );

            return;
        }

        $alternatives = array_map('trim', explode(',', $alternative));
        $phpcsFile->addError(
            sprintf(
                count($alternatives) === 1
                    ? 'Usage of %s %s is forbidden, use %s instead.'
                    : 'Usage of %s %s is forbidden, use one of these instead: %s.',
                $reference['fullyQualifiedName'],
                $nameType,
                implode(', \\', $alternatives)
            ),
            $reference['startPointer'],
            $code
        );
    }

    private function addErrorAndFix(
        File $phpcsFile,
        array $reference,
        string $nameType,
        string $alternative,
        string $code
    ): void {
        $fix = $phpcsFile->addFixableError(
            sprintf(
                'Usage of %s %s is forbidden, use %s instead.',
                $reference['fullyQualifiedName'],
                $nameType,
                $alternative
            ),
            $reference['startPointer'],
            $code
        );

        if (!$fix) {
            return;
        }

        $phpcsFile->fixer->beginChangeset();
        $phpcsFile->fixer->replaceToken($reference['startPointer'], $alternative);
        FixerHelper::removeBetweenIncluding(
            $phpcsFile,
            $reference['startPointer'] + 1,
            $reference['endPointer']
        );
        $phpcsFile->fixer->endChangeset();
    }
}
