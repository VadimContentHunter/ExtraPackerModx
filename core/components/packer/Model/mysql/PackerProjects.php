<?php
namespace Packer\Model\mysql;

use xPDO\xPDO;

class PackerProjects extends \Packer\Model\PackerProjects
{

    public static $metaMap = array (
        'package' => 'Packer\\Model',
        'version' => '1.0',
        'table' => 'packer_projects',
        'extends' => 'xPDO\\Om\\xPDOSimpleObject',
        'tableMeta' => 
        array (
            'engine' => 'InnoDB',
        ),
        'fields' => 
        array (
            'project_path' => '',
        ),
        'fieldMeta' => 
        array (
            'project_path' => 
            array (
                'dbtype' => 'text',
                'phptype' => 'string',
                'null' => false,
                'default' => '',
            ),
        ),
        'indexes' => 
        array (
            'project_path' => 
            array (
                'alias' => 'project_path',
                'primary' => false,
                'unique' => false,
                'type' => 'BTREE',
                'columns' => 
                array (
                    'project_path' => 
                    array (
                        'length' => '',
                        'collation' => 'A',
                        'null' => false,
                    ),
                ),
            ),
        ),
    );

}
