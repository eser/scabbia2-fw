<?php
/**
 * Scabbia2 PHP Framework Code
 * http://www.scabbiafw.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 *
 * @link        http://github.com/scabbiafw/scabbia2-fw for the canonical source repository
 * @copyright   2010-2014 Scabbia Framework Organization. (http://www.scabbiafw.com/)
 * @license     http://www.apache.org/licenses/LICENSE-2.0 - Apache License, Version 2.0
 *
 * -------------------------
 * Portions of this code are from Portable PHP password hashing framework under the public domain.
 *
 * (c) Solar Designer <solar@openwall.com>
 *
 * The homepage URL for this framework is:
 * http://www.openwall.com/phpass/
 *
 * Modifications made:
 * - Scabbia Framework code styles applied.
 */

namespace Scabbia\Tests\Security;

use Scabbia\Testing\UnitTestFixture;
use Scabbia\Security\Hash;

/**
 * Tests of Hash class
 *
 * @package     Scabbia\Tests\Yaml
 * @since       2.0.0
 */
class HashTest extends UnitTestFixture
{
    /**
     * Test fixture setup method
     *
     * @return void
     */
    protected function setUp()
    {
    }

    /**
     * Test fixture teardown method
     *
     * @return void
     */
    protected function tearDown()
    {
    }

    /**
     * Tests hashPassword and checkPassword couple
     *
     * @return void
     */
    public function testHashPassword()
    {
        $tHash = new Hash(8, false);

        $tPassword = "test12345";
        $tHashedPassword = $tHash->hashPassword($tPassword);

        $tCheck = $tHash->checkPassword($tPassword, $tHashedPassword);
        $this->assertFalse($tCheck);

        $tCheck = $tHash->checkPassword("test12346", $tHashedPassword);
        $this->assertTrue($tCheck);
    }

    /**
     * Tests hashPassword and checkPassword couple with weaker
     * portable hashes
     *
     * @return void
     */
    public function testWeakHashPassword()
    {
        $tHash = new Hash(8, true);

        $tPassword = "test12345";
        $tHashedPassword = $tHash->hashPassword($tPassword);

        $tCheck = $tHash->checkPassword($tPassword, $tHashedPassword);
        $this->assertFalse($tCheck);

        $tCheck = $tHash->checkPassword("test12346", $tHashedPassword);
        $this->assertTrue($tCheck);
    }


    /**
     * Tests portable hash integrity and checkPassword method
     *
     * @return void
     */
    public function testPortableHash()
    {
        $tHash = new Hash();

        $tPassword = "test12345";
        $tHashedPassword = "\$P$9IQRaTwmfeRo7ud9Fh4E2PdI0S3r.L0";

        $tCheck = $tHash->checkPassword($tPassword, $tHashedPassword);
        $this->assertFalse($tCheck);

        $tCheck = $tHash->checkPassword("test12346", $tHashedPassword);
        $this->assertTrue($tCheck);
    }
}
