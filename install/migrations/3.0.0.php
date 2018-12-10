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
            'Please, make sure you have a complete database and file backup BEFORE doing the upgrade.',
        ];
    }

    public function postUpdateNotes()
    {
        if (!empty($this->failedTables)) {
            return [
                'The updater completed but was not able to update all tables. ' .
                    'The following tables must be manually updated to InnoDB: ',
                implode(', ', $this->failedTables),
                'If none were defined, Symphony did set the default log filter value while updating. ' .
                    'By default, it will log everything except deprecation notices, ' .
                    'i.e. <code>E_ALL ^ E_DEPRECATED.</code>',
                'Make sure to manually adjust your config.php file for your use case.' .
                    'Symphony uses the same values as PHP\'s <code>error_reporting()</code> function.',
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

        // Init DB
        Symphony::initialiseDatabase();
        Symphony::initialiseExtensionManager();

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
                    ->string(0);
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
            ->modify(['id' => [
                'type' => 'int(11)',
                'signed' => false,
                'auto' => true,
            ]])
            ->execute();

        // Make extensions delegates extension_id unsigned
        // and add the order column
        $edStm = Symphony::Database()
            ->alter('tbl_extensions_delegates')
            ->modify(['extension_id' => [
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

        // Make parent_section unsigned, sortorder signed and element_name varchar(255)
        Symphony::Database()
            ->alter('tbl_fields')
            ->modify(['parent_section' => [
                'type' => 'int(11)',
                'signed' => false,
            ]])
            ->modify(['sortorder' => [
                'type' => 'int(11)',
                'signed' => true,
            ]])
            ->modify(['element_name' => 'varchar(255)'])
            ->execute();

        // Make author id unsigned
        Symphony::Database()
            ->alter('tbl_forgotpass')
            ->modify([
                'author_id' => [
                    'type' => 'int(11)',
                    'signed' => false,
                ],
                'token' => [
                    'type' => 'varchar(255)',
                ]
            ])
            ->execute();

        // Make parent unsigned and sortorder signed
        Symphony::Database()
            ->alter('tbl_pages')
            ->modify(['parent' => [
                'type' => 'int(11)',
                'signed' => false,
                'null' => true,
            ]])
            ->modify(['sortorder' => [
                'type' => 'int(11)',
                'signed' => true,
                'null' => true,
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
        unset($dateFields);

        // Change auth_token_active to auth_token
        if (Symphony::Database()
            ->showColumns()
            ->from('tbl_authors')
            ->like('auth_token_active')
            ->execute()
            ->next()) {
            // Get active tokens
            $activeTokenAuthors = (new AuthorManager)
                ->select()
                ->where(['auth_token_active' => 'yes'])
                ->execute()
                ->rows();
            // Drop and add
            Symphony::Database()
                ->alter('tbl_authors')
                ->drop('auth_token_active')
                ->add([
                    'auth_token' => [
                        'type' => 'varchar(255)',
                        'null' => true,
                    ]
                ])
                ->execute();
            // Create tokens for active users
            foreach ($activeTokenAuthors as $ata) {
                unset($ata['auth_token_active']);
                $ata->set('auth_token', Cryptography::randomBytes());
                $ata->commit();
            }
            unset($activeTokenAuthors);
        }

        // Update the default value for last_seen
        // This change was made in 2.7.0, but the update process never took care of it
        // Re: #2594
        Symphony::Database()
            ->alter('tbl_authors')
            ->modify(['last_seen' => [
                'type' => 'datetime',
                'default' => '1000-01-01 00:00:00',
                'null' => true,
            ]])
            ->execute();

        // Re-save all data sources
        foreach (DatasourceManager::listAll() as $ds) {
            if (!$ds['can_parse']) {
                continue;
            }
            $path = DatasourceManager::__getDriverPath($ds['handle']);
            $ds = @file_get_contents($path);
            $ds = str_replace("\t", '    ', $ds);
            $ds = trim(substr($ds, 0, strpos($ds, '    public function execute')));
            if ($ds) {
                @file_put_contents($path, $ds . "\n}\n");
            }
        }

        // Add the log filter value
        // This was added in 2.7.1, but the update process never took care of it
        // Re: #2762
        // This was fixed by updating to 2.7.8, but since it is not required to,
        // we must take care of it.
        $filter = Symphony::Configuration()->get('filter', 'log');
        if ($filter === null) {
            Symphony::Configuration()->set('filter', E_ALL ^ E_DEPRECATED, 'log');
        }

        // Update the version information
        return parent::upgrade();
    }
}
