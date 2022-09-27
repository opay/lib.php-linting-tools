<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAIAAAACACAMAAAD04JH5AAAABGdBTUEAALGPC/xhBQAAAAFzUkdCAK7OHOkAAABjUExURQBFvEG5f//////LVXSFjwBFvABFvABFvP/LVUG5fwZRvP/MU+nw+fb4+zBsytbj8xpdxIep4KS957/R7leH03CZ2UJ5zv/lqiBzpC2Ykf/03f7Vd6+kd9i4Zqbbxlq+lIDMrg+dfsgAAAAKdFJOU/L////+4//q6vJXlRrmAAAEIElEQVR42sXa23LjIAwA0Nh1610CxvgW59Ld/v9XLkk2M52OAAnJhpf2pfWxJECQHN4/8oa6Hd8Exq/DIRPwURzQFAe8FQasxQGfx7IAVRogMw04gKY0YC1chDJFwAGIFAEL0BROgchKwAJI5IAHaAqnQGIe8AACOWAC1sIpEAgBF/Asw+PxuDug75bZOTdcTo9xvd4dOwFUP7nRalN/H+ZyeSq2BqhuHnUdGHfEtoA+8vRXJE7bATpna8S4h+G4AaB3ukYOTzhKA/rZ1oSBTgQWsIw1cVyugoDemZo+TmIA+uvjg4ABTLrOHIhKSANUVvhfq8KJDVCuZo0TE6CGut5UkABw3z8tSAAEnp8QxAGzkQBEKzEKWJLzzxitjeGsBzFAH139jR3maen8WKZ5iO/SEUEEEJsAZnSL+tmnmJwyiAAmE378pADwMoTDcKUDwgkAH5/YNIJJCANCM1A7FVs3NDEJQUAX+E92SeyclhaCICBQgWOXbBwtKQQhQCAAY49oXWGBuZIAcAXYDtU8a0IIAgB4CugF18DCExiuggBgBt9hxnbwDh8CGKDA+Two9BFiZALALOoOf4YCkwCWIQyYwABQDpHgLD4dsYCBGQC/HhlkDkCAstwAwFV0wQLAEpho9wgzWASfj+uc7wMEQCVgexoAfol1bZrmdrt93of/eWsaCODYGQjk4P9Col7D/95CAKgGHfUmacC9BQgA8GaiAuZ8ABQ92iQMTcRRoQDQTqR7CYBFAjTqTzOmATCVtgNA/0X3hSPAAfR7psBKABhFCE1DQ56G0HqOnIbQQlTvuRCBq+i851K89WaUBEDp0wLbMbShtNiGxAg0JNCG0u7ZkkGrWbtnUwq9AwyYuW05fL0zoQHwwWQhrEIGW8cw4GOToxkYwnbHw+lEAGxxPIf3s3aDC4rANRHcV7e7XdEEpnG72yVV4GDRSl/TTYHnh9axdq+LytDJqj2cn2P143la2+SqNtjStYff1bdxh7wUkcvqIXBZHf6L4F76A/BSqEgd5lzXh/cRCPBAeEM/Sn1gEZm9IcDDIPWRTWwFjQA84UvmQ6tYMxUFVNUfiedH7zYSAAmBUxwAXxB/fhrw9w8z/okuJgnwMeB8fJ/sYRCA6iv7Cww2fZjAAKq/Nu/5I6J/QQF8IWSkQTtME4sD+DSQgzDiGkgswAeBVAl2RvbwaACJYB36LE0AoAmExxMBnvBlEwY9TqSbBCLgbphHHZgTRo9zR7xQpAOq8/0rncOPr3QabQc39eTrzBxAtT561v9favXD//CtEfkiMR/wFAiNLICkIA9QqdKAsyoMkEtCLkBMkA2QSkI2oDqXBgglgQGQSQIDIBMCDkAkBByASAhYAIkVmQdYSwMEqoAHEAgBE3AuDeCXIRewlgawy/D9H2R8Jacx7EvaAAAAAElFTkSuQmCC" alt="OPAY" style="width: 128px; height: 128px; float: left; margin: 0 15px 15px 0;"/>

PHP linting rules
=============================

This is PHP linting tools and rules set used by "OPAY solutions" developers to ensure we deliver maintainable and the highest quality code.

The main point of this tools set is to ensure developers write [PSR compliant code](https://www.php-fig.org/psr/). Many [additional rules](./OpaySniffs/ruleset.xml) are added to increase code quality and readability. None of our code goes to production if at least one code style error or warning is discovered.
<br clear="all"/>

Package content
---------------
- [friendsofphp/php-cs-fixer](https://github.com/FriendsOfPHP/PHP-CS-Fixer) - Powerful PHP Coding Standards tool
- [squizlabs/php_codesniffer](https://github.com/squizlabs/PHP_CodeSniffer) - Another powerful and customizable PHP Coding Standards tool
- [slevomat/coding-standard](https://github.com/slevomat/coding-standard) - Set of additional linting rules for PHP CodeSniffer
- [moxio/php-codesniffer-sniffs](https://github.com/Moxio/php-codesniffer-sniffs) - Set of additional linting rules for PHP CodeSniffer
- Custom Opay linting rules for PHP CodeSniffer

`PHP-CS-Fixer` and `PHP_CodeSniffer` are both PHP code linting tools that complement each other allowing developers to write the highest quality code.

Installation & usage
--------------------
Install as a development dependency using composer:
```
$ composer require --dev opay-dev/php-linting-tools
```
Run tools to validate your files:
```
vendor/bin/php-cs-fixer fix path/to/files --dry-run --verbose
vendor/bin/phpcs --standard=vendor/opay-dev/php-linting-tools/OpaySniffs path/to/files
```
Run tools to fix your files automatically _(not all files can be fixed, some may require manual fixing)_:
```
vendor/bin/php-cs-fixer fix path/to/files --verbose
vendor/bin/phpcbf --standard=vendor/opay-dev/php-linting-tools/OpaySniffs path/to/files
```
Setup custom config and run tools to validate your files:
```
vendor/bin/php-cs-fixer fix --config="ConfigExamples/custom_phpcsfixer_config.php" --dry-run --verbose
vendor/bin/phpcs --standard="ConfigExamples/custom_phpcs_config.xml"
```
Setup custom config and run tools to fix your files automatically:
```
vendor/bin/php-cs-fixer fix --config="ConfigExamples/custom_phpcsfixer_config.php" --verbose
vendor/bin/phpcbf --standard="ConfigExamples/custom_phpcs_config.xml"
```
Configure [bash script](./lint) or add script to `compsoer.json` and run it with single command `composer lint`:
```json
{
    "lint": [
        "vendor/bin/php-cs-fixer fix path/to/files --dry-run --verbose",
        "vendor/bin/phpcs --standard=vendor/opay-dev/php-linting-tools/OpaySniffs path/to/files"
    ]
}
```

Licence
-------
This is set of tools created by different developers teams and collected to one set by Opay developers with additional rules added. This package can be used under MIT licence as long as it does not violate the licenses of other developers.
