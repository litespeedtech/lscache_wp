<?php

class Set_Htaccess {

    public function __construct()
    {
        $this->test_suite();
    }

    public function test_block($install, $access, $abspath)
    {

        error_log('Begin test with install ' . $install
            . ', access ' . $access . ', abspath ' . $abspath);
        /**
         * Converts the intersection of $access and $install to \0
         * then counts the number of \0 characters before the first non-\0.
         */
        $common_count = strspn($access ^ $install, "\0");

        $install_part = substr($install, $common_count);
        $access_part = substr($access, $common_count);
        if ($access_part !== false) {
            // access is longer than install or they are in different dirs.
            if ($install_part === false) {
                $install_part = '';
            }
        }
        elseif ($install_part !== false) {
            // Install is longer than access
            $access_part = '';
            $install_part = rtrim($install_part, '/');
        }
        else {
            // they are equal - no need to find paths.
            $trimmed = rtrim($abspath, '/');
            return array($trimmed, $trimmed, $trimmed);
        }

        $common_path = substr($abspath, 0, -(strlen($install_part) + 1));

        self::path_search_setup($common_path, $install_part, $access_part);

        return array($install_part, $access_part, $common_path);
    }

    public function test_suite()
    {
        $root = '/home/user/public_html/';
        $root_site = 'http://example.com';

        $wordpress = '/home/user/public_html/wordpress/';
        $wordpress_site = 'http://example.com/wordpress';

        $wp = '/home/user/public_html/wp/';
        $wp_site = 'http://example.com/wp';

        $wordpress_turkey = '/home/user/public_html/wordpress/turkey/';
        $wordpress_turkey_site = 'http://example.com/wordpress/turkey';

        $wp_turkey = '/home/user/public_html/wp/turkey/';
        $wp_turkey_site = 'http://example.com/wp/turkey';

        $blog = '/home/user/public_html/blog/';
        $blog_site = 'http://example.com/blog';

        $blog_turkey = '/home/user/public_html/blog/turkey/';
        $blog_turkey_site = 'http://example.com/blog/turkey';

        $diff = '/home/user/public_html/diff/';
        $diff_site = 'http://example.com/diff';

        $diff2 = '/home/user/public_html/diff2/';
        $diff2_site = 'http://example.com/diff2';

        $diff_cat = '/home/user/public_html/diff/cat/';
        $diff_cat_site = 'http://example.com/diff/cat';

        // install, access, abspath
        // if NON STANDARD INSTALL, use urls.
        $tests = array(
            array($root, $root, $root),
            array($wordpress, $wordpress, $wordpress),
            array($wordpress, $root, $wordpress),
            array($root_site, $wordpress_site, $root),
            array($wordpress_turkey, $root, $wordpress_turkey),
            array($wordpress_turkey, $wordpress, $wordpress_turkey),
            array($root_site, $wordpress_turkey_site, $root),
            array($wordpress_site, $wordpress_turkey_site, $wordpress),
            array($wordpress_site, $wp_site, $wordpress),
            array($wp_site, $wordpress_site, $wp),
            array($wordpress_turkey_site, $wp_site, $wordpress_turkey),
            array($wp_site, $wordpress_turkey_site, $wp),
            array($wordpress_turkey_site, $wp_turkey_site, $wordpress_turkey),
            array($wp_turkey_site, $wordpress_turkey_site, $wp_turkey),
            array($wordpress_site, $blog_site, $wordpress),
            array($blog_site, $wordpress_site, $blog),
            array($wordpress_turkey_site, $blog_turkey_site, $wordpress_turkey),
            array($blog_turkey_site, $wordpress_turkey_site, $blog_turkey),
            array($diff_site, $diff2_site, $diff),
            array($diff2_site, $diff_site, $diff2),
            array($diff_cat, $diff, $diff_cat),
            array($diff_site, $diff_cat_site, $diff),
            array($diff2_site, $diff_cat_site, $diff2),
            array($diff_cat_site, $diff2_site, $diff_cat)
        );

        $res = array(
            array($root, $root, $root),
            array($wordpress, $wordpress, $wordpress),
            array($wordpress, $root, $root),
            array($root, $wordpress, $root),
            array($wordpress_turkey, $root, $root),
            array($wordpress_turkey, $wordpress, $wordpress),
            array($root, $wordpress_turkey, $root),
            array($wordpress, $wordpress_turkey, $wordpress),
            array($wordpress, $wp, $root),
            array($wp, $wordpress, $root),
            array($wordpress_turkey, $wp, $root),
            array($wp, $wordpress_turkey, $root),
            array($wordpress_turkey, $wp_turkey, $root),
            array($wp_turkey, $wordpress_turkey, $root),
            array($wordpress, $blog, $root),
            array($blog, $wordpress, $root),
            array($wordpress_turkey, $blog_turkey, $root),
            array($blog_turkey, $wordpress_turkey, $root),
            array($diff, $diff2, $root),
            array($diff2, $diff, $root),
            array($diff_cat, $diff, $diff),
            array($diff, $diff_cat, $diff),
            array($diff2, $diff_cat, $root),
            array($diff_cat, $diff2, $root)
        );

        foreach ($tests as $key=>$test) {
            $out = $this->test_block($test[0], $test[1], $test[2]);

            $expected = $res[$key];

            foreach ($expected as $index=>$val) {
                if (rtrim($val, '/') !== $out[$index]) {
                    error_log('NOT A MATCH: ' . print_r($test, true)
                        . "\nGot: " . print_r($out, true)
                        . "\nExpected: " . print_r($expected, true));
                }
            }
        }
    }

};



