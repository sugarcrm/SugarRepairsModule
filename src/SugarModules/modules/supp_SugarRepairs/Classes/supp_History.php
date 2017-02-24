<?php
// Copyright 2017 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

/**
 * History helpers for Sugar
 */
class supp_History
{
    /**
     * Returns all installed upgrade patches
     * @return array
     */
    public static function getInstalledPatches($sort = 'DESC')
    {
        return self::getInstalledByType('patch', $sort);
    }

    /**
     * Returns the date an instance was upgraded to or past a specific version
     *
     * @param $startVersion
     * @param bool $endVersion
     * @return bool
     */
    public static function getPatchedDate($startVersion, $endVersion = false)
    {
        $patches = self::getInstalledPatches('ASC');

        if ($endVersion) {
            foreach ($patches as $patch) {
                if (version_compare($patch['version'], $startVersion, ">=") && version_compare($patch['version'], $endVersion, "<=")) {
                    return $patch['date_entered'];
                }
            }
        } else {
            foreach ($patches as $patch) {
                if (version_compare($patch['version'], $startVersion, ">=")) {
                    return $patch['date_entered'];
                }
            }
        }

        return false;
    }

    /**
     * Returns all installed modules
     * @return array
     */
    public static function getInstalledModules($sort = 'DESC')
    {
        return self::getInstalledByType('module', $sort);
    }

    /**
     * Generic helper to return records by type
     * @param $type
     * @return array
     */
    public static function getInstalledByType($type, $sort = 'DESC')
    {
        $sql = "SELECT * FROM upgrade_history WHERE type = '{$type}' AND status = 'installed' ORDER BY date_entered {$sort}";
        $result = $GLOBALS['db']->query($sql);

        $installed = array();

        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $installed[$row['id']] = $row;
        }

        return $installed;
    }
}