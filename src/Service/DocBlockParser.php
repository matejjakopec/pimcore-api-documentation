<?php

declare(strict_types=1);

namespace Valantic\PimcoreApiDocumentationBundle\Service;

use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\VarTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use PHPStan\PhpDocParser\ParserConfig;
use ReflectionClass;
use Valantic\PimcoreApiDocumentationBundle\Contract\Service\DocBlockParserInterface;
use Valantic\PimcoreApiDocumentationBundle\Enum\TypeEnum;

class DocBlockParser implements DocBlockParserInterface
{
    private readonly Lexer $lexer;
    private readonly PhpDocParser $phpDocParser;

    public function __construct()
    {
        $usedAttributes = [
            'lines' => true,
            'indexes' => true,
            'comments' => true,
        ];

        $config = new ParserConfig($usedAttributes);

        $this->lexer = new Lexer($config);
        $constExprParser = new ConstExprParser($config);
        $typeParser = new TypeParser($config, $constExprParser);
        $this->phpDocParser = new PhpDocParser($config, $typeParser, $constExprParser);
    }

    public function parseDocBlock(string $docBlock): array
    {
        $docBlockData = [];
        $tokens = new TokenIterator($this->lexer->tokenize($docBlock));
        $parsedDocBlock = $this->phpDocParser->parse($tokens);

        foreach ($parsedDocBlock->children as $docBlockItem) {
            $parameterName = null;

            if ($docBlockItem->value instanceof ParamTagValueNode) {
                $parameterName = ltrim($docBlockItem->value->parameterName, '$');
            }

            if ($docBlockItem->value instanceof VarTagValueNode) {
                $parameterName = ltrim($docBlockItem->value->variableName, '$');
            }

            if ($parameterName) {
                $docBlockData[$parameterName] = $docBlockItem;
            }
        }

        return $docBlockData;
    }

    public function determineTypeHint(PhpDocChildNode $docBlock, ReflectionClass $reflectionClass): array
    {
        $useStatements = $this->parseUseStatements($reflectionClass);
        $typeHint = $docBlock->value->type->type->name ?? null;

        if ($typeHint === null) {
            return [];
        }

        $typeHintStr = (string) $typeHint;

        if (array_key_exists(strtolower($typeHintStr), $useStatements)) {
            return [$useStatements[strtolower($typeHintStr)]];
        }

        $classTypeHint = sprintf('%s\\%s', $reflectionClass->getNamespaceName(), $typeHintStr);

        if (class_exists($classTypeHint)) {
            return [$classTypeHint];
        }

        if (TypeEnum::tryFrom($typeHintStr)) {
            return [TypeEnum::tryFrom($typeHintStr)->swaggerEnum()];
        }

        return [];
    }

    private function parseUseStatements(ReflectionClass $class): array
    {
        $file = $class->getFileName();
        $contents = file_get_contents($file);

        preg_match_all('/use\s+([a-zA-Z0-9_\\\\]+)\s*;/', $contents, $matches);

        $useStatements = [];

        foreach ($matches[1] as $fqn) {
            $parts = explode('\\', $fqn);
            $alias = strtolower(end($parts));
            $useStatements[$alias] = $fqn;
        }

        return $useStatements;
    }
}
