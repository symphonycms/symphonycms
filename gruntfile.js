/*global module*/

module.exports = function (grunt) {

    'use strict';

    grunt.initConfig({

        autoprefixer : {

            styles : {

                files : {

                    'symphony/assets/css/symphony.min.css' : [

                        'symphony/assets/css/src/symphony.css',
                        'symphony/assets/css/src/symphony.grids.css',
                        'symphony/assets/css/src/symphony.forms.css',
                        'symphony/assets/css/src/symphony.tables.css',
                        'symphony/assets/css/src/symphony.frames.css',
                        'symphony/assets/css/src/symphony.tabs.css',
                        'symphony/assets/css/src/symphony.drawers.css',
                        'symphony/assets/css/src/symphony.associations.css',
                        'symphony/assets/css/src/symphony.notices.css',
                        'symphony/assets/css/src/admin.css'
                    ]
                }
            }
        },

        csso : {

            styles : {

                src  : 'symphony/assets/css/symphony.min.css',
                dest : 'symphony/assets/css/symphony.min.css'
            }
        },

        uglify : {

            scripts : {

                files : {

                    'symphony/assets/js/symphony.min.js' : [

                        'symphony/assets/js/lib/jquery.js',
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
                }
            }
        }
    });

    grunt.loadNpmTasks('grunt-autoprefixer');
    grunt.loadNpmTasks('grunt-csso');
    grunt.loadNpmTasks('grunt-contrib-uglify');

    grunt.registerTask('default', ['autoprefixer', 'csso', 'uglify']);
};