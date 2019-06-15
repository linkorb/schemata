<?php

namespace LinkORB\Schemata\Service;

use Parsedown;
use Symfony\Component\Finder\Finder;

class SchemaPagesParserService
{
    private const FILE_INPUT_EXTENSION  = 'md';
    private const FILE_OUTPUT_EXTENSION = 'html';

    /**
     * @var string
     */
    private $pathMarkdown;

    /**
     * @var Parsedown
     */
    private $parser;

    public function __construct($pathMarkdown, Parsedown $parser)
    {
        $this->pathMarkdown = $pathMarkdown;
        $this->parser = $parser;
    }

    public function parse(): array
    {
        $files = $this->findFiles();

        return $this->mdToHtml($files);
    }

    private function findFiles(): array
    {
        $res = [];

        $finder = new Finder();
        $finder
            ->files()
            ->in($this->pathMarkdown)
            ->name(['*.' . self::FILE_INPUT_EXTENSION]);

        foreach ($finder as $file) {
            $contents = $file->getContents();
            $res[$file->getBasename()] = $contents;
        }

        return $res;
    }

    private function mdToHtml(array $files): array
    {
        $convertedFiles = [];
        $hasIndex = false;

        foreach ($files as $name => $contents) {
            if (false === $hasIndex && 'index.' . self::FILE_INPUT_EXTENSION === $name) {
                $hasIndex = true;
            }

            $name = preg_replace(
                '/\.' . self::FILE_INPUT_EXTENSION . '$/',
                '.' . self::FILE_OUTPUT_EXTENSION,
                $name
            );
            $convertedFiles[$name] = $this->parser->text($contents);
        }

        if (false === $hasIndex) {
            $convertedFiles['index.' . self::FILE_OUTPUT_EXTENSION] = $this->parser->text(
                'No `index.' . self::FILE_INPUT_EXTENSION . '` file found.'
            );
        }

        return $convertedFiles;
    }
}
