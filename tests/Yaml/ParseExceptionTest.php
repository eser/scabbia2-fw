<?php
/**
 * Scabbia2 PHP Framework Code
 * http://www.scabbiafw.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        http://github.com/scabbiafw/scabbia2-fw for the canonical source repository
 * @copyright   2010-2015 Scabbia Framework Organization. (http://www.scabbiafw.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 *
 * -------------------------
 * Portions of this code are from Symfony YAML Component under the MIT license.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE-MIT
 * file that was distributed with this source code.
 *
 * Modifications made:
 * - Scabbia Framework code styles applied.
 * - All dump methods are moved under Dumper class.
 * - Redundant classes removed.
 * - Namespace changed.
 * - Tests ported to Scabbia2.
 * - Encoding checks removed.
 */

namespace Scabbia\Tests\Yaml;

use Scabbia\Testing\UnitTestFixture;
use Scabbia\Yaml\ParseException;

/**
 * Tests of ParseException class
 *
 * @package     Scabbia\Tests\Yaml
 * @since       2.0.0
 */
class ParseExceptionTest extends UnitTestFixture
{
    /**
     * Gets data form specifications
     *
     * @return array
     */
    public function getDataFormSpecifications()
    {
        $tException = new ParseException("Error message", 42, "foo: bar", "/var/www/app/config.yml");

        $this->assertEquals(
            "Error message in \"/var/www/app/config.yml\" at line 42 (near \"foo: bar\")",
            $tException->getMessage()
        );
    }
}
