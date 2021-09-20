# attribute-validator

Used to find PHP 8 attributes without defined classes.  While annotations will result in error if the class does not exist, not true for attributes.  [LICENSE](LICENSE.txt)

## Installation

composer require notion-commotion/attribute-validator-command

## Usage

Add a Symfony command (I am sure there is a more proper way of doing this but don't yet know what it is.)

    <?php
    declare(strict_types=1);
    namespace App\Command;
    use NotionCommotion\AttributeValidator\AttributeValidator;
    class AttributeValidator extends AttributeValidatorCommand{}

Execute from the command line:

    $ bin/console app:attribute-validator