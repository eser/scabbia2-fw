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
 */

namespace Scabbia\Objects;

use Scabbia\Helpers\Arrays;

/**
 * Binder
 *
 * @package     Scabbia\Objects
 * @author      Eser Ozvataf <eser@sent.com>
 * @since       1.5.0
 */
class FormHandler
{
    /** @type array records */
    public $records = [];


    /**
     * @ignore
     */
    public static function getFromRequest($uChangeField, array $uFields, array $uPostData = null)
    {
        if ($uPostData === null) {
            $uPostData = $_POST;
        }

        $tNewInstance = new static();
        if (isset($uPostData[$uChangeField])) {
            $tChangedRecords = $uPostData[$uChangeField];
        } else {
            $tChangedRecords = [];
        }

        $tFieldValues = [];
        foreach ($uFields as $tField) {
            $tFieldValues[$tField] = $uPostData[$tField];
        }

        foreach ($tChangedRecords as $tIndex => $tChangedRecord) {
            $tRecord = [
                "index" => $tIndex,
                "changed" => $tChangedRecord
            ];
            foreach ($uFields as $tField) {
                $tRecord[$tField] = isset($tFieldValues[$tField][$tIndex]) ? $tFieldValues[$tField][$tIndex] : null;
            }

            $tNewInstance->records[] = $tRecord;
        }

        return $tNewInstance;
    }

    /**
     * @ignore
     */
    public function getInserted()
    {
        return Arrays::getRows($this->records, "changed", "insert");
    }

    /**
     * @ignore
     */
    public function getUpdated()
    {
        return Arrays::getRows($this->records, "changed", "update");
    }

    /**
     * @ignore
     */
    public function getDeleted()
    {
        return Arrays::getRows($this->records, "changed", "delete");
    }

    /**
     * @ignore
     */
    public function getNotModified()
    {
        return Arrays::getRows($this->records, "changed", "none");
    }

    /**
     * @ignore
     */
    public function getButDeleted()
    {
        return Arrays::getRowsBut($this->records, "changed", "delete");
    }
}
