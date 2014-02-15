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

namespace Scabbia\Mvc;

/**
 * Generator
 *
 * @package     Scabbia\Mvc
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       2.0.0
 */
class Generator
{
    /**
     * Entry point for processor
     *
     * @param array $uAnnotations annotations
     */
    public static function generate(array $uAnnotations)
    {
        $tRoutes = [];

        foreach ($uAnnotations as $tClassKey => $tClass) {
            foreach ($tClass["methods"] as $tMethodKey => $tMethod) {
                if (!isset($tMethod["route"])) {
                    continue;
                }

                $tRoute = $tMethod["route"][0];
                $tRoute["controller"] = $tClassKey;
                $tRoute["action"] = $tMethodKey;
                $tRoutes[] = $tRoute;
            }
        }

        print_r($tRoutes);
    }
}
