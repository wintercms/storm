<?php namespace Winter\Storm\Config;

use PhpParser\Error;
use PhpParser\Lexer\Emulative;
use PhpParser\ParserFactory;
use Winter\Storm\Exception\SystemException;
use Winter\Storm\Parse\PHP\ArrayFile;

/**
 * Configuration rewriter
 *
 * @see https://wintercms.com/docs/services/parser#data-file-array
 *
 * This class lets you rewrite array values inside a basic configuration file
 * that returns a single array definition (a Laravel config file) whilst maintaining
 * the integrity of the file, leaving comments and advanced settings intact.
 */
class ConfigWriter
{
    public function toFile(string $filePath, array $newValues): string
    {
        $arrayFile = ArrayFile::open($filePath)->set($newValues);
        $arrayFile->write();
        return $arrayFile->render();
    }

    public function toContent(string $contents, $newValues): string
    {
        $lexer = new Emulative([
            'usedAttributes' => [
                'comments',
                'startTokenPos',
                'startLine',
                'endTokenPos',
                'endLine'
            ]
        ]);
        $parser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7, $lexer);

        try {
            $ast = $parser->parse($contents);
        } catch (Error $e) {
            throw new SystemException($e);
        }

        return (new ArrayFile($ast, $lexer, null))->set($newValues)->render();
    }
}
