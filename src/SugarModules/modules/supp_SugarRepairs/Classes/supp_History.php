<?php
// Copyright 2017 SugarCRM Inc.  Licensed by SugarCRM under the Apache 2.0 license.

/**
 * History helpers for Sugar
 */
class supp_History extends \UpgradeHistory
{
    /**
     * Returns the exact versions applicable to an installed entry
     * @return bool
     */
    function getExactVersions()
    {
        $manifest = $this->getManifest();

        if (isset($manifest['acceptable_sugar_versions']['exact_matches']) && !empty($manifest['acceptable_sugar_versions']['exact_matches'])) {
            return $manifest['acceptable_sugar_versions']['exact_matches'];
        }

        return false;
    }

    /**
     * Returns the manifest if valid
     * @return bool|mixed
     */
    function getManifest()
    {
        $manifest = unserialize(base64_decode($this->manifest));

        if (is_array($manifest)) {
            return $manifest;
        }

        return false;
    }

    /**
     * Returns all installed upgrade patches
     * @return array
     */
    function getInstalledPatches($sort = 'DESC')
    {
        return $this->getInstalledByType('patch', $sort);
    }

    /**
     * Returns the patch that upgraded an instance to or past a specific version
     *
     * @param $startVersion
     * @param bool $endVersion
     * @return bool
     */
    function getPatch($startVersion, $endVersion = false)
    {
        $patches = $this->getInstalledPatches('ASC');

        if ($endVersion) {
            foreach ($patches as $patch) {
                if (version_compare($patch->version, $startVersion, ">=") && version_compare($patch->version, $endVersion, "<=")) {
                    return $patch;
                }
            }
        } else {
            foreach ($patches as $patch) {
                if (version_compare($patch->version, $startVersion, ">=")) {
                    return $patch;
                }
            }
        }

        return false;
    }

    /**
     * Returns all installed modules
     * @return array
     */
    function getInstalledModules($sort = 'DESC')
    {
        return $this->getInstalledByType('module', $sort);
    }

    /**
     * Generic helper to return records by type
     * @param $type
     * @return array
     */
    function getInstalledByType($type, $sort = 'DESC')
    {
        $sort = strtoupper($sort);

        if ($sort !== 'DESC') {
            $sort = 'ASC';
        }

        $sql = "SELECT id FROM upgrade_history WHERE type = '{$type}' AND status = 'installed' ORDER BY date_entered {$sort}";
        $result = $GLOBALS['db']->query($sql);

        $installed = array();

        while ($row = $GLOBALS['db']->fetchByAssoc($result)) {
            $uh = new History();
            $uh->retrieve($row['id']);
            $installed[$row['id']] = $uh;
        }

        return $installed;
    }
}