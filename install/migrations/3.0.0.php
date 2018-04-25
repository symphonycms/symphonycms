<?php

/**
 * @package install
 */
final class migration_300 extends Migration
{
    private $failedTables = [];

    public function getVersion()
    {
        return '3.0.0';
    }

    public function preUpdateNotes()
    {
        return [
            'Symphony <code>3.0.0</code> is a major release and breaks some APIs. ' .
            'Most of the changes are about the migration to PDO and the new database API.',
            'There is a compatibility layer to allow for gradual updating of extensions.',
            'All tables will be migrated to InnoDb but charset and collate will be kept. ' .
            'Make sure you have an up to date backup in case something goes wrong.',
            'The update process can take a long time if you have many tables. ' .
            'If it ever times out, start the update process again: it will continue where it stopped.',
        ];
    }

    public function postUpdateNotes()
    {
        if (!empty($this->failedTables)) {
            return [
                'The updater completed but was not able to update all tables. ' .
                'The following tables must be manually updated to InnoDB: ',
                implode(', ', $this->failedTables),
            ];
        }
        return [];
    }

    public function upgrade()
    {
        // Upgrade DB settings
        $db = Symphony::Configuration()->get('database');
        $db['driver'] = 'mysql';
        $db['engine'] = 'InnoDB';
        if (empty($db['charset'])) {
            $db['charset'] = 'utf8';
        }
        if (empty($db['collate'])) {
            $db['collate'] = 'utf8_unicode_ci';
        }
        Symphony::Configuration()->setArray(['database' => $db]);
        Symphony::Configuration()->write();

        // Update tables storage engine
        $prefix = Symphony::Database()->getPrefix();
        $tables = Symphony::Database()->show();
        if ($prefix) {
            $tables->like("$prefix%");
        }
        $tables = $tables->execute()->column(0);
        foreach ($tables as $t) {
            try {
                $engine = Symphony::Database()
                    ->select(['ENGINE'])
                    ->from('information_schema.TABLES')
                    ->where(['TABLE_SCHEMA' => $db['db']])
                    ->where(['ENGINE' => ['!=' => null]])
                    ->where(['TABLE_NAME'=> $t])
                    ->execute()
                    ->variable(0);
                if ($engine === 'InnoDB') {
                    continue;
                }
                Symphony::Database()
                    ->alter($t)
                    ->engine('InnoDB')
                    ->unsafeAppendSQLPart('engine', 'ROW_FORMAT=DYNAMIC')
                    ->execute();
            } catch (Exception $ex) {
                $this->failedTables[] = $t;
            }
        }

        if (!empty($this->failedTables)) {
            Symphony::Log()->pushToLog(
                'Failed to upgrade some tables: ' . implode(', ', $this->failedTables),
                E_ERROR,
                true
            );
        }

        // Make extensions id unsigned
        Symphony::Database()
            ->alter('tbl_extensions')
            ->change('id', ['id' => [
                'type' => 'int(11)',
                'signed' => false,
            ]])
            ->execute();

        // Make extensions delegates extension_id unsigned
        // and add the order column
        $edStm = Symphony::Database()
            ->alter('tbl_extensions_delegates')
            ->change('extension_id', ['extension_id' => [
                'type' => 'int(11)',
                'signed' => false,
            ]]);
        if (!Symphony::Database()
            ->showColumns()
            ->from('tbl_extensions_delegates')
            ->like('order')
            ->execute()
            ->next()) {
            $edStm->add([
                'order' => [
                    'type' => 'int(11)',
                    'signed' => true,
                    'default' => 0
                ]
            ])
            ->after('callback');
        }
        $edStm->execute();
        unset($edStm);

        // Make parent_section unsigned and sortorder signed
        Symphony::Database()
            ->alter('tbl_fields')
            ->change('parent_section', ['parent_section' => [
                'type' => 'int(11)',
                'signed' => false,
            ]])
            ->change('sortorder', ['sortorder' => [
                'type' => 'int(11)',
                'signed' => true,
            ]])
            ->execute();

        // Make author id unsigned
        Symphony::Database()
            ->alter('tbl_forgotpass')
            ->change('author_id', ['author_id' => [
                'type' => 'int(11)',
                'signed' => false,
            ]])
            ->execute();

        // Make parent unsigned and sortorder signed
        Symphony::Database()
            ->alter('tbl_pages')
            ->change('parent', ['parent' => [
                'type' => 'int(11)',
                'signed' => false,
            ]])
            ->change('sortorder', ['sortorder' => [
                'type' => 'int(11)',
                'signed' => true,
            ]])
            ->execute();

        // Drop the 'date' column in date fields
        $dateFields = (new FieldManager)->select(['id'])->type('date')->execute()->column('id');
        foreach ($dateFields as $dateFieldId) {
            if (!Symphony::Database()
                ->showColumns()
                ->from("tbl_entries_data_$dateFieldId")
                ->like('date')
                ->execute()
                ->next()) {
                continue;
            }
            Symphony::Database()
                ->alter("tbl_entries_data_$dateFieldId")
                ->drop('date')
                ->execute();
        }

        // Update the version information
        return parent::upgrade();
    }
}
