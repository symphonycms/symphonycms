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

        /*
        jshint: {
            scripts: [
                'symphony/assets/js/src/symphony.js',
                'symphony/assets/js/src/symphony.collapsible.js',
                'symphony/assets/js/src/symphony.orderable.js',
                'symphony/assets/js/src/symphony.selectable.js',
                'symphony/assets/js/src/symphony.duplicator.js',
                'symphony/assets/js/src/symphony.tags.js',
                'symphony/assets/js/src/symphony.suggestions.js',
                'symphony/assets/js/src/symphony.pickable.js',
                'symphony/assets/js/src/symphony.timeago.js',
                'symphony/assets/js/src/symphony.notify.js',
                'symphony/assets/js/src/symphony.drawer.js',
                'symphony/assets/js/src/admin.js'
            ]
        },
        */

        uglify: {
            scripts: {
                options: {
                    banner: '<%= meta.banner %>',
                    preserveComments: 'some'
                },
                files: {
                    'symphony/assets/js/symphony.min.js': [
                        'symphony/assets/js/lib/jquery.js',
                        'symphony/assets/js/lib/signals.js',
                        'symphony/assets/js/lib/crossroads.js',
                        'symphony/assets/js/lib/selectize.js',
                        'symphony/assets/js/lib/moment.min.js',
                        'symphony/assets/js/lib/clndr.min.js',
                        'symphony/assets/js/src/symphony.js',
                        'symphony/assets/js/src/symphony.affix.js',
                        'symphony/assets/js/src/symphony.collapsible.js',
                        'symphony/assets/js/src/symphony.defaultvalue.js',
                        'symphony/assets/js/src/symphony.orderable.js',
                        'symphony/assets/js/src/symphony.selectable.js',
                        'symphony/assets/js/src/symphony.duplicator.js',
                        'symphony/assets/js/src/symphony.tags.js',
                        'symphony/assets/js/src/symphony.pickable.js',
                        'symphony/assets/js/src/symphony.timeago.js',
                        'symphony/assets/js/src/symphony.notify.js',
                        'symphony/assets/js/src/symphony.drawer.js',
                        'symphony/assets/js/src/symphony.calendar.js',
                        'symphony/assets/js/src/symphony.filtering.js',
                        'symphony/assets/js/src/symphony.suggestions.js',
                        'symphony/assets/js/src/backend.js',
                        'symphony/assets/js/src/backend.views.js',
                    ]
                }
            }
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
    //grunt.loadNpmTasks('grunt-contrib-jshint');
    grunt.loadNpmTasks('grunt-contrib-uglify');
    grunt.loadNpmTasks('grunt-contrib-watch');
    grunt.loadNpmTasks('grunt-phpcs');
    grunt.loadNpmTasks('grunt-git-rev-parse');

    grunt.registerTask('default', ['css', 'js']);
    grunt.registerTask('css', ['git-rev-parse', 'concat', 'autoprefixer', 'csso']);
    grunt.registerTask('php', ['phpcs']);
    grunt.registerTask('js', ['git-rev-parse', 'uglify']);
};