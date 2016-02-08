<?php
namespace Shockwavedesign\Mail\Dropbox\Setup;

use Magento\Framework\Setup\SetupInterface;
use Magento\Framework\Setup\UpgradeSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

class UpgradeSchema implements UpgradeSchemaInterface
{
    public function upgrade(SchemaSetupInterface $setup,
                            ModuleContextInterface $context){
        $setup->startSetup();
        if (version_compare($context->getVersion(), '1.0.1') < 0) {
            $this->createMailDropboxUserTable($setup);
        }

        $setup->endSetup();
    }

    /**
     * @param $installer
     */
    protected function createMailDropboxUserTable(SchemaSetupInterface $installer)
    {
        $table = $installer->getConnection()->newTable(
            $installer->getTable('shockwavedesign_mail_dropbox_user')
        )->addColumn(
            'entity_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
            null,
            ['identity' => true, 'unsigned' => true, 'nullable' => false, 'primary' => true],
            'Storeage Id'
        )->addColumn(
            'dropbox_user_id',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Dropbox User Id'
        )->addColumn(
            'access_token',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Access Token'
        )->addColumn(
            'folder_path',
            \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
            255,
            ['nullable' => false],
            'Path to store files for magento'
        )->addIndex(
            $installer->getIdxName('mail', 'dropbox_user_id'),
            'dropbox_user_id'
        )->setComment(
            'Mail Dropbox User Table'
        );
        $installer->getConnection()->createTable($table);
    }
}