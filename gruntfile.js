/*global module*/
module.exports = function (grunt) {
    'use strict';

    // standardize EOL
    grunt.util.linefeed = '\n';

    grunt.initConfig({

        pkg: grunt.file.readJSON('package.json'),

        meta: {
            banner: '/*!\n * <%= pkg.title || pkg.name %>' +
                    ' v<%= pkg.version %>\n' +
                    ' * commit <%= commitish %> -' +
                    ' <%= grunt.template.today("yyyy-mm-dd") %>\n' +
                    ' <%= pkg.homepage ? "* " + pkg.homepage + "\\n" : "" %>' +
                    ' * Copyright (c) <%= grunt.template.today("yyyy") %>\n' +
                    ' * License <%= pkg.license %>\n */\n'
        },

        concat: {
            dist: {
                files: {
                    'symphony/assets/css/symphony.min.css': [
                        'symphony/assets/css/src/symphony.css',
                        'symphony/assets/css/src/symphony.affix.css',
                        'symphony/assets/css/src/symphony.grids.css',
                        'symphony/assets/css/src/symphony.forms.css',
                        'symphony/assets/css/src/symphony.tables.css',
                        'symphony/assets/css/src/symphony.frames.css',
                        'symphony/assets/css/src/symphony.tabs.css',
                        'symphony/assets/css/src/symphony.drawers.css',
                        'symphony/assets/css/src/symphony.associations.css',
                        'symphony/assets/css/src/symphony.notices.css',
                        'symphony/assets/css/src/symphony.suggestions.css',
                        'symphony/assets/css/src/symphony.calendar.css',
                        'symphony/assets/css/src/symphony.filtering.css',
                        'symphony/assets/css/src/admin.css'
                    ],
                    'symphony/assets/css/installer.min.css': [
                        'symphony/assets/css/src/symphony.css',
                        'symphony/assets/css/src/symphony.grids.css',
                        'symphony/assets/css/src/symphony.forms.css',
                        'symphony/assets/css/src/symphony.frames.css',
                        'symphony/assets/css/src/installer.css'
                    ],
                },
            },
        },

        autoprefixer: {
            styles: {
                files: {
                    'symphony/assets/css/symphony.min.css': [
                        'symphony/assets/css/symphony.min.css'
                    ],
                    'symphony/assets/css/installer.min.css': [
                        'symphony/assets/css/installer.min.css'
                    ],
                    'symphony/assets/css/devkit.min.css': [
                        'symphony/assets/css/src/devkit.css'
                    ]
                }
            }
        },

        csso: {
            styles: {
                options: {
                    banner: '<%= meta.banner %>'
                },
                files: {
                    'symphony/assets/css/symphony.min.css': [
                        'symphony/assets/css/symphony.min.css'
                    ],
                    'symphony/assets/css/installer.min.css': [
                        'symphony/assets/css/installer.min.css'
                    ],
                    'symphony/assets/css/devkit.min.css': [
                        'symphony/assets/css/devkit.min.css'
                    ]
                }
            }
        },

        jshint: {
            files: [
                'symphony/assets/js/src/*.js',
            ],
            options: {
                browser: true,
                devel: true,
                curly: true,
                laxbreak: true,
                strict: true,
                nonew: true,
                esversion: 5,
                maxcomplexity: 12,
                undef: true,
                unused: true,
                quotmark: 'single',
                globals: {
                    jQuery: true,
                    moment: true,
                }
            }
        },

        uglify: {
            scripts: {
                options: {
                    banner: '<%= meta.banner %>',
                    compress: {
                        drop_console: true,
                        dead_code: true
                    },
                    output: {
                        comments: 'some',
                        quote_style: 3
                    },
                    warnings: grunt.option('verbose')
                },
                files: {
                    'symphony/assets/js/symphony.min.js': [
                        'symphony/assets/js/lib/jquery.js',
                        'symphony/assets/js/lib/jquery.migrate.js',
                        'symphony/assets/js/lib/signals.js',
                        'symphony/assets/js/lib/crossroads.js',
                        'symphony/assets/js/lib/moment.min.js',
                        'symphony/assets/js/lib/clndr.min.js',
                        'symphony/assets/js/src/symphony.js',
                        'symphony/assets/js/src/backend.js',
                        'symphony/assets/js/src/symphony.*.js',
                        'symphony/assets/js/src/backend.*.js',
                    ]
                }
            }
        },

        curl: {
            'symphony/assets/js/lib/jquery.js': 'https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.js',
            'symphony/assets/js/lib/jquery.migrate.js': 'https://code.jquery.com/jquery-migrate-3.0.1.js',
            'symphony/assets/js/lib/clndr.min.js': 'https://raw.githubusercontent.com/kylestetz/CLNDR/master/clndr.min.js'
        },

        watch: {
            styles: {
                files: 'symphony/assets/css/src/*.css',
                tasks: ['css']
            },
            scripts: {
                files: 'symphony/assets/js/src/*.js',
                tasks: ['js']
            },
            php: {
                files: ['symphony/**/*.php', 'install/**/*.php'],
                tasks: ['php']
            }
        },

        phpcs: {
            application: {
                src: ['symphony/**/*.php', 'install/**/*.php', 'index.php']
            },
            options: {
                bin: 'vendor/bin/phpcs',
                standard: 'PSR1',
                showSniffCodes: true,
                tabWidth: 4,
                errorSeverity: 10
            }
        },

        phpunit: {
            unit: {
                testsuite: 'unit',
                coverageClover: 'unit-cov.xml',
                coveragePhp: 'unit.cov',
                bootstrap: 'tests/boot.php'
            },
            integration: {
                testsuite: 'integration',
                coverageClover: 'integration-cov.xml',
                coveragePhp: 'integration.cov',
                bootstrap: 'tests/int/boot.php'
            },
            options: {
                bin: 'vendor/bin/phpunit',
                configuration: 'tests/phpunit.xml'
            }
        },

        commitish: '',
        'git-rev-parse': {
            options: {
                prop: 'commitish',
                silent: true,
                number: 7
            },
            dist: {}
        }
    });

    grunt.loadNpmTasks('grunt-contrib-concat');
    grunt.loadNpmTasks('grunt-autoprefixer');
    grunt.loadNpmTasks('grunt-csso');
    grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-curl');
    grunt.loadNpmTasks('grunt-phpcs');
    grunt.loadNpmTasks('grunt-phpunit');
    grunt.loadNpmTasks('grunt-git-rev-parse');

    grunt.registerTask('default', ['css', 'js']);
    grunt.registerTask('css', ['git-rev-parse', 'concat', 'autoprefixer', 'csso']);
    grunt.registerTask('php', ['phpcs', 'phpunit:unit']);
    grunt.registerTask('js', ['git-rev-parse', 'jshint', 'uglify']);
    grunt.registerTask('unit', ['jshint', 'phpunit:unit']);
    grunt.registerTask('integration', ['phpunit:integration']);
};