<?php

declare(strict_types=1);

namespace Valantic\PimcoreApiDocumentationBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Console\Attribute\AsCommand;
use Valantic\PimcoreApiDocumentationBundle\Contract\Service\DocsGeneratorInterface;

#[AsCommand(
    name: 'valantic:api-doc:generate',
    description: 'Generate API docs for controller actions.'
)]
class DocGeneratorCommand extends Command
{
    public function __construct(
        private readonly DocsGeneratorInterface $docsGenerator,
        #[Autowire('%valantic.pimcore_api_doc.docs_file%')]
        private readonly string $filePath,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->docsGenerator->generate($this->filePath);
        $output->writeln('<info>API documentation generated successfully.</info>');

        return Command::SUCCESS;
    }
}
