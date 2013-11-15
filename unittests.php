<?php
/**
 * Scabbia2 PHP Framework
 * http://www.scabbiafw.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        http://github.com/scabbiafw/scabbia2 for the canonical source repository
 * @copyright   2010-2013 Scabbia Framework Organization. (http://www.scabbiafw.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 */

require __DIR__ . "/psr0autoloader.php";
spl_autoload_register('autoload');

$tTestClasses = [
    "Scabbia\\Yaml\\Tests\\ParserTest",
    "Scabbia\\Yaml\\Tests\\InlineTest"
];

$tExitCode = Scabbia\Tests\Tests::runUnitTests($tTestClasses);

exit($tExitCode);
