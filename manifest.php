<?PHP
/*****************************************************************************
 * The contents of this file are subject to the RECIPROCAL PUBLIC LICENSE
 * Version 1.1 ("License"); You may not use this file except in compliance
 * with the License. You may obtain a copy of the License at
 * http://opensource.org/licenses/rpl.php. Software distributed under the
 * License is distributed on an "AS IS" basis, WITHOUT WARRANTY OF ANY KIND,
 * either express or implied.
 *
 * You may:
 * a) Use and distribute this code exactly as you received without payment or
 *    a royalty or other fee.
 * b) Create extensions for this code, provided that you make the extensions
 *    publicly available and document your modifications clearly.
 * c) Charge for a fee for warranty or support or for accepting liability
 *    obligations for your customers.
 *
 * You may NOT:
 * a) Charge for the use of the original code or extensions, including in
 *    electronic distribution models, such as ASP (Application Service
 *    Provider).
 * b) Charge for the original source code or your extensions other than a
 *    nominal fee to cover distribution costs where such distribution
 *    involves PHYSICAL media.
 * c) Modify or delete any pre-existing copyright notices, change notices,
 *    or License text in the Licensed Software
 * d) Assert any patent claims against the Licensor or Contributors, or
 *    which would in any way restrict the ability of any third party to use the
 *    Licensed Software.
 *
 * You must:
 * a) Document any modifications you make to this code including the nature of
 *    the change, the authors of the change, and the date of the change.
 * b) Make the source code for any extensions you deploy available via an
 *    Electronic Distribution Mechanism such as FTP or HTTP download.
 * c) Notify the licensor of the availability of source code to your extensions
 *    and include instructions on how to acquire the source code and updates.
 * d) Grant Licensor a world-wide, non-exclusive, royalty-free license to use,
 *    reproduce, perform, modify, sublicense, and distribute your extensions.
 *
 * The Original Code is: SugarCRM
 *                       Kenneth brill
 *                       2008-11-03 kbrill@sugarcrm.com
 *
 * The Initial Developer of the Original Code is Kenneth Brill
 * All Rights Reserved.
 ********************************************************************************/
$manifest = array(

    'acceptable_sugar_versions' => array(
        'regex_matches' => array(
            1 => "6\.5\.*.*",
            2 => "6\.7\.*.*",
            3 => "7\.5\.*.*",
            4 => "7\.6\.*.*",
            5 => "7\.7\.*.*",
        ),
    ),
    'acceptable_sugar_flavors' => array(
        0 => 'CE',
        1 => 'PRO',
        2 => 'CORP',
        3 => 'ENT',
        4 => 'ULT',
    ),
    'name' => 'SugarCRM: Pre-upgrade repair tool',
    'description' => 'A tool to repair language files',
    'author' => 'Ken Brill',
    'published_date' => '12/16/2015',
    'version' => '3.3',
    'type' => 'module',
    'is_uninstallable' => true,
);

$installdefs = array(
    'id' => 'Language_File_Repair_Tool',
    'copy' => array(
        array('from' => '<basepath>/files/custom/Extension/application/Ext/EntryPointRegistry/fixLanguageFiles.php',
            'to' => 'custom/Extension/application/Ext/EntryPointRegistry/fixLanguageFiles.php',
        ),
        array('from' => '<basepath>/files/custom/Extension/modules/Administration/Ext/Administration/FixLanguageFiles.php',
            'to' => 'custom/Extension/modules/Administration/Ext/Administration/FixLanguageFiles.php',
        ),
        array('from' => '<basepath>/files/custom/modules/Administration/fixLanguageFiles.php',
            'to' => 'custom/modules/Administration/fixLanguageFiles.php',
        ),
        array('from' => '<basepath>/files/custom/modules/Administration/fixLanguageFiles.css',
            'to' => 'custom/modules/Administration/fixLanguageFiles.css',
        ),
        array('from' => '<basepath>/files/custom/modules/Administration/fixLanguageFiles.js',
            'to' => 'custom/modules/Administration/fixLanguageFiles.js',
        ),
        array('from' => '<basepath>/files/custom/modules/Administration/fixLanguageFiles.tpl',
            'to' => 'custom/modules/Administration/fixLanguageFiles.tpl',
        ),
        array('from' => '<basepath>/files/custom/modules/Administration/fixLanguageFilesResult.tpl',
            'to' => 'custom/modules/Administration/fixLanguageFilesResult.tpl',
        ),
    ),

    'language' => array(
        array('from' => '<basepath>/files/custom/Extension/modules/Administration/Ext/Language/en_us.fixLanguageFiles.php',
            'to_module' => 'Administration',
            'language' => 'en_us'
        ),
    ),
);
?>
