<?php
declare(strict_types=1);

namespace NotionCommotion\AttributeValidatorCommand;

use Nette\Utils\Strings;
use Nette\Utils\Json;
use RuntimeException;
use Exception;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

use NotionCommotion\AttributeValidator\AttributeValidator;
use NotionCommotion\AttributeValidator\AttributeValidatorException;

class AttributeValidatorCommand extends Command
{
    private const COMMANDS = ['validate', 'getClassesWithUndeclaredAttributes', 'getClassesWithoutUndeclaredAttributes', 'getSuspectClasses', 'getNotFoundClasses', 'getTraits', 'getInterfaces', 'getAbstracts', 'jsonSerialize', 'debugSuspectFiles', 'debugFile'];
    protected static $defaultName = 'attribute:validate';
    protected static $defaultDescription = 'Find PHP attributes without defined classes';

    protected function configure(): void
    {
        $this
        ->setDescription('Attribute Validator.  --help')
        ->addArgument('path', InputArgument::OPTIONAL, 'Path to check', 'src')
        ->addOption('command', null, InputOption::VALUE_REQUIRED, 'What command to run?', 'validate')
        ->setHelp(<<<EOF
The <info>%command.name%</info> command lints a YAML file and outputs to STDOUT
This command allows you to check for PHP8 attributes without classesthe first encountered syntax error.
This command allows you to check for PHP8 attributes without classes
EOF
        )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $command = $input->getOption('command');
        if(!in_array($command, self::COMMANDS)) {
            throw new \Exception(sprintf('Invalid command %s.  Only %s are allowed', $command, implode(', ', self::COMMANDS)));
        }
        if($command==='validate') {
            return $this->$command($input, $output);
        }
        else {
            $output->writeln([
                'Attribute Validator',
                '============',
                '',
            ]);
            $output->writeln("Command: ".$command);
            $path = $input->getArgument('path');
            $output->writeln("Path to check: ".$path);
            if($command==='debugFile'){
                print_r(AttributeValidator::debugFile($path));
            }
            else{
                print_r(AttributeValidator::create($path)->$command());
            }
            return Command::SUCCESS;
        }
    }

    private function validate(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln([
            'Attribute Validator',
            '============',
            '',
        ]);

        $path = $input->getArgument('path');
        $output->writeln("Path to check: ".$path);

        $helper = $this->getHelper('question');

        /*
        $question = new ConfirmationQuestion('Continue with this action?', true);
        if (!$helper->ask($input, $output, $question)) {
        return Command::SUCCESS;
        }
        */

        $validator = AttributeValidator::create($path);
        $errors=0;

        foreach($validator->validate() as $type=>$errs) {
            $output->writeln($type.' errors');
            switch($type) {
                case 'classesWithUndeclaredAttributes':
                    foreach($errs as $e) {
                        $output->writeln(sprintf('   %s: %s', $e['fqcn'], $e['filename']));
                        if(!empty($e['classAttributes'])) {
                            $errors++;
                            $output->writeln(sprintf('      classAttributes: %s', implode(', ', $e['classAttributes'])));
                        }
                        foreach(array_intersect_key($e, array_flip(['propertyAttributes', 'methodAttributes', 'parameterAttributes', 'classConstantAttributes'])) as $attrType=>$ar) {
                            $a = [];
                            foreach($ar as $class=>$t) {
                                $errors++;
                                $a[] = sprintf('%s: %s', $class, implode(', ', $t));
                            }
                            if($a) {
                                $output->writeln(sprintf('      %s: %s', $attrType, implode(', ', $a)));
                            }
                        }
                    }
                    break;
                case 'notFoundClasses':
                case 'suspectClasses':
                    foreach($errs as $e) {
                        $errors++;
                        $a = [];
                        foreach(['class', 'trait', 'interface', 'abstract'] as $t) {
                            if($e[$t]) {
                                $a[] = sprintf('%s: %s', $t, implode(', ', $e[$t]));
                            }
                        }
                        $output->writeln(sprintf('   %s: %s %s', $e['filename'], $e['namespace'], implode(' | ', $a)));
                    }
                    break;
                default: throw new AttributeValidatorException('Invalid test: '.$type);
            }
        }
        $output->writeln('Error count: '.$errors);

        return Command::SUCCESS;
    }
}
